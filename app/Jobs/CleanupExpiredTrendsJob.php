<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Trend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupExpiredTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(): void
    {
        $deactivated = Trend::where('is_active', true)
            ->where('expires_at', '<', now())
            ->update(['is_active' => false]);

        $deleted = Trend::where('is_active', false)
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        Log::info('CleanupExpiredTrendsJob: Completed', [
            'deactivated' => $deactivated,
            'deleted' => $deleted,
        ]);
    }
}
