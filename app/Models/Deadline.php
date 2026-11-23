<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deadline extends Model
{
    public const TYPE_TUGAS = 'tugas';

    public const TYPE_KUIS = 'kuis';

    public const TYPE_LAINNYA = 'lainnya';

    protected $fillable = [
        'user_id',
        'moodle_link_id',
        'uid',
        'course',
        'title',
        'type',
        'starts_at',
        'url',
        'description',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moodleLink(): BelongsTo
    {
        return $this->belongsTo(MoodleLink::class);
    }

    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }

    /* ------------------------------------------------------------------ */
    /* Scope-scope yang dipakai dashboard dan scheduler */
    /* ------------------------------------------------------------------ */

    public function scopeBelumSelesai(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->belumSelesai()->where('starts_at', '<', now());
    }

    public function scopeHariIni(Builder $query): Builder
    {
        return $query->belumSelesai()
            ->whereBetween('starts_at', [today(), today()->endOfDay()]);
    }

    public function scopeMingguIni(Builder $query): Builder
    {
        return $query->belumSelesai()
            ->whereBetween('starts_at', [today()->addDay(), today()->addDays(7)->endOfDay()]);
    }

    public function scopeBerikutnya(Builder $query): Builder
    {
        return $query->belumSelesai()->where('starts_at', '>', today()->addDays(7)->endOfDay());
    }
}
