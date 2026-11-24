<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian halaman preferensi pengingat (FR-06).
 *
 * Halaman memuat DUA form terpisah yang sama-sama mengirim PUT ke
 * /pengaturan/pengingat. Handler harus hanya memperbarui field milik form
 * yang dikirim:
 * - form "Simpan Preferensi" (checkbox) tidak boleh menghapus telegram_chat_id;
 * - form "Simpan Chat ID" tidak boleh mematikan checkbox kanal/waktu;
 * - Chat ID kosong berarti hapus Chat ID, flag tetap.
 */
class ReminderPreferenceTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(): User
    {
        return User::create([
            'name' => 'Mahasiswa Uji',
            'email' => 'pref@mdc.test',
            'password' => 'password123',
            'email_reminder_enabled' => true,
            'telegram_reminder_enabled' => true,
            'remind_h1' => true,
            'remind_h3' => true,
            'telegram_chat_id' => '6542090467',
        ]);
    }

    public function test_simpan_preferensi_tidak_menghapus_chat_id(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->put('/pengaturan/pengingat', [
                'email_reminder_enabled' => '1',
                'telegram_reminder_enabled' => '1',
                'remind_h1' => '1',
                // remind_h3 tidak dikirim (tidak dicentang) -> harus jadi false
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->email_reminder_enabled);
        $this->assertTrue($user->telegram_reminder_enabled);
        $this->assertTrue($user->remind_h1);
        $this->assertFalse($user->remind_h3);
        // Inilah bug lama: chat id ikut terhapus. Sekarang harus tetap.
        $this->assertSame('6542090467', (string) $user->telegram_chat_id);
    }

    public function test_simpan_chat_id_tidak_mematikan_preferensi(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->put('/pengaturan/pengingat', [
                'telegram_chat_id' => '987654321',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('987654321', (string) $user->telegram_chat_id);
        // Bug lama: semua flag jadi false karena checkbox tak terkirim.
        $this->assertTrue($user->email_reminder_enabled);
        $this->assertTrue($user->telegram_reminder_enabled);
        $this->assertTrue($user->remind_h1);
        $this->assertTrue($user->remind_h3);
    }

    public function test_chat_id_kosong_menghapus_chat_id_flag_tetap(): void
    {
        $user = $this->buatUser();

        $this->actingAs($user)
            ->put('/pengaturan/pengingat', [
                'telegram_chat_id' => '',   // dikosongkan -> hapus Chat ID
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertTrue($user->email_reminder_enabled);
        $this->assertTrue($user->telegram_reminder_enabled);
        $this->assertTrue($user->remind_h1);
        $this->assertTrue($user->remind_h3);
    }
}
