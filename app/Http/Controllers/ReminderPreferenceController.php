<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Preferensi pengingat (FR-06, BL-10): kanal (email/Telegram), waktu
 * pengingat (H-1 dan/atau 3 jam), aktif/nonaktif, dan Telegram chat ID.
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
        $validated = $request->validate([
            'telegram_chat_id' => ['nullable', 'string', 'max:64', 'regex:/^-?\d+$/'],
        ], [
            'telegram_chat_id.regex' => 'Telegram Chat ID harus berupa angka (boleh diawali tanda minus).',
        ]);

        $request->user()->update([
            'email_reminder_enabled' => $request->boolean('email_reminder_enabled'),
            'telegram_reminder_enabled' => $request->boolean('telegram_reminder_enabled'),
            'remind_h1' => $request->boolean('remind_h1'),
            'remind_h3' => $request->boolean('remind_h3'),
            'telegram_chat_id' => $validated['telegram_chat_id'] ?? null,
        ]);

        return back()->with('success', 'Preferensi pengingat tersimpan.');
    }
}
