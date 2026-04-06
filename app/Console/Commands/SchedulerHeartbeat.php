<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SchedulerHeartbeat extends Command
{
    protected $signature = 'scheduler:heartbeat';

    protected $description = 'Record a heartbeat timestamp for the scheduler';

    public function handle(): void
    {
        Cache::put('scheduler:heartbeat', now()->toIso8601String(), 300); // 5 min TTL
    }
}
