<?php

namespace App\Http\Controllers;

use App\Models\Deadline;
use App\Models\MoodleLink;
use App\Services\IcalParserService;
use App\Services\MoodleSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Menghubungkan / memutuskan tautan Moodle via URL ekspor kalender iCal
 * (FR-02, FR-06, US-2). URL disimpan terenkripsi dan tidak pernah
 * ditampilkan utuh kembali (hanya fingerprint aman untuk UI).
 */
class MoodleLinkController extends Controller
{
    public function __construct(
        private readonly IcalParserService $parser,
        private readonly MoodleSyncService $sync,
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $link = $user->moodleLink()->first();

        return view('moodle.show', [
            'link' => $link,
            'syncLogs' => $link ? $user->syncLogs()->latest('created_at')->limit(5)->get() : collect(),
            'totalDeadline' => $user->deadlines()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048', 'starts_with:http://,https://'],
        ], [
            'url.required' => 'URL ekspor kalender Moodle wajib diisi.',
            'url.url' => 'Format URL tidak valid.',
            'url.starts_with' => 'URL harus dimulai dengan http:// atau https://.',
        ]);

        $url = $validated['url'];

        // US-2 acceptance criteria: URL tidak valid → galat DAN tidak disimpan.
        $check = $this->parser->validateUrl($url);

        if (! $check['ok']) {
            return back()
                ->withInput(['url' => ''])
                ->withErrors(['url' => 'URL tidak dapat dipakai: '.$check['error']])
                ->with('hint', 'Cara mendapatkannya: Moodle → Dashboard → Kalender → "Impor atau ekspor kalender" → Ekspor kalender → "Dapatkan URL kalender".');
        }

        $link = MoodleLink::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'url_encrypted' => $url,
                'url_fingerprint' => (string) (parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST).parse_url($url, PHP_URL_PATH)),
            ],
        );

        // Sinkronisasi pertama langsung dijalankan (US-2).
        $result = $this->sync->sync($link, force: true);

        if ($result['ok']) {
            if ($result['count'] === 0) {
                return redirect()->route('moodle.show')
                    ->with('success', 'Moodle berhasil terhubung! Kalender Anda masih kosong (0 deadline). Buat tugas/kuis dengan tenggat waktu di Moodle, pastikan Anda ter-enrol di kursusnya, lalu klik "Sinkron sekarang".');
            }

            return redirect()->route('moodle.show')
                ->with('success', 'Moodle berhasil terhubung! '.$result['count'].' deadline diimpor ke dashboard Anda.');
        }

        return redirect()->route('moodle.show')
            ->with('warning', 'Tautan tersimpan, tetapi sinkronisasi pertama gagal: '.$result['message']);
    }

    /** Sinkronisasi manual (FR-03). */
    public function sync(Request $request): RedirectResponse
    {
        $link = $request->user()->moodleLink;

        abort_unless($link !== null, 404, 'Anda belum menghubungkan Moodle.');

        $result = $this->sync->sync($link, force: true);

        $flashKey = $result['ok'] ? 'success' : 'danger';

        return back()->with($flashKey, $result['message'].' ('.$result['count'].' deadline).');
    }

    /** Memutuskan tautan (FR-06). Deadline yang berasal dari tautan ini ikut dihapus (data minimization). */
    public function destroy(Request $request): RedirectResponse
    {
        $link = $request->user()->moodleLink;

        if ($link !== null) {
            Deadline::where('user_id', Auth::id())->where('moodle_link_id', $link->id)->delete();
            $link->delete();
        }

        return redirect()->route('moodle.show')
            ->with('success', 'Tautan Moodle diputus dan seluruh deadline hasil sinkronisasi dihapus.');
    }
}
