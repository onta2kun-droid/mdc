<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%234f46e5'/><text x='50' y='68' font-size='52' font-family='Arial' font-weight='bold' fill='white' text-anchor='middle'>M</text></svg>">
</head>
<body>
@php $authUser = auth()->user(); @endphp

<nav class="topnav">
    <div class="topnav-inner">
        <a href="{{ route(auth()->check() ? 'dashboard' : 'login') }}" class="brand">
            <span class="brand-badge">MDC</span>
            <span class="brand-text">Moodle Deadline Companion</span>
        </a>

        @auth
            <div class="nav-links">
                @if($authUser->hasRole('mahasiswa'))
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <a href="{{ route('moodle.show') }}">Hubungkan Moodle</a>
                @endif
                @if($authUser->hasRole('dosen', 'admin'))
                    <a href="{{ route('dosen.index') }}">Rekap Kelas</a>
                @endif
                @if($authUser->hasRole('admin'))
                    <a href="{{ route('admin.users.index') }}">Kelola Akun</a>
                    <a href="{{ route('admin.logs.index') }}">Log Sistem</a>
                @endif
                <a href="{{ route('reminders.edit') }}">Pengaturan Pengingat</a>
            </div>

            <div class="nav-user">
                <span class="nav-user-name">{{ $authUser->name }}</span>
                <span class="role-badge role-{{ $authUser->role }}">{{ ucfirst($authUser->role) }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Keluar</button>
                </form>
            </div>
        @endauth
    </div>
</nav>

<main class="container">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif
    @if(session('danger'))
        <div class="alert alert-danger">{{ session('danger') }}</div>
    @endif
    @if(session('hint'))
        <div class="alert alert-info">{{ session('hint') }}</div>
    @endif

    @yield('content')
</main>

<footer class="footer">
    {{ config('app.name') }} &mdash; Proyek Komputasi Awan, Kelompok 3 &middot; S1 Pendidikan Komputer
</footer>
</body>
</html>
