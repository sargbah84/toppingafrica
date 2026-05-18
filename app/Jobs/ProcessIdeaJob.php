<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Models\ContentIdea;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Blog\ContentAgentService;
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
            $post = $this->generatePost($agent, $generator, $idea);
            $this->improveSeo($seo, $agent, $post);
            $this->schedulePost($post);
            $idea->markAsGenerated($post->id);

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

            Log::error('ProcessIdeaJob: failed', [
                'idea_id' => $idea->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generatePost(ContentAgentService $agent, PostGeneratorService $generator, ContentIdea $idea): Post
    {
        $guidance = $agent->buildEditorialGuidance();

        $request = new PostGenerationRequest(
            topic: $idea->title."\n\nContext: ".$idea->description,
            aiProvider: 'perplexity',
            length: $idea->suggested_length ?? 'medium',
            tone: $idea->suggested_tone ?? 'professional',
            targetKeyword: $idea->suggested_keyword,
            postType: $idea->suggested_post_type ?? 'article',
            additionalContext: $guidance !== '' ? ['editorial_guidance' => $guidance] : [],
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

        return $post;
    }

    private function improveSeo(SeoIntelligenceService $seo, ContentAgentService $agent, Post $post): void
    {
        $config = $agent->config();
        $threshold = $config['min_seo_score'];
        $maxAttempts = $config['max_improve_attempts'];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $analysis = $seo->analyzePost($post->fresh());

            if ($analysis->overall_score >= $threshold) {
                Log::info('ProcessIdeaJob: SEO target met', [
                    'post_id' => $post->id,
                    'attempt' => $attempt,
                    'score' => $analysis->overall_score,
                ]);

                return;
            }

            try {
                $seo->applyRecommendations($post->fresh(), $analysis);
            } catch (Throwable $e) {
                Log::warning('ProcessIdeaJob: applyRecommendations failed', [
                    'post_id' => $post->id,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                ]);

                return;
            }
        }
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
