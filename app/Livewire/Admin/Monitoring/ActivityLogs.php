<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Monitoring;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.admin')]
class ActivityLogs extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $logName = '';

    #[Url]
    public string $event = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLogName(): void
    {
        $this->resetPage();
    }

    public function updatedEvent(): void
    {
        $this->resetPage();
    }

    public function clearLogs(): void
    {
        Activity::query()->delete();
        session()->flash('message', 'All activity logs cleared.');
    }

    public function getLogs(): LengthAwarePaginator
    {
        return Activity::query()
            ->with('causer')
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('description', 'like', "%{$this->search}%")
                        ->orWhereHas('causer', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->logName, fn ($q) => $q->where('log_name', $this->logName))
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->latest()
            ->paginate(25);
    }

    public function render(): View
    {
        $logNames = Activity::distinct()->pluck('log_name')->filter()->values();
        $events = Activity::distinct()->pluck('event')->filter()->values();

        return view('livewire.admin.monitoring.activity-logs', [
            'logs' => $this->getLogs(),
            'logNames' => $logNames,
            'events' => $events,
        ])->title('Activity Logs');
    }
}
