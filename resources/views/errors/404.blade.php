<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Halaman tidak ditemukan — {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body>
<main class="container">
    <div class="auth-wrap">
        <div class="card" style="text-align:center;">
            <div style="font-size:44px; font-weight:800; color:var(--primary);">404</div>
            <h1 class="card-title">Halaman tidak ditemukan</h1>
            <p class="card-sub">Alamat yang Anda buka tidak tersedia atau sudah dipindahkan.</p>
            <a href="{{ url(auth()->check() ? '/dashboard' : '/login') }}" class="btn btn-primary">Kembali</a>
        </div>
    </div>
</main>
</body>
</html>
