<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderLog extends Model
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_TELEGRAM = 'telegram';

    public const TYPE_H1 = 'h1';

    public const TYPE_H3 = 'h3';

    protected $fillable = [
        'deadline_id',
        'user_id',
        'channel',
        'reminder_type',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
