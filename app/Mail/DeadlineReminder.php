<?php

namespace App\Mail;

use App\Models\Deadline;
use App\Models\ReminderLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email pengingat tenggat (US-4). Dikirim sebagai email teks polos agar
 * tampil baik di semua klien email, termasuk webmail shared hosting.
 */
class DeadlineReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Deadline $deadline,
        public readonly string $reminderType,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->reminderType === ReminderLog::TYPE_H1
            ? 'besok (H-1)'
            : '3 jam dari sekarang';

        return new Envelope(
            subject: '[Pengingat] '.$this->deadline->title.' — tenggat '.$label,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.deadline-reminder',
            with: [
                'deadline' => $this->deadline,
                'label' => $this->reminderType === ReminderLog::TYPE_H1
                    ? '24 jam (H-1)'
                    : '3 jam',
            ],
        );
    }
}
