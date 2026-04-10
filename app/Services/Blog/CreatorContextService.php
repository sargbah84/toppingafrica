<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\Creator;
use Illuminate\Support\Collection;

final class CreatorContextService
{
    /**
     * Fetch published creators by IDs and format them for AI prompt injection.
     *
     * @param  array<int>  $creatorIds
     */
    public function getCreatorContext(array $creatorIds): array
    {
        if (empty($creatorIds)) {
            return [];
        }

        $creators = Creator::whereIn('id', $creatorIds)
            ->where('status', 'published')
            ->with('socialLinks')
            ->get();

        return $creators->map(fn (Creator $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'country' => $c->country,
            'category' => $c->category,
            'bio' => $c->bio,
            'profile_url' => url('/creators/' . $c->slug),
            'follower_count' => $c->follower_count,
            'follower_platform' => $c->follower_platform,
            'is_featured' => $c->is_featured,
            'is_trending' => $c->is_trending,
            'is_rising' => $c->is_rising,
            'social_links' => $c->socialLinks->map(fn ($link) => [
                'platform' => $link->platform,
                'handle' => $link->handle,
                'url' => $link->url,
            ])->toArray(),
        ])->toArray();
    }

    /**
     * Build the prompt section that injects creator data into the AI prompt.
     */
    public function buildCreatorPromptSection(array $creators): string
    {
        if (empty($creators)) {
            return '';
        }

        $lines = ["FEATURED CREATOR PROFILES FROM TOPPING AFRICA DATABASE:"];
        $lines[] = "The following creators are registered on Topping Africa. Use ONLY this data when writing about them — do NOT fabricate or research additional details. Link to their Topping Africa profile pages.";
        $lines[] = '';

        foreach ($creators as $i => $creator) {
            $num = $i + 1;
            $lines[] = "--- Creator #{$num} ---";
            $lines[] = "Name: {$creator['name']}";
            $lines[] = "Country: {$creator['country']}";
            $lines[] = "Category: {$creator['category']}";
            $lines[] = "Profile URL: {$creator['profile_url']}";

            if (!empty($creator['bio'])) {
                $bio = strip_tags($creator['bio']);
                $lines[] = "Bio: {$bio}";
            }

            if (!empty($creator['follower_count']) && !empty($creator['follower_platform'])) {
                $lines[] = "Followers: {$creator['follower_count']} on {$creator['follower_platform']}";
            }

            $badges = [];
            if ($creator['is_featured']) {
                $badges[] = 'Featured';
            }
            if ($creator['is_trending']) {
                $badges[] = 'Trending';
            }
            if ($creator['is_rising']) {
                $badges[] = 'Rising';
            }
            if (!empty($badges)) {
                $lines[] = 'Status: ' . implode(', ', $badges);
            }

            if (!empty($creator['social_links'])) {
                $socials = array_map(
                    fn ($link) => "{$link['platform']}: {$link['handle']}",
                    $creator['social_links']
                );
                $lines[] = 'Social: ' . implode(', ', $socials);
            }

            $lines[] = '';
        }

        $lines[] = "IMPORTANT INSTRUCTIONS FOR CREATOR CONTENT:";
        $lines[] = "- Feature each creator with their REAL data from above. Do NOT invent facts.";
        $lines[] = "- Link to each creator's Topping Africa profile using: <a href=\"PROFILE_URL\">Creator Name</a>";
        $lines[] = "- Highlight their country, category, and any notable badges (Featured/Trending/Rising).";
        $lines[] = "- If writing a listicle or ranking, use these creators as the list entries.";
        $lines[] = "- You may organize/rank them based on the data provided (follower count, badges, etc.).";

        return "\n" . implode("\n", $lines) . "\n";
    }

    /**
     * Search published creators for the autocomplete selector.
     */
    public function searchCreators(string $query = '', ?string $country = null, ?string $category = null, int $limit = 20): Collection
    {
        return Creator::query()
            ->where('status', 'published')
            ->when($query, fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->when($country, fn ($q) => $q->where('country', $country))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderByDesc('is_featured')
            ->orderByDesc('is_trending')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'country', 'category', 'is_featured', 'is_trending', 'is_rising']);
    }
}
