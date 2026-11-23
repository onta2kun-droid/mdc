<?php

namespace App\Services;

use App\Mail\DeadlineReminder;
use App\Models\Deadline;
use App\Models\ReminderLog;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Scheduler pengingat otomatis (FR-05, US-4, BL-07).
 *
 * Aturan:
 *  - hanya tugas yang BELUM selesai dan belum lewat tenggat;
 *  - H-1 = 24 jam sebelum tenggat, H-3 = 3 jam sebelum tenggat;
 *  - idempoten: satu pengingat per (deadline, kanal, tipe) berkat
 *    constraint unik pada tabel reminder_logs — tidak ada duplikat;
 *  - tugas yang sudah ditandai selesai tidak pernah memicu pengingat;
 *  - kegagalan kirim tidak membuat baris log → dicoba ulang pada eksekusi
 *    berikutnya (kegagalan terekam di log aplikasi).
 */
class ReminderService
{
    public function __construct(
        private readonly TelegramService $telegram,
    ) {}

    /**
     * Periksa dan kirim seluruh pengingat yang jatuh tempo.
     *
     * @return array{sent: int}
     */
    public function dispatchDue(): array
    {
        $sent = 0;

        $h1Limit = now()->addHours((int) config('mdc.h1_hours'));
        $h3Limit = now()->addHours((int) config('mdc.h3_hours'));

        $candidates = Deadline::with('user')
            ->belumSelesai()
            ->where('starts_at', '>', now())
            ->where(function ($q) use ($h1Limit, $h3Limit) {
                $q->where('starts_at', '<=', $h1Limit)->orWhere('starts_at', '<=', $h3Limit);
            })
            ->orderBy('starts_at')
            ->get();

        foreach ($candidates as $deadline) {
            /** @var User $user */
            $user = $deadline->user;

            $types = [];
            if ($deadline->starts_at->lte($h1Limit) && $user->remind_h1) {
                $types[] = ReminderLog::TYPE_H1;
            }
            if ($deadline->starts_at->lte($h3Limit) && $user->remind_h3) {
                $types[] = ReminderLog::TYPE_H3;
            }

            foreach ($types as $type) {
                if ($user->email_reminder_enabled && filled($user->email)) {
                    $sent += $this->sendOnce($deadline, ReminderLog::CHANNEL_EMAIL, $type);
                }

                if ($user->telegram_reminder_enabled
                    && filled($user->telegram_chat_id)
                    && $this->telegram->configured()) {
                    $sent += $this->sendOnce($deadline, ReminderLog::CHANNEL_TELEGRAM, $type);
                }
            }
        }

        return ['sent' => $sent];
    }

    private function sendOnce(Deadline $deadline, string $channel, string $type): int
    {
        // Kunci idempotensi: baris log dibuat ATOMIK lebih dahulu. Jika
        // constraint unik melarangnya, pengingat ini sudah pernah terkirim.
        try {
            $log = ReminderLog::create([
                'deadline_id' => $deadline->id,
                'user_id' => $deadline->user_id,
                'channel' => $channel,
                'reminder_type' => $type,
                'sent_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return 0; // sudah pernah dikirim — lewati (tidak ada duplikat)
        }

        $delivered = $channel === ReminderLog::CHANNEL_EMAIL
            ? $this->sendEmail($deadline, $type)
            : $this->telegram->sendMessage(
                $deadline->user->telegram_chat_id,
                $this->composeMessage($deadline, $type),
            );

        if (! $delivered) {
            // Gagal kirim: hapus baris agar dicoba ulang pada eksekusi berikutnya.
            $log->delete();
            Log::warning('Pengiriman pengingat gagal', [
                'deadline_id' => $deadline->id,
                'channel' => $channel,
                'type' => $type,
            ]);

            return 0;
        }

        return 1;
    }

    private function sendEmail(Deadline $deadline, string $type): bool
    {
        try {
            Mail::to($deadline->user->email)->send(new DeadlineReminder($deadline, $type));
        } catch (\Throwable $e) {
            Log::warning('Pengiriman email pengingat gagal', ['deadline_id' => $deadline->id]);

            return false;
        }

        return true;
    }

    public function composeMessage(Deadline $deadline, string $type): string
    {
        $label = $type === ReminderLog::TYPE_H1 ? 'H-1 (24 jam)' : '3 jam';
        $due = $deadline->starts_at->timezone(config('app.timezone', 'UTC'))->translatedFormat('d M Y, H:i');

        $lines = [
            '<b>Pengingat '.$label.' sebelum tenggat</b>',
            '',
            'Mata kuliah: '.e($deadline->course),
            $deadline->type.' — <b>'.e($deadline->title).'</b>',
            'Tenggat: '.$due,
        ];

        if ($deadline->url) {
            $lines[] = '';
            $lines[] = 'Buka di Moodle: '.e($deadline->url);
        }

        $lines[] = '';
        $lines[] = '— '.config('app.name');

        return implode("\n", $lines);
    }
}
