<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Services\ImageSearchService;
use App\Services\MediaService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends Component
{
    public bool $showModal = false;
    public string $activeTab = 'library';
    public string $searchQuery = '';
    public ?string $selectedImageUrl = null;
    public string $context = 'featured_image';

    /** @var array<int, array<string, mixed>> */
    public array $pexelsResults = [];
    public int $pexelsPage = 1;
    public int $pexelsTotalResults = 0;

    /** @var array<int, array<string, mixed>> */
    public array $googleResults = [];
    public int $googleStart = 1;
    public int $googleTotalResults = 0;

    public bool $isDownloading = false;

    public string $librarySearch = '';
    public int $libraryLimit = 24;
    public int $libraryTotal = 0;

    /** @var array<string, string|null> */
    public array $apiErrors = [
        'pexels' => null,
        'google' => null,
    ];

    protected ImageSearchService $imageSearch;
    protected MediaService $mediaService;

    public function boot(ImageSearchService $imageSearch, MediaService $mediaService): void
    {
        $this->imageSearch = $imageSearch;
        $this->mediaService = $mediaService;
    }

    #[On('open-media-picker')]
    public function openModal(string $context = 'featured_image', ?string $keyword = null): void
    {
        $this->context = $context;
        $this->showModal = true;
        $this->selectedImageUrl = null;
        $this->searchQuery = $keyword ?? '';
        $this->pexelsResults = [];
        $this->googleResults = [];
        $this->pexelsPage = 1;
        $this->googleStart = 1;
        $this->libraryLimit = 24;
        $this->apiErrors = ['pexels' => null, 'google' => null];

        if ($this->searchQuery) {
            $this->searchPexels();
            $this->searchGoogle();
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['searchQuery', 'selectedImageUrl', 'pexelsResults', 'googleResults']);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->selectedImageUrl = null;
    }

    public function selectImage(string $url): void
    {
        $this->selectedImageUrl = $url;
    }

    public function insertSelected(): void
    {
        if (!$this->selectedImageUrl) {
            return;
        }

        $this->dispatch('media-selected', url: $this->selectedImageUrl, context: $this->context);
        $this->closeModal();
    }

    // --- Pexels ---

    public function searchPexels(): void
    {
        if (trim($this->searchQuery) === '') {
            return;
        }

        $this->pexelsPage = 1;
        $this->pexelsResults = [];

        $data = $this->imageSearch->searchPexels($this->searchQuery, $this->pexelsPage);

        if (isset($data['error'])) {
            $this->apiErrors['pexels'] = $data['error'];
            return;
        }

        $this->apiErrors['pexels'] = null;
        $this->pexelsResults = $data['results'];
        $this->pexelsTotalResults = $data['total_results'];
    }

    public function loadMorePexels(): void
    {
        $this->pexelsPage++;
        $data = $this->imageSearch->searchPexels($this->searchQuery, $this->pexelsPage);

        if (isset($data['error'])) {
            $this->pexelsPage--;
            return;
        }

        $this->pexelsResults = array_merge($this->pexelsResults, $data['results']);
    }

    // --- Google ---

    public function searchGoogle(): void
    {
        if (trim($this->searchQuery) === '') {
            return;
        }

        $this->googleStart = 1;
        $this->googleResults = [];

        $data = $this->imageSearch->searchGoogle($this->searchQuery, $this->googleStart);

        if (isset($data['error'])) {
            $this->apiErrors['google'] = $data['error'];
            return;
        }

        $this->apiErrors['google'] = null;
        $this->googleResults = $data['results'];
        $this->googleTotalResults = $data['total_results'];
    }

    public function loadMoreGoogle(): void
    {
        $this->googleStart += 10;
        $data = $this->imageSearch->searchGoogle($this->searchQuery, $this->googleStart);

        if (isset($data['error'])) {
            $this->googleStart -= 10;
            return;
        }

        $this->googleResults = array_merge($this->googleResults, $data['results']);
    }

    // --- Download external image via MediaService ---

    public function downloadAndSelect(string $externalUrl, string $attribution = ''): void
    {
        $this->isDownloading = true;

        try {
            $source = $this->activeTab === 'pexels' ? 'pexels' : 'google';
            $media = $this->mediaService->storeFromUrl(
                $externalUrl,
                $source,
                auth()->user(),
                null,
                $attribution ? ['photographer' => $attribution] : null,
            );

            $this->selectedImageUrl = $media->getUrl();
        } catch (\Throwable $e) {
            $this->dispatch('notify', type: 'error', message: 'Failed to download image: ' . $e->getMessage());
        } finally {
            $this->isDownloading = false;
        }
    }

    // --- Called from JS after direct upload ---

    public function onUploadComplete(string $url): void
    {
        $this->selectedImageUrl = $url;
        $this->activeTab = 'library';
    }

    // --- Library browsing ---

    public function loadMoreLibrary(): void
    {
        $this->libraryLimit += 24;
    }

    public function getLibraryImagesProperty(): Collection
    {
        $query = Media::query()
            ->where('collection_name', 'default')
            ->latest();

        if (trim($this->librarySearch) !== '') {
            $query->where(function ($q) {
                $q->where('file_name', 'like', '%' . $this->librarySearch . '%')
                    ->orWhere('name', 'like', '%' . $this->librarySearch . '%');
            });
        }

        $this->libraryTotal = $query->count();

        return $query->limit($this->libraryLimit)->get();
    }

    public function render(): View
    {
        return view('livewire.admin.media-picker', [
            'libraryImages' => $this->libraryImages,
        ]);
    }
}
