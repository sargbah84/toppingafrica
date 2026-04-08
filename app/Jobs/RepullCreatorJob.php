<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Creator;
use App\Services\ClaudeBioService;
use App\Services\CreatorSocialLinkBuilder;
use App\Services\PerplexityService;
use App\Services\WikimediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Re-pull a single creator's AI-generated data (bio, social links, image,
 * follower estimate). Mirrors ManageCreators::repullCreator() but runs in
 * the queue so bulk operations don't block the admin request thread.
 *
 * This is intentionally a full re-pull (not followers-only) to keep one
 * canonical "refresh from AI" code path.
 */
class RepullCreatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 2;

    public function __construct(public int $creatorId) {}

    public function handle(
        PerplexityService $perplexity,
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
        CreatorSocialLinkBuilder $linkBuilder,
    ): void {
        $creator = Creator::find($this->creatorId);

        if (! $creator) {
            // Deleted between dispatch and execution — nothing to do.
            return;
        }

        try {
            $results = $perplexity->discoverCreators($creator->category, $creator->country, 1);

            $match = collect($results)->first(function ($r) use ($creator) {
                return isset($r['name']) && Str::lower($r['name']) === Str::lower($creator->name);
            }) ?? ($results[0] ?? null);

            if (! $match) {
                Log::warning('RepullCreatorJob: no match from Perplexity', [
                    'creator_id' => $creator->id,
                    'name' => $creator->name,
                ]);
                return;
            }

            // Keep the existing name — the AI may have drifted on which creator
            // it returned, and we don't want to rewrite someone else's identity.
            $match['name'] = $creator->name;

            $bio = $claude->generateBio($match);
            $image = $wikimedia->searchCreatorImage($creator->name);

            $newFollowerCount = DiscoverCreatorsJob::normalizeFollowerCount($match['estimated_follower_count'] ?? null);
            $newFollowerPlatform = DiscoverCreatorsJob::normalizeFollowerPlatform($match['follower_platform'] ?? null);

            $creator->update([
                'bio' => $bio,
                'contact_email' => $match['contact_email'] ?? $creator->contact_email,
                'profile_image_url' => $image['image_url'] ?? $creator->getRawOriginal('profile_image_url'),
                'profile_image_attribution' => $image['attribution'] ?? $creator->profile_image_attribution,
                'profile_image_license' => $image['license'] ?? $creator->profile_image_license,
                // Only overwrite on a confident new value.
                'follower_count' => $newFollowerCount ?? $creator->follower_count,
                'follower_platform' => $newFollowerPlatform ?? $creator->follower_platform,
            ]);

            $linkBuilder->build($creator, $match);
        } catch (\Throwable $e) {
            Log::error('RepullCreatorJob: failed', [
                'creator_id' => $creator->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
