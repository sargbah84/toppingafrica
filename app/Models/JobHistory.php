<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobHistory extends Model
{
    protected $fillable = [
        'job_name',
        'queue',
        'status',
        'duration_ms',
        'exception',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }

    public function pruneOlderThan(int $days = 30): int
    {
        return static::where('finished_at', '<', now()->subDays($days))->delete();
    }
}
