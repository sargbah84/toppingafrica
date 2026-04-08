<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\JobHistory;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Cache;

class LogJobHistory
{
    public function handleProcessing(JobProcessing $event): void
    {
        $jobId = $event->job->getJobId();
        Cache::put("job_started:{$jobId}", now()->toIso8601String(), 3600);
    }

    public function handleProcessed(JobProcessed $event): void
    {
        try {
            $jobId = $event->job->getJobId();
            $startedAt = Cache::pull("job_started:{$jobId}");
            $now = now();

            JobHistory::create([
                'job_name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue() ?? 'default',
                'status' => 'completed',
                'duration_ms' => $this->computeDurationMs($startedAt, $now),
                'started_at' => $startedAt ? \Carbon\Carbon::parse($startedAt) : $now,
                'finished_at' => $now,
            ]);
        } catch (\Throwable $e) {
            // Never let monitoring crash the worker.
            \Log::warning('LogJobHistory.handleProcessed failed: ' . $e->getMessage());
        }
    }

    public function handleFailed(JobFailed $event): void
    {
        try {
            $jobId = $event->job->getJobId();
            $startedAt = Cache::pull("job_started:{$jobId}");
            $now = now();

            JobHistory::create([
                'job_name' => $event->job->resolveName(),
                'queue' => $event->job->getQueue() ?? 'default',
                'status' => 'failed',
                'duration_ms' => $this->computeDurationMs($startedAt, $now),
                'exception' => $event->exception?->getMessage(),
                'started_at' => $startedAt ? \Carbon\Carbon::parse($startedAt) : $now,
                'finished_at' => $now,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('LogJobHistory.handleFailed failed: ' . $e->getMessage());
        }
    }

    /**
     * Compute a non-negative duration in milliseconds.
     *
     * Carbon 3's diffInMilliseconds() is signed by default, so calling
     * $now->diffInMilliseconds($earlier) returns a negative value. We use
     * abs() as a belt-and-braces guard against that and any clock skew.
     */
    private function computeDurationMs(?string $startedAt, \Carbon\Carbon $now): ?int
    {
        if (! $startedAt) {
            return null;
        }

        return (int) abs($now->diffInMilliseconds(\Carbon\Carbon::parse($startedAt)));
    }
}
