<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Blog\CreatorContextService;
use App\Services\Blog\PostGeneratorService;
use App\Services\Blog\TitleSuggestionService;
use Illuminate\Support\Str;
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

    public string $postType = 'article';

    public string $length = 'medium';

    public string $tone = 'professional';

    public ?string $targetKeyword = null;

    public ?int $categoryId = null;

    // Creator feature fields
    public bool $featureCreators = false;

    public string $creatorSearch = '';

    public array $creatorSearchResults = [];

    public array $selectedCreators = [];

    public bool $isGenerating = false;

    public bool $isSaving = false;

    public ?array $generatedContent = null;

    public ?string $error = null;

    protected $rules = [
        'topic' => 'required|min:5|max:200',
        'aiProvider' => 'required|in:openai,perplexity,anthropic',
        'postType' => 'required|in:article,quiz,trivia,poll',
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
        $this->postType = 'article';
        $this->length = 'medium';
        $this->tone = 'professional';
        $this->targetKeyword = null;
        $this->categoryId = null;
        $this->featureCreators = false;
        $this->creatorSearch = '';
        $this->creatorSearchResults = [];
        $this->selectedCreators = [];
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

    public function toggleFeatureCreators(): void
    {
        $this->featureCreators = ! $this->featureCreators;

        if (! $this->featureCreators) {
            $this->selectedCreators = [];
            $this->creatorSearch = '';
            $this->creatorSearchResults = [];
        }
    }

    public function updatedCreatorSearch(): void
    {
        if (strlen($this->creatorSearch) < 2) {
            $this->creatorSearchResults = [];

            return;
        }

        $service = app(CreatorContextService::class);
        $selectedIds = array_column($this->selectedCreators, 'id');

        $this->creatorSearchResults = $service->searchCreators($this->creatorSearch)
            ->reject(fn ($c) => in_array($c->id, $selectedIds))
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'country' => $c->country,
                'category' => $c->category,
                'is_featured' => $c->is_featured,
                'is_trending' => $c->is_trending,
            ])
            ->values()
            ->toArray();
    }

    public function selectCreator(int $creatorId): void
    {
        // Prevent duplicates
        if (collect($this->selectedCreators)->contains('id', $creatorId)) {
            return;
        }

        $result = collect($this->creatorSearchResults)->firstWhere('id', $creatorId);
        if ($result) {
            $this->selectedCreators[] = $result;
        }

        $this->creatorSearch = '';
        $this->creatorSearchResults = [];
    }

    public function removeCreator(int $creatorId): void
    {
        $this->selectedCreators = array_values(
            array_filter($this->selectedCreators, fn ($c) => $c['id'] !== $creatorId)
        );
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

            $creatorIds = $this->featureCreators
                ? array_column($this->selectedCreators, 'id')
                : [];

            $request = new PostGenerationRequest(
                topic: $this->topic,
                aiProvider: $this->aiProvider,
                length: $this->length,
                tone: $this->tone,
                targetKeyword: $this->targetKeyword,
                postType: $this->postType,
                additionalContext: $additionalContext,
                creatorIds: $creatorIds,
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
                'post_type' => $postData->postType,
                'type_data' => $postData->typeData,
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
        if (! $this->generatedContent || $this->isSaving) {
            return;
        }

        $this->isSaving = true;

        try {
            $service = app(TitleSuggestionService::class);
            $service->markTitleAsUsed($this->topic);
            $this->usedTitles = $service->getUsedTitles();

            $content = $this->generatedContent;

            $post = Post::create([
                'author_id' => auth()->id(),
                'title' => $content['title'] ?? 'Untitled',
                'slug' => ! empty($content['slug']) ? $content['slug'] : Str::slug($content['title'] ?? 'untitled'),
                'excerpt' => $content['excerpt'] ?? null,
                'content' => $content['content'] ?? '',
                'post_type' => $content['post_type'] ?? 'article',
                'type_data' => $content['type_data'] ?? null,
                'meta_title' => $content['meta_title'] ?? null,
                'meta_description' => $content['meta_description'] ?? null,
                'focus_keyword' => $content['focus_keyword'] ?? null,
                'social_sharing' => $content['social_sharing'] ?? null,
                'status' => 'draft',
                'reading_time' => $content['reading_time'] ?? null,
                'ai_provider' => $content['ai_provider'] ?? null,
                'generation_params' => $content['generation_params'] ?? null,
            ]);

            if (! empty($content['category_ids'])) {
                $post->categories()->sync($content['category_ids']);
            }

            if (! empty($content['suggested_tags'])) {
                $tagIds = collect($content['suggested_tags'])->map(function (string $tagName): int {
                    return Tag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    )->id;
                })->toArray();
                $post->tags()->sync($tagIds);
            }

            $this->generatedContent = null;
            $this->closeModal();
            $this->redirect(route('admin.blog.posts.edit', $post->id), navigate: true);
        } finally {
            $this->isSaving = false;
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
