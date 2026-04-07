<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AiUsageLog;
use App\Models\Trend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Response;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAfricaTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 2;

    public function __construct(public int $count = 15) {}

    /**
     * Run the fetch and return a structured result so the calling Livewire
     * component can show a precise message to the admin instead of forcing
     * them to grep laravel.log.
     *
     * The scheduled queue caller doesn't use the return value — it simply
     * fires and forgets — so changing the shape is safe.
     *
     * @return array{created: int, received: int, error: ?string}
     */
    public function handle(): array
    {
        $perplexity = $this->fetchFromPerplexity();
        if (! $perplexity['ok']) {
            return ['created' => 0, 'received' => 0, 'error' => $perplexity['error']];
        }

        $claude = $this->formatWithClaude($perplexity['data']);
        if (! $claude['ok']) {
            return ['created' => 0, 'received' => 0, 'error' => $claude['error']];
        }

        $trends = $claude['data'];
        $created = $this->saveTrends($trends);

        return [
            'created' => $created,
            'received' => count($trends),
            'error' => null,
        ];
    }

    /**
     * @return array{ok: bool, data: ?string, error: ?string}
     */
    private function fetchFromPerplexity(): array
    {
        try {
            $apiKey = config('services.perplexity.key');

            if (! $apiKey) {
                Log::error('FetchAfricaTrendsJob: Perplexity API key not configured');

                return ['ok' => false, 'data' => null, 'error' => 'Perplexity API key is not configured. Set PERPLEXITY_API_KEY in .env.'];
            }

            $today = now()->format('F j, Y');
            $count = $this->count;

            $prompt = <<<PROMPT
Find up to {$count} positive, uplifting, or exciting trending topics from Africa that are TRENDING TODAY ({$today}) or within the last 24-48 hours — across music, tech, business, sports, culture, or lifestyle.

CRITICAL FRESHNESS REQUIREMENTS:
- Only include topics that broke, went viral, or became newsworthy in the LAST 48 HOURS.
- DO NOT include stories from last week, last month, or older. If you cannot verify a story is from the last 2 days, skip it.
- Prioritize breaking news, today's announcements, viral moments from this week, and very recent releases/launches.
- The source URL must point to an article published within the last 48 hours.

EXCLUSIONS:
- No political conflicts, violence, war, or disasters.
- No evergreen content or background pieces.

For each trend return: title, a 2-sentence summary, category, country name, ISO 2-letter country code, and a source name and URL.

Return as many fresh trends as you can find (up to {$count}). Return as a JSON array only — no markdown, no preamble.
PROMPT;

            $startedAt = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90)->post('https://api.perplexity.ai/chat/completions', [
                'model' => 'sonar',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a research assistant focused on positive African news and culture from the last 24-48 hours only. Always respond with valid JSON only. Never include stories older than 2 days.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
                // Allow Perplexity to search the last 7 days. The prompt
                // still asks for stories from the last 24-48 hours, so the
                // model self-prioritises freshness; this filter just stops
                // returning zero results on a slow news day for Africa.
                'search_recency_filter' => 'week',
            ]);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->successful()) {
                Log::error('FetchAfricaTrendsJob: Perplexity API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->trackUsage($response, 'perplexity', 'sonar', 'trending_fetch', false, $durationMs, $response->body());

                $apiMsg = $response->json('error.message');

                return [
                    'ok' => false,
                    'data' => null,
                    'error' => 'Perplexity API error (HTTP ' . $response->status() . ')'
                        . ($apiMsg ? ': ' . $apiMsg : '. Check storage/logs/laravel.log for the full response body.'),
                ];
            }

            $this->trackUsage($response, 'perplexity', 'sonar', 'trending_fetch', true, $durationMs);

            $content = $response->json('choices.0.message.content', '');

            if (empty($content)) {
                Log::error('FetchAfricaTrendsJob: Empty response from Perplexity');

                return ['ok' => false, 'data' => null, 'error' => 'Perplexity returned an empty response.'];
            }

            return ['ok' => true, 'data' => $content, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('FetchAfricaTrendsJob: Exception calling Perplexity', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'data' => null, 'error' => 'Perplexity request threw an exception: ' . $e->getMessage()];
        }
    }

    /**
     * @return array{ok: bool, data: array, error: ?string}
     */
    private function formatWithClaude(string $rawContent): array
    {
        try {
            $apiKey = config('services.anthropic.key');

            if (! $apiKey) {
                Log::error('FetchAfricaTrendsJob: Anthropic API key not configured');

                return ['ok' => false, 'data' => [], 'error' => 'Anthropic API key is not configured. Set ANTHROPIC_API_KEY in .env.'];
            }

            $systemPrompt = 'You are a data formatter. You will receive raw trend data about Africa. Clean it up, ensure each item has: title, summary, category, country, country_code, source_label, source_url. Category must be one of: Music, Business, Sports, Culture, Tech, Lifestyle. Return ONLY a valid JSON array, no markdown, no explanation.';

            $startedAt = microtime(true);

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Raw trend data:\n\n" . $rawContent,
                    ],
                ],
            ]);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->successful()) {
                Log::error('FetchAfricaTrendsJob: Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->trackUsage($response, 'anthropic', 'claude-haiku-4-5', 'trending_format', false, $durationMs, $response->body());

                $apiMsg = $response->json('error.message');

                return [
                    'ok' => false,
                    'data' => [],
                    'error' => 'Anthropic API error (HTTP ' . $response->status() . ')'
                        . ($apiMsg ? ': ' . $apiMsg : '. Check storage/logs/laravel.log for the full response body.'),
                ];
            }

            $this->trackUsage($response, 'anthropic', 'claude-haiku-4-5', 'trending_format', true, $durationMs);

            $content = $response->json('content.0.text', '');

            // Strip markdown code fences if Claude added them anyway
            $content = preg_replace('/^```(?:json)?\s*/', '', trim($content));
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);

            $trends = json_decode($content, true);

            if (! is_array($trends)) {
                Log::error('FetchAfricaTrendsJob: Failed to parse Claude JSON', [
                    'content' => $content,
                ]);

                return [
                    'ok' => false,
                    'data' => [],
                    'error' => 'Anthropic returned text that is not valid JSON. Check storage/logs/laravel.log for the raw content.',
                ];
            }

            return ['ok' => true, 'data' => $trends, 'error' => null];
        } catch (\Throwable $e) {
            Log::error('FetchAfricaTrendsJob: Exception calling Claude', [
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'data' => [], 'error' => 'Anthropic request threw an exception: ' . $e->getMessage()];
        }
    }

    private function saveTrends(array $trends): int
    {
        $allowedCategories = ['Music', 'Business', 'Sports', 'Culture', 'Tech', 'Lifestyle'];
        $today = today();
        $expiresAt = now()->addDays(5);
        $created = 0;

        foreach ($trends as $trend) {
            $title = trim((string) ($trend['title'] ?? ''));

            if ($title === '') {
                continue;
            }

            $category = $trend['category'] ?? 'Culture';
            if (! in_array($category, $allowedCategories, true)) {
                $category = 'Culture';
            }

            // Skip duplicates for today
            if (Trend::whereDate('trend_date', $today)->where('title', $title)->exists()) {
                continue;
            }

            try {
                Trend::create([
                    'title' => $title,
                    'summary' => trim((string) ($trend['summary'] ?? '')),
                    'category' => $category,
                    'country' => trim((string) ($trend['country'] ?? '')),
                    'country_code' => $trend['country_code'] ? strtoupper(substr($trend['country_code'], 0, 2)) : null,
                    'source_label' => $trend['source_label'] ?? null,
                    'source_url' => $trend['source_url'] ?? null,
                    'trend_date' => $today,
                    'expires_at' => $expiresAt,
                    'is_active' => true,
                ]);

                $created++;
            } catch (\Throwable $e) {
                Log::error('FetchAfricaTrendsJob: Failed to save trend', [
                    'title' => $title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('FetchAfricaTrendsJob: Completed', [
            'created' => $created,
            'received' => count($trends),
        ]);

        return $created;
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
                'anthropic' => [
                    (int) ($data['usage']['input_tokens'] ?? 0),
                    (int) ($data['usage']['output_tokens'] ?? 0),
                ],
                'perplexity' => [
                    (int) ($data['usage']['prompt_tokens'] ?? 0),
                    (int) ($data['usage']['completion_tokens'] ?? 0),
                ],
                default => [0, 0],
            };

            $totalTokens = $inputTokens + $outputTokens;
            $cost = AiUsageLog::calculateCost($provider, $model, $inputTokens, $outputTokens);

            AiUsageLog::create([
                'user_id' => null, // scheduled job — no user
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
            Log::warning('FetchAfricaTrendsJob: usage tracking failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
