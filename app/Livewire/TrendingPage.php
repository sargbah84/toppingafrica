<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Trend;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class TrendingPage extends Component
{
    public string $activeCategory = 'All';

    public Collection $trends;

    public ?Carbon $lastUpdated = null;

    public array $categories = ['All', 'Music', 'Business', 'Sports', 'Culture', 'Tech', 'Lifestyle'];

    public function mount(): void
    {
        $this->trends = collect();
        $this->loadTrends();
    }

    public function filterByCategory(string $category): void
    {
        if (! in_array($category, $this->categories, true)) {
            return;
        }

        $this->activeCategory = $category;
    }

    public function getFilteredTrendsProperty(): Collection
    {
        if ($this->activeCategory === 'All') {
            return $this->trends;
        }

        return $this->trends->where('category', $this->activeCategory)->values();
    }

    private function loadTrends(): void
    {
        try {
            $todayTrends = Trend::active()->today()->latest('created_at')->get();

            if ($todayTrends->isNotEmpty()) {
                $this->trends = $todayTrends;
            } else {
                // Fallback to most recent active trends
                $this->trends = Trend::active()->latest('trend_date')->latest('created_at')->limit(12)->get();
            }

            $this->lastUpdated = $this->trends->max('created_at');
        } catch (\Throwable $e) {
            $this->trends = collect();
            $this->lastUpdated = null;
        }
    }

    #[Layout('components.layouts.blog')]
    #[Title('Trending Across Africa Today')]
    public function render(): View
    {
        return view('livewire.trending-page');
    }
}
