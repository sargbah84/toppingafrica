<?php

declare(strict_types=1);

namespace App\Livewire\Account;

use App\Models\Comment;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class MyComments extends Component
{
    public int $perPage = 10;
    public int $page = 1;

    public ?int $editingId = null;
    public string $editBody = '';

    public function loadMore(): void
    {
        $this->page++;
    }

    public function startEditing(int $commentId): void
    {
        $comment = Comment::where('id', $commentId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->editingId = $commentId;
        $this->editBody = $comment->body;
    }

    public function cancelEditing(): void
    {
        $this->editingId = null;
        $this->editBody = '';
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editBody' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $comment = Comment::where('id', $this->editingId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $comment->update(['body' => $this->editBody]);

        $this->editingId = null;
        $this->editBody = '';
    }

    public function getCommentsProperty(): Collection
    {
        return Comment::where('user_id', auth()->id())
            ->with(['post' => function ($query) {
                $query->select('id', 'title', 'slug');
            }])
            ->latest()
            ->take($this->page * $this->perPage)
            ->get();
    }

    public function getTotalCountProperty(): int
    {
        return Comment::where('user_id', auth()->id())->count();
    }

    public function getHasMoreProperty(): bool
    {
        return $this->totalCount > ($this->page * $this->perPage);
    }

    public function render(): View
    {
        return view('livewire.account.my-comments');
    }
}
