<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WikimediaService
{
    public function searchCreatorImage(string $creatorName): ?array
    {
        try {
            $response = Http::get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'generator' => 'search',
                'gsrsearch' => $creatorName . ' portrait',
                'gsrnamespace' => 6,
                'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata',
                'iiurlwidth' => 400,
                'format' => 'json',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            $pages = $data['query']['pages'] ?? [];

            if (empty($pages)) {
                return null;
            }

            $firstPage = collect($pages)->first();
            $imageInfo = $firstPage['imageinfo'][0] ?? null;

            if (! $imageInfo) {
                return null;
            }

            $extMetadata = $imageInfo['extmetadata'] ?? [];

            $attribution = isset($extMetadata['Artist']['value'])
                ? strip_tags($extMetadata['Artist']['value'])
                : 'Unknown';

            $license = $extMetadata['LicenseShortName']['value'] ?? 'Unknown';

            return [
                'image_url' => $imageInfo['thumburl'] ?? $imageInfo['url'] ?? null,
                'attribution' => $attribution,
                'license' => $license,
            ];
        } catch (\Throwable $e) {
            Log::error('WikimediaService: Failed to search creator image', [
                'creator' => $creatorName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
