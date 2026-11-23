<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memastikan seluruh halaman web ter-render tanpa error Blade (200 OK).
 */
class PageRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_publik_ter_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke akun Anda');
        $this->get('/register')->assertOk()->assertSee('Buat akun baru');
    }

    public function test_halaman_mahasiswa_ter_render(): void
    {
        $user = User::create([
            'name' => 'Mhs Render',
            'email' => 'render@mdc.test',
            'password' => 'password123',
            'role' => 'mahasiswa',
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Dashboard Deadline');
        $this->actingAs($user)->get('/moodle')->assertOk()->assertSee('Hubungkan Moodle Anda');
        $this->actingAs($user)->get('/pengaturan/pengingat')->assertOk()->assertSee('Pengaturan Pengingat');
    }

    public function test_halaman_dosen_dan_admin_ter_render(): void
    {
        $dosen = User::create([
            'name' => 'Dosen Render',
            'email' => 'dosen.render@mdc.test',
            'password' => 'password123',
            'role' => 'dosen',
        ]);
        $admin = User::create([
            'name' => 'Admin Render',
            'email' => 'admin.render@mdc.test',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this->actingAs($dosen)->get('/dosen')->assertOk()->assertSee('Rekap Kesiapan Kelas');
        $this->actingAs($admin)->get('/admin/pengguna')->assertOk()->assertSee('Kelola Akun');
        $this->actingAs($admin)->get('/admin/log')->assertOk()->assertSee('Log Sinkronisasi');
    }
}
