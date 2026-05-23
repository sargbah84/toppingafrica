<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Models\ContentIdea;
use App\Models\Creator;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Blog\ContentAgentService;
use App\Services\Blog\ContentImagesService;
use App\Services\Blog\FeaturedImageService;
use App\Services\Blog\PostGeneratorService;
use App\Services\Blog\Seo\SeoIntelligenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessIdeaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public readonly int $ideaId,
        public readonly string $scheduledFor,
        public readonly ?int $authorId = null,
    ) {}

    public function handle(
        ContentAgentService $agent,
        PostGeneratorService $generator,
        SeoIntelligenceService $seo,
        FeaturedImageService $featuredImage,
        ContentImagesService $contentImages,
    ): void {
        $idea = ContentIdea::find($this->ideaId);
        if (! $idea) {
            return;
        }

        // Idempotency: if this idea already produced a post, don't double-generate.
        if ($idea->status === 'generated' && $idea->generated_post_id) {
            return;
        }

        $idea->markAsGenerating($this->authorId);

        try {
            [$post, $imageQuery] = $this->generatePost($agent, $generator, $idea);
            $this->attachFeaturedImage($featuredImage, $post, $imageQuery, $idea);
            $this->attachContentImages($contentImages, $post, $imageQuery);
            $finalScore = $this->improveSeo($seo, $agent, $post);
            $this->schedulePost($post);
            $idea->markAsGenerated($post->id);

            activity('content_agent')
                ->event('post_scheduled')
                ->performedOn($post->fresh())
                ->withProperties([
                    'idea_id' => $idea->id,
                    'idea_title' => $idea->title,
                    'seo_score' => $finalScore,
                    'scheduled_at' => $post->scheduled_at?->toDateTimeString(),
                ])
                ->log("Scheduled post #{$post->id} (SEO: {$finalScore})");

            Log::info('ProcessIdeaJob: scheduled post', [
                'idea_id' => $idea->id,
                'post_id' => $post->id,
                'scheduled_at' => $post->scheduled_at?->toDateTimeString(),
            ]);
        } catch (Throwable $e) {
            $idea->update([
                'status' => 'pending',
                'generation_error' => Str::limit($e->getMessage(), 500),
            ]);

            activity('content_agent')
                ->event('generation_failed')
                ->withProperties([
                    'idea_id' => $idea->id,
                    'idea_title' => $idea->title,
                    'error' => Str::limit($e->getMessage(), 200),
                ])
                ->log("Failed to generate post for idea #{$idea->id}");

            Log::error('ProcessIdeaJob: failed', [
                'idea_id' => $idea->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{0: Post, 1: string} [post, ai_suggested_image_query]
     */
    private function generatePost(ContentAgentService $agent, PostGeneratorService $generator, ContentIdea $idea): array
    {
        $guidance = $agent->buildEditorialGuidance();

        // Topic is the high-level subject the AI writes about. Passing the
        // description here concatenated with the title would (a) bloat the
        // prompt and (b) become a hostile fallback title when the AI parser
        // ever fails. Keep topic as just the idea title; the description
        // and any extra context belong in additionalContext where the
        // prompt builders can wrap it explicitly.
        $additional = $guidance !== '' ? ['editorial_guidance' => $guidance] : [];
        if (! empty($idea->description)) {
            $additional['idea_context'] = trim((string) $idea->description);
        }

        $request = new PostGenerationRequest(
            topic: trim((string) $idea->title),
            aiProvider: 'perplexity',
            length: $idea->suggested_length ?? 'medium',
            tone: $idea->suggested_tone ?? 'professional',
            targetKeyword: $idea->suggested_keyword,
            postType: $idea->suggested_post_type ?? 'article',
            additionalContext: $additional,
        );

        $data = $generator->generate($request);

        $slug = $data->slug ?? Str::slug($data->title);
        $original = $slug;
        $i = 2;
        while (Post::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$i++;
        }

        $post = Post::create([
            'title' => $data->title,
            'slug' => $slug,
            'content' => $data->content,
            'excerpt' => $data->excerpt,
            'post_type' => $data->postType,
            'type_data' => $data->typeData ?: null,
            'meta_title' => $data->metaTitle,
            'meta_description' => $data->metaDescription,
            'focus_keyword' => $data->focusKeyword,
            'status' => 'draft',
            'is_featured' => false,
            'reading_time' => $data->readingTime,
            'author_id' => $this->authorId,
        ]);

        if (! empty($data->categoryIds)) {
            $post->categories()->sync($data->categoryIds);
        }

        if (! empty($data->suggestedTags)) {
            $tagIds = collect($data->suggestedTags)
                ->map(fn (string $name) => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id)
                ->toArray();
            $post->tags()->sync($tagIds);
        }

        // Attach creators the AI flagged — gives the library matcher a
        // direct signal for picking the right profile photo as featured.
        if (! empty($data->featuredCreatorSlugs)) {
            $creatorIds = Creator::query()
                ->whereIn('slug', $data->featuredCreatorSlugs)
                ->pluck('id')
                ->all();
            if ($creatorIds !== []) {
                $post->creators()->sync($creatorIds);
            }
        }

        return [$post, (string) ($data->featuredImageQuery ?? '')];
    }

    private function attachFeaturedImage(FeaturedImageService $service, Post $post, string $aiQuery, ContentIdea $idea): void
    {
        $query = $aiQuery !== ''
            ? $aiQuery
            : trim(($idea->suggested_keyword ?? $post->focus_keyword ?? $post->title).' Africa');

        $url = $service->attach($post->fresh(), $query);

        Log::info('ProcessIdeaJob: featured image attempt', [
            'post_id' => $post->id,
            'query' => $query,
            'attached' => $url !== null,
        ]);
    }

    private function attachContentImages(ContentImagesService $service, Post $post, string $aiQuery): void
    {
        try {
            $inserted = $service->attach($post->fresh(), $aiQuery !== '' ? $aiQuery : null);
            Log::info('ProcessIdeaJob: content images inserted', [
                'post_id' => $post->id,
                'inserted' => $inserted,
                'post_type' => $post->post_type,
            ]);
        } catch (Throwable $e) {
            // Body-image insertion failure shouldn't block scheduling.
            Log::warning('ProcessIdeaJob: content images failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function improveSeo(SeoIntelligenceService $seo, ContentAgentService $agent, Post $post): int
    {
        $config = $agent->config();
        $threshold = $config['min_seo_score'];
        $maxAttempts = $config['max_improve_attempts'];
        $latestScore = 0;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $analysis = $seo->analyzePost($post->fresh());
            $latestScore = $analysis->overall_score;

            if ($analysis->overall_score >= $threshold) {
                Log::info('ProcessIdeaJob: SEO target met', [
                    'post_id' => $post->id,
                    'attempt' => $attempt,
                    'score' => $analysis->overall_score,
                ]);

                return $latestScore;
            }

            try {
                $seo->applyRecommendations($post->fresh(), $analysis);
            } catch (Throwable $e) {
                Log::warning('ProcessIdeaJob: applyRecommendations failed', [
                    'post_id' => $post->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                return $latestScore;
            }
        }

        return $latestScore;
    }

    private function schedulePost(Post $post): void
    {
        $scheduledAt = Carbon::parse($this->scheduledFor);

        if ($scheduledAt->isPast()) {
            $scheduledAt = now()->addMinutes(5);
        }

        $post->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'published_at' => null,
        ]);
    }
}
