<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Models\Post;
use App\Models\SeoAnalysis;
use App\Services\Blog\Seo\SeoIntelligenceService;
use Livewire\Attributes\On;
use Livewire\Component;

class SeoAnalysisPanel extends Component
{
    public ?int $postId = null;
    public ?array $analysis = null;
    public bool $isAnalyzing = false;
    public bool $showAnalysisModal = false;
    public bool $showFullReport = false;
    public bool $showApplyPreview = false;
    public bool $isApplying = false;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;
    public string $activeTab = 'strengths';
    public array $applyPreview = [];

    public function mount(?int $postId = null): void
    {
        $this->postId = $postId;

        if ($this->postId) {
            $this->loadLatestAnalysis();
        }
    }

    #[On('post-saved')]
    public function onPostSaved(int $postId): void
    {
        $this->postId = $postId;
        $this->loadLatestAnalysis();
    }

    public function loadLatestAnalysis(): void
    {
        if (! $this->postId) {
            return;
        }

        $latest = SeoAnalysis::where('post_id', $this->postId)
            ->latest()
            ->first();

        if ($latest) {
            $this->analysis = $latest->toArray();
        }
    }

    /**
     * Step 1: Open the modal with the animated checklist.
     * Validation/cooldown checks happen here so errors show before the modal.
     */
    public function analyze(): void
    {
        if (! $this->postId) {
            $this->errorMessage = 'Please save the post first before analyzing SEO.';

            return;
        }

        $post = Post::with('categories')->find($this->postId);

        if (! $post) {
            $this->errorMessage = 'Post not found.';

            return;
        }

        $service = app(SeoIntelligenceService::class);

        if (! $service->canAnalyze($post)) {
            $remaining = $service->getCooldownRemaining($post);
            $minutes = (int) ceil($remaining / 60);
            $this->errorMessage = "Please wait {$minutes} minute(s) before re-analyzing.";

            return;
        }

        $this->showAnalysisModal = true;
        $this->isAnalyzing = true;
        $this->errorMessage = null;
    }

    /**
     * Step 2: Run the actual analysis. Called from the modal's Alpine init()
     * after the checklist animation has started.
     */
    public function runAnalysis(): void
    {
        if (! $this->postId || ! $this->isAnalyzing) {
            return;
        }

        $post = Post::with('categories')->find($this->postId);

        if (! $post) {
            $this->dispatch('analysis-finished', success: false, grade: '', score: 0);
            $this->isAnalyzing = false;

            return;
        }

        try {
            $service = app(SeoIntelligenceService::class);
            $result = $service->analyzePost($post);
            $this->analysis = $result->toArray();
            $this->successMessage = "SEO analysis complete! Score: {$result->grade} ({$result->overall_score}/100)";
            $this->dispatch('seo-analysis-complete', score: $result->overall_score, grade: $result->grade);
            $this->dispatch('analysis-finished', success: true, grade: $result->grade, score: $result->overall_score);
        } catch (\Exception $e) {
            $this->errorMessage = 'Analysis failed: '.$e->getMessage();
            $this->dispatch('analysis-finished', success: false, grade: '', score: 0);
        } finally {
            $this->isAnalyzing = false;
        }
    }

    public function closeAnalysisModal(): void
    {
        if ($this->isAnalyzing) {
            return;
        }
        $this->showAnalysisModal = false;
    }

    public function openFullReport(): void
    {
        $this->showFullReport = true;
    }

    public function closeFullReport(): void
    {
        $this->showFullReport = false;
    }

    public function openApplyPreview(): void
    {
        if (! $this->postId || ! $this->analysis) {
            return;
        }

        $post = Post::with(['tags', 'categories'])->find($this->postId);
        $analysisModel = SeoAnalysis::find($this->analysis['id']);

        if (! $post || ! $analysisModel) {
            return;
        }

        $service = app(SeoIntelligenceService::class);
        $this->applyPreview = $service->getApplyPreview($post, $analysisModel);
        $this->showApplyPreview = true;
    }

    public function closeApplyPreview(): void
    {
        if ($this->isApplying) {
            return;
        }

        $this->showApplyPreview = false;
        $this->applyPreview = [];
    }

    public function applyRecommendations(): void
    {
        if (! $this->postId || ! $this->analysis) {
            return;
        }

        $post = Post::with(['tags', 'categories'])->find($this->postId);
        $analysisModel = SeoAnalysis::find($this->analysis['id']);

        if (! $post || ! $analysisModel) {
            $this->errorMessage = 'Post or analysis not found.';

            return;
        }

        $this->isApplying = true;

        try {
            $service = app(SeoIntelligenceService::class);
            $applied = $service->applyRecommendations($post, $analysisModel);

            if (! empty($applied)) {
                $this->dispatch('recommendations-applied');

                $post->refresh();
                $this->dispatch('content-updated');

                try {
                    $post = Post::with('categories')->find($this->postId);
                    $result = $service->analyzePost($post);
                    $this->analysis = $result->toArray();
                    $this->successMessage = "Recommendations applied & re-analyzed! New score: {$result->grade} ({$result->overall_score}/100)";
                    $this->dispatch('seo-analysis-complete', score: $result->overall_score, grade: $result->grade);
                } catch (\Exception $e) {
                    $this->successMessage = 'Recommendations applied, but re-analysis failed: '.$e->getMessage();
                }

                $this->showApplyPreview = false;
                $this->applyPreview = [];
            } else {
                $this->successMessage = 'No changes needed - post is already well optimized!';
                $this->showApplyPreview = false;
                $this->applyPreview = [];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Failed to apply recommendations: '.$e->getMessage();
        } finally {
            $this->isApplying = false;
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $postTags = [];
        if ($this->postId) {
            $postTags = Post::find($this->postId)?->tags?->pluck('name')->map(fn ($n) => strtolower($n))->toArray() ?? [];
        }

        return view('livewire.admin.blog.seo-analysis-panel', [
            'postTags' => $postTags,
        ]);
    }
}
