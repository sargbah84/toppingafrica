<?php

declare(strict_types=1);

namespace App\Services\Blog;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TitleSuggestionService
{
    private array $niches = [
        'africa-news' => 'African current affairs, breaking news, continental developments, pan-African issues',
        'entertainment' => 'African entertainment, Afrobeats, Nollywood, music, film, celebrity news, festivals',
        'business' => 'African business, economy, startups, investment, trade, fintech, entrepreneurship',
        'technology' => 'African technology, tech startups, digital transformation, innovation, AI in Africa',
        'sports' => 'African sports, AFCON, Premier League African players, athletics, football, rugby',
        'health' => 'African health, wellness, medical breakthroughs, disease prevention, healthcare access',
        'politics' => 'African politics, governance, elections, democracy, policy, leadership, diplomacy',
    ];

    public function getSuggestedTitles(string $niche = 'africa-news', int $count = 10): array
    {
        // Cache titles per niche - persists indefinitely until manually refreshed
        $cacheKey = "blog_title_suggestions_{$niche}";

        // Check if we have cached titles first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Generate and cache forever (until manually refreshed)
        $titles = $this->generateTitles($niche, $count);
        Cache::forever($cacheKey, $titles);

        return $titles;
    }

    public function refreshTitles(string $niche = 'africa-news', int $count = 10): array
    {
        // Clear cache and generate fresh titles
        $cacheKey = "blog_title_suggestions_{$niche}";
        Cache::forget($cacheKey);

        // Generate new titles and cache them forever
        $titles = $this->generateTitles($niche, $count);
        Cache::forever($cacheKey, $titles);

        return $titles;
    }

    public function getCachedTitles(string $niche = 'africa-news'): ?array
    {
        $cacheKey = "blog_title_suggestions_{$niche}";
        return Cache::get($cacheKey);
    }

    /**
     * Get trending keywords for a niche.
     *
     * @return array<int, array{keyword: string, type: string, value: int}>
     */
    public function getTrendingKeywords(string $niche): array
    {
        // Placeholder - can be connected to Google Trends or other trending data services
        return [];
    }

    private function generateTitles(string $niche, int $count): array
    {
        $nicheDescription = $this->niches[$niche] ?? $this->niches['africa-news'];

        // Try Perplexity first for real-time trending data
        $titles = $this->generateWithPerplexity($nicheDescription, $count);

        if (empty($titles)) {
            // Fall back to OpenAI
            $titles = $this->generateWithOpenAI($nicheDescription, $count);
        }

        return $titles;
    }

    private function generateWithPerplexity(string $nicheDescription, int $count): array
    {
        $apiKey = config('blog.ai.providers.perplexity.api_key');

        if (empty($apiKey)) {
            return [];
        }

        try {
            $prompt = $this->buildPrompt($nicheDescription, $count);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post('https://api.perplexity.ai/chat/completions', [
                'model' => config('blog.ai.providers.perplexity.model', 'sonar-pro'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an African news and content strategist who tracks trending topics across the continent. Return ONLY a JSON array of title suggestions, no other text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                Log::error('Perplexity title suggestion failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $content = $response->json('choices.0.message.content', '');
            return $this->parseJsonResponse($content);
        } catch (\Exception $e) {
            Log::error('Perplexity title suggestion error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function generateWithOpenAI(string $nicheDescription, int $count): array
    {
        $apiKey = config('blog.ai.providers.openai.api_key', env('OPENAI_API_KEY', ''));

        if (empty($apiKey)) {
            return [];
        }

        try {
            $prompt = $this->buildPrompt($nicheDescription, $count);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->withoutVerifying()->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('blog.ai.providers.openai.model', 'gpt-4o'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an African news and content strategist. Return ONLY a JSON array of title suggestions, no other text.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                Log::error('OpenAI title suggestion failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $content = $response->json('choices.0.message.content', '');
            return $this->parseJsonResponse($content);
        } catch (\Exception $e) {
            Log::error('OpenAI title suggestion error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function buildPrompt(string $nicheDescription, int $count): string
    {
        $currentYear = date('Y');
        $currentMonth = date('F');
        $currentDate = date('F j, Y');

        return <<<PROMPT
Today's date is {$currentDate}. Generate {$count} trending and engaging blog post title ideas for {$currentMonth} {$currentYear} in the niche of: {$nicheDescription}

Context: These titles are for Topping Africa, a leading African news and magazine website covering news, entertainment, business, technology, sports, health, politics, and culture across the African continent.

Requirements:
- Titles that include a year MUST use {$currentYear} - NEVER use outdated years
- Include seasonal/timely references for {$currentMonth} {$currentYear} where appropriate
- Titles should be timely and relevant to current events and trends in Africa
- Each title should be SEO-friendly and click-worthy
- Include a mix of news analysis, listicles, opinion pieces, and feature articles
- Titles should be 50-70 characters for optimal display
- Focus on stories that matter to African and global audiences interested in Africa

Return ONLY a valid JSON array with this exact structure (no markdown, no explanation):
[
    {
        "title": "The blog post title",
        "category": "Category name like Africa News, Entertainment, Business, Technology, Sports, Health, Politics, Culture",
        "type": "how-to|listicle|guide|trends|case-study|analysis|opinion|feature"
    }
]
PROMPT;
    }

    private function parseJsonResponse(string $content): array
    {
        // Clean up the response
        $content = trim($content);

        // Remove markdown code blocks if present
        $content = preg_replace('/^```json\s*/i', '', $content);
        $content = preg_replace('/^```\s*/i', '', $content);
        $content = preg_replace('/\s*```$/i', '', $content);

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($data)) {
                return [];
            }

            // Validate and format the response
            return array_map(function ($item) {
                return [
                    'title' => $item['title'] ?? '',
                    'category' => $item['category'] ?? 'General',
                    'type' => $item['type'] ?? 'article',
                ];
            }, array_filter($data, fn ($item) => !empty($item['title'])));
        } catch (\JsonException $e) {
            Log::warning('Failed to parse title suggestions JSON', [
                'content' => substr($content, 0, 500),
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    public function getAvailableNiches(): array
    {
        return [
            'africa-news' => 'Africa News',
            'entertainment' => 'Entertainment',
            'business' => 'Business & Economy',
            'technology' => 'Technology',
            'sports' => 'Sports',
            'health' => 'Health & Wellness',
            'politics' => 'Politics & Governance',
        ];
    }

    public function markTitleAsUsed(string $title): void
    {
        $usedTitles = $this->getUsedTitles();
        $normalizedTitle = $this->normalizeTitle($title);

        if (!in_array($normalizedTitle, $usedTitles, true)) {
            $usedTitles[] = $normalizedTitle;
            Cache::forever('blog_used_titles', $usedTitles);
        }
    }

    public function getUsedTitles(): array
    {
        return Cache::get('blog_used_titles', []);
    }

    public function isUsedTitle(string $title): bool
    {
        $normalizedTitle = $this->normalizeTitle($title);
        return in_array($normalizedTitle, $this->getUsedTitles(), true);
    }

    private function normalizeTitle(string $title): string
    {
        // Normalize the title for comparison (lowercase, trim whitespace)
        return strtolower(trim($title));
    }
}
