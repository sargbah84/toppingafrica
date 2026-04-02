<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Models\Category;
use App\Services\Blog\PostGeneratorService;
use App\Services\Blog\TitleSuggestionService;
use Livewire\Attributes\On;
use Livewire\Component;

class PostGenerator extends Component
{
    public bool $showModal = false;

    // Title input mode: 'suggest' or 'manual'
    public string $inputMode = 'suggest';
    public string $selectedNiche = 'africa-news';
    public array $suggestedTitles = [];
    public array $usedTitles = [];
    public array $trendingKeywords = [];
    public bool $isLoadingTitles = false;

    public string $topic = '';
    public string $aiProvider = 'perplexity';
    public string $length = 'medium';
    public string $tone = 'professional';
    public ?string $targetKeyword = null;
    public ?int $categoryId = null;

    public bool $isGenerating = false;
    public ?array $generatedContent = null;
    public ?string $error = null;

    protected $rules = [
        'topic' => 'required|min:5|max:200',
        'aiProvider' => 'required|in:openai,perplexity,anthropic',
        'length' => 'required|in:short,medium,long',
        'tone' => 'required|in:professional,conversational,technical,beginner',
        'targetKeyword' => 'nullable|max:50',
        'categoryId' => 'nullable|exists:categories,id',
    ];

    public bool $dataLoaded = false;

    #[On('open-ai-generator')]
    public function openModal(): void
    {
        $this->resetForm();
        $this->dataLoaded = false;
        $this->showModal = true;
    }

    /**
     * Deferred data loading -- called by wire:init after the modal is visible.
     * This prevents the Google Trends API call from blocking the modal open.
     */
    public function loadModalData(): void
    {
        if ($this->dataLoaded) {
            return;
        }

        $service = app(TitleSuggestionService::class);
        $this->usedTitles = $service->getUsedTitles();

        if (empty($this->suggestedTitles)) {
            $cached = $service->getCachedTitles($this->selectedNiche);
            if ($cached) {
                $this->suggestedTitles = $cached;
            }
        }

        if (empty($this->trendingKeywords)) {
            $this->trendingKeywords = $service->getTrendingKeywords($this->selectedNiche);
        }

        $this->dataLoaded = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dataLoaded = false;
    }

    private function resetForm(): void
    {
        $this->inputMode = 'suggest';
        $this->topic = '';
        $this->aiProvider = 'perplexity';
        $this->length = 'medium';
        $this->tone = 'professional';
        $this->targetKeyword = null;
        $this->categoryId = null;
        $this->generatedContent = null;
        $this->error = null;
        $this->isGenerating = false;
        $this->isLoadingTitles = false;
    }

    public function setInputMode(string $mode): void
    {
        $this->inputMode = $mode;

        if ($mode === 'suggest' && empty($this->suggestedTitles)) {
            $this->loadSuggestedTitles();
        }
    }

    public function loadSuggestedTitles(bool $forceGenerate = false): void
    {
        $service = app(TitleSuggestionService::class);

        $this->trendingKeywords = $service->getTrendingKeywords($this->selectedNiche);

        if (! $forceGenerate) {
            $cached = $service->getCachedTitles($this->selectedNiche);
            if ($cached && ! empty($cached)) {
                $this->suggestedTitles = $cached;

                return;
            }
        }

        $this->isLoadingTitles = true;
        $this->error = null;

        try {
            $this->suggestedTitles = $service->getSuggestedTitles($this->selectedNiche, 10);

            if (empty($this->suggestedTitles)) {
                $this->error = 'Could not load title suggestions. Please try again or enter a topic manually.';
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to load suggestions: '.$e->getMessage();
        } finally {
            $this->isLoadingTitles = false;
        }
    }

    public function refreshTitles(): void
    {
        $this->isLoadingTitles = true;
        $this->error = null;

        try {
            $service = app(TitleSuggestionService::class);
            $this->suggestedTitles = $service->refreshTitles($this->selectedNiche, 10);

            if (empty($this->suggestedTitles)) {
                $this->error = 'Could not refresh suggestions. Please try again.';
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to refresh: '.$e->getMessage();
        } finally {
            $this->isLoadingTitles = false;
        }
    }

    public function changeNiche(string $niche): void
    {
        $this->selectedNiche = $niche;
        $this->suggestedTitles = [];
        $this->trendingKeywords = [];
        $this->loadSuggestedTitles();
    }

    public function selectTitle(string $title): void
    {
        $this->topic = $title;
        $this->inputMode = 'manual';
    }

    public function generate(): void
    {
        $this->validate();

        $this->isGenerating = true;
        $this->error = null;
        $this->generatedContent = null;

        try {
            $additionalContext = [];
            if ($this->categoryId) {
                $additionalContext['category_id'] = $this->categoryId;
            }
            if (! empty($this->trendingKeywords)) {
                $additionalContext['trending_keywords'] = $this->trendingKeywords;
            }

            $request = new PostGenerationRequest(
                topic: $this->topic,
                aiProvider: $this->aiProvider,
                length: $this->length,
                tone: $this->tone,
                targetKeyword: $this->targetKeyword,
                additionalContext: $additionalContext,
            );

            $generatorService = app(PostGeneratorService::class);
            $postData = $generatorService->generate($request);

            $this->generatedContent = [
                'title' => $postData->title,
                'content' => $postData->content,
                'excerpt' => $postData->excerpt,
                'slug' => $postData->slug,
                'meta_title' => $postData->metaTitle,
                'meta_description' => $postData->metaDescription,
                'focus_keyword' => $postData->focusKeyword,
                'reading_time' => $postData->readingTime,
                'suggested_tags' => $postData->suggestedTags,
                'suggested_categories' => $postData->suggestedCategories,
                'category_ids' => $postData->categoryIds,
                'social_sharing' => $postData->socialSharing,
                'internal_links' => $postData->internalLinks,
                'ai_provider' => $postData->aiProvider,
                'generation_params' => $postData->generationParams,
            ];

            $this->dispatch('content-generated', content: $this->generatedContent);
        } catch (\Exception $e) {
            $this->error = 'Generation failed: '.$e->getMessage();
        } finally {
            $this->isGenerating = false;
        }
    }

    public function useContent(): void
    {
        if ($this->generatedContent) {
            $service = app(TitleSuggestionService::class);
            $service->markTitleAsUsed($this->topic);
            $this->usedTitles = $service->getUsedTitles();

            session(['generated_post_content' => $this->generatedContent]);

            $this->dispatch('use-generated-content', content: $this->generatedContent);

            $currentRoute = request()->route()?->getName();
            if ($currentRoute !== 'admin.blog.posts.create') {
                $this->redirect(route('admin.blog.posts.create'), navigate: true);
            } else {
                $this->closeModal();
            }
        }
    }

    public function clearContent(): void
    {
        $this->generatedContent = null;
        $this->topic = '';
        $this->targetKeyword = null;
    }

    public function render()
    {
        if (! $this->showModal) {
            return view('livewire.admin.blog.post-generator', [
                'providers' => [],
                'lengths' => [],
                'tones' => [],
                'categories' => collect(),
                'niches' => [],
            ]);
        }

        $generatorService = app(PostGeneratorService::class);
        $titleService = app(TitleSuggestionService::class);

        return view('livewire.admin.blog.post-generator', [
            'providers' => $generatorService->getAvailableProviders(),
            'lengths' => $generatorService->getLengthOptions(),
            'tones' => $generatorService->getToneOptions(),
            'categories' => Category::ordered()->get(),
            'niches' => $titleService->getAvailableNiches(),
        ]);
    }
}
