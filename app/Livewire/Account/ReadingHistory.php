<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\Creator;
use App\Models\Post;
use App\Models\View as ViewRecord;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

/**
 * "Recently Viewed" — formerly "Reading History".
 *
 * Now polymorphic: shows both blog posts a user has read and creator
 * profiles they've visited, ordered by most recent. The Blade template
 * branches on $view->viewable_type to render the right card per row.
 *
 * Deduplication is by (viewable_type, viewable_id) so visiting the same
 * creator profile 5 times only shows up once in the history.
 */
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
        return ViewRecord::query()
            ->where('user_id', auth()->id())
            ->with([
                'viewable' => function ($morphTo) {
                    // Eager-load fields differently per morph target type.
                    // Posts get the full reading-card payload; creators get
                    // their dashboard-card payload (image, category, country).
                    $morphTo->morphWith([
                        Post::class => ['author:id,name', 'categories:id,name,slug'],
                        Creator::class => ['socialLinks'],
                    ]);
                },
            ])
            ->latest('viewed_at')
            ->take($this->page * $this->perPage * 2) // fetch extra to absorb dedup
            ->get()
            // Drop orphans (the related model has been deleted since the view was recorded).
            ->filter(fn ($view) => $view->viewable !== null)
            // Dedupe by composite key — visiting the same page multiple times shows once.
            ->unique(fn ($view) => $view->viewable_type . ':' . $view->viewable_id)
            ->take($this->page * $this->perPage)
            ->values();
    }

    public function getTotalCountProperty(): int
    {
        return ViewRecord::query()
            ->where('user_id', auth()->id())
            ->select('viewable_type', 'viewable_id')
            ->distinct()
            ->get()
            ->count();
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
