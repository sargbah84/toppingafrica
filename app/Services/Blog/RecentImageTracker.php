<?php

declare(strict_types=1);

namespace App\Services\Blog;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Cross-post image deduplication + quality ranking.
 *
 * Two jobs that the image-sourcing services (FeaturedImageService,
 * ContentImagesService, SeoIntelligenceService::autoInsertImages) all share:
 *
 *   1. Dedup ACROSS posts. Each path previously deduped only within the post
 *      it was generating, so the same handful of top Pexels/Google hits for a
 *      generic query ("{keyword} Africa") landed on post after post. We track
 *      every external image URL we've already saved — via the source_url we
 *      stamp into media custom_properties — and skip those URLs on later posts.
 *
 *   2. Rank by quality. Selection was "first reachable candidate", so a barely
 *      passing 600x400 thumbnail beat an available 2000x1300 hero further down
 *      the list. We score each candidate by resolution and aspect ratio and
 *      hand them back best-first, with sub-threshold images dropped outright.
 */
final class RecentImageTracker
{
    /** Hard floor — anything smaller than this is rejected outright. */
    private const MIN_WIDTH = 600;

    private const MIN_HEIGHT = 400;

    /**
     * How many of the most-recent stored images to consider when deciding
     * "already used". Bounds the query; older images are allowed to recur
     * since by then they've long rotated off the front page.
     */
    private const RECENT_LOOKBACK = 500;

    /**
     * Lowercased source_url set of recently-stored external images, loaded
     * once per request. Null until first use.
     *
     * @var array<string, true>|null
     */
    private ?array $recentUrls = null;

    /**
     * Filter out recently-used and sub-threshold candidates, then sort the
     * survivors best-quality-first.
     *
     * Accepts the raw `results` rows from ImageSearchService (Google or
     * Pexels) — each a map with at least a `url`, optionally `width`/`height`.
     * `$alsoSkip` lets callers exclude URLs already chosen within the current
     * post (the in-post dedup the callers used to do by hand).
     *
     * Pexels rows always carry width/height; Google rows usually do but not
     * always — when dimensions are missing we keep the candidate (we can't
     * judge it) and sort it after everything we *can* measure.
     *
     * @param  array<int, array<string, mixed>>  $results
     * @param  array<int, string>  $alsoSkip  extra URLs to exclude (current-post dedup)
     * @return array<int, array<string, mixed>> same rows, filtered + sorted
     */
    public function rankCandidates(array $results, array $alsoSkip = []): array
    {
        $skip = $this->recentUrlSet();
        foreach ($alsoSkip as $url) {
            if (is_string($url) && $url !== '') {
                $skip[strtolower($url)] = true;
            }
        }

        $ranked = [];
        foreach ($results as $result) {
            $url = $result['url'] ?? null;
            if (! is_string($url) || $url === '' || ! str_starts_with($url, 'http')) {
                continue;
            }
            if (isset($skip[strtolower($url)])) {
                continue;
            }

            $width = (int) ($result['width'] ?? 0);
            $height = (int) ($result['height'] ?? 0);

            // Reject obvious thumbnails when we have dimensions. When we don't
            // (some Google hits), keep the candidate — many are still good.
            if ($width > 0 && $height > 0) {
                if ($width < self::MIN_WIDTH || $height < self::MIN_HEIGHT) {
                    continue;
                }
            }

            $result['_quality'] = $this->qualityScore($width, $height);
            $ranked[] = $result;
        }

        // Stable sort, best score first. usort isn't stable pre-8.0 but we're
        // on 8.2+, where it is — so equal-score candidates keep search order.
        usort($ranked, fn (array $a, array $b): int => $b['_quality'] <=> $a['_quality']);

        return $ranked;
    }

    /**
     * Has this exact URL already been saved on a recent post?
     */
    public function isRecentlyUsed(string $url): bool
    {
        return isset($this->recentUrlSet()[strtolower($url)]);
    }

    /**
     * Score a candidate: more pixels is better, but penalize extreme aspect
     * ratios (banners, slivers) that crop badly into a featured/inline slot.
     * Returns 0 when dimensions are unknown so measured candidates win.
     */
    private function qualityScore(int $width, int $height): float
    {
        if ($width <= 0 || $height <= 0) {
            return 0.0;
        }

        // Megapixels, capped so a 6000px banner doesn't dwarf good landscape
        // photos — beyond ~3MP returns diminish for our display sizes.
        $megapixels = min(($width * $height) / 1_000_000, 3.0);

        $ratio = $width / $height;
        // Ideal landscape sits around 4:3–16:9 (1.33–1.78). Score 1.0 inside
        // that band, falling off toward squares and extreme panoramas.
        $aspectScore = match (true) {
            $ratio >= 1.3 && $ratio <= 1.85 => 1.0,
            $ratio >= 1.0 && $ratio < 1.3 => 0.7,   // squarish
            $ratio > 1.85 && $ratio <= 2.4 => 0.6,  // wide
            default => 0.3,                          // portrait / ultra-wide
        };

        return $megapixels * $aspectScore;
    }

    /**
     * @return array<string, true>
     */
    private function recentUrlSet(): array
    {
        if ($this->recentUrls !== null) {
            return $this->recentUrls;
        }

        $this->recentUrls = [];

        Media::query()
            ->whereIn('collection_name', ['default', 'featured_image'])
            ->whereNotNull('custom_properties')
            ->latest('id')
            ->limit(self::RECENT_LOOKBACK)
            ->get(['id', 'custom_properties'])
            ->each(function (Media $media): void {
                $custom = is_array($media->custom_properties) ? $media->custom_properties : [];
                $sourceUrl = $custom['source_url'] ?? null;
                if (is_string($sourceUrl) && $sourceUrl !== '') {
                    $this->recentUrls[strtolower($sourceUrl)] = true;
                }
            });

        return $this->recentUrls;
    }
}
