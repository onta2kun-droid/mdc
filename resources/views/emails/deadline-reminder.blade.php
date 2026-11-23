Halo {{ $deadline->user->name }},

Ini pengingat bahwa {{ $deadline->type }} berikut akan mencapai tenggat
dalam {{ $label }}:

    Mata kuliah : {{ $deadline->course }}
    Nama        : {{ $deadline->title }}
    Tenggat     : {{ $deadline->starts_at->timezone(config('app.timezone', 'UTC'))->translatedFormat('l, d F Y H:i') }}
@if($deadline->url)
    Tautan      : {{ $deadline->url }}
@endif

Segera selesaikan dan kumpulkan sebelum batas waktu.

Anda menerima email ini karena pengingat email aktif pada akun Anda di
{{ config('app.name') }}. Ubah preferensi pengingat kapan saja di halaman
"Pengaturan Pengingat".

— {{ config('app.name') }}
