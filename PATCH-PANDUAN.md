# Patch Halaman Preferensi Pengingat (FR-06)

## Akar bug

Halaman pengaturan pengingat punya **dua form terpisah** yang sama-sama mengirim
PUT ke route `reminders.update`:

| Form | Field yang dikirim | Tombol |
|---|---|---|
| Kartu kiri — preferensi | 4 checkbox: email, telegram, H-1, H-3 | "Simpan Preferensi" |
| Kartu kanan — Telegram | 1 textbox: `telegram_chat_id` | "Simpan Chat ID" |

Handler `update()` lama **selalu menimpa kelima field sekaligus**:

- Tekan "Simpan Chat ID" → checkbox tidak ikut dikirim → semua flag jadi `0`.
- Tekan "Simpan Preferensi" → textbox tidak ikut dikirim → Chat ID jadi `null`.

## Perbaikan (tanpa mengubah Blade/route)

Controller baru membedakan form lewat keberadaan key `telegram_chat_id` pada
request (`$request->exists()`, bukan `has()` — nilai kosong diubah middleware
menjadi null sehingga `has()` bernilai false):

- **Form Chat ID** → hanya update `telegram_chat_id` (dikosongkan = hapus Chat ID).
- **Form Preferensi** → hanya update 4 flag; Chat ID **tidak disentuh**.
- Pesan sukses dibedakan per form.

## Isi paket

| File | Salin ke | Keterangan |
|---|---|---|
| `app/Http/Controllers/ReminderPreferenceController.php` | `app/Http/Controllers/` | **TIMPA** — inti perbaikan |
| `resources/views/settings/reminders.blade.php` | `resources/views/settings/` | **TIMPA** — blade bersih (juga mengaktifkan kembali tombol simpan yang sempat dinonaktifkan sementara) |
| `tests/Feature/ReminderPreferenceTest.php` | `tests/Feature/` | **BARU** — 3 test otomatis penjaga bug ini |

## A. Laptop — demo Docker lokal (untuk bukti laporan)

```powershell
# 0) copy zip ke dalam folder mdc, lalu:
cd "D:\KULIAH\SEMESTER 5\Komputasi\mdc-main\mdc"
Expand-Archive -Path .\mdc-patch-preferensi.zip -DestinationPath . -Force

# 1) kode di-bake ke image -> wajib rebuild
docker compose up -d --build
```

Uji di **http://localhost:8080** (login dulu), buka **Pengaturan Pengingat**:

- **Uji 1**: matikan centang "3 jam sebelum tenggat" → **Simpan Preferensi** →
  halaman kembali: H-3 mati, dan **Chat ID tetap 6542090467** (dulu: terhapus).
- **Uji 2**: tekan **Simpan Chat ID** (tanpa mengubah isi) → semua centang
  **tetap tercentang** (dulu: semua jadi kosong).

Screenshot sebelum/sesudah kedua uji = bukti perbaikan bug untuk laporan.

Bukti tambahan (3 test otomatis, pakai SQLite :memory: — tidak menyentuh data
demo di container):

```powershell
docker compose exec app php artisan test --filter=ReminderPreferenceTest
```

## B. Hosting produksi (DirectAdmin File Manager)

1. Upload/timpa `ReminderPreferenceController.php` ke
   `/home/knowosm1/mdc/app/Http/Controllers/`.
2. Upload/timpa `reminders.blade.php` ke
   `/home/knowosm1/mdc/resources/views/settings/`.
3. Upload `ReminderPreferenceTest.php` ke `/home/knowosm1/mdc/tests/Feature/`
   (opsional — berguna bila ingin menjalankan test di hosting).
4. Tidak perlu restart apa pun (opcache otomatis mengikuti file baru). Uji di
   domain aktif: ulangi **Uji 1** dan **Uji 2** di atas.

Verifikasi cepat lewat SSH (opsional):

```bash
cd /home/knowosm1/mdc
php artisan tinker --execute="print_r(App\Models\User::first()?->only(['email_reminder_enabled','telegram_reminder_enabled','remind_h1','remind_h3','telegram_chat_id']));"
```

## C. Push ke GitHub

**Jika repo sudah dibuat** (di hosting — ikut Panduan 1):

```bash
cd /home/knowosm1/mdc
git add app/Http/Controllers/ReminderPreferenceController.php resources/views/settings/reminders.blade.php tests/Feature/ReminderPreferenceTest.php
git status                                # pastikan .env TIDAK muncul
git commit -m "fix(preferensi): form Chat ID tidak lagi mematikan flag kanal/waktu (FR-06)"
git push origin main
git rev-parse HEAD                        # hash commit baru -> catat ke laporan
```

**Jika repo belum dibuat**: terapkan patch dulu (bagian B), lalu jalankan
`PANDUAN-1-GITHUB.md` dari Langkah 0 — commit pertamamu otomatis sudah berisi
kode yang sudah diperbaiki.

## Kenapa data lama aman

- Patch hanya mengubah cara handler membaca form — tidak menyentuh database.
- Nilai yang sudah benar (dipasang lewat tinker) tetap; halaman membaca ulang
  dari database setiap kali dibuka.
- Sampai patch terpasang di hosting, **hindari menekan kedua tombol simpan** di
  produksi.
