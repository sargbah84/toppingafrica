<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageSearchService
{
    /**
     * Search Pexels for stock photos.
     *
     * @return array{results: array<int, array<string, mixed>>, total_results: int, page: int, per_page: int}
     */
    public function searchPexels(string $query, int $page = 1, int $perPage = 20): array
    {
        $apiKey = config('blog.media.pexels.api_key');

        if (! $apiKey) {
            return ['results' => [], 'total_results' => 0, 'page' => $page, 'per_page' => $perPage, 'error' => 'Pexels API key not configured.'];
        }

        $response = Http::withHeaders([
            'Authorization' => $apiKey,
        ])->get('https://api.pexels.com/v1/search', [
            'query' => $query,
            'page' => $page,
            'per_page' => $perPage,
        ]);

        if (! $response->successful()) {
            return ['results' => [], 'total_results' => 0, 'page' => $page, 'per_page' => $perPage, 'error' => 'Pexels API request failed.'];
        }

        $data = $response->json();

        $results = collect($data['photos'] ?? [])->map(fn (array $photo): array => [
            'id' => $photo['id'],
            'url' => $photo['src']['large'] ?? $photo['src']['original'],
            'thumb' => $photo['src']['medium'] ?? $photo['src']['small'],
            'photographer' => $photo['photographer'] ?? 'Unknown',
            'photographer_url' => $photo['photographer_url'] ?? '',
            'alt' => $photo['alt'] ?? '',
            'width' => $photo['width'],
            'height' => $photo['height'],
            'source' => 'pexels',
        ])->all();

        return [
            'results' => $results,
            'total_results' => $data['total_results'] ?? 0,
            'page' => $data['page'] ?? $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Search Google Images via Serper.dev API.
     *
     * @return array{results: array<int, array<string, mixed>>, total_results: int, start: int}
     */
    public function searchGoogle(string $query, int $start = 1): array
    {
        $apiKey = config('services.serper.api_key');

        if (! $apiKey) {
            return ['results' => [], 'total_results' => 0, 'start' => $start, 'error' => 'Serper API key not configured. Add SERPER_API_KEY to your .env file.'];
        }

        $page = max(1, (int) ceil($start / 10));

        $response = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://google.serper.dev/images', [
            'q' => $query,
            'page' => $page,
            'num' => 10,
        ]);

        if (! $response->successful()) {
            return ['results' => [], 'total_results' => 0, 'start' => $start, 'error' => 'Image search request failed: '.$response->status()];
        }

        $data = $response->json();

        $results = collect($data['images'] ?? [])->map(fn (array $item): array => [
            'url' => $item['imageUrl'] ?? '',
            'thumb' => $item['thumbnailUrl'] ?? $item['imageUrl'] ?? '',
            'title' => $item['title'] ?? '',
            'context_url' => $item['link'] ?? '',
            'width' => $item['imageWidth'] ?? 0,
            'height' => $item['imageHeight'] ?? 0,
            'source' => 'google',
        ])->all();

        return [
            'results' => $results,
            'total_results' => count($results) > 0 ? 1000 : 0,
            'start' => $start,
        ];
    }

    /**
     * Download an external image and store it on S3.
     *
     * @return string The full S3 URL of the stored image.
     */
    public function downloadToS3(string $url, ?string $filename = null): string
    {
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to download image from: '.$url);
        }

        $contentType = $response->header('Content-Type') ?? 'image/jpeg';
        $extension = match (true) {
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'gif') => 'gif',
            str_contains($contentType, 'webp') => 'webp',
            str_contains($contentType, 'svg') => 'svg',
            default => 'jpg',
        };

        if (! $filename) {
            $filename = Str::random(20).'.'.$extension;
        } elseif (! pathinfo($filename, PATHINFO_EXTENSION)) {
            $filename .= '.'.$extension;
        }

        $path = 'content/'.$filename;

        Storage::disk('s3')->put($path, $response->body(), 'public');

        $bucket = config('filesystems.disks.s3.bucket');

        return "https://{$bucket}.s3.amazonaws.com/{$path}";
    }
}
