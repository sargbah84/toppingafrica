<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibrary extends Component
{
    use WithFileUploads;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'collection')]
    public string $collectionFilter = '';

    /** @var array<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public ?int $selectedMediaId = null;
    public ?string $selectedMediaUrl = null;
    public ?string $selectedMediaName = null;

    public bool $showUploadPanel = false;
    public string $uploadCollection = 'content_images';

    public int $perPage = 24;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUploads(): void
    {
        $this->validate([
            'uploads.*' => ['image', 'max:10240'],
        ]);
    }

    public function upload(): void
    {
        $this->validate([
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*' => ['image', 'max:10240'],
        ]);

        $uploadCount = 0;

        foreach ($this->uploads as $upload) {
            // Create a standalone media entry using Spatie's Media model
            // Attach to a generic model or use the media library's standalone upload
            $media = new Media();
            $media->name = pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME);
            $media->file_name = $upload->getClientOriginalName();
            $media->mime_type = $upload->getMimeType();
            $media->size = $upload->getSize();
            $media->collection_name = $this->uploadCollection;
            $media->disk = config('media-library.disk_name', 'public');
            $media->manipulations = [];
            $media->custom_properties = [];
            $media->generated_conversions = [];
            $media->responsive_images = [];

            // Move file to media disk
            $path = $upload->storeAs(
                'media/' . now()->format('Y/m'),
                $upload->hashName(),
                'public'
            );

            if ($path) {
                $uploadCount++;
            }
        }

        $this->uploads = [];
        $this->showUploadPanel = false;

        $this->dispatch('notify', type: 'success', message: "{$uploadCount} file(s) uploaded.");
        $this->dispatch('media-uploaded');
    }

    public function selectMedia(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);

        $this->selectedMediaId = $media->id;
        $this->selectedMediaUrl = $media->getUrl();
        $this->selectedMediaName = $media->name;

        $this->dispatch('media-selected', [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'name' => $media->name,
            'thumbnail' => $media->hasGeneratedConversion('thumbnail')
                ? $media->getUrl('thumbnail')
                : $media->getUrl(),
        ]);
    }

    public function insertMedia(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);

        $this->dispatch('insert-media', [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'alt' => $media->name,
        ]);
    }

    public function deleteMedia(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);
        $media->delete();

        if ($this->selectedMediaId === $mediaId) {
            $this->reset('selectedMediaId', 'selectedMediaUrl', 'selectedMediaName');
        }

        $this->dispatch('notify', type: 'success', message: 'Media deleted.');
    }

    public function clearSelection(): void
    {
        $this->reset('selectedMediaId', 'selectedMediaUrl', 'selectedMediaName');
    }

    #[Computed]
    public function media(): LengthAwarePaginator
    {
        return Media::query()
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('file_name', 'like', '%' . $this->search . '%');
            })
            ->when($this->collectionFilter !== '', function ($query) {
                $query->where('collection_name', $this->collectionFilter);
            })
            ->where('mime_type', 'like', 'image/%')
            ->orderByDesc('created_at')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function collections(): array
    {
        return Media::query()
            ->select('collection_name')
            ->distinct()
            ->pluck('collection_name')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.admin.blog.media-library');
    }
}
