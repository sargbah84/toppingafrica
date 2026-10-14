<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Tag;
use App\Services\AI\Concerns\TracksAiUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Generates meta title, meta description, focus keyword and tags for an
 * existing post (e.g. sponsored or submitted articles published as-is).
 */
final class SeoMetaGenerator
{
    use TracksAiUsage;

    /**
     * @return array{meta_title: string, meta_description: string, focus_keyword: string}
     */
    public function generate(string $title, string $excerpt, string $content): array
    {
        $data = $this->request($this->metaPrompt($title, $excerpt, $this->plainText($content)), 'seo-meta', 400);

        return [
            'meta_title' => Str::limit(trim((string) ($data['meta_title'] ?? '')), 70, ''),
            'meta_description' => Str::limit(trim((string) ($data['meta_description'] ?? '')), 160, ''),
            'focus_keyword' => Str::limit(trim((string) ($data['focus_keyword'] ?? '')), 100, ''),
        ];
    }

    /**
     * Tags matching an existing Tag (case-insensitive) reuse its spelling.
     *
     * @return array<int, string>
     */
    public function generateTags(string $title, string $excerpt, string $content): array
    {
        $data = $this->request($this->tagsPrompt($title, $excerpt, $this->plainText($content)), 'seo-tags', 200);

        $tags = collect($data['tags'] ?? [])
            ->filter(fn ($tag) => is_string($tag))
            ->map(fn (string $tag) => Str::limit(trim($tag, " \t\n\r\0\x0B#"), 50, ''))
            ->filter()
            ->unique(fn (string $tag) => Str::lower($tag))
            ->take(8)
            ->values();

        $existing = Tag::whereIn(DB::raw('LOWER(name)'), $tags->map(Str::lower(...))->all())->pluck('name')
            ->keyBy(fn (string $name) => Str::lower($name));

        return $tags->map(fn (string $tag) => $existing[Str::lower($tag)] ?? $tag)->all();
    }

    private function request(string $prompt, string $feature, int $maxTokens): array
    {
        $apiKey = config('services.openai.api_key', env('OPENAI_API_KEY', ''));
        $model = config('blog.ai.providers.openai.model', 'gpt-4o');

        if (empty($apiKey)) {
            throw new \RuntimeException('OpenAI API key is not configured.');
        }

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an SEO editor for Topping Africa, an African news and magazine website. Write accurate search metadata based only on the article provided. Return JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.4,
            'response_format' => ['type' => 'json_object'],
        ]);

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        if (! $response->successful()) {
            $this->trackUsage($response, 'openai', $model, $feature, false, $durationMs, $response->body());
            Log::error('SeoMetaGenerator: OpenAI request failed', ['feature' => $feature, 'status' => $response->status()]);

            throw new \RuntimeException('Failed to generate SEO metadata.');
        }

        $this->trackUsage($response, 'openai', $model, $feature, true, $durationMs);

        return json_decode((string) $response->json('choices.0.message.content'), true) ?? [];
    }

    private function plainText(string $content): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($content))), 6000);
    }

    private function metaPrompt(string $title, string $excerpt, string $body): string
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

    private function tagsPrompt(string $title, string $excerpt, string $body): string
    {
        return <<<PROMPT
Suggest 5-8 tags for this article.

Title: {$title}
Excerpt: {$excerpt}
Content: {$body}

Requirements:
- Mix named entities (people, brands, places, works) with broader topics (e.g. "Afrobeats", "Liberia", "Fintech")
- 1-3 words each, Title Case, no hashtags
- Only use names and topics that appear in or are clearly implied by the article

Return JSON: {"tags": ["...", "..."]}
PROMPT;
    }
}
