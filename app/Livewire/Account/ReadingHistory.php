<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\PostView;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class ReadingHistory extends Component
{
    public int $perPage = 10;

    public int $page = 1;

    public function loadMore(): void
    {
        $this->page++;
    }

    public function getHistoryProperty(): Collection
    {
        return PostView::where('user_id', auth()->id())
            ->with(['post' => function ($query) {
                $query->select('id', 'title', 'slug', 'featured_image', 'excerpt', 'reading_time', 'published_at', 'author_id')
                    ->with('author:id,name', 'categories:id,name,slug');
            }])
            ->latest('viewed_at')
            ->take($this->page * $this->perPage)
            ->get()
            ->filter(fn ($view) => $view->post !== null)
            ->unique('post_id');
    }

    public function getTotalCountProperty(): int
    {
        return PostView::where('user_id', auth()->id())
            ->distinct('post_id')
            ->count('post_id');
    }

    public function getHasMoreProperty(): bool
    {
        return $this->totalCount > $this->history->count();
    }

    public function render(): View
    {
        return view('livewire.account.reading-history');
    }
}
