<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\AI\Concerns\TracksAiUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PerplexityService
{
    use TracksAiUsage;

    private const MODEL = 'sonar';
    private const FEATURE = 'creator-discovery';

    /**
     * Search for candidate creator profiles by name or social handle.
     * Returns the same JSON shape as discoverCreators so downstream
     * (bio generation, social links, image search) can reuse it as-is.
     */
    public function searchCreatorByName(string $query, int $count = 5): array
    {
        $startedAt = microtime(true);

        try {
            $apiKey = config('services.perplexity.key');

            if (! $apiKey) {
                Log::error('PerplexityService: API key not configured');

                return [];
            }

            $query = trim($query);

            $prompt = <<<PROMPT
Find up to {$count} real African creators (broadly defined — influencers, musicians, actors, fashion designers, chefs, athletes, artists, social-media creators, etc.) matching the name or handle: "{$query}". Return the best matches — if only one clearly matches, return only that one. Do NOT invent people.

Respond ONLY with valid JSON. No markdown, no preamble, no explanation. Return a JSON array of objects with these exact keys:
- name (string, full name)
- country (string, the African country they are from or primarily based in)
- bio_summary (string, 1-2 sentence summary of who they are and what they do)
- category (string, choose the single best fit from: Comedy, Fashion, Food, Music, Lifestyle, Travel, Tech, Beauty, Sports, Education, Finance, Health, Art, Gaming, Photography)
- contact_email (string or null, their public contact email if available)
- instagram_handle (string or null, just the username without @)
- tiktok_handle (string or null, just the username without @)
- youtube_channel_url (string or null, full URL)
- twitter_handle (string or null, just the username without @)
- facebook_url (string or null, full URL)
- website_url (string or null, full URL)
- estimated_follower_count (integer or null, rough estimate on their LARGEST platform; integer like 250000 for 250K)
- follower_platform (string or null, one of: "instagram", "tiktok", "youtube", "twitter", "facebook"; set whenever estimated_follower_count is set)

If no credible match exists, return an empty JSON array [].

Return ONLY the JSON array, nothing else.
PROMPT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.perplexity.ai/chat/completions', [
                'model' => 'sonar',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a research assistant that identifies African creators (influencers, musicians, actors, designers, chefs, athletes, artists, social-media creators, etc.) by name or handle. Always respond with valid JSON only. Never invent people — return an empty array if no credible match.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.1,
            ]);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->successful()) {
                Log::error('PerplexityService: creator search request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->trackUsage(
                    response: $response,
                    providerSlug: 'perplexity',
                    model: self::MODEL,
                    feature: 'creator-search',
                    isSuccessful: false,
                    durationMs: $durationMs,
                    errorMessage: 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 400),
                );

                return [];
            }

            $this->trackUsage(
                response: $response,
                providerSlug: 'perplexity',
                model: self::MODEL,
                feature: 'creator-search',
                isSuccessful: true,
                durationMs: $durationMs,
            );

            $content = $response->json('choices.0.message.content', '');
            $content = preg_replace('/^```(?:json)?\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);

            $creators = json_decode($content, true);

            if (! is_array($creators)) {
                Log::error('PerplexityService: Failed to parse creator-search JSON response', [
                    'content' => $content,
                ]);

                return [];
            }

            return $creators;
        } catch (\Throwable $e) {
            Log::error('PerplexityService: Failed to search creators', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function discoverCreators(string $niche, string $country, int $count = 10): array
    {
        $startedAt = microtime(true);

        try {
            $apiKey = config('services.perplexity.key');

            if (! $apiKey) {
                Log::error('PerplexityService: API key not configured');

                return [];
            }

            $prompt = <<<PROMPT
Find {$count} rising African creators from {$country} who specialize in {$niche}. "Creators" here is broad — it includes influencers, musicians, actors, fashion designers, chefs, athletes, artists, and social-media creators. Focus on people who have a meaningful public presence (whether through social media, traditional media, or both) and who are gaining popularity.

CRITICAL: For each creator, find and include any social media handles they have (aim for 2-3 when possible). Most modern African creators — even outside the social-media-native crowd — maintain at least one or two public accounts. Use real, verified handles only; leave a field null if you can't verify it.

Respond ONLY with valid JSON. No markdown, no preamble, no explanation. Return a JSON array of objects with these exact keys:
- name (string, full name)
- country (string, "{$country}")
- bio_summary (string, 1-2 sentence summary of who they are and what they do)
- category (string, the creator's PRIMARY content category — choose the single best fit from: Comedy, Fashion, Food, Music, Lifestyle, Travel, Tech, Beauty, Sports, Education, Finance, Health, Art, Gaming, Photography. Do NOT just echo "{$niche}" — assign the category that truly matches their content)
- contact_email (string or null, their public contact email if available)
- instagram_handle (string or null, just the username without @ — e.g. "amaradiallo" not "@amaradiallo")
- tiktok_handle (string or null, just the username without @)
- youtube_channel_url (string or null, full URL like "https://youtube.com/@channelname")
- twitter_handle (string or null, just the username without @)
- facebook_url (string or null, full URL)
- website_url (string or null, full URL)
- estimated_follower_count (integer or null, a rough estimate of the creator's follower count on their LARGEST platform. Use a single integer like 250000 for 250K, 1500000 for 1.5M. Round to 2 significant figures — this is a rough estimate, not a precise number. Return null ONLY if you genuinely have no idea of their rough size)
- follower_platform (string or null, the platform that the estimated_follower_count refers to — one of: "instagram", "tiktok", "youtube", "twitter", "facebook". Must be set whenever estimated_follower_count is set)

REMEMBER: Even outside pure social-media-native creators, most active African creators (musicians, actors, designers, chefs, athletes) maintain Instagram, TikTok, or X accounts. Only return null when you genuinely cannot verify a handle. Make every effort to populate the social media fields.

For estimated_follower_count: pick the platform where the creator is biggest and give your best rough estimate. Typical ranges: nano (1K-10K), micro (10K-100K), mid (100K-500K), large (500K-1M), mega (1M+). A rough ballpark is far better than null.

Return ONLY the JSON array, nothing else.
PROMPT;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.perplexity.ai/chat/completions', [
                'model' => 'sonar',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a research assistant that discovers African creators (broadly defined — influencers, musicians, actors, designers, chefs, athletes, artists, social-media creators, etc.). Always respond with valid JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.1,
            ]);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->successful()) {
                Log::error('PerplexityService: API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->trackUsage(
                    response: $response,
                    providerSlug: 'perplexity',
                    model: self::MODEL,
                    feature: self::FEATURE,
                    isSuccessful: false,
                    durationMs: $durationMs,
                    errorMessage: 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 400),
                );

                return [];
            }

            $this->trackUsage(
                response: $response,
                providerSlug: 'perplexity',
                model: self::MODEL,
                feature: self::FEATURE,
                isSuccessful: true,
                durationMs: $durationMs,
            );

            $content = $response->json('choices.0.message.content', '');

            // Strip markdown code blocks if present
            $content = preg_replace('/^```(?:json)?\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);

            $creators = json_decode($content, true);

            if (! is_array($creators)) {
                Log::error('PerplexityService: Failed to parse JSON response', [
                    'content' => $content,
                ]);

                return [];
            }

            return $creators;
        } catch (\Throwable $e) {
            Log::error('PerplexityService: Failed to discover creators', [
                'niche' => $niche,
                'country' => $country,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
