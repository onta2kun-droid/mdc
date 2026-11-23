@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
<div class="auth-wrap">
    <a href="{{ route('login') }}" class="brand">
        <span class="brand-badge">MDC</span>
        <span class="brand-text">Moodle Deadline Companion</span>
    </a>

    <div class="card">
        <h1 class="card-title">Buat akun baru</h1>
        <p class="card-sub">Hubungkan kalender Moodle Anda dan mulai pantau semua deadline.</p>

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <div class="form-group">
                <label for="name">Nama lengkap</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                @error('name') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                @error('email') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password">
                <div class="form-hint">Minimal 8 karakter. Disimpan dalam bentuk hash, bukan teks biasa.</div>
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation">Konfirmasi password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                @error('password_confirmation') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="role">Saya mendaftar sebagai</label>
                <select id="role" name="role">
                    <option value="mahasiswa" @selected(old('role', 'mahasiswa') === 'mahasiswa')>Mahasiswa</option>
                    <option value="dosen" @selected(old('role') === 'dosen')>Dosen</option>
                </select>
                <div class="form-hint">Akun admin dibuat oleh admin sistem, tidak melalui pendaftaran mandiri.</div>
                @error('role') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Daftar</button>
        </form>
    </div>

    <p class="auth-foot">
        Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
    </p>
</div>
@endsection
