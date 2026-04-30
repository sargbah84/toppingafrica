<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\DataTransferObjects\Blog\PostData;
use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\DataTransferObjects\Blog\SocialSharingData;
use App\Repositories\CategoryRepository;
use App\Services\AI\OpenAIBlogService;
use App\Services\AI\PerplexityBlogService;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class PostGeneratorService
{
    public function __construct(
        private readonly OpenAIBlogService $openAiService,
        private readonly PerplexityBlogService $perplexityService,
        private readonly ReadingTimeCalculator $readingTimeCalculator,
        private readonly InternalLinkSuggester $linkSuggester,
        private readonly SocialSharingGenerator $sharingGenerator,
        private readonly CategoryRepository $categoryRepository,
    ) {}

    public function generate(PostGenerationRequest $request): PostData
    {
        $service = match ($request->aiProvider) {
            'openai' => $this->openAiService,
            'perplexity' => $this->perplexityService,
            default => throw new InvalidArgumentException('Invalid AI provider: '.$request->aiProvider),
        };

        // Generate content using AI
        $content = $service->generateBlogPost($request);

        // Calculate reading time
        $readingTime = $this->readingTimeCalculator->calculate($content['body']);

        // Suggest internal links
        $internalLinks = $this->linkSuggester->suggest($content['body'], $request->topic);

        // Generate social sharing text
        $socialSharing = $this->sharingGenerator->generate(
            $content['title'],
            $content['excerpt'],
            $content['tags']
        );

        // Match suggested categories to actual category IDs
        $categoryIds = $this->matchCategoriesToIds($content['categories']);

        return new PostData(
            title: $content['title'],
            content: $content['body'],
            excerpt: $content['excerpt'],
            slug: Str::slug($content['title']),
            metaTitle: $content['meta_title'],
            metaDescription: $content['meta_description'],
            focusKeyword: $content['focus_keyword'],
            ogMeta: [
                'title' => $content['meta_title'],
                'description' => $content['meta_description'],
                'type' => 'article',
            ],
            socialSharing: $socialSharing->toArray(),
            readingTime: $readingTime,
            aiProvider: $request->aiProvider,
            generationParams: [
                'length' => $request->length,
                'tone' => $request->tone,
                'target_keyword' => $request->targetKeyword,
            ],
            categoryIds: $categoryIds,
            suggestedTags: $content['tags'],
            suggestedCategories: $content['categories'],
            internalLinks: $internalLinks,
            postType: $request->postType,
            typeData: $content['type_data'] ?? [],
        );
    }

    public function regenerateSocialSharing(string $title, string $excerpt, string $provider = 'openai'): SocialSharingData
    {
        $service = match ($provider) {
            'openai' => $this->openAiService,
            'perplexity' => $this->perplexityService,
            default => throw new InvalidArgumentException('Invalid AI provider'),
        };

        $result = $service->generateSocialSharing($title, $excerpt);

        return new SocialSharingData(
            twitter: $result['twitter'] ?? '',
            linkedin: $result['linkedin'] ?? '',
        );
    }

    public function getAvailableProviders(): array
    {
        $providers = [];

        if (config('blog.ai.providers.openai.enabled')) {
            $providers['openai'] = 'OpenAI GPT-4';
        }

        if (config('blog.ai.providers.perplexity.enabled')) {
            $providers['perplexity'] = 'Perplexity (with live search)';
        }

        return $providers;
    }

    public function getLengthOptions(): array
    {
        return [
            'short' => 'Short (~800 words)',
            'medium' => 'Medium (~1,500 words)',
            'long' => 'Long (~2,500+ words)',
        ];
    }

    public function getToneOptions(): array
    {
        return config('blog.ai.tones', []);
    }

    private function matchCategoriesToIds(array $categoryNames): array
    {
        $ids = [];

        foreach ($categoryNames as $name) {
            $category = $this->categoryRepository->findBySlug(Str::slug($name));
            if ($category) {
                $ids[] = $category->id;
            }
        }

        return $ids;
    }
}
