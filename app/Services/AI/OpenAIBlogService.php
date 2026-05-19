<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DataTransferObjects\Blog\PostGenerationRequest;
use App\Services\AI\Concerns\TracksAiUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class OpenAIBlogService implements BlogGeneratorInterface
{
    use TracksAiUsage;

    private string $apiKey;

    private string $model;

    private int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $this->model = config('blog.ai.providers.openai.model', 'gpt-4o');
        $this->maxTokens = config('blog.ai.providers.openai.max_tokens', 4000);
    }

    public function generateBlogPost(PostGenerationRequest $request): array
    {
        $prompt = $this->buildBlogPrompt($request);

        try {
            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(180)->post('https://api.openai.com/v1/chat/completions', [
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
                'response_format' => ['type' => 'json_object'],
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                $this->trackUsage($response, 'openai', $this->model, 'blog-generation', false, $durationMs, $response->body());
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \RuntimeException('Failed to generate blog post');
            }

            $this->trackUsage($response, 'openai', $this->model, 'blog-generation', true, $durationMs);

            $content = $response->json('choices.0.message.content');
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON response from OpenAI');
            }

            return $this->normalizeResponse($data);
        } catch (\Exception $e) {
            Log::error('OpenAI blog generation failed', [
                'error' => $e->getMessage(),
                'topic' => $request->topic,
            ]);
            throw $e;
        }
    }

    public function generateSocialSharing(string $title, string $excerpt): array
    {
        $prompt = $this->buildSocialPrompt($title, $excerpt);

        try {
            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
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
                'response_format' => ['type' => 'json_object'],
            ]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if (! $response->successful()) {
                $this->trackUsage($response, 'openai', $this->model, 'blog-social-sharing', false, $durationMs, $response->body());
                throw new \RuntimeException('Failed to generate social sharing posts');
            }

            $this->trackUsage($response, 'openai', $this->model, 'blog-social-sharing', true, $durationMs);

            $content = $response->json('choices.0.message.content');

            return json_decode($content, true) ?? [];
        } catch (\Exception $e) {
            Log::error('OpenAI social sharing generation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    private function getSystemPrompt(): string
    {
        $categories = implode(', ', array_column(config('blog.default_categories', []), 'name'));

        return <<<PROMPT
You are an expert SEO content writer for Topping Africa, a leading African news, entertainment, business, technology, and culture magazine.

Your content should:
1. Be optimized for SEO with natural keyword integration
2. Have a compelling hook in the introduction — mention the target keyword in the first paragraph
3. Reference current events, trends, and developments across the African continent
4. CRITICAL: The "body" field MUST be formatted as clean HTML (using <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <a> tags). Do NOT use markdown syntax (no ##, **, *, -, [] etc). The content is displayed in a WYSIWYG HTML editor.
5. Include actionable insights and analysis relevant to African audiences
6. Be engaging and valuable for readers interested in African affairs

STRUCTURE & FORMATTING RULES:
7. Use at least 4 subheadings (<h2> and <h3>) to break content into clear sections. Include the target keyword in at least 2 headings.
8. Use <strong> (bold) for key terms, <em> (italic) for emphasis, and <ul>/<ol> lists — ALL THREE must be present in every article.
9. Keep paragraphs short: 3-5 sentences maximum per <p> tag. Break longer paragraphs.
10. Keep sentences concise: average 15-20 words per sentence. Break any sentence longer than 25 words into two.

READABILITY RULES:
11. Start 25%+ of sentences with transition words (However, Furthermore, Additionally, Moreover, Meanwhile, Consequently, In addition, As a result, On the other hand, For instance, Similarly, Therefore, Nevertheless, Ultimately, Notably, Importantly, Specifically, In particular).
12. Use simple, everyday language. Avoid academic or complex words (3+ syllables) where simpler alternatives exist. Target <15% complex words.
13. Write in active voice. Avoid passive constructions (e.g., "was created by" → "created"). Target <15% passive voice.
14. Aim for a Flesch Reading Ease score of 60-80 (easily understood by a general audience).

LINKS & CTAs:
15. Include 3-5 internal links to Topping Africa category pages or sections. Use this format: <a href="https://toppingafrica.com/category/CATEGORY-SLUG">Category Name</a>
16. Include 2-3 external links to authoritative sources. Use this format: <a href="URL" target="_blank" rel="noopener noreferrer">Source Name</a>
17. Include at least 2 different CTA phrases throughout the article (e.g., "Explore more", "Discover", "Read more about", "Share your thoughts", "Subscribe", "Leave a comment below").
18. Include a short "Explore More on Topping Africa" CTA section near the end of the post (before the conclusion), listing 2-3 relevant categories with brief descriptions and links.

CONTENT RULES:
19. IMPORTANT — Listicle / comparison posts: If the post is a list (e.g., "Top 10 African Tech Startups", "Best African Music Festivals"), ensure entries are well-researched and relevant to the African context.
20. CREATOR PROFILES: When creator profile data is provided, use ONLY the supplied data — do NOT fabricate details. Link to their Topping Africa profile pages using: <a href="PROFILE_URL">Creator Name</a>. Creator profile links count as valuable internal links.

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

Always return valid JSON with the exact structure specified.
PROMPT;
    }

    private function buildBlogPrompt(PostGenerationRequest $request): string
    {
        $wordCount = $request->getWordCount();
        $tone = $request->getToneDescription();
        $categories = implode(', ', array_column(config('blog.default_categories', []), 'name'));

        $trendingSection = $this->buildTrendingKeywordsSection($request);
        $typeSection = $this->buildPostTypeSection($request);
        $creatorSection = $request->additionalContext['creator_prompt'] ?? '';
        $editorialSection = $request->additionalContext['editorial_guidance'] ?? '';

        return <<<PROMPT
Generate a comprehensive blog post with the following requirements:

Topic: {$request->topic}
Target Length: approximately {$wordCount} words
Tone: {$tone}
Target Keyword (if any): {$request->targetKeyword}
{$trendingSection}{$creatorSection}{$editorialSection}{$typeSection}
SEO CHECKLIST — ensure the body content includes ALL of the following:
- Target keyword in the first paragraph and in at least 2 H2/H3 headings
- At least 4 subheadings (H2/H3)
- Bold (<strong>), italic (<em>), AND list (<ul>/<ol>) formatting all present
- 25%+ of sentences start with transition words
- 3-5 internal links to Topping Africa categories
- 2-3 external links to authoritative sources (with target="_blank" rel="noopener noreferrer")
- 2+ CTA phrases (explore, discover, share, comment, read more, subscribe)
- Short paragraphs (3-5 sentences) and short sentences (15-20 words avg)

Return a JSON object with these exact keys:
{
    "title": "SEO-optimized blog post title",
    "body": "The full blog post content as clean HTML (use <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <a> tags — NO markdown)",
    "excerpt": "Compelling 2-3 sentence excerpt for preview",
    "meta_title": "SEO meta title (MUST be 50-60 characters, include the focus keyword)",
    "meta_description": "SEO meta description (MUST be 150-160 characters, include the focus keyword, compelling and click-worthy)",
    "focus_keyword": "Primary focus keyword",
    "tags": ["tag1", "tag2", "tag3", "tag4", "tag5", "tag6", "tag7", "tag8", "tag9", "tag10"],
    "categories": ["Category1", "Category2", "Category3"],
    "internal_link_topics": ["Related topic 1", "Related topic 2", "Related topic 3"],
    "featured_creator_slugs": ["creator-slug-1", "creator-slug-2"],
    "featured_image_query": "A short, specific image search query that would find a strong hero photo for this article. Be concrete (e.g. 'Burna Boy at AFCON 2026 trophy ceremony') rather than generic ('African music'). Always include the most specific named subject or event from the article. Avoid copyrighted character names; prefer real people, places, events, products.",
    "type_data": {}
}

Available categories to choose from: {$categories}
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
            if (! empty($keyword)) {
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

Return a JSON object with these exact keys:
{
    "twitter": "Twitter post (max 280 chars, include 2-3 relevant hashtags related to Africa)",
    "linkedin": "LinkedIn post (150-200 words, professional tone, engaging hook, focused on African business/news context)"
}

Make them compelling and drive clicks to the article.
PROMPT;
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
            'featured_creator_slugs' => array_values(array_filter(
                $data['featured_creator_slugs'] ?? [],
                fn ($s) => is_string($s) && $s !== ''
            )),
            'featured_image_query' => trim((string) ($data['featured_image_query'] ?? '')),
        ];
    }
}
