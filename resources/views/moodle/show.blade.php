@extends('layouts.app')

@section('title', 'Hubungkan Moodle')

@section('content')
<div class="page-header">
    <div>
        <h1>Hubungkan Moodle Anda</h1>
        <p>Sistem hanya MEMBACA ekspor kalender milik Anda sendiri — tidak pernah mengubah apa pun di Moodle.</p>
    </div>
</div>

<div class="grid-2">
    <div>
        <div class="card">
            <h2 class="card-title">URL Ekspor Kalender (iCal)</h2>

            @if($link)
                <p class="card-sub">
                    Status: terhubung ke <strong>{{ $link->masked_url }}</strong>.
                    URL lengkap disimpan terenkripsi dan tidak ditampilkan kembali demi keamanan.
                </p>

                <div class="table-wrap">
                    <table class="table">
                        <tr><td class="muted">Terakhir disinkronkan</td><td>{{ $link->last_synced_at?->translatedFormat('l, d M Y H:i') ?? 'belum pernah' }}</td></tr>
                        <tr><td class="muted">Status sinkron terakhir</td>
                            <td>
                                @if($link->last_status === 'ok') <span class="badge badge-ok">Berhasil</span>
                                @elseif($link->last_status === 'error') <span class="badge badge-err">Gagal</span>
                                @else <span class="muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @if($link->last_error)
                            <tr><td class="muted">Pesan galat terakhir</td><td>{{ $link->last_error }}</td></tr>
                        @endif
                        <tr><td class="muted">Total deadline tersimpan</td><td>{{ $totalDeadline }}</td></tr>
                    </table>
                </div>

                <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:16px;">
                    <form method="POST" action="{{ route('moodle.sync') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">Sinkron Sekarang</button>
                    </form>
                    <form method="POST" action="{{ route('moodle.store') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline" onclick="document.getElementById('url').scrollIntoView(); return false;">Ganti URL</button>
                    </form>
                    <form method="POST" action="{{ route('moodle.destroy') }}" onsubmit="return confirm('Putuskan tautan Moodle? Seluruh deadline hasil sinkronisasi akan dihapus.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Putuskan Tautan</button>
                    </form>
                </div>
            @endif

            <form method="POST" action="{{ route('moodle.store') }}" style="{{ $link ? 'margin-top:22px; border-top:1px solid var(--border); padding-top:18px;' : '' }}">
                @csrf
                <div class="form-group">
                    <label for="url">{{ $link ? 'Ganti dengan URL baru' : 'Tempel URL ekspor kalender Moodle Anda' }}</label>
                    <input id="url" type="url" name="url" value="{{ old('url') }}" required
                           placeholder="https://moodle.kampus.ac.id/calendar/export_execute.php?...">
                    <div class="form-hint">
                        Contoh: <code class="mono">https://moodle.kampus.ac.id/calendar/export_execute.php?userid=123&amp;authtoken=...&amp;preset_what=all&amp;preset_time=recentupcoming</code>
                    </div>
                    @error('url') <div class="error-text">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ $link ? 'Simpan & Sinkron Ulang' : 'Hubungkan & Sinkron' }}</button>
            </form>
        </div>
    </div>

    <div>
        <div class="card">
            <h2 class="card-title">Cara mendapatkan URL iCal</h2>
            <ol class="small" style="line-height:1.9; padding-left:18px; margin:10px 0 0;">
                <li>Login ke Moodle kampus Anda.</li>
                <li>Buka <strong>Dashboard</strong>, cari blok <strong>Kalender</strong>.</li>
                <li>Klik tautan <strong>"Impor atau ekspor kalender"</strong> di bawah kalender.</li>
                <li>Pilih tab <strong>Ekspor</strong>.</li>
                <li>Biarkan pilihan default (Semua acara, rentang "Terbaru &amp; mendatang").</li>
                <li>Klik <strong>"Dapatkan URL kalender"</strong>, lalu salin URL yang muncul.</li>
                <li>Tempel URL tersebut di form di samping.</li>
            </ol>
            <p class="small muted" style="margin-top:14px;">
                Keamanan: URL memuat token pribadi Anda. MDC menyimpannya <strong>terenkripsi</strong>,
                tidak menampilkannya kembali, dan tidak menulisnya ke log. Anda dapat memutuskan tautan kapan saja.
            </p>
        </div>

        @if($link)
            <div class="card">
                <h2 class="card-title">Riwayat sinkronisasi terakhir</h2>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Waktu</th><th>Status</th><th>Hasil</th></tr></thead>
                        <tbody>
                            @forelse($syncLogs as $log)
                                <tr>
                                    <td class="small">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                                    <td>
                                        @if($log->status === 'ok') <span class="badge badge-ok">Berhasil</span>
                                        @else <span class="badge badge-err">Gagal</span>
                                        @endif
                                    </td>
                                    <td class="small muted">{{ $log->message }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="muted">Belum ada riwayat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
