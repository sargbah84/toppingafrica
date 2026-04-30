<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class CategoryRepository
{
    public function __construct(
        private readonly Category $model,
    ) {}

    public function find(int $id): ?Category
    {
        return $this->model->find($id);
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function getAll(): Collection
    {
        return $this->model->ordered()->get();
    }

    public function getAllWithPostCounts(): Collection
    {
        return $this->model
            ->ordered()
            ->withCount(['posts', 'publishedPosts'])
            ->get();
    }

    public function getWithPosts(int $limit = 5): Collection
    {
        return $this->model
            ->ordered()
            ->withCount(['publishedPosts'])
            ->having('published_posts_count', '>', 0)
            ->limit($limit)
            ->get();
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            $this->model->where('id', $id)->update(['order' => $order]);
        }
    }

    public function getNextOrder(): int
    {
        return ($this->model->max('order') ?? 0) + 1;
    }
}
