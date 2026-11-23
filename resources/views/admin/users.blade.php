@extends('layouts.app')

@section('title', 'Kelola Akun')

@section('content')
<div class="page-header">
    <div>
        <h1>Kelola Akun &amp; Peran</h1>
        <p>Tambah akun, ubah peran, dan hapus pengguna (FR-08, US-7).</p>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
        <input type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau email...">
        <button type="submit" class="btn btn-outline btn-sm">Cari</button>
    </form>
</div>

<div class="card">
    <h2 class="card-title">Tambah akun baru</h2>
    <form method="POST" action="{{ route('admin.users.store') }}" style="margin-top:10px;">
        @csrf
        <div class="grid-2">
            <div class="form-group">
                <label for="name">Nama</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required minlength="8">
            </div>
            <div class="form-group">
                <label for="role">Peran</label>
                <select id="role" name="role">
                    <option value="mahasiswa" @selected(old('role') === 'mahasiswa')>Mahasiswa</option>
                    <option value="dosen" @selected(old('role') === 'dosen')>Dosen</option>
                    <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Buat Akun</button>
    </form>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Peran</th>
                    <th>Deadline</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                    <tr>
                        <td><strong>{{ $u->name }}</strong>{{ $u->id === $currentUserId ? ' <span class="muted small">(Anda)</span>' : '' }}</td>
                        <td class="small">{{ $u->email }}</td>
                        <td>
                            <form class="inline-form flex-between" method="POST" action="{{ route('admin.users.update', $u) }}">
                                @csrf
                                @method('PUT')
                                <select name="role" style="width:auto; min-width:130px;">
                                    <option value="mahasiswa" @selected($u->role === 'mahasiswa')>Mahasiswa</option>
                                    <option value="dosen" @selected($u->role === 'dosen')>Dosen</option>
                                    <option value="admin" @selected($u->role === 'admin')>Admin</option>
                                </select>
                                <button type="submit" class="btn btn-outline btn-sm">Simpan</button>
                            </form>
                        </td>
                        <td>{{ $u->deadlines_count }}</td>
                        <td>
                            @if($u->id !== $currentUserId)
                                <form class="inline-form" method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                      onsubmit="return confirm('Hapus akun {{ $u->name }}? Seluruh data terkait ikut terhapus.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            @else
                                <span class="muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">Tidak ada pengguna ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:14px;">{{ $users->links() }}</div>
</div>
@endsection
