<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Post;
use App\Models\User;
use App\Notifications\AgentImageAttachmentFailed;
use App\Services\ImageSearchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Resolves and attaches a featured image to a Post.
 *
 * Selection order:
 *   1. Local media library (LibraryImageMatcher) when confidence is high —
 *      reuses curated assets and avoids hitting external APIs.
 *   2. Google Images (via Serper) — specific, real-world results.
 *   3. Pexels — licensed stock as a last resort.
 *   4. Low-confidence library matches — better than nothing.
 *
 * Within each source we walk EVERY candidate until one passes the
 * reachability check, so a single bad URL doesn't kill the whole pipeline
 * (the previous "first candidate or bust" behavior left ~half of agent
 * posts without images in production).
 */
final class FeaturedImageService
{
    /**
     * Total external candidates to try across Google + Pexels. Each one
     * costs a HEAD request and (on success) an addMediaFromUrl download,
     * so capping this bounds the job's wall time when many URLs are dead.
     */
    private const MAX_EXTERNAL_ATTEMPTS = 8;

    public function __construct(
        private readonly ImageSearchService $imageSearch,
        private readonly LibraryImageMatcher $libraryMatcher,
    ) {}

    /**
     * Attach a featured image to the given post.
     *
     * Skips entirely if the post already has a featured image (use $force
     * to override). Returns the public URL of the attached media on
     * success, null when nothing usable was found — in which case admins
     * are notified so they can sub in an image manually.
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

        $attempts = [];

        // 1. High-confidence library match — copy the existing media file
        // into the post's featured_image collection rather than re-uploading.
        // findBestForPost adds the post's attached creators + title/keyword
        // to the search signal so we pick the right profile photo when the
        // article is about a specific creator.
        $libraryHit = $this->libraryMatcher->findBestForPost($post, $query);
        if ($libraryHit && $libraryHit['score_normalized'] >= LibraryImageMatcher::HIGH_CONFIDENCE_THRESHOLD) {
            $attempts[] = ['source' => 'library_high', 'media_id' => $libraryHit['media']->id, 'score' => $libraryHit['score_normalized']];
            $url = $this->attachFromLibrary($post, $libraryHit['media']);
            if ($url !== null) {
                $this->logSuccess($post, $query, 'library_high', $url);

                return $url;
            }
        }

        // 2. External: Google → Pexels, walking every candidate.
        foreach ($this->externalCandidates($query) as $candidate) {
            if (count($attempts) >= self::MAX_EXTERNAL_ATTEMPTS) {
                break;
            }
            $attempts[] = ['source' => $candidate['source'], 'url' => $candidate['url']];

            if (! $this->isReachableImage($candidate['url'])) {
                continue;
            }

            $url = $this->attachFromUrl($post, $candidate['url']);
            if ($url !== null) {
                $this->logSuccess($post, $query, $candidate['source'], $url);

                return $url;
            }
        }

        // 3. Low-confidence library fallback — better than nothing.
        $libraryFallbacks = $this->libraryMatcher->findCandidates($query, 3);
        foreach ($libraryFallbacks as $candidate) {
            $attempts[] = ['source' => 'library_low', 'media_id' => $candidate['media']->id, 'score' => $candidate['score_normalized']];
            $url = $this->attachFromLibrary($post, $candidate['media']);
            if ($url !== null) {
                $this->logSuccess($post, $query, 'library_low', $url);

                return $url;
            }
        }

        $this->notifyAdmins($post, $query, $attempts);

        return null;
    }

    /**
     * Copy an existing library Media row into the post's featured_image
     * collection. The source library media row stays untouched so it can
     * be matched again for future posts. Uses addMediaFromUrl against the
     * source's public S3 URL because Spatie's S3-to-S3 copy isn't first
     * class — this round-trips through a temp file but is cheap and robust.
     */
    private function attachFromLibrary(Post $post, Media $sourceMedia): ?string
    {
        try {
            $sourceUrl = $sourceMedia->getFullUrl();
            if ($sourceUrl === '') {
                return null;
            }

            $media = $post->addMediaFromUrl($sourceUrl)
                ->usingFileName($sourceMedia->file_name)
                ->usingName($sourceMedia->name)
                ->withCustomProperties(array_merge(
                    is_array($sourceMedia->custom_properties) ? $sourceMedia->custom_properties : [],
                    ['reused_from_library_media_id' => $sourceMedia->id],
                ))
                ->toMediaCollection('featured_image_new');

            $post->clearMediaCollection('featured_image');
            $media->collection_name = 'featured_image';
            $media->save();

            return $media->getUrl();
        } catch (Throwable $e) {
            Log::error('FeaturedImageService: library copy failed', [
                'post_id' => $post->id,
                'source_media_id' => $sourceMedia->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Download a remote URL into the post's featured_image collection.
     * Uses a staging "featured_image_new" collection so the existing image
     * is preserved if addMediaFromUrl throws mid-flight.
     */
    private function attachFromUrl(Post $post, string $url): ?string
    {
        try {
            $media = $post->addMediaFromUrl($url)
                ->toMediaCollection('featured_image_new');

            $post->clearMediaCollection('featured_image');
            $media->collection_name = 'featured_image';
            $media->save();

            return $media->getUrl();
        } catch (Throwable $e) {
            Log::warning('FeaturedImageService: addMediaFromUrl failed', [
                'post_id' => $post->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Yield Google and Pexels candidates one at a time so the attach loop
     * can stop as soon as one succeeds.
     *
     * @return \Generator<array{url: string, source: string}>
     */
    private function externalCandidates(string $query): \Generator
    {
        try {
            $google = $this->imageSearch->searchGoogle($query);
        } catch (Throwable $e) {
            Log::warning('FeaturedImageService: Google search threw', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            $google = ['results' => []];
        }

        foreach ($google['results'] ?? [] as $result) {
            $url = $result['url'] ?? null;
            if (! is_string($url) || $url === '' || ! str_starts_with($url, 'http')) {
                continue;
            }

            // Skip obvious thumbnails when dimensions are reported. We can't
            // filter when dimensions are missing — many hits are still good.
            $width = (int) ($result['width'] ?? 0);
            $height = (int) ($result['height'] ?? 0);
            if (($width > 0 && $width < 600) || ($height > 0 && $height < 400)) {
                continue;
            }

            yield ['url' => $url, 'source' => 'google'];
        }

        try {
            $pexels = $this->imageSearch->searchPexels($query, 1, 10);
        } catch (Throwable $e) {
            Log::warning('FeaturedImageService: Pexels search threw', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            $pexels = ['results' => []];
        }

        foreach ($pexels['results'] ?? [] as $result) {
            $url = $result['url'] ?? null;
            if (is_string($url) && $url !== '') {
                yield ['url' => $url, 'source' => 'pexels'];
            }
        }
    }

    /**
     * Confirm a URL serves an image. Tries HEAD first (cheap), falls back
     * to a ranged GET when the server rejects HEAD (405 / 501) or returns
     * a Content-Type we can't trust — many image hosts (esp. behind a
     * CDN like Cloudflare) misbehave on HEAD but serve GET correctly.
     */
    private function isReachableImage(string $url): bool
    {
        $headers = [
            'Accept' => 'image/*',
            'User-Agent' => 'Mozilla/5.0 (compatible; ToppingAfricaBot/1.0)',
        ];

        try {
            $head = Http::timeout(8)->withHeaders($headers)->head($url);

            if ($head->successful()) {
                $contentType = strtolower((string) $head->header('Content-Type'));
                if ($contentType !== '' && str_starts_with($contentType, 'image/')) {
                    return true;
                }
            }

            // HEAD said "unsupported" — fall through to a ranged GET.
            if (! in_array($head->status(), [405, 501, 403, 0], true) && $head->successful()) {
                return false;
            }
        } catch (Throwable) {
            // Network error on HEAD — try GET before giving up.
        }

        try {
            $get = Http::timeout(8)
                ->withHeaders($headers + ['Range' => 'bytes=0-1023'])
                ->withOptions(['stream' => false])
                ->get($url);
        } catch (Throwable) {
            return false;
        }

        if (! $get->successful()) {
            return false;
        }

        $contentType = strtolower((string) $get->header('Content-Type'));
        if ($contentType !== '' && str_starts_with($contentType, 'image/')) {
            return true;
        }

        // Last-ditch: sniff the first few bytes for a known image signature
        // so servers that omit Content-Type don't get falsely rejected.
        $body = (string) $get->body();

        return $this->looksLikeImageMagic($body);
    }

    private function looksLikeImageMagic(string $bytes): bool
    {
        if ($bytes === '') {
            return false;
        }

        return str_starts_with($bytes, "\xFF\xD8\xFF")        // JPEG
            || str_starts_with($bytes, "\x89PNG\r\n\x1a\n")   // PNG
            || str_starts_with($bytes, 'GIF87a')              // GIF
            || str_starts_with($bytes, 'GIF89a')
            || (str_starts_with($bytes, 'RIFF') && str_contains(substr($bytes, 0, 16), 'WEBP'));
    }

    private function logSuccess(Post $post, string $query, string $source, string $url): void
    {
        Log::info('FeaturedImageService: attached', [
            'post_id' => $post->id,
            'source' => $source,
            'query' => $query,
            'url' => $url,
        ]);
    }

    /**
     * Send mail + database notification to every admin user. Failures here
     * never propagate — losing a notification shouldn't fail the job.
     */
    private function notifyAdmins(Post $post, string $query, array $attempts): void
    {
        Log::error('FeaturedImageService: all sources exhausted', [
            'post_id' => $post->id,
            'query' => $query,
            'attempt_count' => count($attempts),
        ]);

        activity('content_agent')
            ->event('image_attachment_failed')
            ->performedOn($post)
            ->withProperties([
                'query' => $query,
                'attempt_count' => count($attempts),
            ])
            ->log("Featured image missing — needs manual attach: \"{$post->title}\"");

        try {
            $admins = User::query()
                ->where(fn ($q) => $q->where('is_super_admin', true)->orWhere('is_staff', true))
                ->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new AgentImageAttachmentFailed(
                    postId: $post->id,
                    postTitle: $post->title,
                    query: $query,
                    attempts: $attempts,
                ));
            }
        } catch (Throwable $e) {
            Log::warning('FeaturedImageService: admin notify failed', [
                'post_id' => $post->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
