@extends('layouts.app')

@section('title', 'Log Sistem')

@section('content')
<div class="page-header">
    <div>
        <h1>Log Sinkronisasi &amp; Notifikasi</h1>
        <p>100 entri terakhir untuk melacak masalah layanan (US-7). URL iCal tidak pernah dicatat di log.</p>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Log Sinkronisasi iCal</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Waktu</th><th>Pengguna</th><th>Status</th><th>Deadline</th><th>Pesan</th></tr>
            </thead>
            <tbody>
                @forelse($syncLogs as $log)
                    <tr>
                        <td class="small">{{ $log->created_at?->translatedFormat('d M Y H:i:s') }}</td>
                        <td class="small">{{ $log->user->name }}</td>
                        <td>
                            @if($log->status === 'ok') <span class="badge badge-ok">Berhasil</span>
                            @else <span class="badge badge-err">Gagal</span>
                            @endif
                        </td>
                        <td>{{ $log->deadlines_count }}</td>
                        <td class="small muted">{{ $log->message }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada log sinkronisasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <h2 class="card-title">Log Pengiriman Pengingat</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Dikirim</th><th>Pengguna</th><th>Kanal</th><th>Tipe</th><th>Tugas</th></tr>
            </thead>
            <tbody>
                @forelse($reminderLogs as $log)
                    <tr>
                        <td class="small">{{ $log->sent_at?->translatedFormat('d M Y H:i:s') }}</td>
                        <td class="small">{{ $log->user->name }}</td>
                        <td><span class="badge badge-lainnya">{{ ucfirst($log->channel) }}</span></td>
                        <td class="small">{{ strtoupper($log->reminder_type) }}</td>
                        <td class="small">
                            {{ $log->deadline?->title }}
                            <span class="muted">({{ $log->deadline?->course }})</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Belum ada pengingat terkirim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
