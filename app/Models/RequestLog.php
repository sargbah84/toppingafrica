<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'method',
        'url',
        'status_code',
        'ip_address',
        'user_agent',
        'response_time_ms',
        'error_message',
        'exception',
        'response_body',
        'route_name',
        'created_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isError(): bool
    {
        return $this->status_code >= 400;
    }

    public static function pruneOlderThan(int $days = 30): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
