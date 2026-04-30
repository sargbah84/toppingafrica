<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Models\Post;
use App\Models\PostReaction;
use Illuminate\View\View;
use Livewire\Component;

class PostReactions extends Component
{
    public int $postId;

    public array $counts = [];

    public ?string $userReaction = null;

    public function mount(int $postId): void
    {
        $this->postId = $postId;
        $this->loadCounts();

        if (auth()->check()) {
            $reaction = PostReaction::where('post_id', $postId)
                ->where('user_id', auth()->id())
                ->first();
            $this->userReaction = $reaction?->type;
        }
    }

    public function react(string $type): void
    {
        if (! auth()->check()) {
            $this->dispatch('open-login-prompt');

            return;
        }

        $post = Post::findOrFail($this->postId);
        $result = PostReaction::toggle($post, auth()->id(), $type, request()->ip());

        match ($result['action']) {
            'removed' => $this->userReaction = null,
            'added', 'changed' => $this->userReaction = $type,
            default => null,
        };

        $this->loadCounts();
    }

    public function loadCounts(): void
    {
        $this->counts = PostReaction::countsForPost($this->postId);
    }

    public function getTotalProperty(): int
    {
        return array_sum($this->counts);
    }

    public function render(): View
    {
        return view('livewire.blog.post-reactions');
    }
}
