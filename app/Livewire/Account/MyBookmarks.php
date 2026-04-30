<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\Bookmark;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class MyBookmarks extends Component
{
    public int $perPage = 10;

    public int $page = 1;

    public function loadMore(): void
    {
        $this->page++;
    }

    public function remove(int $postId): void
    {
        Bookmark::where('user_id', auth()->id())
            ->where('post_id', $postId)
            ->delete();
    }

    public function getBookmarksProperty(): Collection
    {
        return Bookmark::where('user_id', auth()->id())
            ->with(['post' => function ($query) {
                $query->select('id', 'title', 'slug', 'featured_image', 'excerpt', 'reading_time', 'published_at', 'author_id')
                    ->with('author:id,name', 'categories:id,name,slug');
            }])
            ->latest('created_at')
            ->take($this->page * $this->perPage)
            ->get()
            ->filter(fn ($bookmark) => $bookmark->post !== null);
    }

    public function getTotalCountProperty(): int
    {
        return Bookmark::where('user_id', auth()->id())->count();
    }

    public function getHasMoreProperty(): bool
    {
        return $this->totalCount > $this->bookmarks->count();
    }

    public function render(): View
    {
        return view('livewire.account.my-bookmarks');
    }
}
