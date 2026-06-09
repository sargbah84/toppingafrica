<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Creator;
use App\Services\Blog\CreatorDiscoveryService;
use App\Services\CreatorEnricher;
use App\Services\PerplexityService;
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

    /**
     * Validate and normalize a contact email from the AI. Returns the
     * trimmed lowercased email if it parses as RFC-compliant, or null
     * otherwise. We use FILTER_VALIDATE_EMAIL (shape check only, no DNS)
     * because Symfony Mime rejects anything that fails this same check —
     * so passing here guarantees the mail job won't crash later.
     */
    public static function normalizeContactEmail(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $v = strtolower(trim($value));

        if ($v === '') {
            return null;
        }

        return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
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
        CreatorEnricher $enricher,
        CreatorDiscoveryService $discovery,
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $creators = $perplexity->discoverCreators($this->niche, $this->country);

        if (empty($creators)) {
            return;
        }

        foreach ($creators as $creatorData) {
            $name = trim((string) ($creatorData['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            // Same strict name guard as post-discovery, so bulk discovery
            // stops minting common-noun creators (last, sale, ruth, ...).
            // No body to cross-check against here, so single-word names lean
            // on length + denylist only.
            if (! $discovery->isValidName($name, $name)) {
                Log::error('DiscoverCreatorsJob: rejected junk creator name', ['name' => $name]);

                continue;
            }

            // Duplicate detection on the normalized slug — variant-proof
            // against the same person showing up as "Burna Boy" / "burna boy".
            $slug = Str::slug($name);

            if (Creator::where('slug', $slug)->exists()) {
                continue;
            }

            try {
                $country = $creatorData['country'] ?? $this->country;

                $creator = Creator::create([
                    'name' => $name,
                    'country' => $country,
                    'category' => $creatorData['category'] ?? $this->niche,
                    'contact_email' => self::normalizeContactEmail($creatorData['contact_email'] ?? null),
                    'status' => 'pending',
                    'follower_count' => self::normalizeFollowerCount($creatorData['estimated_follower_count'] ?? null),
                    'follower_platform' => self::normalizeFollowerPlatform($creatorData['follower_platform'] ?? null),
                ]);

                // Bio + avatar + social links, shared with EnrichCreatorJob.
                $enricher->enrich($creator, $creatorData);
            } catch (\Throwable $e) {
                Log::error('DiscoverCreatorsJob: Failed to create creator', [
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
