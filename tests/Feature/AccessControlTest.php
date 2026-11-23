<?php

namespace Tests\Feature;

use App\Models\Deadline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian acceptance criteria autentikasi & hak akses peran
 * (US-1, US-6, US-7, FR-01): role salah harus menerima HTTP 403.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function buatUser(string $role): User
    {
        return User::create([
            'name' => 'Pengguna '.ucfirst($role),
            'email' => $role.'@mdc.test',
            'password' => 'password123',
            'role' => $role,
        ]);
    }

    public function test_login_berhasil_menampilkan_dashboard(): void
    {
        $user = $this->buatUser('mahasiswa');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_salah_ditolak(): void
    {
        $user = $this->buatUser('mahasiswa');

        $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-yang-salah',
        ]);

        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_mahasiswa_ditolak_403_di_rekap_dosen(): void
    {
        $mahasiswa = $this->buatUser('mahasiswa');

        $this->actingAs($mahasiswa)->get('/dosen')->assertForbidden();
    }

    public function test_mahasiswa_ditolak_403_di_halaman_admin(): void
    {
        $mahasiswa = $this->buatUser('mahasiswa');

        $this->actingAs($mahasiswa)->get('/admin/pengguna')->assertForbidden();
        $this->actingAs($mahasiswa)->get('/admin/log')->assertForbidden();
    }

    public function test_dosen_ditolak_403_di_halaman_admin(): void
    {
        $dosen = $this->buatUser('dosen');

        $this->actingAs($dosen)->get('/admin/pengguna')->assertForbidden();
    }

    public function test_admin_bisa_mengakses_halaman_admin(): void
    {
        $admin = $this->buatUser('admin');

        $this->actingAs($admin)->get('/admin/pengguna')->assertOk();
        $this->actingAs($admin)->get('/admin/log')->assertOk();
    }

    public function test_dosen_dan_admin_bisa_mengakses_rekap(): void
    {
        $dosen = $this->buatUser('dosen');
        $admin = $this->buatUser('admin');

        $this->actingAs($dosen)->get('/dosen')->assertOk();
        $this->actingAs($admin)->get('/dosen')->assertOk();
    }

    public function test_rekap_tidak_menampilkan_data_pribadi_mahasiswa(): void
    {
        $dosen = $this->buatUser('dosen');
        $mahasiswa = User::create([
            'name' => 'Rahasia Namaku',
            'email' => 'rahasia@mdc.test',
            'password' => 'password123',
            'role' => 'mahasiswa',
        ]);

        Deadline::create([
            'user_id' => $mahasiswa->id,
            'uid' => 'ev-200@moodle.test',
            'course' => 'Komputasi Awan',
            'title' => 'Tugas Rekap',
            'type' => 'tugas',
            'starts_at' => now()->addDays(2),
        ]);

        $response = $this->actingAs($dosen)->get('/dosen');

        $response->assertOk();
        $response->assertSee('Komputasi Awan');       // data agregat tampil
        $response->assertDontSee('Rahasia Namaku');    // data pribadi tidak
        $response->assertDontSee('rahasia@mdc.test');
    }

    public function test_mahasiswa_tidak_bisa_menandai_tugas_orang_lain(): void
    {
        $pemilik = $this->buatUser('mahasiswa');
        $penyusup = User::create([
            'name' => 'Penyusup',
            'email' => 'penyusup@mdc.test',
            'password' => 'password123',
            'role' => 'mahasiswa',
        ]);

        $deadline = Deadline::create([
            'user_id' => $pemilik->id,
            'uid' => 'ev-300@moodle.test',
            'course' => 'Komputasi Awan',
            'title' => 'Tugas Pemilik',
            'type' => 'tugas',
            'starts_at' => now()->addDays(1),
        ]);

        $this->actingAs($penyusup)
            ->patch("/deadlines/{$deadline->id}/selesai")
            ->assertForbidden();

        $this->assertNull($deadline->fresh()->completed_at);
    }

    public function test_health_endpoint_publik(): void
    {
        $response = $this->getJson('/health');

        $response->assertOk()->assertJsonPath('ok', true);
    }
}
