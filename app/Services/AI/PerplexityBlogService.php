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
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->timeout(180)->post('https://api.perplexity.ai/chat/completions', [
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

            if (! $response->successful()) {
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

                throw new \RuntimeException('Perplexity API error: '.$errorMessage);
            }

            $this->trackUsage($response, 'perplexity', $this->model, 'blog-generation', true, $durationMs);

            $rawContent = $response->json('choices.0.message.content');

            // Perplexity may return content with markdown code blocks
            $content = $this->extractJson($rawContent);
            $data = json_decode($content, true);

            // Perplexity routinely pretty-prints its JSON response with raw
            // newlines inside body string values, which is invalid JSON.
            // If the first decode fails, escape unescaped control chars
            // inside string contexts and retry once.
            if (json_last_error() !== JSON_ERROR_NONE) {
                $firstError = json_last_error_msg();
                $repaired = $this->escapeControlCharsInsideStrings($content);
                $data = json_decode($repaired, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $dumpPath = $this->dumpRawResponse($rawContent, $content, $repaired);
                    $finishReason = $response->json('choices.0.finish_reason');
                    $truncated = $this->looksTruncated($content) || $finishReason === 'length';

                    Log::error('Perplexity returned unparseable JSON (after repair)', [
                        'topic' => $request->topic,
                        'finish_reason' => $finishReason,
                        'looks_truncated' => $truncated,
                        'response_bytes' => strlen($content),
                        'json_error_initial' => $firstError,
                        'json_error_after_repair' => json_last_error_msg(),
                        'raw_dump_path' => $dumpPath,
                        'extracted_preview' => substr($content, 0, 500),
                        'repaired_preview' => substr($repaired, 0, 500),
                    ]);

                    if ($truncated) {
                        throw new \RuntimeException(
                            'Perplexity response was truncated (finish_reason='.($finishReason ?? 'unknown').
                            '). Raise max_tokens in config/blog.php or shorten the requested length.'
                        );
                    }

                    throw new \RuntimeException(
                        'Perplexity returned unparseable JSON: '.json_last_error_msg()
                    );
                }
            }

            // Sanity check: a valid response must at least have a non-empty
            // title and body. Otherwise downstream code creates a corrupted
            // post with the JSON envelope as the body (the bug that produced
            // posts #629-#631 on 2026-05-19).
            $title = trim((string) ($data['title'] ?? ''));
            $body = trim((string) ($data['body'] ?? ''));
            if ($title === '' || $body === '') {
                Log::error('Perplexity JSON parsed but missing title/body', [
                    'topic' => $request->topic,
                    'has_title' => $title !== '',
                    'has_body' => $body !== '',
                    'keys_returned' => is_array($data) ? array_keys($data) : 'not_array',
                ]);

                throw new \RuntimeException(
                    'Perplexity response missing required title or body field.'
                );
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
                'Authorization' => 'Bearer '.$this->apiKey,
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

            if (! $response->successful()) {
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
3. Have a compelling hook in the introduction — mention the target keyword in the first paragraph
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
15. Include 3-5 internal links to Topping Africa category pages or sections. Use this format: <a href='https://toppingafrica.com/category/CATEGORY-SLUG'>Category Name</a>
16. Include 2-3 external links to authoritative sources. Use this format: <a href='URL' target='_blank' rel='noopener noreferrer'>Source Name</a>
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
        $creatorSection = $request->additionalContext['creator_prompt'] ?? '';
        $editorialSection = $request->additionalContext['editorial_guidance'] ?? '';
        $ideaContext = trim((string) ($request->additionalContext['idea_context'] ?? ''));
        $ideaContextSection = $ideaContext !== '' ? "\nAdditional context about this topic:\n{$ideaContext}\n" : '';

        return <<<PROMPT
Generate a comprehensive blog post with the following requirements:

Topic: {$request->topic}
Target Length: approximately {$wordCount} words
Tone: {$tone}
Target Keyword (if any): {$request->targetKeyword}
{$ideaContextSection}{$trendingSection}{$creatorSection}{$editorialSection}{$typeSection}
Use your online search capabilities to include the latest information and trends about this topic, particularly as they relate to Africa.

SEO CHECKLIST — ensure the body content includes ALL of the following:
- Target keyword in the first paragraph and in at least 2 H2/H3 headings
- At least 4 subheadings (H2/H3)
- Bold (<strong>), italic (<em>), AND list (<ul>/<ol>) formatting all present
- 25%+ of sentences start with transition words
- 3-5 internal links to Topping Africa categories
- 2-3 external links to authoritative sources (with target='_blank' rel='noopener noreferrer')
- 2+ CTA phrases (explore, discover, share, comment, read more, subscribe)
- Short paragraphs (3-5 sentences) and short sentences (15-20 words avg)

CRITICAL JSON RULES — violations break parsing:
- Inside the "body" string, use SINGLE QUOTES for ALL HTML attributes, never double quotes.
  CORRECT:  <a href='https://example.com' rel='noopener'>text</a>
  WRONG:    <a href="https://example.com" rel="noopener">text</a>
- Escape any literal double quote inside JSON strings as \" (e.g. "She said \"hello\"")
- Do not wrap the response in markdown code fences. Return raw JSON only.

Return a JSON object with these exact keys (no markdown, just JSON):
{
    "title": "SEO-optimized blog post title",
    "body": "The full blog post content as clean HTML (use <h2>, <h3>, <p>, <ul>, <ol>, <li>, <strong>, <em>, <a> tags — NO markdown)",
    "excerpt": "Compelling 2-3 sentence excerpt for preview",
    "meta_title": "SEO meta title (MUST be 50-60 characters, include the focus keyword)",
    "meta_description": "SEO meta description (MUST be 150-160 characters, include the focus keyword, compelling and click-worthy)",
    "focus_keyword": "Primary focus keyword",
    "tags": ["tag1", "tag2", "tag3", "tag4", "tag5", "tag6", "tag7", "tag8", "tag9", "tag10"],
    "categories": ["Category1", "Category2"],
    "internal_link_topics": ["Related topic 1", "Related topic 2", "Related topic 3"],
    "featured_creator_slugs": ["creator-slug-1", "creator-slug-2"],
    "featured_image_query": "A short, specific image search query that would find a strong hero photo for this article. Be concrete (e.g. 'Burna Boy at AFCON 2026 trophy ceremony') rather than generic ('African music'). Always include the most specific named subject or event from the article. Avoid copyrighted character names; prefer real people, places, events, products.",
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

        // Find the outermost balanced JSON object by walking braces while
        // respecting string boundaries and escape sequences. The previous
        // greedy regex (`\{[\s\S]*\}`) could grab past the actual JSON when
        // the model echoed example braces in the body content, producing
        // un-parseable input that fell through to the unstructured fallback.
        $start = strpos($content, '{');
        if ($start === false) {
            return $content;
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($content);

        for ($i = $start; $i < $length; $i++) {
            $char = $content[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
            }
        }

        // Unbalanced — return as-is so json_decode reports the actual error
        // and the unstructured fallback handles it explicitly.
        return $content;
    }

    /**
     * Heuristic for "Perplexity stopped generating mid-JSON" — usually
     * because the response hit max_tokens. Walks the JSON state machine
     * once and returns true if it ends with unclosed braces or an
     * unterminated string.
     */
    private function looksTruncated(string $content): bool
    {
        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($content);

        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
            }
        }

        return $depth !== 0 || $inString;
    }

    /**
     * Dump a failing Perplexity response to a debug file so we can inspect
     * the exact bytes causing the parse failure. Returns the relative path
     * within storage/ for inclusion in error logs.
     */
    private function dumpRawResponse(string $raw, string $extracted, string $repaired): string
    {
        $dir = storage_path('logs/agent-failures');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $filename = sprintf('%s/%s-%s.txt', $dir, date('Ymd-His'), bin2hex(random_bytes(3)));

        $payload = "=== RAW Perplexity content ===\n".$raw
            ."\n\n=== EXTRACTED (after extractJson) ===\n".$extracted
            ."\n\n=== REPAIRED (after escapeControlCharsInsideStrings) ===\n".$repaired;

        @file_put_contents($filename, $payload);

        return str_replace(storage_path(), 'storage', $filename);
    }

    /**
     * Replace raw control characters (newline, carriage return, tab, etc.)
     * inside JSON string contexts with their escape sequences so json_decode
     * accepts pretty-printed responses. Characters outside strings — including
     * the newlines between key/value pairs — are preserved.
     */
    private function escapeControlCharsInsideStrings(string $content): string
    {
        $out = '';
        $inString = false;
        $escape = false;
        $length = strlen($content);

        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];

            if ($escape) {
                $out .= $char;
                $escape = false;
                continue;
            }

            if ($char === '\\') {
                $out .= $char;
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;
                $out .= $char;
                continue;
            }

            if ($inString) {
                $out .= match ($char) {
                    "\n" => '\\n',
                    "\r" => '\\r',
                    "\t" => '\\t',
                    "\f" => '\\f',
                    "\b" => '\\b',
                    default => ord($char) < 0x20
                        ? sprintf('\\u%04x', ord($char))
                        : $char,
                };
                continue;
            }

            $out .= $char;
        }

        return $out;
    }

    private function parseUnstructuredResponse(string $content, PostGenerationRequest $request): array
    {
        // Fallback parsing for non-JSON responses
        return [
            'title' => $request->topic,
            'body' => $content,
            'excerpt' => substr(strip_tags($content), 0, 160).'...',
            'meta_title' => substr($request->topic, 0, 60),
            'meta_description' => substr(strip_tags($content), 0, 160),
            'focus_keyword' => strtolower(explode(' ', $request->topic)[0] ?? ''),
            'tags' => [],
            'categories' => [],
            'internal_link_topics' => [],
            'featured_creator_slugs' => [],
            'featured_image_query' => '',
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
            'featured_creator_slugs' => array_values(array_filter(
                $data['featured_creator_slugs'] ?? [],
                fn ($s) => is_string($s) && $s !== ''
            )),
            'featured_image_query' => trim((string) ($data['featured_image_query'] ?? '')),
        ];
    }
}
