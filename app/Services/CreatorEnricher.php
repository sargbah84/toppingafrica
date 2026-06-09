<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Creator;

/**
 * Fills in a creator's bio, avatar, and social links. Shared by the two
 * creation paths so the enrichment logic lives in one place:
 *
 *   - DiscoverCreatorsJob — bulk niche discovery via Perplexity.
 *   - EnrichCreatorJob — creators auto-discovered from generated post bodies.
 *
 * Operates on an existing Creator row plus the loose data array we have about
 * them (which may be just a name for post-discovered creators). Every step is
 * best-effort: a failure in one enrichment never blocks the others, and the
 * creator already exists regardless.
 */
final class CreatorEnricher
{
    public function __construct(
        private readonly ClaudeBioService $bio,
        private readonly CreatorAvatarService $avatar,
        private readonly CreatorSocialLinkBuilder $linkBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $data  what we know about the creator
     *                                      (at minimum 'name'); may carry
     *                                      category/country/follower hints.
     */
    public function enrich(Creator $creator, array $data): void
    {
        $data['name'] ??= $creator->name;
        $country = is_string($data['country'] ?? null) && $data['country'] !== ''
            ? $data['country']
            : $creator->country;

        // Bio — only generate when we don't already have one.
        if (blank($creator->bio)) {
            $bio = $this->bio->generateBio($data);
            if ($bio !== '') {
                $creator->update(['bio' => $bio]);
            }
        }

        // Avatar — only fetch when none is attached yet.
        if (blank($creator->profile_image_url)) {
            $imageMeta = $this->avatar->fetchAndAttach($creator, $country);
            if ($imageMeta !== null) {
                $creator->update([
                    'profile_image_attribution' => $imageMeta['attribution'],
                    'profile_image_license' => $imageMeta['license'],
                ]);
            }
        }

        $this->linkBuilder->build($creator, $data);
    }
}
