<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    public const STATUS_OK = 'ok';

    public const STATUS_ERROR = 'error';

    public $timestamps = false;

    protected $fillable = ['user_id', 'status', 'deadlines_count', 'message', 'created_at'];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
