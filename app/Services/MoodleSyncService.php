<?php

namespace App\Services;

use App\Models\Deadline;
use App\Models\MoodleLink;
use App\Models\SyncLog;
use Illuminate\Support\Facades\Log;

/**
 * Sinkronisasi deadline dari Moodle (FR-03, BL-05).
 *
 * - Berjalan berkala (scheduler 30 menit) maupun manual (tombol "Sinkron sekarang").
 * - Upsert berbasis (user_id, uid) agar tidak ada duplikasi.
 * - Rate limit antar sinkronisasi (risiko no. 5 pada LKM 9).
 * - URL iCal tidak pernah ditulis ke log — hanya statusnya (NFR-01).
 */
class MoodleSyncService
{
    public function __construct(
        private readonly IcalParserService $parser,
    ) {}

    /**
     * @return array{ok: bool, count: int, message: string, skipped: bool}
     */
    public function sync(MoodleLink $link, bool $force = false): array
    {
        if (! $force && $link->last_synced_at !== null
            && $link->last_synced_at->gt(now()->subMinutes((int) config('mdc.min_sync_interval')))) {
            return ['ok' => true, 'count' => 0, 'message' => 'Sinkronisasi dilewati: terlalu dekat dengan sinkron terakhir.', 'skipped' => true];
        }

        try {
            $events = $this->parser->fetchAndParse($link->url);
        } catch (\Throwable $e) {
            $this->markFailed($link, $e->getMessage());

            return ['ok' => false, 'count' => 0, 'message' => $e->getMessage(), 'skipped' => false];
        }

        foreach ($events as $event) {
            Deadline::updateOrCreate(
                ['user_id' => $link->user_id, 'uid' => $event['uid']],
                [
                    'moodle_link_id' => $link->id,
                    'course' => $event['course'],
                    'title' => $event['summary'],
                    'type' => $event['type'],
                    'starts_at' => $event['starts_at'],
                    'url' => $event['url'],
                    'description' => $event['description'],
                ],
            );
        }

        $link->forceFill([
            'last_synced_at' => now(),
            'last_status' => 'ok',
            'last_error' => null,
        ])->save();

        SyncLog::create([
            'user_id' => $link->user_id,
            'status' => SyncLog::STATUS_OK,
            'deadlines_count' => count($events),
            'message' => 'Sinkronisasi berhasil.',
            'created_at' => now(),
        ]);

        return ['ok' => true, 'count' => count($events), 'message' => 'Sinkronisasi berhasil.', 'skipped' => false];
    }

    /**
     * Sinkronkan seluruh tautan aktif (dipanggil scheduler `mdc:sync`).
     *
     * @return array{processed: int, failed: int}
     */
    public function syncAll(bool $force = false): array
    {
        $processed = 0;
        $failed = 0;

        foreach (MoodleLink::with('user')->orderBy('id')->cursor() as $link) {
            $result = $this->sync($link, $force);

            if ($result['skipped']) {
                continue;
            }

            $result['ok'] ? $processed++ : $failed++;
        }

        return ['processed' => $processed, 'failed' => $failed];
    }

    private function markFailed(MoodleLink $link, string $message): void
    {
        $link->forceFill([
            'last_synced_at' => now(),
            'last_status' => 'error',
            'last_error' => $message,
        ])->save();

        SyncLog::create([
            'user_id' => $link->user_id,
            'status' => SyncLog::STATUS_ERROR,
            'deadlines_count' => 0,
            'message' => $message,
            'created_at' => now(),
        ]);

        Log::warning('Sinkronisasi iCal gagal', ['user_id' => $link->user_id, 'reason' => $message]);
    }
}
