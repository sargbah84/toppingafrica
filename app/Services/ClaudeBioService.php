<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeBioService
{
    public function generateBio(array $creatorData): string
    {
        $fallback = $creatorData['bio_summary'] ?? 'An African content creator.';

        try {
            $apiKey = config('services.anthropic.key');

            if (! $apiKey) {
                Log::error('ClaudeBioService: API key not configured');

                return $fallback;
            }

            $context = json_encode($creatorData, JSON_UNESCAPED_UNICODE);

            $prompt = <<<PROMPT
Based on the following creator data, write a clean, engaging 2-3 sentence bio suitable for a public profile page on an African content creator directory.

Creator data:
{$context}

The bio should:
- Mention their country and content niche
- Highlight what makes them notable and their content style
- Use a warm, celebratory tone — this is a spotlight feature
- Be concise and professional, suitable for display on a profile card

Return ONLY the bio text, no quotes, no labels, no explanation.
PROMPT;

            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 256,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if (! $response->successful()) {
                Log::error('ClaudeBioService: API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return $fallback;
            }

            $bio = $response->json('content.0.text', '');

            return ! empty($bio) ? trim($bio) : $fallback;
        } catch (\Throwable $e) {
            Log::error('ClaudeBioService: Failed to generate bio', [
                'creator' => $creatorData['name'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }
}
