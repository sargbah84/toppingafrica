<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Models\User;
use App\Services\ImageSearchService;
use App\Services\MediaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Inserts inline images into a post's HTML body during agent generation.
 *
 * Unlike SeoIntelligenceService::autoInsertImages (which only runs when
 * applyRecommendations is invoked), this is called by the agent on every
 * post — so even posts that pass the SEO threshold get visual content.
 *
 * Strategy:
 *   - Listicles get one image per H2/H3 list item (capped at 8) since
 *     each item benefits from its own visual.
 *   - Other post types get 2-3 images distributed after H2/H3 headings.
 *   - Each image is sourced from Google Images via Serper first (specific,
 *     real-world results), with Pexels as the licensed-stock fallback.
 *   - Reachability is HEAD-checked so we never save crawler.html / 404
 *     pages as images (the same safeguard FeaturedImageService uses).
 */
final class ContentImagesService
{
    public function __construct(
        private readonly ImageSearchService $imageSearch,
        private readonly MediaService $mediaService,
    ) {}

    public function attach(Post $post, ?string $aiQuery = null): int
    {
        $content = $post->content ?? '';
        if ($content === '') {
            return 0;
        }

        $existingCount = preg_match_all('/<img\s/i', $content);
        if ($existingCount >= $this->targetImageCount($post)) {
            return 0;
        }

        $author = $post->author ?? User::query()->where('is_super_admin', true)->first();
        if (! $author) {
            return 0;
        }

        $needed = $this->targetImageCount($post) - $existingCount;
        $positions = $this->findInsertionPositions($content, $needed);

        if ($positions === []) {
            return 0;
        }

        $queries = $this->buildQueries($post, $aiQuery, count($positions));
        $usedUrls = [];
        $inserted = 0;

        // Walk positions in reverse so earlier offsets stay valid after splicing.
        $positions = array_reverse($positions);

        foreach ($positions as $idx => $position) {
            $query = $queries[count($positions) - 1 - $idx] ?? $queries[0] ?? null;
            if (! $query) {
                continue;
            }

            $image = $this->findImage($query, $usedUrls);
            if (! $image) {
                continue;
            }

            try {
                $media = $this->mediaService->storeFromUrl(
                    $image['url'],
                    $image['source'],
                    $author,
                    $image['alt'] ?: $query,
                    array_filter([
                        'photographer' => $image['photographer'] ?? null,
                        'photographer_url' => $image['photographer_url'] ?? null,
                    ])
                );
            } catch (Throwable $e) {
                Log::warning('ContentImagesService: failed to store image', [
                    'post_id' => $post->id,
                    'query' => $query,
                    'url' => $image['url'],
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            $imgHtml = $this->renderFigure(
                $media->getUrl(),
                $image['alt'] ?: $query,
                $image['photographer'] ?? null,
                $image['source'],
                $image['context_url'] ?? null,
            );
            $content = substr_replace($content, $imgHtml, $position, 0);
            $usedUrls[] = $image['url'];
            $inserted++;
        }

        if ($inserted > 0) {
            $post->content = $content;
            $post->save();
        }

        return $inserted;
    }

    private function targetImageCount(Post $post): int
    {
        return match ($post->post_type) {
            'listicle' => 8, // one per item, up to 8
            'gallery' => 8,
            'article' => 3,
            default => 3,
        };
    }

    /**
     * Build a list of search queries — one per image slot — varied so we
     * don't fetch the same photo three times. Falls back to focus_keyword
     * + " Africa" if nothing better is available.
     *
     * @return string[]
     */
    private function buildQueries(Post $post, ?string $aiQuery, int $slots): array
    {
        $queries = [];

        if ($aiQuery && trim($aiQuery) !== '') {
            $queries[] = trim($aiQuery);
        }

        $focus = trim((string) ($post->focus_keyword ?? ''));
        $title = trim((string) $post->title);

        // Mine H2/H3 headings as per-section queries (especially useful for listicles).
        if (preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is', $post->content ?? '', $matches)) {
            foreach ($matches[1] as $heading) {
                $clean = trim(strip_tags($heading));
                if ($clean === '') {
                    continue;
                }
                // Keep headings short for image search and bias toward Africa.
                $clean = mb_substr($clean, 0, 80);
                $queries[] = $clean.' Africa';
            }
        }

        if ($focus !== '') {
            $queries[] = $focus.' Africa';
        }
        if ($title !== '') {
            $queries[] = mb_substr($title, 0, 80).' Africa';
        }

        // De-dupe while preserving order, then pad to slots count by repeating.
        $queries = array_values(array_unique($queries));
        if ($queries === []) {
            return [];
        }

        while (count($queries) < $slots) {
            $queries[] = $queries[count($queries) % count(array_unique($queries))];
        }

        return $queries;
    }

    /**
     * @return array{url:string,alt:string,source:string,photographer?:string,photographer_url?:string}|null
     */
    private function findImage(string $query, array $usedUrls): ?array
    {
        // Google first — more specific, real-world results.
        try {
            $google = $this->imageSearch->searchGoogle($query);
        } catch (Throwable $e) {
            $google = ['results' => []];
        }

        foreach ($google['results'] ?? [] as $result) {
            $url = $result['url'] ?? '';
            if (! is_string($url) || $url === '' || ! str_starts_with($url, 'http') || in_array($url, $usedUrls, true)) {
                continue;
            }
            $width = (int) ($result['width'] ?? 0);
            $height = (int) ($result['height'] ?? 0);
            if ($width > 0 && $width < 600) {
                continue;
            }
            if ($height > 0 && $height < 400) {
                continue;
            }
            if (! $this->isReachableImage($url)) {
                continue;
            }

            return [
                'url' => $url,
                'alt' => $result['title'] ?? $query,
                'source' => 'google',
                'context_url' => $result['context_url'] ?? null,
            ];
        }

        // Pexels fallback — always reachable (their CDN guarantees image bytes).
        try {
            $pexels = $this->imageSearch->searchPexels($query, 1, 5);
        } catch (Throwable $e) {
            return null;
        }

        foreach ($pexels['results'] ?? [] as $result) {
            $url = $result['url'] ?? '';
            if (! is_string($url) || $url === '' || in_array($url, $usedUrls, true)) {
                continue;
            }

            return [
                'url' => $url,
                'alt' => $result['alt'] ?? $query,
                'source' => 'pexels',
                'photographer' => $result['photographer'] ?? null,
                'photographer_url' => $result['photographer_url'] ?? null,
            ];
        }

        return null;
    }

    private function isReachableImage(string $url): bool
    {
        try {
            $head = Http::timeout(8)->withHeaders([
                'Accept' => 'image/*',
                'User-Agent' => 'Mozilla/5.0 (compatible; ToppingAfricaBot/1.0)',
            ])->head($url);
        } catch (Throwable) {
            return false;
        }

        if (! $head->successful()) {
            return false;
        }

        $contentType = strtolower((string) $head->header('Content-Type'));

        return $contentType !== '' && str_starts_with($contentType, 'image/');
    }

    /**
     * Insertion positions: after each H2/H3 + its trailing </p>. Caps at
     * $count slots, evenly distributed when there are more headings than
     * needed.
     *
     * @return int[]
     */
    private function findInsertionPositions(string $content, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        if (! preg_match_all('/<\/h[23]>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $positions = [];
        foreach ($matches[0] as $match) {
            $headingEnd = $match[1] + strlen($match[0]);
            $after = substr($content, $headingEnd);
            if (preg_match('/<\/p>/i', $after, $pMatch, PREG_OFFSET_CAPTURE)) {
                $positions[] = $headingEnd + $pMatch[0][1] + strlen($pMatch[0][0]);
            }
        }

        if (count($positions) <= $count) {
            return $positions;
        }

        $step = count($positions) / $count;
        $picked = [];
        for ($i = 0; $i < $count; $i++) {
            $picked[] = $positions[(int) round($i * $step)];
        }

        return $picked;
    }

    private function renderFigure(string $url, string $alt, ?string $photographer, string $source, ?string $contextUrl = null): string
    {
        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $safeAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');

        $html = "\n<figure class='content-image'>"
            ."<img src='{$safeUrl}' alt='{$safeAlt}' loading='lazy' style='max-width:100%;height:auto;' />";

        $caption = $this->buildCaption($source, $photographer, $contextUrl);
        if ($caption !== '') {
            $html .= "<figcaption style='font-size:0.75rem;color:#9ca3af;margin-top:0.25rem;'>{$caption}</figcaption>";
        }

        return $html."</figure>\n";
    }

    private function buildCaption(string $source, ?string $photographer, ?string $contextUrl): string
    {
        if ($source === 'pexels' && $photographer) {
            $safePhotographer = htmlspecialchars($photographer, ENT_QUOTES, 'UTF-8');

            return "Photo by {$safePhotographer} on Pexels";
        }

        if ($source === 'google' && $contextUrl) {
            $host = parse_url($contextUrl, PHP_URL_HOST);
            if (! is_string($host) || $host === '') {
                return '';
            }
            $host = preg_replace('/^www\./i', '', $host);
            $safeHost = htmlspecialchars($host, ENT_QUOTES, 'UTF-8');
            $safeUrl = htmlspecialchars($contextUrl, ENT_QUOTES, 'UTF-8');

            return "Source: <a href='{$safeUrl}' target='_blank' rel='noopener noreferrer nofollow' style='color:#6b7280;'>{$safeHost}</a>";
        }

        return '';
    }
}
