<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resolves a creator avatar in two steps:
 *   1. Google Images via Serper.dev (preferred — best coverage for African creators).
 *   2. Wikimedia Commons (fallback — CC-licensed but thin coverage).
 *
 * The picked image is downloaded once and stored on S3, so the URL we save on
 * the Creator row points at our bucket — not at a Google-hosted thumbnail that
 * will rotate or 404 within weeks. Returns null when both sources are empty,
 * which keeps the existing initials-fallback flow on the frontend intact.
 */
class CreatorAvatarService
{
    public function __construct(
        private readonly WikimediaService $wikimedia,
    ) {}

    /**
     * @return array{image_url: string, attribution: ?string, license: ?string}|null
     */
    public function fetch(string $creatorName, ?string $country = null): ?array
    {
        $serper = $this->fromSerper($creatorName, $country);

        if ($serper) {
            return $serper;
        }

        // Wikimedia returns image_url + attribution + license keys already;
        // re-shape only if needed so callers see a stable contract.
        $wiki = $this->wikimedia->searchCreatorImage($creatorName);

        if (! empty($wiki['image_url'])) {
            $stored = $this->downloadToS3($wiki['image_url'], $creatorName);

            if ($stored) {
                return [
                    'image_url' => $stored,
                    'attribution' => $wiki['attribution'] ?? null,
                    'license' => $wiki['license'] ?? null,
                ];
            }
        }

        return null;
    }

    /**
     * Run a live Serper search and return the top N results as raw items
     * (NOT downloaded). Used by the edit-modal picker so staff can choose.
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
                Log::warning('CreatorAvatarService: Serper search failed', [
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
     * Download a user-picked image URL into S3 and return the permanent URL.
     * Used when staff pick a result from the manual Serper picker.
     */
    public function storePicked(string $sourceUrl, string $creatorName): ?string
    {
        return $this->downloadToS3($sourceUrl, $creatorName);
    }

    /**
     * @return array{image_url: string, attribution: ?string, license: ?string}|null
     */
    private function fromSerper(string $creatorName, ?string $country): ?array
    {
        $query = trim($creatorName . ' ' . ($country ?? '') . ' portrait');
        $candidates = $this->searchCandidates($query, 5);

        foreach ($candidates as $candidate) {
            $stored = $this->downloadToS3($candidate['url'], $creatorName);

            if ($stored) {
                return [
                    'image_url' => $stored,
                    // Serper results don't include explicit licensing — we
                    // tag the source for traceability but leave license null.
                    'attribution' => 'Google Images (' . parse_url($candidate['url'], PHP_URL_HOST) . ')',
                    'license' => null,
                ];
            }
        }

        return null;
    }

    /**
     * Download an external image to S3. Returns the permanent URL, or null on
     * any failure (so the caller can fall through to the next source).
     */
    private function downloadToS3(string $url, string $creatorName): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $body = $response->body();

            // Sanity guard against HTML error pages disguised as images.
            if (strlen($body) < 1024) {
                return null;
            }

            $contentType = $response->header('Content-Type') ?? 'image/jpeg';
            $extension = match (true) {
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'gif') => 'gif',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'svg') => 'svg',
                default => 'jpg',
            };

            $slug = Str::slug($creatorName) ?: 'creator';
            $path = 'creators/' . $slug . '-' . Str::random(8) . '.' . $extension;

            Storage::disk('s3')->put($path, $body, 'public');

            $bucket = config('filesystems.disks.s3.bucket');

            return "https://{$bucket}.s3.amazonaws.com/{$path}";
        } catch (\Throwable $e) {
            Log::warning('CreatorAvatarService: download failed', [
                'url' => $url,
                'creator' => $creatorName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
