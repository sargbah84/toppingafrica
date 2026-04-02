<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PostsList extends Component
{
    use WithPagination;

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortField = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    /** @var array<int> */
    public array $selectedPosts = [];

    public bool $selectAll = false;

    public string $bulkAction = '';

    public int $perPage = 15;

    protected array $allowedSortFields = [
        'created_at',
        'title',
        'views_count',
        'published_at',
    ];

    protected array $allowedStatuses = [
        'all',
        'published',
        'draft',
        'scheduled',
        'trashed',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->reset('selectedPosts', 'selectAll');
    }

    public function updatedSelectAll(bool $value): void
    {
        if ($value) {
            $this->selectedPosts = $this->posts->pluck('id')->toArray();
        } else {
            $this->selectedPosts = [];
        }
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, $this->allowedSortFields, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }

        $this->resetPage();
    }

    public function filterByStatus(string $status): void
    {
        if (! in_array($status, $this->allowedStatuses, true)) {
            return;
        }

        $this->statusFilter = $status;
        $this->resetPage();
        $this->reset('selectedPosts', 'selectAll');
    }

    public function applyBulkAction(): void
    {
        if (empty($this->selectedPosts) || empty($this->bulkAction)) {
            return;
        }

        match ($this->bulkAction) {
            'delete' => $this->bulkDelete(),
            'publish' => $this->bulkPublish(),
            'unpublish' => $this->bulkUnpublish(),
            default => null,
        };

        $this->reset('selectedPosts', 'selectAll', 'bulkAction');
    }

    public function deletePost(int $postId): void
    {
        $post = Post::findOrFail($postId);
        $post->delete();

        $this->dispatch('notify', type: 'success', message: 'Post moved to trash.');
    }

    public function restorePost(int $postId): void
    {
        Post::withTrashed()->findOrFail($postId)->restore();

        $this->dispatch('notify', type: 'success', message: 'Post restored.');
    }

    public function forceDeletePost(int $postId): void
    {
        Post::withTrashed()->findOrFail($postId)->forceDelete();

        $this->dispatch('notify', type: 'success', message: 'Post permanently deleted.');
    }

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return $this->buildQuery()->paginate($this->perPage);
    }

    #[Computed]
    public function statusCounts(): array
    {
        return [
            'all' => Post::count(),
            'published' => Post::where('status', 'published')->count(),
            'draft' => Post::where('status', 'draft')->count(),
            'scheduled' => Post::where('status', 'scheduled')->count(),
            'trashed' => Post::onlyTrashed()->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.blog.posts-list');
    }

    protected function buildQuery(): Builder
    {
        $query = Post::query()->with(['author', 'categories']);

        if ($this->statusFilter === 'trashed') {
            $query->onlyTrashed();
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where('title', 'like', '%' . $this->search . '%');
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        return $query;
    }

    protected function bulkDelete(): void
    {
        Post::whereIn('id', $this->selectedPosts)->delete();

        $this->dispatch('notify', type: 'success', message: count($this->selectedPosts) . ' posts moved to trash.');
    }

    protected function bulkPublish(): void
    {
        Post::whereIn('id', $this->selectedPosts)->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->dispatch('notify', type: 'success', message: count($this->selectedPosts) . ' posts published.');
    }

    protected function bulkUnpublish(): void
    {
        Post::whereIn('id', $this->selectedPosts)->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        $this->dispatch('notify', type: 'success', message: count($this->selectedPosts) . ' posts unpublished.');
    }
}
