<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CategoryManager extends Component
{
    // Form fields
    public string $name = '';
    public string $slug = '';
    public string $description = '';
    public string $color = '#3b82f6';
    public string $icon = '';
    public ?int $parent_id = null;
    public bool $is_active = true;

    // Editing state
    public ?int $editingCategoryId = null;
    public bool $showForm = false;

    // Inline editing
    public ?int $inlineEditId = null;
    public string $inlineEditName = '';
    public string $inlineEditColor = '';
    public string $inlineEditIcon = '';

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->color = $category->color ?? '#3b82f6';
        $this->icon = $category->icon ?? '';
        $this->parent_id = $category->parent_id;
        $this->is_active = $category->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules());

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'color' => $this->color,
            'icon' => $this->icon ?: null,
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
        ];

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $category->update($data);
            $message = 'Category updated.';
        } else {
            $data['order'] = Category::max('order') + 1;
            Category::create($data);
            $message = 'Category created.';
        }

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::withCount('posts')->findOrFail($categoryId);

        if ($category->posts_count > 0) {
            $this->dispatch('notify', type: 'error', message: 'Cannot delete a category with posts. Remove posts first.');
            return;
        }

        // Re-parent children to the deleted category's parent
        Category::where('parent_id', $categoryId)->update(['parent_id' => $category->parent_id]);

        $category->delete();

        $this->dispatch('notify', type: 'success', message: 'Category deleted.');
    }

    public function toggleActive(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    // Inline editing

    public function startInlineEdit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->inlineEditId = $category->id;
        $this->inlineEditName = $category->name;
        $this->inlineEditColor = $category->color ?? '#3b82f6';
        $this->inlineEditIcon = $category->icon ?? '';
    }

    public function saveInlineEdit(): void
    {
        if (! $this->inlineEditId) {
            return;
        }

        $this->validate([
            'inlineEditName' => ['required', 'string', 'min:2', 'max:100'],
            'inlineEditColor' => ['required', 'string', 'max:20'],
            'inlineEditIcon' => ['nullable', 'string', 'max:50'],
        ]);

        $category = Category::findOrFail($this->inlineEditId);
        $category->update([
            'name' => $this->inlineEditName,
            'color' => $this->inlineEditColor,
            'icon' => $this->inlineEditIcon ?: null,
        ]);

        $this->cancelInlineEdit();
        $this->dispatch('notify', type: 'success', message: 'Category updated.');
    }

    public function cancelInlineEdit(): void
    {
        $this->inlineEditId = null;
        $this->inlineEditName = '';
        $this->inlineEditColor = '';
        $this->inlineEditIcon = '';
    }

    // Reordering

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Category::where('id', (int) $id)->update(['order' => $index + 1]);
        }

        $this->dispatch('notify', type: 'success', message: 'Categories reordered.');
    }

    public function moveUp(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $swapWith = Category::where('parent_id', $category->parent_id)
            ->where('order', '<', $category->order)
            ->orderByDesc('order')
            ->first();

        if ($swapWith) {
            $this->swapOrder($category, $swapWith);
        }
    }

    public function moveDown(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $swapWith = Category::where('parent_id', $category->parent_id)
            ->where('order', '>', $category->order)
            ->orderBy('order')
            ->first();

        if ($swapWith) {
            $this->swapOrder($category, $swapWith);
        }
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->withCount('posts')
            ->with('children')
            ->topLevel()
            ->ordered()
            ->get();
    }

    #[Computed]
    public function parentOptions(): Collection
    {
        return Category::query()
            ->topLevel()
            ->ordered()
            ->when($this->editingCategoryId, function ($q) {
                $q->where('id', '!=', $this->editingCategoryId);
            })
            ->get(['id', 'name']);
    }

    public function render(): View
    {
        return view('livewire.admin.blog.category-manager');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'slug')->ignore($this->editingCategoryId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['required', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset([
            'editingCategoryId',
            'name',
            'slug',
            'description',
            'color',
            'icon',
            'parent_id',
            'is_active',
        ]);
        $this->color = '#3b82f6';
        $this->is_active = true;
    }

    protected function swapOrder(Category $a, Category $b): void
    {
        $tempOrder = $a->order;
        $a->update(['order' => $b->order]);
        $b->update(['order' => $tempOrder]);
    }
}
