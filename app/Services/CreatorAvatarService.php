<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Creator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Resolves a creator avatar in two steps:
 *   1. Google Images via Serper.dev (preferred — best coverage for African creators).
 *   2. Wikimedia Commons (fallback — CC-licensed but thin coverage).
 *
 * Attachment goes through Spatie MediaLibrary's `profile_image` collection on
 * the Creator model, matching the manual-upload pipeline. This keeps a single
 * storage path on prod (Spatie → S3 via the collection's disk) and a single
 * URL accessor (Creator::profile_image_url already prefers Spatie media).
 *
 * Returns null when both sources fail so the frontend's initials fallback
 * kicks in.
 */
class CreatorAvatarService
{
    public function __construct(
        private readonly WikimediaService $wikimedia,
    ) {}

    /**
     * Fetch and attach an avatar to the given creator. Tries Serper first,
     * Wikimedia second. Returns metadata for the caller to persist (attribution,
     * license) or null if nothing was attached.
     *
     * @return array{attribution: ?string, license: ?string}|null
     */
    public function fetchAndAttach(Creator $creator, ?string $country = null): ?array
    {
        $country = $country ?? $creator->country;

        $serperResult = $this->trySerper($creator, $country);
        if ($serperResult !== null) {
            return $serperResult;
        }

        $wiki = $this->wikimedia->searchCreatorImage($creator->name);
        if (! empty($wiki['image_url'])) {
            if ($this->attachFromUrl($creator, $wiki['image_url'])) {
                return [
                    'attribution' => $wiki['attribution'] ?? null,
                    'license' => $wiki['license'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Run a live Serper search and return the top N results as raw items
     * (NOT attached). Used by the edit-modal picker so staff can choose.
     *
     * @return array<int, array{url: string, thumb: string, title: string, width: int, height: int}>
     */
    public function searchCandidates(string $query, int $limit = 12): array
    {
        $apiKey = config('services.serper.api_key');

        if (! $apiKey) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'X-API-KEY' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post('https://google.serper.dev/images', [
                'q' => $query,
                'num' => max(1, min($limit, 20)),
            ]);

            if (! $response->successful()) {
                Log::error('CreatorAvatarService: Serper search failed', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);

                return [];
            }

            $items = $response->json('images') ?? [];

            return collect($items)
                ->filter(fn ($i) => ! empty($i['imageUrl']))
                ->take($limit)
                ->map(fn ($i) => [
                    'url' => $i['imageUrl'],
                    'thumb' => $i['thumbnailUrl'] ?? $i['imageUrl'],
                    'title' => $i['title'] ?? '',
                    'width' => (int) ($i['imageWidth'] ?? 0),
                    'height' => (int) ($i['imageHeight'] ?? 0),
                ])
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::error('CreatorAvatarService: Serper search exception', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Attach a staff-picked URL to the creator's profile_image collection.
     * Throws on failure so the caller can surface the precise reason in the
     * picker UI (instead of swallowing it as null).
     */
    public function attachPickedUrl(Creator $creator, string $sourceUrl): void
    {
        $this->attachFromUrl($creator, $sourceUrl, throwOnFailure: true);
    }

    /**
     * @return array{attribution: ?string, license: ?string}|null
     */
    private function trySerper(Creator $creator, ?string $country): ?array
    {
        $query = trim($creator->name . ' ' . ($country ?? '') . ' portrait');
        $candidates = $this->searchCandidates($query, 5);

        foreach ($candidates as $candidate) {
            if ($this->attachFromUrl($creator, $candidate['url'])) {
                return [
                    'attribution' => 'Google Images (' . parse_url($candidate['url'], PHP_URL_HOST) . ')',
                    'license' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Download an external image into a temp file and attach it to the
     * creator's `profile_image` Spatie media collection. Spatie handles the
     * actual S3 write (per the collection's configured disk), matching the
     * manual-upload pipeline in ManageCreators::savePhotoFromBase64().
     *
     * Returns true on success, false on any failure (caller decides whether
     * to try the next candidate or surface the error).
     */
    private function attachFromUrl(Creator $creator, string $url, bool $throwOnFailure = false): bool
    {
        $tmpPath = null;

        try {
            $response = Http::withHeaders([
                // Bare Guzzle UA gets 403'd by Instagram CDN, Cloudflare-fronted
                // sites and several news hosts that Google indexes. A real
                // browser-like UA gets through almost everywhere.
                'User-Agent' => 'Mozilla/5.0 (compatible; ToppingAfricaBot/1.0; +https://toppingafrica.com)',
                'Accept' => 'image/avif,image/webp,image/png,image/jpeg,image/*;q=0.8,*/*;q=0.5',
            ])->timeout(30)->withOptions([
                'allow_redirects' => true,
            ])->get($url);

            if (! $response->successful()) {
                $msg = "HTTP {$response->status()} from {$url}";
                if ($throwOnFailure) {
                    throw new \RuntimeException($msg);
                }
                Log::error('CreatorAvatarService: download non-2xx', ['url' => $url, 'status' => $response->status()]);
                return false;
            }

            $body = $response->body();

            // Sanity guard: an HTML error page disguised as image/jpeg won't
            // crash Spatie but will give us a useless avatar.
            if (strlen($body) < 1024) {
                $msg = "Image too small ({$this->bytes(strlen($body))}) — likely an error page from {$url}";
                if ($throwOnFailure) {
                    throw new \RuntimeException($msg);
                }
                return false;
            }

            $contentType = strtolower($response->header('Content-Type') ?? 'image/jpeg');
            if (! str_starts_with($contentType, 'image/')) {
                $msg = "Non-image content-type ({$contentType}) from {$url}";
                if ($throwOnFailure) {
                    throw new \RuntimeException($msg);
                }
                return false;
            }

            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'gif') => 'gif',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'svg') => 'svg',
                default => 'jpg',
            };

            $slug = Str::slug($creator->name) ?: 'creator';
            $tmpPath = tempnam(sys_get_temp_dir(), 'creator_avatar_') . '.' . $extension;
            file_put_contents($tmpPath, $body);

            // Mirror the base64 upload flow: clear, then add. Spatie writes to
            // the collection's disk (S3 in prod) and the model's accessor
            // returns the resulting URL automatically.
            $creator->clearMediaCollection('profile_image');
            $creator->addMedia($tmpPath)
                ->usingFileName($slug . '.' . $extension)
                ->toMediaCollection('profile_image');

            return true;
        } catch (\Throwable $e) {
            if ($throwOnFailure) {
                throw $e;
            }
            Log::error('CreatorAvatarService: attach failed', [
                'url' => $url,
                'creator' => $creator->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        } finally {
            if ($tmpPath && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    private function bytes(int $n): string
    {
        return $n < 1024 ? "{$n}B" : round($n / 1024, 1) . 'KB';
    }
}
