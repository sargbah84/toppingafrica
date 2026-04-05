<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Monitoring;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class JobMonitor extends Component
{
    use WithPagination;

    public string $activeSection = 'overview';

    public function setSection(string $section): void
    {
        if (in_array($section, ['overview', 'pending', 'failed', 'batches'], true)) {
            $this->activeSection = $section;
            $this->resetPage();
        }
    }

    public function retryFailedJob(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        session()->flash('message', "Job {$uuid} queued for retry.");
    }

    public function retryAllFailed(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        session()->flash('message', 'All failed jobs queued for retry.');
    }

    public function deleteFailedJob(string $uuid): void
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();
        session()->flash('message', 'Failed job deleted.');
    }

    public function flushFailedJobs(): void
    {
        Artisan::call('queue:flush');
        session()->flash('message', 'All failed jobs cleared.');
    }

    public function getOverview(): array
    {
        return [
            'pending' => DB::table('jobs')->count(),
            'failed' => DB::table('failed_jobs')->count(),
            'batches' => DB::table('job_batches')->count(),
            'batches_pending' => DB::table('job_batches')->where('pending_jobs', '>', 0)->count(),
        ];
    }

    public function getPendingJobs(): Collection
    {
        return DB::table('jobs')
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $job->display_name = $payload['displayName'] ?? 'Unknown';
                $job->attempts_count = $job->attempts;
                $job->created = \Carbon\Carbon::createFromTimestamp($job->created_at);
                $job->available = \Carbon\Carbon::createFromTimestamp($job->available_at);

                return $job;
            });
    }

    public function getFailedJobs(): Collection
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $job->display_name = $payload['displayName'] ?? 'Unknown';
                $job->short_exception = \Illuminate\Support\Str::limit($job->exception, 200);
                $job->failed_date = \Carbon\Carbon::parse($job->failed_at);

                return $job;
            });
    }

    public function getBatches(): Collection
    {
        return DB::table('job_batches')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($batch) {
                $batch->created = \Carbon\Carbon::createFromTimestamp($batch->created_at);
                $batch->progress = $batch->total_jobs > 0
                    ? round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100, 1)
                    : 0;

                return $batch;
            });
    }

    public function getScheduledTasks(): array
    {
        // Get scheduled tasks from the kernel
        $output = Artisan::call('schedule:list');
        $result = Artisan::output();

        $tasks = [];
        foreach (explode("\n", trim($result)) as $line) {
            $line = trim($line);
            if ($line && !str_starts_with($line, 'INFO')) {
                $tasks[] = $line;
            }
        }

        return $tasks;
    }

    public function render(): View
    {
        return view('livewire.admin.monitoring.job-monitor', [
            'overview' => $this->getOverview(),
            'pendingJobs' => $this->activeSection === 'pending' ? $this->getPendingJobs() : collect(),
            'failedJobs' => $this->activeSection === 'failed' ? $this->getFailedJobs() : collect(),
            'batches' => $this->activeSection === 'batches' ? $this->getBatches() : collect(),
            'scheduledTasks' => $this->getScheduledTasks(),
        ])->title('Job Monitor');
    }
}
