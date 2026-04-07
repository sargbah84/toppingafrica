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
