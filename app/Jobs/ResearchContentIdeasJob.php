<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiUsageLog;
use App\Models\ContentIdea;
use App\Models\Trend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResearchContentIdeasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    private const NICHE_TREND_CATEGORIES = [
        'africa-news' => null, // all categories
        'entertainment' => ['Music', 'Culture', 'Lifestyle'],
        'business' => ['Business'],
        'technology' => ['Tech'],
        'sports' => ['Sports'],
        'health' => ['Lifestyle'],
        'politics' => null, // all categories
    ];

    public function __construct(public int $perNiche = 5) {}

    /**
     * @return array{created: int, cleaned_up: int, error: ?string}
     */
    public function handle(): array
    {
        $cleanedUp = $this->cleanup();
        $niches = config('blog.ai.niches', []);
        $apiKey = config('blog.ai.providers.perplexity.api_key', '');

        if (empty($apiKey)) {
            return ['created' => 0, 'cleaned_up' => $cleanedUp, 'error' => 'Perplexity API key is not configured.'];
        }

        $totalCreated = 0;
        $lastError = null;

        foreach ($niches as $nicheKey => $nicheLabel) {
            $result = $this->researchNiche($nicheKey, $nicheLabel, $apiKey);

            if ($result['error']) {
                $lastError = $result['error'];
                Log::warning('ResearchContentIdeasJob: Failed niche research', [
                    'niche' => $nicheKey,
                    'error' => $result['error'],
                ]);

                continue;
            }

            $totalCreated += $this->saveIdeas($result['data'], $nicheKey);
        }

        // Merge active trends not already represented
        $totalCreated += $this->enrichFromTrends();

        Log::info('ResearchContentIdeasJob: Completed', [
            'created' => $totalCreated,
            'cleaned_up' => $cleanedUp,
        ]);

        return [
            'created' => $totalCreated,
            'cleaned_up' => $cleanedUp,
            'error' => $lastError,
        ];
    }

    private function cleanup(): int
    {
        return ContentIdea::where('expires_at', '<', now())
            ->where('status', '!=', 'generated')
            ->delete();
    }

    /**
     * @return array{data: array, error: ?string}
     */
    private function researchNiche(string $nicheKey, string $nicheLabel, string $apiKey): array
    {
        try {
            $today = now()->format('F j, Y');
            $count = $this->perNiche;

            $prompt = <<<PROMPT
Today is {$today}. Research {$count} high-potential blog article topics in the niche of: {$nicheLabel}.

These articles will be published on Topping Africa, a leading African news and culture magazine. Focus on topics that are:
- Trending or gaining search momentum RIGHT NOW
- Underserved by existing content (content gaps where a well-written article could rank)
- Highly relevant to African audiences
- Positive, constructive, or informative (avoid violence, disasters, political conflicts)

For each topic, assess:
- Current search interest level (high/medium/low)
- Content competition level (high/medium/low) — how many quality articles already exist
- Relevance to African audiences (1-10 scale)
- A primary keyword to target for SEO
- The best post format: article, listicle, quiz, or trivia

Return a JSON array only — no markdown, no preamble:
[{
    "title": "Compelling article headline",
    "description": "2-3 sentences describing the article angle and why it would perform well",
    "search_interest": "high|medium|low",
    "competition": "high|medium|low",
    "suggested_keyword": "primary seo keyword",
    "relevance_to_africa": 8,
    "source_url": "https://example.com/reference",
    "suggested_post_type": "article|listicle|quiz|trivia"
}]
PROMPT;

            $startedAt = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90)->post('https://api.perplexity.ai/chat/completions', [
                'model' => config('blog.ai.providers.perplexity.model', 'sonar-pro'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO research analyst specializing in African content markets. You identify high-potential article topics by analyzing current search trends, content gaps, and audience demand. Always respond with valid JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.3,
                'search_recency_filter' => 'week',
            ]);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->successful()) {
                $this->trackUsage($response, 'perplexity', config('blog.ai.providers.perplexity.model', 'sonar-pro'), 'content-lab-research', false, $durationMs, $response->body());

                $apiMsg = $response->json('error.message');

                return [
                    'data' => [],
                    'error' => "Perplexity API error (HTTP {$response->status()})".($apiMsg ? ": {$apiMsg}" : ''),
                ];
            }

            $this->trackUsage($response, 'perplexity', config('blog.ai.providers.perplexity.model', 'sonar-pro'), 'content-lab-research', true, $durationMs);

            $content = $response->json('choices.0.message.content', '');

            if (empty($content)) {
                return ['data' => [], 'error' => 'Perplexity returned an empty response.'];
            }

            $parsed = $this->extractJson($content);

            if (! is_array($parsed)) {
                return ['data' => [], 'error' => 'Failed to parse JSON from Perplexity response.'];
            }

            return ['data' => $parsed, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('ResearchContentIdeasJob: Exception researching niche', [
                'niche' => $nicheKey,
                'error' => $e->getMessage(),
            ]);

            return ['data' => [], 'error' => $e->getMessage()];
        }
    }

    private function saveIdeas(array $ideas, string $niche): int
    {
        $created = 0;
        $expiresAt = now()->addDays(7);

        foreach ($ideas as $idea) {
            $title = trim((string) ($idea['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            // Skip if a similar title already exists as active
            if (ContentIdea::active()->where('title', $title)->exists()) {
                continue;
            }

            $postType = $idea['suggested_post_type'] ?? 'article';
            $allowedTypes = ['article', 'listicle', 'quiz', 'trivia'];
            if (! in_array($postType, $allowedTypes, true)) {
                $postType = 'article';
            }

            $score = $this->scoreTopic($idea);

            try {
                ContentIdea::create([
                    'title' => $title,
                    'description' => trim((string) ($idea['description'] ?? '')),
                    'niche' => $niche,
                    'category' => $this->nicheToCategoryName($niche),
                    'suggested_keyword' => $idea['suggested_keyword'] ?? null,
                    'seo_score' => $score,
                    'search_interest' => $idea['search_interest'] ?? 'medium',
                    'competition' => $idea['competition'] ?? 'medium',
                    'relevance_score' => min(10, max(1, (int) ($idea['relevance_to_africa'] ?? 5))),
                    'source' => 'perplexity',
                    'source_url' => $idea['source_url'] ?? null,
                    'suggested_tone' => 'professional',
                    'suggested_length' => 'long',
                    'suggested_post_type' => $postType,
                    'status' => 'pending',
                    'expires_at' => $expiresAt,
                    'researched_at' => now(),
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::error('ResearchContentIdeasJob: Failed to save idea', [
                    'title' => $title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }

    private function enrichFromTrends(): int
    {
        $activeTrends = Trend::where('is_active', true)
            ->where('expires_at', '>', now())
            ->get();

        $created = 0;
        $expiresAt = now()->addDays(7);

        foreach ($activeTrends as $trend) {
            // Skip if already represented
            if (ContentIdea::active()->where('title', $trend->title)->exists()) {
                continue;
            }

            $niche = $this->trendCategoryToNiche($trend->category);

            $score = $this->scoreTopic([
                'search_interest' => 'medium',
                'competition' => 'low',
                'relevance_to_africa' => 8,
                'source' => 'trends',
            ]);

            try {
                ContentIdea::create([
                    'title' => $trend->title,
                    'description' => $trend->summary,
                    'niche' => $niche,
                    'category' => $trend->category,
                    'suggested_keyword' => strtolower(explode(':', $trend->title)[0]),
                    'seo_score' => $score,
                    'search_interest' => 'medium',
                    'competition' => 'low',
                    'relevance_score' => 8,
                    'source' => 'trends',
                    'source_url' => $trend->source_url,
                    'suggested_tone' => 'professional',
                    'suggested_length' => 'long',
                    'suggested_post_type' => 'article',
                    'status' => 'pending',
                    'expires_at' => $expiresAt,
                    'researched_at' => now(),
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::error('ResearchContentIdeasJob: Failed to save trend idea', [
                    'title' => $trend->title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $created;
    }

    private function scoreTopic(array $topic): int
    {
        // Search interest (35%)
        $interestScore = match ($topic['search_interest'] ?? 'medium') {
            'high' => 35,
            'medium' => 20,
            'low' => 10,
            default => 20,
        };

        // Competition inverted (30%) — low competition = better
        $competitionScore = match ($topic['competition'] ?? 'medium') {
            'low' => 30,
            'medium' => 18,
            'high' => 8,
            default => 18,
        };

        // Africa relevance (20%)
        $relevance = min(10, max(1, (int) ($topic['relevance_to_africa'] ?? 5)));
        $relevanceScore = (int) round(($relevance / 10) * 20);

        // Timeliness bonus (15%)
        $timelinessScore = 0;
        if (($topic['source'] ?? '') === 'trends') {
            $timelinessScore = 15;
        } elseif (($topic['search_interest'] ?? '') === 'high') {
            $timelinessScore = 15;
        } elseif (($topic['search_interest'] ?? '') === 'medium') {
            $timelinessScore = 8;
        }

        return min(100, $interestScore + $competitionScore + $relevanceScore + $timelinessScore);
    }

    private function extractJson(string $content): ?array
    {
        // Remove markdown code blocks if present
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
            $content = trim($matches[1]);
        }

        // Try to find JSON array
        if (preg_match('/\[[\s\S]*\]/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try to find JSON object and wrap in array
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return [$decoded];
            }
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function nicheToCategoryName(string $niche): ?string
    {
        return match ($niche) {
            'africa-news' => 'Africa News',
            'entertainment' => 'Entertainment',
            'business' => 'Business & Economy',
            'technology' => 'Technology',
            'sports' => 'Sports',
            'health' => 'Health & Wellness',
            'politics' => 'Politics & Governance',
            default => null,
        };
    }

    private function trendCategoryToNiche(string $category): string
    {
        return match ($category) {
            'Music', 'Culture', 'Lifestyle' => 'entertainment',
            'Business' => 'business',
            'Tech' => 'technology',
            'Sports' => 'sports',
            default => 'africa-news',
        };
    }

    private function trackUsage(
        Response $response,
        string $provider,
        string $model,
        string $feature,
        bool $isSuccessful,
        int $durationMs,
        ?string $errorMessage = null,
    ): void {
        try {
            $data = $response->json() ?? [];

            [$inputTokens, $outputTokens] = match ($provider) {
                'perplexity' => [
                    (int) ($data['usage']['prompt_tokens'] ?? 0),
                    (int) ($data['usage']['completion_tokens'] ?? 0),
                ],
                default => [0, 0],
            };

            $totalTokens = $inputTokens + $outputTokens;
            $cost = AiUsageLog::calculateCost($provider, $model, $inputTokens, $outputTokens);

            AiUsageLog::create([
                'user_id' => null,
                'provider' => $provider,
                'model' => $model,
                'feature' => $feature,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost' => $cost,
                'duration_ms' => $durationMs,
                'is_successful' => $isSuccessful,
                'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ResearchContentIdeasJob: usage tracking failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
