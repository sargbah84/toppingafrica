<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Models\Post;
use App\Models\Reaction;
use Illuminate\View\View;
use Livewire\Component;

class PostReactions extends Component
{
    public Post $post;

    public ?string $userReaction = null;

    public array $counts = [];

    public int $totalReactions = 0;

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->loadCounts();

        if (auth()->check()) {
            $reaction = Reaction::where('post_id', $post->id)
                ->where('user_id', auth()->id())
                ->first();

            $this->userReaction = $reaction?->type;
        }
    }

    public function react(string $type): void
    {
        if (! auth()->check()) {
            return;
        }

        $result = Reaction::toggle(
            $this->post,
            auth()->id(),
            $type,
            request()->ip()
        );

        $this->userReaction = $result;
        $this->loadCounts();
    }

    private function loadCounts(): void
    {
        $reactionTypes = array_keys(config('blog.reactions', []));

        $dbCounts = Reaction::where('post_id', $this->post->id)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $this->counts = [];
        foreach ($reactionTypes as $type) {
            $this->counts[$type] = (int) ($dbCounts[$type] ?? 0);
        }

        $this->totalReactions = array_sum($this->counts);
    }

    public function render(): View
    {
        return view('livewire.blog.post-reactions');
    }
}
