<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class PostRepository
{
    public function __construct(
        private readonly Post $model,
    ) {}

    public function find(int $id): ?Post
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug): ?Post
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug): ?Post
    {
        return $this->model->published()->where('slug', $slug)->first();
    }

    public function create(array $data): Post
    {
        return $this->model->create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        return $post->fresh();
    }

    public function delete(Post $post): bool
    {
        return $post->delete();
    }

    public function forceDelete(Post $post): bool
    {
        return $post->forceDelete();
    }

    public function restore(Post $post): bool
    {
        return $post->restore();
    }

    public function getPublished(int $perPage = 12): LengthAwarePaginator
    {
        return $this->model
            ->published()
            ->latest()
            ->with(['author', 'categories', 'tags'])
            ->paginate($perPage);
    }

    public function getFeatured(int $limit = 1): Collection
    {
        return $this->model
            ->published()
            ->featured()
            ->latest()
            ->with(['author', 'categories', 'tags'])
            ->limit($limit)
            ->get();
    }

    public function getByCategory(Category $category, int $perPage = 12): LengthAwarePaginator
    {
        return $this->model
            ->published()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->latest()
            ->with(['author', 'categories', 'tags'])
            ->paginate($perPage);
    }

    public function getByTag(Tag $tag, int $perPage = 12): LengthAwarePaginator
    {
        return $this->model
            ->published()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->latest()
            ->with(['author', 'categories', 'tags'])
            ->paginate($perPage);
    }

    public function getByAuthor(User $author, int $perPage = 12): LengthAwarePaginator
    {
        return $this->model
            ->published()
            ->where('author_id', $author->id)
            ->latest()
            ->with(['author', 'categories', 'tags'])
            ->paginate($perPage);
    }

    public function search(string $term, int $perPage = 12): LengthAwarePaginator
    {
        return $this->model
            ->published()
            ->search($term)
            ->latest()
            ->with(['author', 'categories', 'tags'])
            ->paginate($perPage);
    }

    public function getRelated(Post $post, int $limit = 3): Collection
    {
        $categoryIds = $post->categories->pluck('id');
        $tagIds = $post->tags->pluck('id');

        return $this->model
            ->published()
            ->where('id', '!=', $post->id)
            ->where(function (Builder $query) use ($categoryIds, $tagIds) {
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                })->orWhereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', $tagIds);
                });
            })
            ->latest()
            ->with(['author', 'categories'])
            ->limit($limit)
            ->get();
    }

    public function getPopular(int $limit = 5): Collection
    {
        return $this->model
            ->published()
            ->popular()
            ->with(['author', 'categories'])
            ->limit($limit)
            ->get();
    }

    public function getRecent(int $limit = 5, ?int $excludeId = null): Collection
    {
        return $this->model
            ->published()
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->latest()
            ->with(['author', 'categories', 'latestSeoAnalysis'])
            ->limit($limit)
            ->get();
    }

    public function getScheduledReadyToPublish(): Collection
    {
        return $this->model->readyToPublish()->get();
    }

    public function getAllForAdmin(
        ?string $status = null,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $this->model->with(['author', 'categories']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->search($search);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getTrashedForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->onlyTrashed()
            ->with(['author', 'categories'])
            ->latest()
            ->paginate($perPage);
    }

    public function syncCategories(Post $post, array $categoryIds): void
    {
        $post->categories()->sync($categoryIds);
    }

    public function syncTags(Post $post, array $tagIds): void
    {
        $post->tags()->sync($tagIds);
    }

    public function attachTags(Post $post, array $tagIds): void
    {
        $post->tags()->syncWithoutDetaching($tagIds);
    }

    public function getCountByStatus(): array
    {
        return [
            'draft' => $this->model->draft()->count(),
            'published' => $this->model->published()->count(),
            'scheduled' => $this->model->scheduled()->count(),
            'total' => $this->model->count(),
        ];
    }

    public function getTotalViews(): int
    {
        return (int) $this->model->sum('views_count');
    }

    public function getForSitemap(): Collection
    {
        return $this->model
            ->published()
            ->select(['slug', 'updated_at'])
            ->get();
    }

    public function getForRssFeed(int $limit = 20): Collection
    {
        return $this->model
            ->published()
            ->latest()
            ->with(['author', 'categories'])
            ->limit($limit)
            ->get();
    }
}
