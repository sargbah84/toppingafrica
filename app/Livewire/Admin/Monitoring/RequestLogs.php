<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Monitoring;

use App\Models\RequestLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class RequestLogs extends Component
{
    use WithPagination;

    #[Url]
    public string $statusFilter = '';

    #[Url]
    public string $methodFilter = '';

    #[Url]
    public string $search = '';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function clearLogs(): void
    {
        RequestLog::query()->delete();
        session()->flash('message', 'All request logs cleared.');
    }

    public function pruneOldLogs(): void
    {
        $count = RequestLog::pruneOlderThan(30);
        session()->flash('message', "Pruned {$count} logs older than 30 days.");
    }

    public function getLogs(): LengthAwarePaginator
    {
        return RequestLog::query()
            ->with('user:id,name')
            ->when($this->statusFilter, function ($q) {
                if ($this->statusFilter === 'errors') {
                    $q->where('status_code', '>=', 400);
                } elseif ($this->statusFilter === '4xx') {
                    $q->whereBetween('status_code', [400, 499]);
                } elseif ($this->statusFilter === '5xx') {
                    $q->where('status_code', '>=', 500);
                } elseif ($this->statusFilter === 'success') {
                    $q->where('status_code', '<', 400);
                }
            })
            ->when($this->methodFilter, fn ($q) => $q->where('method', $this->methodFilter))
            ->when($this->search, fn ($q) => $q->where('url', 'like', "%{$this->search}%"))
            ->latest('created_at')
            ->paginate(30);
    }

    public function getStats(): array
    {
        $total = RequestLog::count();
        $errors = RequestLog::where('status_code', '>=', 400)->count();
        $avgTime = (int) round((float) RequestLog::avg('response_time_ms'));
        $topErrors = RequestLog::where('status_code', '>=', 400)
            ->select('status_code', 'url', DB::raw('COUNT(*) as count'))
            ->groupBy('status_code', 'url')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return [
            'total' => $total,
            'errors' => $errors,
            'error_rate' => $total > 0 ? round(($errors / $total) * 100, 1) : 0,
            'avg_time' => $avgTime,
            'top_errors' => $topErrors,
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.monitoring.request-logs', [
            'logs' => $this->getLogs(),
            'stats' => $this->getStats(),
        ])->title('Request Logs');
    }
}
