<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReminderLog;
use App\Models\SyncLog;
use Illuminate\View\View;

/**
 * Log sinkronisasi & log pengiriman notifikasi (FR-08, US-7, BL-12).
 * Entri gagal beserta waktu dan penyebabnya tampil di sini sehingga
 * masalah layanan dapat cepat dilacak.
 */
class LogController extends Controller
{
    public function index(): View
    {
        return view('admin.logs', [
            'syncLogs' => SyncLog::with('user')->orderByDesc('created_at')->limit(100)->get(),
            'reminderLogs' => ReminderLog::with(['deadline', 'user'])
                ->orderByDesc('sent_at')
                ->limit(100)
                ->get(),
        ]);
    }
}
