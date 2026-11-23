<?php

namespace Tests\Feature;

use App\Mail\DeadlineReminder;
use App\Models\Deadline;
use App\Models\ReminderLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Pengujian pengingat (FR-05, US-4, BL-07):
 * - H-1 dan 3 jam terkirim untuk tugas belum selesai;
 * - tidak ada duplikat ketika command dijalankan ulang (idempoten);
 * - tugas yang sudah ditandai selesai tidak memicu pengingat.
 */
class ReminderTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(): User
    {
        return User::create([
            'name' => 'Mahasiswa Uji',
            'email' => 'uji@mdc.test',
            'password' => 'password123',
            'email_reminder_enabled' => true,
            'telegram_reminder_enabled' => false,
            'remind_h1' => true,
            'remind_h3' => true,
        ]);
    }

    public function test_pengingat_h1_dan_h3_terkirim_satu_kali(): void
    {
        Mail::fake();

        $user = $this->buatUser();
        $deadline = Deadline::create([
            'user_id' => $user->id,
            'uid' => 'ev-100@moodle.test',
            'course' => 'Komputasi Awan',
            'title' => 'Tugas Uji Pengingat',
            'type' => 'tugas',
            'starts_at' => now()->addHours(2),
        ]);

        $this->artisan('mdc:send-reminders')->assertSuccessful();

        // 2 email: satu H-1, satu 3-jam (keduanya masuk jendela karena sisa 2 jam).
        Mail::assertSent(DeadlineReminder::class, 2);
        $this->assertSame(2, ReminderLog::where('deadline_id', $deadline->id)->where('channel', 'email')->count());

        // Jalankan ulang → TIDAK ada pengiriman kedua (idempoten).
        $this->artisan('mdc:send-reminders')->assertSuccessful();
        Mail::assertSent(DeadlineReminder::class, 2);
        $this->assertSame(2, ReminderLog::where('deadline_id', $deadline->id)->count());
    }

    public function test_tugas_selesai_tidak_memicu_pengingat(): void
    {
        Mail::fake();

        $user = $this->buatUser();
        Deadline::create([
            'user_id' => $user->id,
            'uid' => 'ev-101@moodle.test',
            'course' => 'Komputasi Awan',
            'title' => 'Tugas Sudah Selesai',
            'type' => 'tugas',
            'starts_at' => now()->addHours(2),
            'completed_at' => now(),
        ]);

        $this->artisan('mdc:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertSame(0, ReminderLog::count());
    }

    public function test_pengingat_hormati_preferensi_nonaktif(): void
    {
        Mail::fake();

        $user = $this->buatUser();
        $user->update(['remind_h1' => false, 'remind_h3' => false]);

        Deadline::create([
            'user_id' => $user->id,
            'uid' => 'ev-102@moodle.test',
            'course' => 'Komputasi Awan',
            'title' => 'Tugas Preferensi Mati',
            'type' => 'tugas',
            'starts_at' => now()->addHours(2),
        ]);

        $this->artisan('mdc:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_tenggat_lebih_dari_h1_belum_memicu(): void
    {
        Mail::fake();

        $user = $this->buatUser();
        Deadline::create([
            'user_id' => $user->id,
            'uid' => 'ev-103@moodle.test',
            'course' => 'Komputasi Awan',
            'title' => 'Tugas Jauhnya 3 Hari',
            'type' => 'tugas',
            'starts_at' => now()->addDays(3),
        ]);

        $this->artisan('mdc:send-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }
}
