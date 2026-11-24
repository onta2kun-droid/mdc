<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Preferensi pengingat (FR-06, BL-10): kanal (email/Telegram), waktu
 * pengingat (H-1 dan/atau 3 jam), aktif/nonaktif, dan Telegram chat ID.
 *
 * Halaman settings/reminders.blade.php memuat DUA form terpisah yang sama-sama
 * mengirim PUT ke route reminders.update:
 *   (1) form "Simpan Preferensi" — hanya checkbox kanal/waktu;
 *   (2) form "Simpan Chat ID"    — hanya input telegram_chat_id.
 *
 * Handler membedakan form lewat keberadaan key "telegram_chat_id" pada request
 * ($request->exists(), BUKAN has(): nilai kosong diubah middleware
 * ConvertEmptyStringsToNull menjadi null sehingga has() bernilai false).
 * Hanya field milik form yang dikirim yang diperbarui — tanpa ini, satu
 * handler yang menimpa kelima field sekaligus membuat kedua form saling
 * menghapus data (checkbox tak terkirim -> false; chat id tak terkirim -> null).
 */
class ReminderPreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.reminders', [
            'user' => $request->user(),
            'telegramReady' => app(TelegramService::class)->configured(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Form (2): "Simpan Chat ID" — key telegram_chat_id hanya ada di form ini.
        // Kiriman kosong (string kosong -> null oleh middleware) berarti hapus
        // Chat ID; flag kanal/waktu TIDAK disentuh.
        if ($request->exists('telegram_chat_id')) {
            $validated = $request->validate([
                'telegram_chat_id' => ['nullable', 'string', 'max:64', 'regex:/^-?\d+$/'],
            ], [
                'telegram_chat_id.regex' => 'Telegram Chat ID harus berupa angka (boleh diawali tanda minus).',
            ]);

            $chatId = trim((string) ($validated['telegram_chat_id'] ?? ''));

            $request->user()->update([
                'telegram_chat_id' => $chatId === '' ? null : $chatId,
            ]);

            return back()->with('success', 'Telegram Chat ID tersimpan.');
        }

        // Form (1): "Simpan Preferensi" — checkbox yang tidak terkirim berarti
        // tidak dicentang (false). telegram_chat_id TIDAK disentuh agar nilai
        // yang sudah tersimpan tidak ikut terhapus.
        $request->user()->update([
            'email_reminder_enabled' => $request->boolean('email_reminder_enabled'),
            'telegram_reminder_enabled' => $request->boolean('telegram_reminder_enabled'),
            'remind_h1' => $request->boolean('remind_h1'),
            'remind_h3' => $request->boolean('remind_h3'),
        ]);

        return back()->with('success', 'Preferensi pengingat tersimpan.');
    }
}
