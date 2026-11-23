<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoodleLink extends Model
{
    /**
     * URL iCal disimpan terenkripsi di kolom url_encrypted.
     * Aplikasi membaca/menulisnya lewat atribut virtual "url".
     */
    protected $fillable = ['user_id', 'url_encrypted', 'url_fingerprint'];

    protected $appends = ['masked_url'];

    /** Kolom yang tidak boleh keluar dari model (NFR-01: URL berisi token). */
    protected $hidden = ['url_encrypted', 'url_fingerprint'];

    protected function casts(): array
    {
        return [
            'url_encrypted' => 'encrypted',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class);
    }

    /** Atribut virtual untuk membaca URL iCal yang sudah didekripsi. */
    public function getUrlAttribute(): ?string
    {
        return $this->url_encrypted;
    }

    public function setUrlAttribute(?string $value): void
    {
        $this->attributes['url_encrypted'] = $value;
    }

    /**
     * Tampilan aman untuk UI: hanya host + nama file, tanpa query string
     * (tanpa authtoken), mis. "moodle.kampus.ac.id/…/export_execute.php".
     */
    public function getMaskedUrlAttribute(): string
    {
        $host = parse_url($this->attributes['url_fingerprint'] ?? '', PHP_URL_HOST);

        return $host ? $host.' (tersambung)' : '(tersambung)';
    }
}
