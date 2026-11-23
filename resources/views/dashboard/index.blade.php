@extends('layouts.app')

@section('title', 'Dashboard Deadline')

@section('content')
@php
    $bucketDefs = [
        'terlambat' => ['Terlambat', 'late'],
        'hari_ini'  => ['Hari Ini', 'today'],
        'minggu_ini' => ['Minggu Ini', ''],
        'berikutnya' => ['Berikutnya', ''],
        'selesai'   => ['Selesai', ''],
    ];
@endphp

<div class="page-header">
    <div>
        <h1>Dashboard Deadline</h1>
        <p>Selamat datang, {{ auth()->user()->name }}. Semua tenggat Moodle Anda dalam satu tempat.</p>
    </div>

    <form method="GET" action="{{ route('dashboard') }}" class="filter-bar">
        <label for="course" class="muted small">Filter mata kuliah:</label>
        <select id="course" name="course" onchange="this.form.submit()">
            <option value="">Semua mata kuliah</option>
            @foreach($courses as $c)
                <option value="{{ $c }}" @selected($selectedCourse === $c)>{{ $c }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-outline btn-sm">Terapkan</button></noscript>
    </form>
</div>

@if(!$hasMoodle && $totalAktif === 0)
    <div class="alert alert-info">
        <strong>Belum terhubung ke Moodle.</strong>
        Sambungkan URL ekspor kalender (iCal) Moodle Anda agar deadline tugas dan kuis terkumpul otomatis di sini.
        <a href="{{ route('moodle.show') }}">Hubungkan sekarang &rarr;</a>
    </div>
@endif

<div class="stats">
    <div class="stat stat-danger"><div class="num">{{ $buckets['terlambat']->count() }}</div><div class="lbl">Terlambat</div></div>
    <div class="stat stat-warning"><div class="num">{{ $buckets['hari_ini']->count() }}</div><div class="lbl">Hari ini</div></div>
    <div class="stat stat-primary"><div class="num">{{ $buckets['minggu_ini']->count() }}</div><div class="lbl">Minggu ini</div></div>
    <div class="stat"><div class="num">{{ $buckets['berikutnya']->count() }}</div><div class="lbl">Berikutnya</div></div>
    <div class="stat stat-success"><div class="num">{{ $buckets['selesai']->count() }}</div><div class="lbl">Selesai (terbaru)</div></div>
</div>

@foreach($bucketDefs as $key => [$label, $tone])
    @php $items = $buckets[$key]; @endphp
    <div class="section-head {{ $tone }}">
        <h2>{{ $label }}</h2>
        <span class="count">{{ $items->count() }}</span>
    </div>

    @if($items->isEmpty())
        <div class="empty">
            @if($key === 'terlambat') Tidak ada tugas terlambat. Bagus, pertahankan!
            @elseif($key === 'selesai') Belum ada tugas yang ditandai selesai.
            @else Tidak ada tenggat pada rentang ini.
            @endif
        </div>
    @else
        @foreach($items as $d)
            <div class="deadline-item {{ $key === 'selesai' ? 'done' : '' }}">
                <div class="deadline-info">
                    <div class="deadline-title">
                        <span class="badge badge-{{ $d->type }}">{{ ucfirst($d->type) }}</span>
                        {{ $d->title }}
                    </div>
                    <div class="deadline-meta">
                        {{ $d->course }}
                        &middot; <span class="deadline-due {{ $tone }}">
                            Tenggat: {{ $d->starts_at->translatedFormat('l, d M Y — H:i') }}
                        </span>
                        &middot; {{ $d->starts_at->diffForHumans() }}
                        @if($key === 'selesai' && $d->completed_at)
                            &middot; selesai {{ $d->completed_at->translatedFormat('d M Y H:i') }}
                        @endif
                    </div>
                </div>

                <div class="deadline-actions">
                    @if($d->url)
                        <a class="btn btn-outline btn-sm" href="{{ $d->url }}" target="_blank" rel="noopener">Buka di Moodle</a>
                    @endif

                    @if($key === 'selesai')
                        <form class="inline-form" method="POST" action="{{ route('deadlines.uncomplete', $d) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-ghost btn-sm">Batal</button>
                        </form>
                    @else
                        <form class="inline-form" method="POST" action="{{ route('deadlines.complete', $d) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-success btn-sm">Tandai Selesai</button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
@endforeach
@endsection
