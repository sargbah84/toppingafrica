<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Creator;
use App\Services\ClaudeBioService;
use App\Services\CreatorSocialLinkBuilder;
use App\Services\PerplexityService;
use App\Services\WikimediaService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DiscoverCreatorsJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(
        public string $niche,
        public string $country,
    ) {}

    private const VALID_PLATFORMS = ['instagram', 'tiktok', 'youtube', 'twitter', 'facebook'];

    /**
     * Parse the LLM's `estimated_follower_count` field into a clean integer
     * or null. Accepts plain ints, "250K" / "1.5M" shorthand, and comma-formatted
     * strings because the model doesn't always follow the "integer" instruction.
     */
    public static function normalizeFollowerCount(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $v = strtolower(str_replace([',', ' '], '', $value));
            if (preg_match('/^([\d.]+)([km])?$/', $v, $m)) {
                $n = (float) $m[1];
                $mult = ($m[2] ?? '') === 'm' ? 1_000_000 : (($m[2] ?? '') === 'k' ? 1_000 : 1);
                $value = (int) round($n * $mult);
            } else {
                return null;
            }
        }

        $value = (int) $value;

        // Cap at 10B to guard against hallucinated absurdities while still
        // comfortably covering every real creator on the planet.
        if ($value <= 0 || $value > 10_000_000_000) {
            return null;
        }

        return $value;
    }

    public static function normalizeFollowerPlatform(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $v = strtolower(trim($value));

        return in_array($v, self::VALID_PLATFORMS, true) ? $v : null;
    }

    public function handle(
        PerplexityService $perplexity,
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
        CreatorSocialLinkBuilder $linkBuilder,
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $creators = $perplexity->discoverCreators($this->niche, $this->country);

        if (empty($creators)) {
            return;
        }

        foreach ($creators as $creatorData) {
            $name = $creatorData['name'] ?? null;

            if (! $name) {
                continue;
            }

            // Duplicate detection: check by exact name and by slug
            $slug = Str::slug($name);

            if (Creator::where('name', $name)->orWhere('slug', $slug)->exists()) {
                continue;
            }

            try {
                $bio = $claude->generateBio($creatorData);
                $image = $wikimedia->searchCreatorImage($name);

                $creator = Creator::create([
                    'name' => $name,
                    'bio' => $bio,
                    'country' => $creatorData['country'] ?? $this->country,
                    'category' => $creatorData['category'] ?? $this->niche,
                    'contact_email' => $creatorData['contact_email'] ?? null,
                    'status' => 'pending',
                    'profile_image_url' => $image['image_url'] ?? null,
                    'profile_image_attribution' => $image['attribution'] ?? null,
                    'profile_image_license' => $image['license'] ?? null,
                    'follower_count' => self::normalizeFollowerCount($creatorData['estimated_follower_count'] ?? null),
                    'follower_platform' => self::normalizeFollowerPlatform($creatorData['follower_platform'] ?? null),
                ]);

                $linkBuilder->build($creator, $creatorData);
            } catch (\Throwable $e) {
                Log::error('DiscoverCreatorsJob: Failed to create creator', [
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

}
