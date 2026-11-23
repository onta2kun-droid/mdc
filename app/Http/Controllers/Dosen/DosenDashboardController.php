<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Rekap agregat untuk dosen (FR-07, US-6, BL-11).
 *
 * Hanya menampilkan ANGKA AGREGAT (jumlah/persentase mahasiswa yang
 * menandai tugas selesai) — tidak pernah menampilkan data pribadi
 * mahasiswa, sesuai acceptance criteria US-6 dan risiko privasi no. 7.
 */
class DosenDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $course = $request->query('course');

        $courses = Deadline::query()->select('course')->distinct()->orderBy('course')->pluck('course');

        $rows = Deadline::query()
            ->when($course !== null && $course !== '', fn ($q) => $q->where('course', $course))
            ->selectRaw('course, title, type, starts_at')
            ->selectRaw('COUNT(DISTINCT user_id) as total_mahasiswa')
            ->selectRaw('SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as total_selesai')
            ->groupBy('course', 'title', 'type', 'starts_at')
            ->orderBy('starts_at')
            ->limit(100)
            ->get()
            ->map(function ($row) {
                $row->persentase = $row->total_mahasiswa > 0
                    ? (int) round($row->total_selesai / $row->total_mahasiswa * 100)
                    : 0;

                return $row;
            });

        return view('dosen.index', [
            'rows' => $rows,
            'courses' => $courses,
            'selectedCourse' => $course,
        ]);
    }
}
