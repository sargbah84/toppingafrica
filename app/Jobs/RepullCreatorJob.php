<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Creator;
use App\Services\ClaudeBioService;
use App\Services\CreatorAvatarService;
use App\Services\CreatorSocialLinkBuilder;
use App\Services\PerplexityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Re-pull a single creator's AI-generated data (bio, social links, avatar,
 * follower estimate). Mirrors ManageCreators::repullCreator() but runs in
 * the queue so bulk operations don't block the admin request thread.
 *
 * Avatar lookup goes through CreatorAvatarService (Serper primary, Wikimedia
 * fallback, with the picked image downloaded to S3 for link-rot safety).
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
        CreatorAvatarService $avatar,
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
            $image = $avatar->fetch($creator->name, $creator->country);

            $newFollowerCount = DiscoverCreatorsJob::normalizeFollowerCount($match['estimated_follower_count'] ?? null);
            $newFollowerPlatform = DiscoverCreatorsJob::normalizeFollowerPlatform($match['follower_platform'] ?? null);
            $newContactEmail = DiscoverCreatorsJob::normalizeContactEmail($match['contact_email'] ?? null);

            $creator->update([
                'bio' => $bio,
                // Only overwrite on a confident new value — invalid/garbage AI
                // output must not wipe previously-good fields.
                'contact_email' => $newContactEmail ?? $creator->contact_email,
                'profile_image_url' => $image['image_url'] ?? $creator->getRawOriginal('profile_image_url'),
                'profile_image_attribution' => $image['attribution'] ?? $creator->profile_image_attribution,
                'profile_image_license' => $image['license'] ?? $creator->profile_image_license,
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
