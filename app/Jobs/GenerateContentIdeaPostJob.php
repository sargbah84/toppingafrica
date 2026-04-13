<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Models\ContentIdea;
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
            $request = new PostGenerationRequest(
                topic: $idea->title."\n\nContext: ".$idea->description,
                aiProvider: 'perplexity',
                length: $idea->suggested_length,
                tone: $idea->suggested_tone,
                targetKeyword: $idea->suggested_keyword,
                postType: $idea->suggested_post_type,
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

            if (! empty($postData->categoryIds)) {
                $post->categories()->sync($postData->categoryIds);
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
