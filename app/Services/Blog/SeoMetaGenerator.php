<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Services\AI\Concerns\TracksAiUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generates meta title, meta description and focus keyword for an existing
 * post (e.g. sponsored or submitted articles published as-is).
 */
final class SeoMetaGenerator
{
    use TracksAiUsage;

    /**
     * @return array{meta_title: string, meta_description: string, focus_keyword: string}
     */
    public function generate(string $title, string $excerpt, string $content): array
    {
        $apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $model = config('blog.ai.providers.openai.model', 'gpt-4o');

        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $body = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 6000);

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->withoutVerifying()->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an SEO editor for Topping Africa, an African news and magazine website. Write accurate, click-worthy search metadata based only on the article provided. Return JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => $this->buildPrompt($title, $excerpt, $body),
                ],
            ],
            'max_tokens' => 400,
            'temperature' => 0.4,
            'response_format' => ['type' => 'json_object'],
        ]);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if (! $response->successful()) {
            $this->trackUsage($response, 'openai', $model, 'seo-meta', false, $durationMs, $response->body());
            Log::error('SeoMetaGenerator: OpenAI request failed', ['status' => $response->status()]);

            throw new \RuntimeException('Failed to generate SEO metadata.');
        }

        $this->trackUsage($response, 'openai', $model, 'seo-meta', true, $durationMs);

        $data = json_decode((string) $response->json('choices.0.message.content'), true) ?? [];

        return [
            'meta_title' => Str::limit(trim((string) ($data['meta_title'] ?? '')), 70, ''),
            'meta_description' => Str::limit(trim((string) ($data['meta_description'] ?? '')), 160, ''),
            'focus_keyword' => Str::limit(trim((string) ($data['focus_keyword'] ?? '')), 100, ''),
        ];
    }

    private function buildPrompt(string $title, string $excerpt, string $body): string
    {
        return <<<PROMPT
Generate SEO metadata for this article.

Title: {$title}
Excerpt: {$excerpt}
Content: {$body}

Requirements:
- meta_title: 50-60 characters, includes the focus keyword near the start, no site name
- meta_description: 140-155 characters, includes the focus keyword, summarizes the article and invites the click
- focus_keyword: the 2-4 word phrase people would most likely search to find this article
- Do not invent facts that are not in the article

Return JSON: {"meta_title": "...", "meta_description": "...", "focus_keyword": "..."}
PROMPT;
    }
}
