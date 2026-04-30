<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Models\Bookmark;
use Illuminate\View\View;
use Livewire\Component;

class BookmarkButton extends Component
{
    public int $postId;

    public bool $isBookmarked = false;

    public function mount(int $postId): void
    {
        $this->postId = $postId;

        if (auth()->check()) {
            $this->isBookmarked = Bookmark::where('user_id', auth()->id())
                ->where('post_id', $postId)
                ->exists();
        }
    }

    public function toggle(): void
    {
        if (! auth()->check()) {
            $this->dispatch('open-login-prompt');

            return;
        }

        $this->isBookmarked = Bookmark::toggle(auth()->id(), $this->postId);
    }

    public function render(): View
    {
        return view('livewire.blog.bookmark-button');
    }
}
