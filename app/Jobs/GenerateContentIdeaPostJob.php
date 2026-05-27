<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Models\Category;
use App\Models\ContentIdea;
use App\Models\Creator;
use App\Models\Post;
use App\Models\Tag;
use App\Services\Blog\PostGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class GenerateContentIdeaPostJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public readonly int $ideaId,
        public readonly int $userId,
        public readonly ?string $previousStatus = null,
        public readonly ?int $previousPostId = null,
    ) {}

    public function handle(PostGeneratorService $generatorService): void
    {
        $idea = ContentIdea::find($this->ideaId);

        if (! $idea) {
            return;
        }

        try {
            // For spotlight ideas, resolve the proposed slug to a real Creator
            // and pass its ID through so the generator injects the full creator
            // context block (bio, socials, badges) into the AI prompt.
            $creator = null;
            $creatorIds = [];
            if ($idea->suggested_post_type === 'spotlight' && $idea->suggested_creator_slug) {
                $creator = Creator::where('slug', $idea->suggested_creator_slug)
                    ->where('status', 'published')
                    ->first();
                if ($creator) {
                    $creatorIds = [$creator->id];
                }
            }

            $request = new PostGenerationRequest(
                topic: $idea->title."\n\nContext: ".$idea->description,
                aiProvider: 'perplexity',
                length: $idea->suggested_length,
                tone: $idea->suggested_tone,
                targetKeyword: $idea->suggested_keyword,
                postType: $idea->suggested_post_type,
                creatorIds: $creatorIds,
            );

            $postData = $generatorService->generate($request);

            $slug = $postData->slug ?? Str::slug($postData->title);
            $originalSlug = $slug;
            $counter = 2;
            while (Post::where('slug', $slug)->exists()) {
                $slug = $originalSlug.'-'.$counter;
                $counter++;
            }

            $post = Post::create([
                'title' => $postData->title,
                'slug' => $slug,
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
                'author_id' => $this->userId,
            ]);

            $categoryIds = $postData->categoryIds ?? [];

            // Always route spotlights into the Spotlight category so the public
            // /spotlight lane and hero priority list pick them up automatically.
            if ($idea->suggested_post_type === 'spotlight') {
                $spotlightCategoryId = Category::where('slug', 'spotlight')->value('id');
                if ($spotlightCategoryId && ! in_array($spotlightCategoryId, $categoryIds, true)) {
                    $categoryIds[] = $spotlightCategoryId;
                }
            }

            if (! empty($categoryIds)) {
                $post->categories()->sync($categoryIds);
            }

            // Attach the spotlighted creator on the post_creator pivot so the
            // creator profile page picks it up in their feed.
            if ($creator) {
                $post->creators()->syncWithoutDetaching([$creator->id]);
            }

            if (! empty($postData->suggestedTags)) {
                $tagIds = collect($postData->suggestedTags)->map(function (string $tagName): int {
                    return Tag::firstOrCreate(
                        ['slug' => Str::slug($tagName)],
                        ['name' => $tagName]
                    )->id;
                })->toArray();
                $post->tags()->sync($tagIds);
            }

            $idea->markAsGenerated($post->id);
        } catch (\Throwable $e) {
            $idea->update([
                'status' => $this->previousStatus ?? 'pending',
                'generated_post_id' => $this->previousPostId,
                'generation_error' => Str::limit($e->getMessage(), 500),
            ]);
        }
    }
}
