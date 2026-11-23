<?php

namespace App\Console\Commands;

use App\Models\MoodleLink;
use App\Services\MoodleSyncService;
use Illuminate\Console\Command;

class SyncMoodle extends Command
{
    protected $signature = 'mdc:sync
                            {--user= : Sinkronkan hanya milik pengguna dengan ID ini}
                            {--force : Abaikan batas jeda minimum antar sinkronisasi}';

    protected $description = 'Sinkronkan deadline dari URL iCal Moodle (berkala 30 menit / manual)';

    public function handle(MoodleSyncService $sync): int
    {
        $userId = $this->option('user');
        $force = (bool) $this->option('force');

        if ($userId !== null) {
            $link = MoodleLink::where('user_id', $userId)->first();

            if ($link === null) {
                $this->error("Pengguna #{$userId} belum menghubungkan Moodle.");

                return self::FAILURE;
            }

            $result = $sync->sync($link, $force);
            $this->info($result['message'].' ('.$result['count'].' deadline).');

            return $result['ok'] ? self::SUCCESS : self::FAILURE;
        }

        $result = $sync->syncAll($force);
        $this->info("Sinkronisasi massal selesai: {$result['processed']} berhasil, {$result['failed']} gagal.");

        return self::SUCCESS;
    }
}
