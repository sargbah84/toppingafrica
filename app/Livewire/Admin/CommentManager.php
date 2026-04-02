<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CommentManager extends Component
{
    use WithPagination;

    #[Url]
    public string $status = 'all';

    #[Url]
    public string $search = '';

    public array $selected = [];
    public bool $selectAll = false;

    public function updatedStatus(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selected = $this->comments->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function approve(int $commentId): void
    {
        Comment::findOrFail($commentId)->update(['status' => 'approved']);
        $this->dispatch('notify', type: 'success', message: 'Comment approved.');
    }

    public function markSpam(int $commentId): void
    {
        Comment::findOrFail($commentId)->update(['status' => 'spam']);
        $this->dispatch('notify', type: 'success', message: 'Comment marked as spam.');
    }

    public function deleteComment(int $commentId): void
    {
        Comment::findOrFail($commentId)->delete();
        $this->dispatch('notify', type: 'success', message: 'Comment deleted.');
    }

    public function bulkApprove(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Comment::whereIn('id', $this->selected)->update(['status' => 'approved']);
        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->dispatch('notify', type: 'success', message: "{$count} comments approved.");
    }

    public function bulkSpam(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Comment::whereIn('id', $this->selected)->update(['status' => 'spam']);
        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->dispatch('notify', type: 'success', message: "{$count} comments marked as spam.");
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }

        Comment::whereIn('id', $this->selected)->delete();
        $count = count($this->selected);
        $this->selected = [];
        $this->selectAll = false;
        $this->dispatch('notify', type: 'success', message: "{$count} comments deleted.");
    }

    #[Computed]
    public function comments(): LengthAwarePaginator
    {
        return Comment::query()
            ->with(['post:id,title,slug', 'user:id,name,email'])
            ->when($this->status !== 'all', function ($query): void {
                $query->where('status', $this->status);
            })
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($q): void {
                    $q->where('body', 'like', "%{$this->search}%")
                      ->orWhere('guest_name', 'like', "%{$this->search}%")
                      ->orWhere('guest_email', 'like', "%{$this->search}%")
                      ->orWhereHas('user', function ($uq): void {
                          $uq->where('name', 'like', "%{$this->search}%");
                      });
                });
            })
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function statusCounts(): array
    {
        return [
            'all' => Comment::count(),
            'pending' => Comment::where('status', 'pending')->count(),
            'approved' => Comment::where('status', 'approved')->count(),
            'spam' => Comment::where('status', 'spam')->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.comment-manager');
    }
}
