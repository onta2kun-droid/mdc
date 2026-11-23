# Moodle Deadline Companion (MDC)

Aplikasi web cloud-native untuk mengatasi deadline tugas dan kuis Moodle yang tersebar di banyak course: seluruh tenggat terkumpul otomatis dalam satu dashboard, dengan pengingat email/Telegram tepat waktu.

> Proyek Komputasi Awan — LKM 9, Kelompok 3, S1 Pendidikan Komputer.
> Dibangun dengan **Laravel 13** (rilis 17 Maret 2026, PHP 8.3–8.5) + **Docker**.

---

## Fitur (sesuai backlog LKM 9)

| ID | Fitur | Status |
|----|-------|--------|
| BL-01 | Struktur proyek + Docker Compose | Selesai |
| BL-02 | Skema database: `users(+role)`, `moodle_links`, `deadlines`, `reminder_logs`, `sync_logs` | Selesai |
| BL-03 | Registrasi/login + peran (mahasiswa, dosen, admin), password bcrypt | Selesai |
| BL-04 | Hubungkan Moodle via URL iCal — divalidasi, disimpan **terenkripsi** | Selesai |
| BL-05 | Sinkronisasi iCal berkala (30 menit) + manual, upsert anti-duplikat | Selesai |
| BL-06 | Dashboard deadline (Terlambat/Hari ini/Minggu ini/Berikutnya) + tandai selesai + filter mata kuliah | Selesai |
| BL-07 | Pengingat email H-1 & 3 jam, idempoten lewat `reminder_logs` | Selesai |
| BL-08 | Deployment: HTTPS (DirectAdmin/Caddy) + cron scheduler + `/health` | Panduan di DEPLOY.md |
| BL-09 | Pengujian: 26 test otomatis (`php artisan test`) | Selesai |
| BL-10 | Pengingat Telegram + halaman preferensi pengingat | Selesai |
| BL-11 | Dashboard dosen: rekap agregat persentase selesai (tanpa data pribadi) | Selesai |
| BL-12 | Panel admin: kelola akun/peran + log sinkronisasi & notifikasi | Selesai |

Catatan desain: LKM 9 semula men-cantumkan FastAPI + React. Tim memutuskan memakai **Laravel 13 monolit (Blade)** agar deployment di shared hosting DirectAdmin sederhana sambil tetap memenuhi seluruh kebutuhan fungsional/nonfungsional yang sama (NFR-05: kode modular controller–service–model).

## Akun demo (hasil `db:seed`)

| Email | Peran | Password |
|-------|-------|----------|
| `mahasiswa@mdc.test` | Mahasiswa | `password` |
| `dosen@mdc.test` | Dosen | `password` |
| `admin@mdc.test` | Admin | `password` |

> Ganti password sebelum dipakai sungguhan.

## Menjalankan di komputer lokal (tanpa Docker)

Prasyarat: PHP 8.3+ (ekstensi `pdo_sqlite`, `mbstring`, `curl`, `openssl`, `xml`, `zip`) dan Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed        # SQLite dibuat otomatis + akun demo
php artisan serve                 # buka http://127.0.0.1:8000
```

Scheduler (agar sinkronisasi & pengingat berjalan) — buka terminal kedua:

```bash
php artisan schedule:work
```

Email pengingat di mode dev ditulis ke `storage/logs/laravel.log` (`MAIL_MAILER=log`).

## Menjalankan dengan Docker

```bash
docker compose up --build
```

- Aplikasi  : http://localhost:8080
- Mailpit   : http://localhost:8025 (semua email pengingat "masuk" di sini — cara aman menguji FR-05)
- Scheduler : service `scheduler` menjalankan `php artisan schedule:work`
- Migrasi otomatis dijalankan `docker/entrypoint.sh` setiap container mulai.

Untuk VPS milik sendiri dengan HTTPS otomatis (Caddy + Let's Encrypt):

```bash
# Arahkan A record domain ke IP VPS terlebih dahulu
docker compose -f docker-compose.vps.yml up -d --build
```

## Cara menguji alur utama (demo MVP)

1. Login sebagai `mahasiswa@mdc.test`.
2. Menu **Hubungkan Moodle** → tempel URL ekspor kalender Moodle, atau untuk uji cepat pakai file contoh: `php artisan serve` di satu terminal + `php -S localhost:8181` di folder proyek, lalu isi URL `http://localhost:8181/sample-moodle.ics`.
3. Deadline langsung terimpor dan muncul di dashboard, dikelompokkan per rentang waktu.
4. Klik **Tandai Selesai** → pengingat untuk tugas itu otomatis berhenti.
5. Jalankan `php artisan mdc:send-reminders` → pengingat H-1/3 jam terkirim untuk tugas mendekati tenggat (cek Mailpit/log).
6. Login sebagai `dosen@mdc.test` → lihat **Rekap Kelas** (agregat, tanpa nama mahasiswa).
7. Login sebagai `admin@mdc.test` → **Kelola Akun** dan **Log Sistem**.

## Konfigurasi penting (.env)

| Variabel | Keterangan |
|----------|-----------|
| `DB_CONNECTION` | `sqlite` (default) atau `mysql` untuk produksi shared hosting |
| `MAIL_MAILER` | `log` (dev), `sendmail`/`smtp` (produksi hostdataid) |
| `TELEGRAM_BOT_TOKEN` | Token bot dari @BotFather (opsional) |
| `MDC_MIN_SYNC_INTERVAL` | Jeda minimum antar sinkronisasi per pengguna (menit) |
| `MDC_H1_HOURS` / `MDC_H3_HOURS` | Jendela pengingat (default 24 jam / 3 jam) |
| `APP_TIMEZONE` | Default `Asia/Makassar` (WITA) |

## Catatan keamanan (NFR-01)

- URL iCal (memuat authtoken) disimpan **terenkripsi** (`encrypted` cast), tidak pernah ditampilkan ulang (hanya fingerprint host), dan **tidak pernah ditulis ke log**.
- Password di-hash bcrypt; akses halaman dibatasi middleware `role:` — pelanggaran peran menghasilkan HTTP 403 (diuji otomatis).
- Pengingat idempoten: constraint unik `(deadline_id, channel, reminder_type)` menjamin tidak ada notifikasi ganda walau scheduler berjalan ganda.
- Fitur "Putuskan Tautan" menghapus URL tersimpan beserta deadline hasil sinkronisasi (data minimization).

## Struktur kode

```
app/
├── Console/Commands/      mdc:sync, mdc:send-reminders
├── Http/
│   ├── Controllers/       Auth/, Admin/, Dosen/, Dashboard, MoodleLink, ...
│   └── Middleware/        EnsureRole (pembatas peran → 403)
├── Mail/                  DeadlineReminder (email pengingat)
├── Models/                User, MoodleLink, Deadline, ReminderLog, SyncLog
└── Services/              IcalParserService, MoodleSyncService,
                           ReminderService, TelegramService
docker/                    entrypoint.sh, Caddyfile (HTTPS otomatis VPS)
routes/console.php         jadwal scheduler (30 menit sync, 5 menit reminder)
tests/Feature/             26 pengujian (parser, sinkron, pengingat, akses, render)
```

## Deployment ke hosting

Lihat **DEPLOY.md** — berisi panduan lengkap hostdataid/DirectAdmin (PHP 8.3+, MySQL, upload, cron, SSL `itsricho.my.id`) serta opsi VPS dengan Docker.
