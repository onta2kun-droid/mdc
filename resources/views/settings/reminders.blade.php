@extends('layouts.app')

@section('title', 'Pengaturan Pengingat')

@section('content')
<div class="page-header">
    <div>
        <h1>Pengaturan Pengingat</h1>
        <p>Atur kanal dan waktu pengingat agar tidak mengganggu namun tepat sasaran (FR-06).</p>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h2 class="card-title">Kanal pengingat</h2>
        <p class="card-sub">Pengingat hanya dikirim untuk tugas yang BELUM ditandai selesai.</p>

        <form method="POST" action="{{ route('reminders.update') }}">
            @csrf
            @method('PUT')

            <div class="check-row">
                <input id="email_reminder_enabled" type="checkbox" name="email_reminder_enabled" value="1"
                       @checked(old('email_reminder_enabled', $user->email_reminder_enabled))>
                <div>
                    <label for="email_reminder_enabled" class="check-title">Email ke {{ $user->email }}</label>
                    <div class="check-desc">Dikirim otomatis oleh scheduler server. Email akun diambil dari data pendaftaran.</div>
                </div>
            </div>

            <div class="check-row">
                <input id="telegram_reminder_enabled" type="checkbox" name="telegram_reminder_enabled" value="1"
                       @checked(old('telegram_reminder_enabled', $user->telegram_reminder_enabled))>
                <div>
                    <label for="telegram_reminder_enabled" class="check-title">Telegram</label>
                    <div class="check-desc">Butuh Telegram Chat ID (isi di samping) dan bot token yang diatur admin server.
                    @unless($telegramReady)
                        <strong style="color:var(--amber);">Bot Telegram belum dikonfigurasi di server — pengingat Telegram belum akan terkirim.</strong>
                    @endunless
                    </div>
                </div>
            </div>

            <h2 class="card-title" style="margin-top:18px;">Waktu pengingat</h2>

            <div class="check-row">
                <input id="remind_h1" type="checkbox" name="remind_h1" value="1"
                       @checked(old('remind_h1', $user->remind_h1))>
                <div>
                    <label for="remind_h1" class="check-title">H-1 (24 jam sebelum tenggat)</label>
                    <div class="check-desc">Waktu terbaik untuk mulai mengerjakan.</div>
                </div>
            </div>

            <div class="check-row">
                <input id="remind_h3" type="checkbox" name="remind_h3" value="1"
                       @checked(old('remind_h3', $user->remind_h3))>
                <div>
                    <label for="remind_h3" class="check-title">3 jam sebelum tenggat</label>
                    <div class="check-desc">Tombol "kabur" terakhir sebelum deadline.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Simpan Preferensi</button>
        </form>
    </div>

    <div class="card">
        <h2 class="card-title">Telegram Chat ID</h2>
        <p class="card-sub">Agar bot dapat mengirim pesan pribadi ke Anda.</p>

        <form method="POST" action="{{ route('reminders.update') }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="telegram_chat_id">Chat ID Anda</label>
                <input id="telegram_chat_id" type="text" name="telegram_chat_id"
                       value="{{ old('telegram_chat_id', $user->telegram_chat_id) }}"
                       placeholder="contoh: 123456789">
                @error('telegram_chat_id') <div class="error-text">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-outline">Simpan Chat ID</button>
        </form>

        <h2 class="card-title" style="margin-top:20px;">Cara mendapatkan Chat ID</h2>
        <ol class="small" style="line-height:1.9; padding-left:18px;">
            <li>Buka <strong>@userinfobot</strong> di aplikasi Telegram, kirim perintah <code class="mono">/start</code>.</li>
            <li>Bot membalas dengan <strong>Id</strong> Anda (angka). Salin angka tersebut.</li>
            <li>Tempel ke kolom di atas dan simpan.</li>
            <li>Pastikan Anda sudah pernah mengirim pesan ke bot pengingat MDC sekali (agar bot diizinkan mengirim ke Anda).</li>
        </ol>

        <div class="alert alert-info" style="margin-top:16px; margin-bottom:0;">
            Preferensi disimpan per akun. Menonaktifkan pengingat berlaku untuk semua tugas, termasuk yang baru tersinkron.
        </div>
    </div>
</div>
@endsection
