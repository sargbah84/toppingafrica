<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class TagManager extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    // Form fields
    public string $name = '';
    public string $slug = '';

    // Edit state
    public ?int $editingTagId = null;
    public bool $showForm = false;

    public int $perPage = 25;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEditForm(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);

        $this->editingTagId = $tag->id;
        $this->name = $tag->name;
        $this->slug = $tag->slug;
        $this->showForm = true;
    }

    public function updatedName(string $value): void
    {
        if (! $this->editingTagId) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $this->validate($this->rules());

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
        ];

        if ($this->editingTagId) {
            $tag = Tag::findOrFail($this->editingTagId);
            $tag->update($data);
            $message = 'Tag updated.';
        } else {
            Tag::create($data);
            $message = 'Tag created.';
        }

        $this->resetForm();
        $this->showForm = false;
        $this->dispatch('notify', type: 'success', message: $message);
    }

    public function deleteTag(int $tagId): void
    {
        $tag = Tag::findOrFail($tagId);
        $tag->posts()->detach();
        $tag->delete();

        $this->dispatch('notify', type: 'success', message: 'Tag deleted.');
    }

    public function mergeTags(int $sourceTagId, int $targetTagId): void
    {
        $sourceTag = Tag::findOrFail($sourceTagId);
        $targetTag = Tag::findOrFail($targetTagId);

        // Move all post associations from source to target
        $postIds = $sourceTag->posts()->pluck('posts.id')->toArray();
        $existingPostIds = $targetTag->posts()->pluck('posts.id')->toArray();
        $newPostIds = array_diff($postIds, $existingPostIds);

        $targetTag->posts()->attach($newPostIds);
        $sourceTag->posts()->detach();
        $sourceTag->delete();

        $this->dispatch('notify', type: 'success', message: "Tag \"{$sourceTag->name}\" merged into \"{$targetTag->name}\".");
    }

    #[Computed]
    public function tags(): LengthAwarePaginator
    {
        return Tag::query()
            ->withCount('posts')
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.admin.blog.tag-manager');
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'slug' => [
                'required',
                'string',
                'max:60',
                Rule::unique('tags', 'slug')->ignore($this->editingTagId),
            ],
        ];
    }

    protected function resetForm(): void
    {
        $this->reset(['editingTagId', 'name', 'slug']);
    }
}
