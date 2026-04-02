<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Blog;

final readonly class PostData
{
    public function __construct(
        public string $title,
        public string $content,
        public ?string $excerpt = null,
        public ?string $slug = null,
        public ?string $featuredImage = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?string $focusKeyword = null,
        public ?array $ogMeta = null,
        public ?array $socialSharing = null,
        public string $status = 'draft',
        public ?\DateTimeInterface $publishedAt = null,
        public ?\DateTimeInterface $scheduledAt = null,
        public ?int $readingTime = null,
        public bool $isFeatured = false,
        public ?string $aiProvider = null,
        public ?array $generationParams = null,
        public array $categoryIds = [],
        public array $tagIds = [],
        public array $suggestedTags = [],
        public array $suggestedCategories = [],
        public array $internalLinks = [],
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'slug' => $this->slug,
            'featured_image' => $this->featuredImage,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'focus_keyword' => $this->focusKeyword,
            'og_meta' => $this->ogMeta,
            'social_sharing' => $this->socialSharing,
            'status' => $this->status,
            'published_at' => $this->publishedAt,
            'scheduled_at' => $this->scheduledAt,
            'reading_time' => $this->readingTime,
            'is_featured' => $this->isFeatured,
            'ai_provider' => $this->aiProvider,
            'generation_params' => $this->generationParams,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'],
            content: $data['content'],
            excerpt: $data['excerpt'] ?? null,
            slug: $data['slug'] ?? null,
            featuredImage: $data['featured_image'] ?? null,
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            focusKeyword: $data['focus_keyword'] ?? null,
            ogMeta: $data['og_meta'] ?? null,
            socialSharing: $data['social_sharing'] ?? null,
            status: $data['status'] ?? 'draft',
            publishedAt: isset($data['published_at']) ? new \DateTime($data['published_at']) : null,
            scheduledAt: isset($data['scheduled_at']) ? new \DateTime($data['scheduled_at']) : null,
            readingTime: $data['reading_time'] ?? null,
            isFeatured: $data['is_featured'] ?? false,
            aiProvider: $data['ai_provider'] ?? null,
            generationParams: $data['generation_params'] ?? null,
            categoryIds: $data['category_ids'] ?? [],
            tagIds: $data['tag_ids'] ?? [],
            suggestedTags: $data['suggested_tags'] ?? [],
            suggestedCategories: $data['suggested_categories'] ?? [],
            internalLinks: $data['internal_links'] ?? [],
        );
    }
}
