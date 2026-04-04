<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Services\AI\Concerns\TracksAiUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PerplexityBlogService implements BlogGeneratorInterface
{
    use TracksAiUsage;

    private string $apiKey;
    private string $model;
    private int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('blog.ai.providers.perplexity.api_key', '');
        $this->model = config('blog.ai.providers.perplexity.model', 'sonar-pro');
        $this->maxTokens = config('blog.ai.providers.perplexity.max_tokens', 4000);
    }

    private function validateApiKey(): void
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Perplexity API key is not configured. Please add PERPLEXITY_API_KEY to your .env file.');
        }
    }

    public function generateBlogPost(PostGenerationRequest $request): array
    {
        $this->validateApiKey();

        $prompt = $this->buildBlogPrompt($request);

        try {
            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(120)->post('https://api.perplexity.ai/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => $this->maxTokens,
                'temperature' => 0.7,
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $this->trackUsage($response, 'perplexity', $this->model, 'blog-generation', false, $durationMs, $response->body());
                $errorBody = $response->json() ?? [];
                $errorMessage = $errorBody['error']['message'] ?? $response->body();

                Log::error('Perplexity API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                if ($response->status() === 401) {
                    $isQuotaError = str_contains($errorMessage, 'quota') || str_contains($errorMessage, 'billing');
                    if ($isQuotaError) {
                        throw new \RuntimeException('Perplexity API quota exceeded. Please check your plan and billing details at perplexity.ai/settings/api.');
                    }
                    throw new \RuntimeException('Invalid Perplexity API key. Please check your PERPLEXITY_API_KEY in .env file.');
                }

                if ($response->status() === 429) {
                    throw new \RuntimeException('Perplexity API rate limit exceeded. Please try again later.');
                }

                throw new \RuntimeException('Perplexity API error: ' . $errorMessage);
            }

            $this->trackUsage($response, 'perplexity', $this->model, 'blog-generation', true, $durationMs);

            $content = $response->json('choices.0.message.content');

            // Perplexity may return content with markdown code blocks
            $content = $this->extractJson($content);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to parse as structured content
                return $this->parseUnstructuredResponse($response->json('choices.0.message.content'), $request);
            }

            return $this->normalizeResponse($data);
        } catch (\Exception $e) {
            Log::error('Perplexity blog generation failed', [
                'error' => $e->getMessage(),
                'topic' => $request->topic,
            ]);
            throw $e;
        }
    }

    public function generateSocialSharing(string $title, string $excerpt): array
    {
        $this->validateApiKey();

        $prompt = $this->buildSocialPrompt($title, $excerpt);

        try {
            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(60)->post('https://api.perplexity.ai/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a social media expert specializing in African news and content. Generate engaging social media posts. Return JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 500,
                'temperature' => 0.8,
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if (!$response->successful()) {
                $this->trackUsage($response, 'perplexity', $this->model, 'blog-social-sharing', false, $durationMs, $response->body());
                throw new \RuntimeException('Failed to generate social sharing posts');
            }

            $this->trackUsage($response, 'perplexity', $this->model, 'blog-social-sharing', true, $durationMs);

            $content = $this->extractJson($response->json('choices.0.message.content'));
            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::error('Perplexity social sharing generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getProviderName(): string
    {
        return 'perplexity';
    }

    private function getSystemPrompt(): string
    {
        $categories = implode(', ', array_column(config('blog.default_categories', []), 'name'));

        return <<<PROMPT
You are an expert SEO content writer for Topping Africa, a leading African news, entertainment, business, technology, and culture magazine.

Your content should:
1. Be optimized for SEO with natural keyword integration
2. Include up-to-date information and current trends (use your online search capabilities)
3. Have a compelling hook in the introduction
4. Use clear H2 and H3 headings for structure
5. Include actionable insights and analysis relevant to African audiences
6. Be engaging and valuable for readers interested in African affairs
7. Naturally reference 1-3 relevant Topping Africa categories/sections within the body content, linking to category pages where appropriate. Use this format: <a href="https://toppingafrica.com/category/CATEGORY-SLUG">Category Name</a>
8. Include a short "Explore More on Topping Africa" CTA section near the end of the post (before the conclusion), listing 2-3 relevant categories with brief descriptions and links.
9. IMPORTANT — Listicle / comparison posts: If the post is a list (e.g., "Top 10 African Tech Startups", "Best African Music Festivals"), ensure entries are well-researched and relevant to the African context.

AVAILABLE TOPPING AFRICA SECTIONS (use these for internal references):
- Africa News: https://toppingafrica.com/category/africa-news
- Entertainment: https://toppingafrica.com/category/entertainment
- Business & Economy: https://toppingafrica.com/category/business
- Technology: https://toppingafrica.com/category/technology
- Sports: https://toppingafrica.com/category/sports
- Health & Wellness: https://toppingafrica.com/category/health
- Politics & Governance: https://toppingafrica.com/category/politics
- Culture & Lifestyle: https://toppingafrica.com/category/culture
- Opinion & Editorial: https://toppingafrica.com/category/opinion

Available categories to choose from: {$categories}

IMPORTANT: Return your response as valid JSON only, no markdown code blocks.
PROMPT;
    }

    private function buildBlogPrompt(PostGenerationRequest $request): string
    {
        $wordCount = $request->getWordCount();
        $tone = $request->getToneDescription();
        $categories = implode(', ', array_column(config('blog.default_categories', []), 'name'));

        $trendingSection = $this->buildTrendingKeywordsSection($request);
        $typeSection = $this->buildPostTypeSection($request);

        return <<<PROMPT
Generate a comprehensive blog post with the following requirements:

Topic: {$request->topic}
Target Length: approximately {$wordCount} words
Tone: {$tone}
Target Keyword (if any): {$request->targetKeyword}
{$trendingSection}{$typeSection}
Use your online search capabilities to include the latest information and trends about this topic, particularly as they relate to Africa.

Return a JSON object with these exact keys (no markdown, just JSON):
{
    "title": "SEO-optimized blog post title",
    "body": "Brief introduction text (1-2 paragraphs) for context",
    "excerpt": "Compelling 2-3 sentence excerpt for preview",
    "meta_title": "SEO meta title (max 60 characters)",
    "meta_description": "SEO meta description (155-160 characters)",
    "focus_keyword": "Primary focus keyword",
    "tags": ["tag1", "tag2", "tag3", "tag4", "tag5"],
    "categories": ["Category1", "Category2"],
    "internal_link_topics": ["Related topic 1", "Related topic 2"],
    "type_data": {}
}

Available categories: {$categories}
PROMPT;
    }

    private function buildPostTypeSection(PostGenerationRequest $request): string
    {
        return match ($request->postType) {
            'quiz' => <<<'QUIZ'

POST TYPE: Quiz
Generate an interactive quiz. The "body" should be a brief intro (1-2 paragraphs).
The "type_data" must contain:
{
    "questions": [
        {
            "question": "Question text?",
            "answers": [
                {"text": "Option A", "is_correct": false},
                {"text": "Option B", "is_correct": true},
                {"text": "Option C", "is_correct": false},
                {"text": "Option D", "is_correct": false}
            ],
            "explanation": "Why the correct answer is correct."
        }
    ],
    "passing_score": 60,
    "show_answers": true
}
Generate 5-8 quiz questions with 4 answer options each. Only ONE answer should be correct per question.
QUIZ,
            'trivia' => <<<'TRIVIA'

POST TYPE: Trivia
Generate a collection of fascinating trivia facts. The "body" should be a brief intro (1-2 paragraphs).
The "type_data" must contain:
{
    "facts": [
        {"text": "An interesting fact about the topic.", "source": "Source name or publication"}
    ],
    "source_url": ""
}
Generate 8-12 surprising, well-researched trivia facts with sources.
TRIVIA,
            'poll' => <<<'POLL'

POST TYPE: Poll
Generate an opinion poll. The "body" should be a brief intro explaining the poll context (1-2 paragraphs).
The "type_data" must contain:
{
    "options": [
        {"text": "Option 1"},
        {"text": "Option 2"},
        {"text": "Option 3"}
    ],
    "allow_multiple": false,
    "show_results_before_vote": false,
    "ends_at": null
}
Generate 4-8 compelling poll options that cover different perspectives. Options should be concise and clear.
POLL,
            default => '',
        };
    }

    private function buildTrendingKeywordsSection(PostGenerationRequest $request): string
    {
        $trendingKeywords = $request->additionalContext['trending_keywords'] ?? [];

        if (empty($trendingKeywords)) {
            return '';
        }

        $lines = [];
        foreach (array_slice($trendingKeywords, 0, 10) as $trend) {
            $keyword = $trend['keyword'] ?? '';
            $type = $trend['type'] ?? 'top';
            $value = $trend['formatted_value'] ?? $trend['value'] ?? '';
            if (!empty($keyword)) {
                $lines[] = "- {$keyword} ({$type}, score: {$value})";
            }
        }

        if (empty($lines)) {
            return '';
        }

        $list = implode("\n", $lines);

        return <<<TRENDS

TRENDING KEYWORDS FROM GOOGLE TRENDS (incorporate these into the article):
{$list}

Instructions for trending keywords:
- Naturally weave relevant trending keywords into the article content
- Include at least 3-5 trending keywords as tags in the "tags" array
- Use trending keywords in subheadings where they fit naturally
- Do NOT force keywords where they don't make sense
TRENDS;
    }

    private function buildSocialPrompt(string $title, string $excerpt): string
    {
        return <<<PROMPT
Generate social media posts to promote this blog article from Topping Africa:

Title: {$title}
Excerpt: {$excerpt}

Return a JSON object with these exact keys (no markdown):
{
    "twitter": "Twitter post (max 280 chars, include 2-3 relevant hashtags related to Africa)",
    "linkedin": "LinkedIn post (150-200 words, professional tone, engaging hook, focused on African business/news context)"
}

Make them compelling and drive clicks to the article.
PROMPT;
    }

    private function extractJson(string $content): string
    {
        // Remove markdown code blocks if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            return trim($matches[1]);
        }

        // Try to find JSON object
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            return $matches[0];
        }

        return $content;
    }

    private function parseUnstructuredResponse(string $content, PostGenerationRequest $request): array
    {
        // Fallback parsing for non-JSON responses
        return [
            'title' => $request->topic,
            'body' => $content,
            'excerpt' => substr(strip_tags($content), 0, 160) . '...',
            'meta_title' => substr($request->topic, 0, 60),
            'meta_description' => substr(strip_tags($content), 0, 160),
            'focus_keyword' => strtolower(explode(' ', $request->topic)[0] ?? ''),
            'tags' => [],
            'categories' => [],
            'internal_link_topics' => [],
        ];
    }

    private function normalizeResponse(array $data): array
    {
        return [
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'excerpt' => $data['excerpt'] ?? '',
            'meta_title' => $data['meta_title'] ?? '',
            'meta_description' => $data['meta_description'] ?? '',
            'focus_keyword' => $data['focus_keyword'] ?? '',
            'tags' => array_slice($data['tags'] ?? [], 0, 10),
            'categories' => array_slice($data['categories'] ?? [], 0, 3),
            'internal_link_topics' => $data['internal_link_topics'] ?? [],
        ];
    }
}
