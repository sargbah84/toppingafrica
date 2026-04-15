<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\AI\Concerns\TracksAiUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeBioService
{
    use TracksAiUsage;

    private const MODEL = 'claude-haiku-4-5';
    private const FEATURE = 'creator-bio';

    public function generateBio(array $creatorData): string
    {
        $fallback = $creatorData['bio_summary'] ?? 'An African content creator.';
        $startedAt = microtime(true);

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

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->successful()) {
                Log::error('ClaudeBioService: API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->trackUsage(
                    response: $response,
                    providerSlug: 'anthropic',
                    model: self::MODEL,
                    feature: self::FEATURE,
                    isSuccessful: false,
                    durationMs: $durationMs,
                    errorMessage: 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 400),
                );

                return $fallback;
            }

            $this->trackUsage(
                response: $response,
                providerSlug: 'anthropic',
                model: self::MODEL,
                feature: self::FEATURE,
                isSuccessful: true,
                durationMs: $durationMs,
            );

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

    /**
     * Rewrite an existing (likely admin-edited) bio, preserving corrections
     * staff have made while polishing tone, grammar, and concision. Used by
     * the "Enhance" button on the Edit Creator modal so staff can fix facts
     * and let AI tighten the prose without a full re-pull.
     */
    public function enhanceBio(string $existingBio, array $context): string
    {
        $fallback = $existingBio;
        $startedAt = microtime(true);

        try {
            $apiKey = config('services.anthropic.key');

            if (! $apiKey) {
                Log::error('ClaudeBioService: API key not configured');

                return $fallback;
            }

            $ctx = json_encode($context, JSON_UNESCAPED_UNICODE);

            $prompt = <<<PROMPT
Rewrite the following creator bio to be clean, engaging, and suitable for a public profile page on an African content creator directory.

Existing bio (preserve the facts — a human editor has corrected these):
{$existingBio}

Additional context (name, country, category):
{$ctx}

The rewritten bio should:
- Keep every fact from the existing bio intact — do not drop, invent, or contradict details
- Mention their country and content niche
- Use a warm, celebratory tone — this is a spotlight feature
- Be 2-3 sentences, concise and professional, suitable for display on a profile card

Return ONLY the rewritten bio text, no quotes, no labels, no explanation.
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

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            if (! $response->successful()) {
                Log::error('ClaudeBioService: Enhance request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $this->trackUsage(
                    response: $response,
                    providerSlug: 'anthropic',
                    model: self::MODEL,
                    feature: self::FEATURE,
                    isSuccessful: false,
                    durationMs: $durationMs,
                    errorMessage: 'HTTP ' . $response->status() . ': ' . mb_substr($response->body(), 0, 400),
                );

                return $fallback;
            }

            $this->trackUsage(
                response: $response,
                providerSlug: 'anthropic',
                model: self::MODEL,
                feature: self::FEATURE,
                isSuccessful: true,
                durationMs: $durationMs,
            );

            $bio = $response->json('content.0.text', '');

            return ! empty($bio) ? trim($bio) : $fallback;
        } catch (\Throwable $e) {
            Log::error('ClaudeBioService: Failed to enhance bio', [
                'creator' => $context['name'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return $fallback;
        }
    }
}
