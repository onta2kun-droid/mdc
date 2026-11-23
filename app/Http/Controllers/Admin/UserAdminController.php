<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Kelola akun & peran (FR-08, US-7, BL-12).
 */
class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $users = User::withCount('deadlines')
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'q' => $q,
            'currentUserId' => Auth::id(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:mahasiswa,dosen,admin'],
        ], [
            'email.unique' => 'Email sudah terdaftar.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        User::create($validated);

        return back()->with('success', 'Akun "'.$validated['name'].'" dibuat dengan peran '.$validated['role'].'.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:mahasiswa,dosen,admin'],
        ]);

        if ($user->id === Auth::id() && $validated['role'] !== 'admin') {
            return back()->with('danger', 'Anda tidak dapat menurunkan peran akun sendiri.');
        }

        $user->update($validated);

        return back()->with('success', 'Peran "'.$user->name.'" diubah menjadi '.$validated['role'].'.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('danger', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return back()->with('success', 'Akun "'.$user->name.'" dihapus.');
    }
}
