<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PerplexityService
{
    public function discoverCreators(string $niche, string $country, int $count = 10): array
    {
        try {
            $apiKey = config('services.perplexity.key');

            if (! $apiKey) {
                Log::error('PerplexityService: API key not configured');

                return [];
            }

            $prompt = <<<PROMPT
Find {$count} rising African content creators from {$country} who specialize in {$niche} content. Focus on creators who are active on social media and gaining popularity.

CRITICAL: For each creator, you MUST find and include at least 2-3 social media handles. These creators are active on social media — finding their handles is essential. Use real, verified handles only.

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

REMEMBER: These are SOCIAL MEDIA creators — almost all of them will have Instagram and/or TikTok handles. Only return null if you genuinely cannot find a handle. Make every effort to populate the social media fields.

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
                        'content' => 'You are a research assistant that discovers African content creators. Always respond with valid JSON only.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.1,
            ]);

            if (! $response->successful()) {
                Log::error('PerplexityService: API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

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
