@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
<div class="auth-wrap">
    <a href="{{ route('login') }}" class="brand">
        <span class="brand-badge">MDC</span>
        <span class="brand-text">Moodle Deadline Companion</span>
    </a>

    <div class="card">
        <h1 class="card-title">Masuk ke akun Anda</h1>
        <p class="card-sub">Lihat semua deadline Moodle dalam satu dashboard dan terima pengingat tepat waktu.</p>

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
                @error('email') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password">
                @error('password') <div class="error-text">{{ $message }}</div> @enderror
            </div>

            <div class="check-row">
                <input id="remember" type="checkbox" name="remember" @checked(old('remember'))>
                <label for="remember" class="check-title">Tetap masuk di perangkat ini</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Masuk</button>
        </form>
    </div>

    <p class="auth-foot">
        Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
    </p>
</div>
@endsection
