<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Trend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchAfricaTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 180;
    public int $tries = 2;

    public function handle(): int
    {
        $rawContent = $this->fetchFromPerplexity();

        if ($rawContent === null) {
            return 0;
        }

        $trends = $this->formatWithClaude($rawContent);

        if (empty($trends)) {
            return 0;
        }

        return $this->saveTrends($trends);
    }

    private function fetchFromPerplexity(): ?string
    {
        try {
            $apiKey = config('services.perplexity.key');

            if (! $apiKey) {
                Log::error('FetchAfricaTrendsJob: Perplexity API key not configured');

                return null;
            }

            $prompt = "Find 7 positive, uplifting, or exciting trending topics from Africa right now — across music, tech, business, sports, culture, or lifestyle. No political conflicts, violence, or disasters. For each trend return: title, a 2-sentence summary, category, country name, ISO 2-letter country code, and a source name and URL if available. Return as a JSON array only.";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(90)->post('https://api.perplexity.ai/chat/completions', [
                'model' => 'sonar',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a research assistant focused on positive African news and culture. Always respond with valid JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.2,
            ]);

            if (! $response->successful()) {
                Log::error('FetchAfricaTrendsJob: Perplexity API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content', '');

            if (empty($content)) {
                Log::error('FetchAfricaTrendsJob: Empty response from Perplexity');

                return null;
            }

            return $content;
        } catch (\Throwable $e) {
            Log::error('FetchAfricaTrendsJob: Exception calling Perplexity', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function formatWithClaude(string $rawContent): array
    {
        try {
            $apiKey = config('services.anthropic.key');

            if (! $apiKey) {
                Log::error('FetchAfricaTrendsJob: Anthropic API key not configured');

                return [];
            }

            $systemPrompt = 'You are a data formatter. You will receive raw trend data about Africa. Clean it up, ensure each item has: title, summary, category, country, country_code, source_label, source_url. Category must be one of: Music, Business, Sports, Culture, Tech, Lifestyle. Return ONLY a valid JSON array, no markdown, no explanation.';

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 2048,
                'system' => $systemPrompt,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Raw trend data:\n\n" . $rawContent,
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('FetchAfricaTrendsJob: Claude API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

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

                return [];
            }

            return $trends;
        } catch (\Throwable $e) {
            Log::error('FetchAfricaTrendsJob: Exception calling Claude', [
                'error' => $e->getMessage(),
            ]);

            return [];
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
}
