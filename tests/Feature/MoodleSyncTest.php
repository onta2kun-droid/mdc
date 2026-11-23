<?php

namespace Tests\Feature;

use App\Models\Deadline;
use App\Models\MoodleLink;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\MoodleSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Pengujian sinkronisasi iCal → tabel deadlines (FR-03, BL-05):
 * upsert tanpa duplikasi dan pencatatan log saat gagal.
 */
class MoodleSyncTest extends TestCase
{
    use RefreshDatabase;

    private const ICS_URL = 'https://moodle.kampus.ac.id/calendar/export_execute.php?userid=1&authtoken=rAHASIA';

    private function sampleIcs(string $summary = 'Tugas A'): string
    {
        return "BEGIN:VCALENDAR\nVERSION:2.0\nBEGIN:VEVENT\nUID:ev-1@moodle.test\nSUMMARY:{$summary}\nCATEGORIES:Komputasi Awan\nDTSTART;TZID=Asia/Makassar:20261120T235900\nEND:VEVENT\nEND:VCALENDAR\n";
    }

    public function test_sinkronisasi_membuat_deadline(): void
    {
        Http::fake([self::ICS_URL.'*' => Http::response($this->sampleIcs(), 200)]);

        $user = User::create(['name' => 'Mhs', 'email' => 'mhs@t.test', 'password' => 'password123']);
        $link = MoodleLink::create([
            'user_id' => $user->id,
            'url_encrypted' => self::ICS_URL,
            'url_fingerprint' => 'https://moodle.kampus.ac.id/calendar/export_execute.php',
        ]);

        $result = app(MoodleSyncService::class)->sync($link, force: true);

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['count']);
        $this->assertSame(1, $user->deadlines()->count());
        $this->assertSame('ok', $link->fresh()->last_status);

        // URL berisi authtoken → tidak boleh muncul di log sinkronisasi (NFR-01).
        $this->assertDatabaseMissing('sync_logs', ['message' => self::ICS_URL]);
        $this->assertDatabaseHas('sync_logs', ['status' => 'ok', 'user_id' => $user->id]);
    }

    public function test_sinkronisasi_ulang_tidak_menduplikasi(): void
    {
        Http::fake([self::ICS_URL.'*' => Http::response($this->sampleIcs(), 200)]);

        $user = User::create(['name' => 'Mhs', 'email' => 'mhs@t.test', 'password' => 'password123']);
        $link = MoodleLink::create([
            'user_id' => $user->id,
            'url_encrypted' => self::ICS_URL,
            'url_fingerprint' => 'fp',
        ]);

        $service = app(MoodleSyncService::class);
        $service->sync($link, force: true);

        // Data berubah di Moodle (judul sama, UID sama) → tetap satu baris.
        $service->sync($link, force: true);

        $this->assertSame(1, $user->deadlines()->count());
        $this->assertSame(2, SyncLog::where('user_id', $user->id)->count());
    }

    public function test_gagal_unduh_tercatat_sebagai_error(): void
    {
        Http::fake([self::ICS_URL.'*' => Http::response('Internal Server Error', 500)]);

        $user = User::create(['name' => 'Mhs', 'email' => 'mhs@t.test', 'password' => 'password123']);
        $link = MoodleLink::create([
            'user_id' => $user->id,
            'url_encrypted' => self::ICS_URL,
            'url_fingerprint' => 'fp',
        ]);

        $result = app(MoodleSyncService::class)->sync($link, force: true);

        $this->assertFalse($result['ok']);
        $this->assertSame('error', $link->fresh()->last_status);
        $this->assertNotNull($link->fresh()->last_error);
        $this->assertSame(0, Deadline::count());
        $this->assertDatabaseHas('sync_logs', ['status' => 'error', 'user_id' => $user->id]);
    }
}
