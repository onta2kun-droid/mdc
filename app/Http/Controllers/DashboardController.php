<?php

namespace App\Http\Controllers;

use App\Models\Deadline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Dashboard deadline mahasiswa (FR-04, US-3, US-5).
 * Tugas dikelompokkan: Terlambat, Hari ini, Minggu ini, Berikutnya, Selesai.
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Filter per mata kuliah (FR-04), default: semua.
        $courses = $user->deadlines()->select('course')->distinct()->orderBy('course')->pluck('course');
        $course = $request->query('course');

        $base = fn () => $user->deadlines()
            ->when($course !== null && $course !== '', fn ($q) => $q->where('course', $course));

        $buckets = [
            'terlambat' => $base()->terlambat()->orderBy('starts_at')->get(),
            'hari_ini' => $base()->hariIni()->orderBy('starts_at')->get(),
            'minggu_ini' => $base()->mingguIni()->orderBy('starts_at')->get(),
            'berikutnya' => $base()->berikutnya()->orderBy('starts_at')->get(),
            'selesai' => $base()->whereNotNull('completed_at')->orderByDesc('completed_at')->limit(30)->get(),
        ];

        return view('dashboard.index', [
            'buckets' => $buckets,
            'courses' => $courses,
            'selectedCourse' => $course,
            'hasMoodle' => $user->moodleLink()->exists(),
            'totalAktif' => $user->deadlines()->belumSelesai()->where('starts_at', '>', now())->count(),
        ]);
    }

    /** Tandai tugas selesai (US-5) → pengingat berikutnya otomatis berhenti. */
    public function complete(Request $request, Deadline $deadline): RedirectResponse
    {
        abort_unless($deadline->user_id === Auth::id(), 403);

        $deadline->update(['completed_at' => now()]);

        return back()->with('success', 'Tugas "'.$deadline->title.'" ditandai selesai. Pengingat untuk tugas ini dihentikan.');
    }

    /** Batal tandai selesai (kalau salah centang). */
    public function uncomplete(Request $request, Deadline $deadline): RedirectResponse
    {
        abort_unless($deadline->user_id === Auth::id(), 403);

        $deadline->update(['completed_at' => null]);

        return back()->with('success', 'Status tugas "'.$deadline->title.'" dikembalikan ke belum selesai.');
    }
}
