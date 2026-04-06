<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Monitoring;

use App\Models\JobHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class JobMonitor extends Component
{
    use WithPagination;

    public string $activeTab = 'overview';

    public bool $autoRefresh = false;

    public function setTab(string $tab): void
    {
        $validTabs = ['overview', 'queued', 'failed', 'history', 'scheduled', 'config'];
        if (in_array($tab, $validTabs, true)) {
            $this->activeTab = $tab;
            $this->resetPage();
        }
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = ! $this->autoRefresh;
    }

    // --- Scheduler & Queue Worker Status ---

    public function getSchedulerStatus(): array
    {
        $heartbeat = Cache::get('scheduler:heartbeat');
        $isRunning = $heartbeat && now()->diffInSeconds(\Carbon\Carbon::parse($heartbeat)) < 120;

        return [
            'running' => $isRunning,
            'last_heartbeat' => $heartbeat ? \Carbon\Carbon::parse($heartbeat)->diffForHumans() : null,
        ];
    }

    public function getQueueWorkerStatus(): array
    {
        $pending = DB::table('jobs')->count();
        // Check if jobs are being processed by looking at recent history
        $recentlyProcessed = JobHistory::where('finished_at', '>=', now()->subMinutes(2))->exists();
        $hasReserved = DB::table('jobs')->whereNotNull('reserved_at')->exists();

        $isRunning = $recentlyProcessed || $hasReserved;

        return [
            'running' => $isRunning,
            'pending' => $pending,
        ];
    }

    public function runScheduler(): void
    {
        Artisan::call('schedule:run');
        session()->flash('message', 'Scheduler executed manually.');
    }

    public function restartQueueWorker(): void
    {
        Artisan::call('queue:restart');
        session()->flash('message', 'Queue restart signal sent. Workers will restart after their current job.');
    }

    public function processScheduledPosts(): void
    {
        $count = \App\Models\Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->update([
                'status' => 'published',
                'published_at' => now(),
                'scheduled_at' => null,
            ]);

        session()->flash('message', "{$count} scheduled post(s) published.");
    }

    // --- Overview Stats ---

    public function getStats(): array
    {
        $today = now()->startOfDay();
        $last24h = now()->subHours(24);

        return [
            'completed_today' => JobHistory::where('status', 'completed')
                ->where('finished_at', '>=', $today)->count(),
            'avg_duration' => (int) JobHistory::where('status', 'completed')
                ->where('finished_at', '>=', $today)
                ->avg('duration_ms'),
            'failed_24h' => JobHistory::where('status', 'failed')
                ->where('finished_at', '>=', $last24h)->count(),
            'pending' => DB::table('jobs')->count(),
        ];
    }

    public function getRecentActivity(): Collection
    {
        return JobHistory::orderByDesc('finished_at')
            ->limit(20)
            ->get();
    }

    // --- Queued Jobs ---

    public function getQueuedJobs(): Collection
    {
        return DB::table('jobs')
            ->orderBy('created_at')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $job->display_name = class_basename($payload['displayName'] ?? 'Unknown');
                $job->attempts_count = $job->attempts;
                $job->created = \Carbon\Carbon::createFromTimestamp($job->created_at);
                $job->available = \Carbon\Carbon::createFromTimestamp($job->available_at);
                $job->is_reserved = $job->reserved_at !== null;

                return $job;
            });
    }

    // --- Failed Jobs ---

    public function getFailedJobs(): Collection
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $job->display_name = class_basename($payload['displayName'] ?? 'Unknown');
                $job->short_exception = \Illuminate\Support\Str::limit($job->exception, 200);
                $job->failed_date = \Carbon\Carbon::parse($job->failed_at);

                return $job;
            });
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

    // --- Job History ---

    public function getJobHistory(): Collection
    {
        return JobHistory::orderByDesc('finished_at')
            ->limit(100)
            ->get();
    }

    public function clearJobHistory(): void
    {
        JobHistory::truncate();
        session()->flash('message', 'Job history cleared.');
    }

    // --- Scheduled Tasks ---

    public function getScheduledTasks(): array
    {
        Artisan::call('schedule:list');
        $result = Artisan::output();

        $tasks = [];
        foreach (explode("\n", trim($result)) as $line) {
            $line = trim($line);
            if ($line && ! str_starts_with($line, 'INFO')) {
                $tasks[] = $line;
            }
        }

        return $tasks;
    }

    // --- Schedule Config ---

    public function getScheduleConfig(): array
    {
        return [
            'queue_driver' => config('queue.default'),
            'queue_connection' => config('queue.connections.' . config('queue.default') . '.driver', 'N/A'),
            'failed_driver' => config('queue.failed.driver', 'database-uuids'),
            'retry_after' => config('queue.connections.' . config('queue.default') . '.retry_after', 90),
            'max_tries' => config('queue.connections.' . config('queue.default') . '.max_tries', 'Not set'),
            'cache_driver' => config('cache.default'),
            'scheduler_timezone' => config('app.timezone'),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.monitoring.job-monitor', [
            'schedulerStatus' => $this->getSchedulerStatus(),
            'queueStatus' => $this->getQueueWorkerStatus(),
            'stats' => $this->getStats(),
            'recentActivity' => $this->activeTab === 'overview' ? $this->getRecentActivity() : collect(),
            'queuedJobs' => $this->activeTab === 'queued' ? $this->getQueuedJobs() : collect(),
            'failedJobs' => $this->activeTab === 'failed' ? $this->getFailedJobs() : collect(),
            'jobHistory' => $this->activeTab === 'history' ? $this->getJobHistory() : collect(),
            'scheduledTasks' => $this->activeTab === 'scheduled' ? $this->getScheduledTasks() : [],
            'scheduleConfig' => $this->activeTab === 'config' ? $this->getScheduleConfig() : [],
            'failedCount' => DB::table('failed_jobs')->count(),
        ])->title('Job Monitor');
    }
}
