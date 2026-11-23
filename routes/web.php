<?php

use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dosen\DosenDashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MoodleLinkController;
use App\Http\Controllers\ReminderPreferenceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Moodle Deadline Companion (MDC)
| Rute web sesuai LKM 9: dashboard deadline, integrasi iCal Moodle,
| pengingat email/Telegram, rekap dosen, dan panel admin.
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

// Health check untuk uptime monitor (NFR-03) — publik.
Route::get('/health', HealthController::class)->name('health');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Dashboard deadline + penandaan selesai (FR-04, US-3, US-5).
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::patch('/deadlines/{deadline}/selesai', [DashboardController::class, 'complete'])->name('deadlines.complete');
    Route::patch('/deadlines/{deadline}/batal-selesai', [DashboardController::class, 'uncomplete'])->name('deadlines.uncomplete');

    // Integrasi Moodle via iCal (FR-02, FR-03, FR-06, US-2).
    Route::get('/moodle', [MoodleLinkController::class, 'show'])->name('moodle.show');
    Route::post('/moodle', [MoodleLinkController::class, 'store'])->name('moodle.store');
    Route::post('/moodle/sync', [MoodleLinkController::class, 'sync'])->name('moodle.sync');
    Route::delete('/moodle', [MoodleLinkController::class, 'destroy'])->name('moodle.destroy');

    // Preferensi pengingat email/Telegram (FR-06, BL-10).
    Route::get('/pengaturan/pengingat', [ReminderPreferenceController::class, 'edit'])->name('reminders.edit');
    Route::put('/pengaturan/pengingat', [ReminderPreferenceController::class, 'update'])->name('reminders.update');

    // Rekap agregat dosen (FR-07, US-6) — mahasiswa menerima 403.
    Route::get('/dosen', DosenDashboardController::class)
        ->middleware('role:dosen,admin')
        ->name('dosen.index');

    // Panel admin (FR-08, US-7) — non-admin menerima 403.
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pengguna', [UserAdminController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserAdminController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserAdminController::class, 'update'])->name('users.update');
        Route::delete('/pengguna/{user}', [UserAdminController::class, 'destroy'])->name('users.destroy');
        Route::get('/log', [LogController::class, 'index'])->name('logs.index');
    });
});
