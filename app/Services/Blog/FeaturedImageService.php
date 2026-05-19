<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Services\ImageSearchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resolves and attaches a featured image to a Post.
 *
 * Strategy: Google Images (via Serper) first because it yields more
 * specific, real-world results that match an article's actual subject.
 * Falls back to Pexels stock if Google returns nothing usable. Stores
 * the result on the Post's `featured_image` Spatie media collection.
 */
final class FeaturedImageService
{
    public function __construct(
        private readonly ImageSearchService $imageSearch,
    ) {}

    /**
     * Attach a featured image to the given post by running the supplied
     * search query against Google Images, falling back to Pexels.
     *
     * Skips entirely if the post already has a featured image (use $force
     * to override). Validates that the resolved URL actually returns an
     * image before clearing any existing media, so a failed lookup can
     * never destroy a previously-attached image.
     *
     * Returns the public URL of the attached media on success, null when
     * nothing usable was found.
     */
    public function attach(Post $post, string $query, bool $force = false): ?string
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        if (! $force && $post->getFirstMedia('featured_image')) {
            return null;
        }

        $imageUrl = $this->findGoogleImage($query) ?? $this->findPexelsImage($query);

        if (! $imageUrl) {
            Log::info('FeaturedImageService: no image found', [
                'post_id' => $post->id,
                'query' => $query,
            ]);

            return null;
        }

        if (! $this->isReachableImage($imageUrl)) {
            Log::info('FeaturedImageService: candidate URL not a reachable image', [
                'post_id' => $post->id,
                'url' => $imageUrl,
            ]);

            return null;
        }

        try {
            // Only clear after we know the new image downloads successfully.
            // addMediaFromUrl throws UnreachableUrl synchronously if the
            // remote returns a non-2xx; doing it first preserves the prior
            // image when the new one fails.
            $media = $post->addMediaFromUrl($imageUrl)
                ->toMediaCollection('featured_image_new');

            // Promote: clear the old collection then move the new media.
            $post->clearMediaCollection('featured_image');
            $media->collection_name = 'featured_image';
            $media->save();

            return $media->getUrl();
        } catch (Throwable $e) {
            Log::warning('FeaturedImageService: failed to attach', [
                'post_id' => $post->id,
                'query' => $query,
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function isReachableImage(string $url): bool
    {
        try {
            $head = Http::timeout(10)->withHeaders([
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

    private function findGoogleImage(string $query): ?string
    {
        try {
            $response = $this->imageSearch->searchGoogle($query);
        } catch (Throwable $e) {
            Log::warning('FeaturedImageService: Google search threw', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        foreach ($response['results'] ?? [] as $result) {
            $url = $result['url'] ?? null;
            if (! is_string($url) || $url === '') {
                continue;
            }

            // Skip obviously-too-small thumbnails and any data:/non-http URLs.
            $width = (int) ($result['width'] ?? 0);
            $height = (int) ($result['height'] ?? 0);
            if ($width > 0 && $width < 600) {
                continue;
            }
            if ($height > 0 && $height < 400) {
                continue;
            }
            if (! str_starts_with($url, 'http')) {
                continue;
            }

            return $url;
        }

        return null;
    }

    private function findPexelsImage(string $query): ?string
    {
        try {
            $response = $this->imageSearch->searchPexels($query, 1, 5);
        } catch (Throwable $e) {
            Log::warning('FeaturedImageService: Pexels search threw', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        foreach ($response['results'] ?? [] as $result) {
            $url = $result['url'] ?? null;
            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return null;
    }
}
