<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses ditolak — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
<main class="container">
    <div class="auth-wrap">
        <div class="card" style="text-align:center;">
            <div style="font-size:44px; font-weight:800; color:var(--red);">403</div>
            <h1 class="card-title">Akses ditolak</h1>
            <p class="card-sub">
                @if($exception->getMessage() && $exception->getMessage() !== 'Forbidden')
                    {{ $exception->getMessage() }}
                @else
                    Anda tidak memiliki hak akses untuk halaman ini.
                @endif
            </p>
            <a href="{{ url(auth()->check() ? '/dashboard' : '/login') }}" class="btn btn-primary">Kembali</a>
        </div>
    </div>
</main>
</body>
</html>
