<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Models\PollVote;
use App\Models\Post;
use Illuminate\View\View;
use Livewire\Component;

class PollWidget extends Component
{
    public Post $post;
    public ?int $selectedOption = null;
    public bool $hasVoted = false;
    public array $results = [];
    public int $totalVotes = 0;

    public function mount(Post $post): void
    {
        $this->post = $post;

        if (auth()->check()) {
            $vote = PollVote::where('post_id', $post->id)
                ->where('user_id', auth()->id())
                ->first();

            if ($vote) {
                $this->hasVoted = true;
                $this->selectedOption = $vote->option_index;
                $this->loadResults();
            }
        }

        if ($this->isExpired() || ($post->type_data['show_results_before_vote'] ?? false)) {
            $this->loadResults();
        }
    }

    public function vote(): void
    {
        if (!auth()->check()) {
            return;
        }

        if ($this->selectedOption === null || $this->hasVoted || $this->isExpired()) {
            return;
        }

        $success = PollVote::castVote(
            $this->post,
            auth()->id(),
            $this->selectedOption,
            request()->ip()
        );

        if ($success) {
            $this->hasVoted = true;
            $this->loadResults();
        }
    }

    public function isExpired(): bool
    {
        $endsAt = $this->post->type_data['ends_at'] ?? null;

        return $endsAt && now()->greaterThan($endsAt);
    }

    public function loadResults(): void
    {
        $options = $this->post->type_data['options'] ?? [];
        $voteCounts = PollVote::where('post_id', $this->post->id)
            ->selectRaw('option_index, count(*) as votes')
            ->groupBy('option_index')
            ->pluck('votes', 'option_index')
            ->toArray();

        $this->totalVotes = array_sum($voteCounts);

        $this->results = [];
        foreach ($options as $index => $option) {
            $votes = $voteCounts[$index] ?? 0;
            $this->results[] = [
                'text' => $option['text'],
                'votes' => $votes,
                'percentage' => $this->totalVotes > 0 ? round(($votes / $this->totalVotes) * 100) : 0,
            ];
        }
    }

    public function render(): View
    {
        return view('livewire.blog.poll-widget');
    }
}
