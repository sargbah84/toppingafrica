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
use League\CommonMark\CommonMarkConverter;

final class PostGeneratorService
{
    public function __construct(
        private readonly OpenAIBlogService $openAiService,
        private readonly PerplexityBlogService $perplexityService,
        private readonly ReadingTimeCalculator $readingTimeCalculator,
        private readonly InternalLinkSuggester $linkSuggester,
        private readonly SocialSharingGenerator $sharingGenerator,
        private readonly CategoryRepository $categoryRepository,
        private readonly CreatorContextService $creatorContextService,
    ) {}

    public function generate(PostGenerationRequest $request): PostData
    {
        $service = match ($request->aiProvider) {
            'openai' => $this->openAiService,
            'perplexity' => $this->perplexityService,
            default => throw new InvalidArgumentException('Invalid AI provider: '.$request->aiProvider),
        };

        // Inject creator context into the request if creator IDs were provided
        if (! empty($request->creatorIds)) {
            $creators = $this->creatorContextService->getCreatorContext($request->creatorIds);
            if (! empty($creators)) {
                $creatorPrompt = $this->creatorContextService->buildCreatorPromptSection($creators);
                $additionalContext = array_merge($request->additionalContext, [
                    'creator_prompt' => $creatorPrompt,
                    'creators' => $creators,
                ]);
                $request = new PostGenerationRequest(
                    topic: $request->topic,
                    aiProvider: $request->aiProvider,
                    length: $request->length,
                    tone: $request->tone,
                    targetKeyword: $request->targetKeyword,
                    postType: $request->postType,
                    additionalContext: $additionalContext,
                    creatorIds: $request->creatorIds,
                );
            }
        }

        // Generate content using AI
        $content = $service->generateBlogPost($request);

        // Convert markdown to HTML if the AI returned markdown instead of HTML
        $content['body'] = $this->ensureHtml($content['body']);

        // Sanitize field lengths to prevent DB truncation errors
        $content['title'] = Str::limit($content['title'] ?? '', 250, '');
        $content['excerpt'] = Str::limit($content['excerpt'] ?? '', 500, '');
        $content['meta_title'] = Str::limit($content['meta_title'] ?? '', 255, '');
        $content['meta_description'] = Str::limit($content['meta_description'] ?? '', 255, '');
        $content['focus_keyword'] = Str::limit($content['focus_keyword'] ?? '', 255, '');

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
            slug: Str::limit(Str::slug($content['title']), 250, ''),
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

    /**
     * Convert markdown to HTML if the AI returned markdown instead of HTML.
     * Detects markdown by checking for common patterns like ## headings or **bold**.
     */
    private function ensureHtml(string $body): string
    {
        // If body already contains block-level HTML tags, it's likely HTML
        if (preg_match('/<(h[1-6]|p|ul|ol|div|table|blockquote)\b/i', $body)) {
            return $body;
        }

        // Markdown indicators: headings, bold, unordered lists, links
        $hasMarkdown = preg_match('/^#{1,6}\s/m', $body)
            || preg_match('/\*\*.+?\*\*/', $body)
            || preg_match('/^\s*[-*]\s/m', $body)
            || preg_match('/\[.+?\]\(.+?\)/', $body);

        if (! $hasMarkdown) {
            return $body;
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($body)->getContent();
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
