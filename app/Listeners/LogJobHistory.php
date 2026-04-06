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
        $jobId = $event->job->getJobId();
        $startedAt = Cache::pull("job_started:{$jobId}");
        $now = now();

        $durationMs = $startedAt
            ? (int) $now->diffInMilliseconds(\Carbon\Carbon::parse($startedAt))
            : null;

        JobHistory::create([
            'job_name' => $event->job->resolveName(),
            'queue' => $event->job->getQueue() ?? 'default',
            'status' => 'completed',
            'duration_ms' => $durationMs,
            'started_at' => $startedAt ? \Carbon\Carbon::parse($startedAt) : $now,
            'finished_at' => $now,
        ]);
    }

    public function handleFailed(JobFailed $event): void
    {
        $jobId = $event->job->getJobId();
        $startedAt = Cache::pull("job_started:{$jobId}");
        $now = now();

        $durationMs = $startedAt
            ? (int) $now->diffInMilliseconds(\Carbon\Carbon::parse($startedAt))
            : null;

        JobHistory::create([
            'job_name' => $event->job->resolveName(),
            'queue' => $event->job->getQueue() ?? 'default',
            'status' => 'failed',
            'duration_ms' => $durationMs,
            'exception' => $event->exception?->getMessage(),
            'started_at' => $startedAt ? \Carbon\Carbon::parse($startedAt) : $now,
            'finished_at' => $now,
        ]);
    }
}
