@extends('layouts.app')

@section('title', 'Rekap Kelas')

@section('content')
<div class="page-header">
    <div>
        <h1>Rekap Kesiapan Kelas</h1>
        <p>Persentase mahasiswa yang menandai tugas selesai (agregat, tanpa data pribadi — US-6).</p>
    </div>

    <form method="GET" action="{{ route('dosen.index') }}" class="filter-bar">
        <label for="course" class="muted small">Mata kuliah:</label>
        <select id="course" name="course" onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($courses as $c)
                <option value="{{ $c }}" @selected($selectedCourse === $c)>{{ $c }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="btn btn-outline btn-sm">Terapkan</button></noscript>
    </form>
</div>

<div class="card">
    @if($rows->isEmpty())
        <div class="empty">Belum ada data tugas pada mata kuliah ini.</div>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mata Kuliah</th>
                        <th>Tugas / Kuis</th>
                        <th>Tipe</th>
                        <th>Tenggat</th>
                        <th class="text-center">Mahasiswa</th>
                        <th>Menandai Selesai</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->course }}</td>
                            <td><strong>{{ $row->title }}</strong></td>
                            <td><span class="badge badge-{{ $row->type }}">{{ ucfirst($row->type) }}</span></td>
                            <td class="small">{{ $row->starts_at->translatedFormat('d M Y — H:i') }}</td>
                            <td>{{ $row->total_mahasiswa }}</td>
                            <td>
                                <div class="flex-between">
                                    <div class="progress">
                                        <span class="{{ $row->persentase >= 100 ? 'full' : '' }}" style="width: {{ $row->persentase }}%;"></span>
                                    </div>
                                    <span class="small" style="min-width:52px; text-align:right;">
                                        {{ $row->total_selesai }}/{{ $row->total_mahasiswa }} ({{ $row->persentase }}%)
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="small muted" style="margin-top:12px;">
            Angka dihitung dari mahasiswa yang menggunakan MDC dan mengambil mata kuliah terkait — bukan seluruh kelas riil.
        </p>
    @endif
</div>
@endsection
