<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboard;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostView;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StatsOverview extends Component
{
    public string $viewsPeriod = 'week';

    #[Computed]
    public function postCounts(): array
    {
        return [
            'total' => Post::count(),
            'published' => Post::where('status', 'published')->count(),
            'draft' => Post::where('status', 'draft')->count(),
            'scheduled' => Post::where('status', 'scheduled')->count(),
        ];
    }

    #[Computed]
    public function viewStats(): array
    {
        return [
            'today' => PostView::whereDate('created_at', today())->count(),
            'week' => PostView::where('created_at', '>=', now()->subWeek())->count(),
            'month' => PostView::where('created_at', '>=', now()->subMonth())->count(),
            'total' => Post::sum('views_count'),
        ];
    }

    #[Computed]
    public function recentComments(): Collection
    {
        return Comment::query()
            ->with(['post:id,title,slug', 'user:id,name'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function popularPosts(): Collection
    {
        return Post::query()
            ->published()
            ->select(['id', 'title', 'slug', 'views_count', 'published_at'])
            ->orderByDesc('views_count')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function recentPosts(): Collection
    {
        return Post::query()
            ->with('author:id,name')
            ->select(['id', 'title', 'slug', 'status', 'author_id', 'created_at', 'published_at'])
            ->latest()
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function viewsChartData(): array
    {
        $days = match ($this->viewsPeriod) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            default => 7,
        };

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = [
                'date' => $date->format('M d'),
                'views' => PostView::whereDate('created_at', $date->toDateString())->count(),
            ];
        }

        return $data;
    }

    public function setViewsPeriod(string $period): void
    {
        if (in_array($period, ['today', 'week', 'month'], true)) {
            $this->viewsPeriod = $period;
        }
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.stats-overview');
    }
}
