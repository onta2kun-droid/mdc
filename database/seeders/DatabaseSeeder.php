<?php

namespace Database\Seeders;

use App\Models\Deadline;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Akun demo untuk LKM Komputasi Awan — Kelompok 3.
     * Password semua akun: "password" (ganti di produksi!).
     */
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin MDC',
            'email' => 'admin@mdc.my.id',
            'password' => Hash::make('prabowoboss'),
            'role' => User::ROLE_ADMIN,
        ]);

        $dosen = User::create([
            'name' => 'Dosen Pengampu',
            'email' => 'dosen@mdc.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_DOSEN,
        ]);

        $mahasiswa = User::create([
            'name' => 'Mahasiswa Contoh',
            'email' => 'mahasiswa@mdc.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_MAHASISWA,
        ]);

        // Deadline contoh (tanpa koneksi Moodle) agar dashboard tidak kosong
        // saat demo. Data nyata akan tersinkron otomatis dari iCal Moodle.
        $samples = [
            ['(Contoh) Pemrograman Web', 'Tugas 1: Membuat layout responsive', Deadline::TYPE_TUGAS, now()->subDays(1), false],
            ['(Contoh) Basis Data', 'Kuis Normalisasi', Deadline::TYPE_KUIS, today()->setTime(17, 0), false],
            ['(Contoh) Komputasi Awan', 'LKM 10: Deployment container', Deadline::TYPE_TUGAS, now()->addDays(2)->setTime(23, 59), false],
            ['(Contoh) Statistika', 'Laporan analisis data', Deadline::TYPE_TUGAS, now()->addDays(9)->setTime(12, 0), false],
            ['(Contoh) Pemrograman Web', 'Tugas 0: Instalasi tooling', Deadline::TYPE_TUGAS, now()->subDays(3), true],
        ];

        foreach ($samples as [$course, $title, $type, $due, $done]) {
            Deadline::create([
                'user_id' => $mahasiswa->id,
                'uid' => 'seed-'.md5($course.$title),
                'course' => $course,
                'title' => $title,
                'type' => $type,
                'starts_at' => $due,
                'completed_at' => $done ? now() : null,
            ]);
        }

        $this->command->info('Akun demo dibuat: admin@mdc.test, dosen@mdc.test, mahasiswa@mdc.test (password: password)');
    }
}
