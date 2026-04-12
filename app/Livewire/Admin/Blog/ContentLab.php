<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Jobs\ResearchContentIdeasJob;
use App\Models\ContentIdea;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Blog\PostGeneratorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ContentLab extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $nicheFilter = 'all';

    public int $fetchCount = 5;

    public bool $fetching = false;

    // Generation progress modal
    public bool $showProgressModal = false;

    public bool $isGenerating = false;

    public ?int $generatingIdeaId = null;

    public string $generationStep = '';  // researching, generating, saving, complete, failed

    public ?string $generationError = null;

    public ?int $generatedPostId = null;

    public ?string $generatedPostTitle = null;

    public ?string $generatedPostSlug = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedNicheFilter(): void
    {
        $this->resetPage();
    }

    // ── Research Now ─────────────────────────────────────────

    public function fetchNow(): void
    {
        $this->fetching = true;

        $count = max(1, min(10, $this->fetchCount));

        try {
            $result = (new ResearchContentIdeasJob($count))->handle();

            if ($result['error']) {
                session()->flash('error', $result['error']);
            } elseif ($result['created'] > 0) {
                session()->flash('success', "Discovered {$result['created']} new content idea(s). Cleaned up {$result['cleaned_up']} expired idea(s).");
            } else {
                session()->flash('success', "Research complete but no new ideas found (all may already exist). Cleaned up {$result['cleaned_up']} expired idea(s).");
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Research failed: '.$e->getMessage());
        } finally {
            $this->fetching = false;
            $this->resetPage();
        }
    }

    // ── Generate ─────────────────────────────────────────────

    public function generate(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);

        // Open modal and start progress
        $this->showProgressModal = true;
        $this->isGenerating = true;
        $this->generatingIdeaId = $ideaId;
        $this->generationStep = 'researching';
        $this->generationError = null;
        $this->generatedPostId = null;
        $this->generatedPostTitle = null;
        $this->generatedPostSlug = null;

        try {
            $idea->markAsGenerating();

            // Step 1: Researching & preparing
            $this->generationStep = 'researching';

            $request = new PostGenerationRequest(
                topic: $idea->title."\n\nContext: ".$idea->description,
                aiProvider: 'perplexity',
                length: $idea->suggested_length,
                tone: $idea->suggested_tone,
                targetKeyword: $idea->suggested_keyword,
                postType: $idea->suggested_post_type,
            );

            // Step 2: Generating content with AI
            $this->generationStep = 'generating';

            $generatorService = app(PostGeneratorService::class);
            $postData = $generatorService->generate($request);

            // Step 3: Saving as draft post
            $this->generationStep = 'saving';

            $post = Post::create([
                'title' => $postData->title,
                'slug' => $postData->slug ?? Str::slug($postData->title),
                'content' => $postData->content,
                'excerpt' => $postData->excerpt,
                'post_type' => $postData->postType,
                'type_data' => $postData->typeData ?: null,
                'meta_title' => $postData->metaTitle,
                'meta_description' => $postData->metaDescription,
                'focus_keyword' => $postData->focusKeyword,
                'og_meta' => null,
                'status' => 'draft',
                'is_featured' => false,
                'reading_time' => $postData->readingTime,
                'author_id' => auth()->id(),
            ]);

            // Sync categories
            if (! empty($postData->categoryIds)) {
                $post->categories()->sync($postData->categoryIds);
            }

            // Sync tags
            if (! empty($postData->suggestedTags)) {
                $tagIds = collect($postData->suggestedTags)->map(function (string $tagName): int {
                    return Tag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    )->id;
                })->toArray();
                $post->tags()->sync($tagIds);
            }

            // Mark idea as generated
            $idea->markAsGenerated($post->id);

            // Step 4: Complete
            $this->generationStep = 'complete';
            $this->generatedPostId = $post->id;
            $this->generatedPostTitle = $post->title;
            $this->generatedPostSlug = $post->slug;
        } catch (\Throwable $e) {
            $idea->update(['status' => 'pending']);
            $this->generationStep = 'failed';
            $this->generationError = $e->getMessage();
        } finally {
            $this->isGenerating = false;
            $this->generatingIdeaId = null;
        }
    }

    public function closeProgressModal(): void
    {
        $this->showProgressModal = false;
        $this->generationStep = '';
        $this->generationError = null;
        $this->generatedPostId = null;
        $this->generatedPostTitle = null;
        $this->generatedPostSlug = null;
    }

    // ── Idea Actions ─────────────────────────────────────────

    public function dismiss(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);
        $idea->dismiss();

        session()->flash('success', 'Idea dismissed.');
    }

    public function restoreIdea(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);
        $idea->restore();

        session()->flash('success', 'Idea restored.');
    }

    public function delete(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);
        $idea->delete();

        session()->flash('success', 'Idea deleted.');
    }

    public function cleanup(): void
    {
        $deleted = ContentIdea::where(function ($q) {
            $q->where('expires_at', '<', now())
                ->where('status', '!=', 'generated');
        })->orWhere('status', 'dismissed')->delete();

        session()->flash('success', "Cleaned up {$deleted} old/dismissed idea(s).");
        $this->resetPage();
    }

    // ── Data ─────────────────────────────────────────────────

    public function getIdeas(): LengthAwarePaginator
    {
        $query = ContentIdea::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhere('suggested_keyword', 'like', "%{$this->search}%");
            });
        }

        $query = match ($this->filter) {
            'pending' => $query->where('status', 'pending')->where('expires_at', '>', now()),
            'generated' => $query->where('status', 'generated'),
            'dismissed' => $query->where('status', 'dismissed'),
            'expired' => $query->where('expires_at', '<=', now())->where('status', '!=', 'generated'),
            default => $query,
        };

        if ($this->nicheFilter !== 'all') {
            $query->where('niche', $this->nicheFilter);
        }

        return $query->orderByDesc('seo_score')->latest('researched_at')->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.admin.blog.content-lab', [
            'ideas' => $this->getIdeas(),
            'niches' => config('blog.ai.niches', []),
            'counts' => [
                'all' => ContentIdea::count(),
                'pending' => ContentIdea::where('status', 'pending')->where('expires_at', '>', now())->count(),
                'generated' => ContentIdea::where('status', 'generated')->count(),
                'dismissed' => ContentIdea::where('status', 'dismissed')->count(),
                'expired' => ContentIdea::where('expires_at', '<=', now())->where('status', '!=', 'generated')->count(),
            ],
            'lastResearched' => ContentIdea::latest('researched_at')->value('researched_at'),
        ])->title('Content Lab');
    }
}
