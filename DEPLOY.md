# Panduan Deployment — Moodle Deadline Companion

Dua jalur deployment disediakan sesuai kondisi server:

- **Jalur A — Shared hosting hostdataid (DirectAdmin)** ← kondisi Anda saat ini
- **Jalur B — VPS sendiri dengan Docker + Caddy** (opsional, sesuai BL-08)

---

## Jalur A — hostdataid / DirectAdmin (itsricho.my.id)

Docker **tidak dapat berjalan** di shared hosting, jadi aplikasi dideploy secara native (PHP). Semua yang dibutuhkan sudah disediakan panel.

### A1. Aktifkan SSL untuk itsricho.my.id (kerjakan pertama)

1. Login DirectAdmin → **Account Manager → SSL/TLS Certificates**.
2. Pilih **"Free & automatic certificate from Let's Encrypt"**.
3. Centang `itsricho.my.id` **dan** `www.itsricho.my.id`.
4. Centang **"Force SSL with https redirect"** → Save.
5. Tes `https://itsricho.my.id` — pastikan ikon gembok muncul.

Gagal? Pastikan **A record** domain sudah mengarah ke IP hosting; bila menu Let's Encrypt tidak ada, buat tiket ke support hostdataid ("mohon aktifkan Let's Encrypt untuk akun saya"). Batas Let's Encrypt 5 penerbitan/minggu per domain.

### A2. Pilih PHP 8.3+ dan ekstensi

1. DirectAdmin → **Select PHP Version** (atau "PHP Configuration").
2. Pilih **PHP 8.3 atau 8.4** (Laravel 13 minimal PHP 8.3).
3. Aktifkan ekstensi: `pdo`, `pdo_mysql` (atau `pdo_sqlite`), `mbstring`, `curl`, `openssl`, `xml`, `zip`, `fileinfo`, `ctype`, `tokenizer`.

### A3. Buat database MySQL (disarankan untuk produksi)

1. DirectAdmin → **Account Manager → MySQL Management → Create new Database**.
2. Catat: nama database, username, password (format umumnya `USER_mdc`).
3. (Alternatif ringan untuk MVP: tetap pakai SQLite — file `database/database.sqlite` sudah tersertakan dan sudah ter-migrasi + seed, tanpa perlu langkah apa pun.)

### A4. Upload aplikasi

1. Upload `mdc.zip` melalui **File Manager** ke folder home (`/home/USERNAME/`), lalu **Extract**.
   Hasilnya: `/home/USERNAME/mdc/` (kode aplikasi) — *di luar* `public_html`, supaya `.env` dan kode tidak bisa diakses web.
2. Salin seluruh **isi** folder `/home/USERNAME/mdc/public/` ke `/home/USERNAME/domains/itsricho.my.id/public_html/` (tambahkan juga file `.htaccess` bila belum ikut tersalin — File Manager bisa menampilkan hidden files).
3. Edit `public_html/index.php` (dua baris teratas menunjuk ke aplikasi):

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

require __DIR__.'/../../mdc/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../../mdc/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

   (Sesuaikan `mdc` bila nama folder berbeda — polanya `__DIR__.'/../../NAMA_FOLDER/vendor/autoload.php'`.)

### A5. Konfigurasi .env

Edit `/home/USERNAME/mdc/.env` melalui File Manager (klik kanan → Edit):

```ini
APP_NAME="Moodle Deadline Companion"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://itsricho.my.id

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=USERNAME_mdc
DB_USERNAME=USERNAME_mdc
DB_PASSWORD=xxxx

MAIL_MAILER=sendmail
MAIL_FROM_ADDRESS=no-reply@itsricho.my.id

TELEGRAM_BOT_TOKEN=token-dari-botfather
```

> Pakai SQLite? Cukup `DB_CONNECTION=sqlite` dan abaikan baris `DB_HOST` dst. Pastikan folder `database/` writable.
> Bila `APP_KEY` masih kosong, jalankan `php artisan key:generate` (A6) atau salin `APP_KEY` dari `.env` yang tercantum.

### A6. Migrasi database (butuh akses terminal)

DirectAdmin hostdataid umumnya menyediakan **Terminal** (ikon di dashboard). Bila tidak ada, minta support mengaktifkan SSH.

```bash
cd ~/mdc
php artisan migrate --force --seed     # untuk MySQL; untuk SQLite: php artisan migrate:fresh --seed --force
php artisan storage:link               # (aman diabaikan bila gagal di shared hosting)
php artisan config:clear
```

Verifikasi: buka `https://itsricho.my.id` — halaman login MDC tampil. Login `admin@mdc.test` / `password` (segera ganti password, atau buat akun admin baru lalu hapus akun demo).

### A7. Cron scheduler (wajib agar sinkronisasi & pengingat berjalan)

DirectAdmin → **Account Manager → Cron Jobs → Add**:

```
* * * * * cd /home/USERNAME/mdc && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

- Sesuaikan `/usr/local/bin/php` dengan path PHP aktif (cek dengan `which php` di Terminal, atau tanyakan ke support hostdataid).
- Cron ini menjalankan jadwal dari `routes/console.php`: `mdc:sync` tiap 30 menit, `mdc:send-reminders` tiap 5 menit.

### A8. Health check & uptime (NFR-03)

- Endpoint: `https://itsricho.my.id/health` → JSON `{"ok":true,...}`; daftarkan ke UptimeRobot agar tahu bila layanan turun (target uptime ≥ 99%).
- Backup: DirectAdmin → **Daily Backup/Remote Backup**, atau cron harian `php artisan backup` bila ditambahkan. Minimal: cadangkan file `database/database.sqlite` (SQLite) atau `mysqldump` (MySQL) harian ke luar server.

### A9. Checklist akhir jalur A

- [ ] HTTPS aktif + force redirect (A1)
- [ ] PHP 8.3+ dengan ekstensi lengkap (A2)
- [ ] Database dibuat & terisi (A3/A6)
- [ ] `APP_DEBUG=false`, `APP_URL=https://itsricho.my.id` (A5)
- [ ] Cron `schedule:run` tiap menit aktif (A7)
- [ ] Password akun demo sudah diganti (A6)
- [ ] `/health` hijau di uptime monitor (A8)

---

## Jalur B — VPS sendiri dengan Docker (opsional)

Cocok bila kelak pindah ke VPS (mis. sesuai LKM: VPS 2 vCPU/2 GB).

```bash
# 1. Arahkan A record itsricho.my.id → IP VPS
# 2. Clone/upload proyek ke VPS, lalu:
cd mdc
docker compose -f docker-compose.vps.yml up -d --build
```

- Caddy otomatis menerbitkan sertifikat **Let's Encrypt** untuk `itsricho.my.id` (tanpa konfigurasi manual, otomatis perpanjang).
- Migrasi otomatis dijalankan entrypoint; scheduler berjalan sebagai service terpisah.
- Pantau: `docker compose -f docker-compose.vps.yml logs -f app`.

---

## Troubleshooting umum

| Gejala | Penyebab & solusi |
|--------|-------------------|
| Halaman blank / 500 | `storage/` & `bootstrap/cache` tidak writable → chmod 775; cek `storage/logs/laravel.log` |
| "Composer platform check" | Versi PHP hosting di bawah 8.3 → ganti versi PHP di panel |
| CSS tampil polos | Folder `public/assets` tidak ikut tersalin ke `public_html` |
| Redirect login terus | Cookie tidak terkirim karena `APP_URL`/HTTPS tidak konsisten → samakan `APP_URL=https://itsricho.my.id` |
| Pengingat tidak terkirim | Cron belum aktif (A7); cek halaman **Admin → Log Sistem** |
| Sinkron gagal di log | URL iCal kadaluarsa/dinonaktifkan admin Moodle → minta pengguna memperbarui URL (fitur "Ganti URL") |
