<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Jobs\EnrichCreatorJob;
use App\Models\Creator;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns the AI's `mentioned_creators` list (real people referenced in a
 * generated article who aren't yet in our creators table) into creator
 * records, then hands their IDs back so ProcessIdeaJob can attach them to
 * the post alongside the already-known creators.
 *
 * Two concerns dominate the design:
 *
 *   - No duplicates. We dedup on the normalized SLUG, not the raw name —
 *     the AI emits the same person in varying forms across posts ("Burna
 *     Boy", "burna boy", "Burna  Boy"), and Str::slug collapses all of them
 *     to "burna-boy". This catches variants that an exact-name check misses.
 *     We also dedup within a single call so two mentions that slug-collide
 *     don't race to create the same record.
 *
 *   - No junk. Auto-creation runs on EVERY generated post, so a weak guard
 *     would flood the table with common nouns and stray words. isValidName()
 *     enforces a strict guard (length, denylist, multi-word OR proper-noun
 *     in the body) before anything is created. New creators are 'pending',
 *     keeping them out of public listings until an editor reviews them.
 */
final class CreatorDiscoveryService
{
    /**
     * Discover (match-or-create) creators from the AI's mentioned list.
     *
     * @param  array<int, array{name: string, category?: ?string, country?: ?string}>  $mentioned
     * @param  string  $bodyHtml  the post body, used to confirm names actually appear (capitalized) in the article
     * @return array<int, int> creator IDs (existing matches + newly created)
     */
    public function discover(array $mentioned, string $bodyHtml): array
    {
        if (! $this->isEnabled()) {
            return [];
        }
        if ($mentioned === []) {
            return [];
        }

        $maxPerPost = (int) config('blog.creator_discovery.max_per_post', 5);
        $plainBody = strip_tags($bodyHtml);

        $ids = [];
        $createdCount = 0;
        $seenSlugs = [];

        foreach ($mentioned as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $slug = Str::slug($name);
            if ($slug === '' || isset($seenSlugs[$slug])) {
                continue; // empty after slugging, or already handled this call
            }
            $seenSlugs[$slug] = true;

            // Existing creator? Match on slug (variant-proof) — never create
            // a duplicate, just reuse the record.
            $existingId = Creator::query()->where('slug', $slug)->value('id');
            if ($existingId !== null) {
                $ids[] = (int) $existingId;

                continue;
            }

            if (! $this->isValidName($name, $plainBody)) {
                continue;
            }

            if ($createdCount >= $maxPerPost) {
                Log::error('CreatorDiscoveryService: max_per_post reached, skipping remaining mentions', [
                    'max' => $maxPerPost,
                    'skipped_name' => $name,
                ]);
                break;
            }

            $creator = $this->createPending($name, $row);
            if ($creator !== null) {
                $ids[] = $creator->id;
                $createdCount++;
                EnrichCreatorJob::dispatch($creator->id);
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Whether creator discovery is on. The admin UI toggle (Content Calendar
     * settings) writes the `creator_discovery_enabled` Setting, which takes
     * precedence so the feature can be flipped at runtime without a deploy —
     * e.g. when discovery is misbehaving. Falls back to the config default
     * (env CREATOR_DISCOVERY_ENABLED) when no Setting row exists yet.
     */
    public function isEnabled(): bool
    {
        $configDefault = (bool) config('blog.creator_discovery.enabled', true);
        $setting = Setting::get('creator_discovery_enabled', $configDefault);

        return filter_var($setting, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Strict guard: is this a plausible real creator name, not junk?
     *
     * All conditions must hold:
     *   1. Length >= configured minimum (kills initials / tiny words).
     *   2. No token is on the denylist (common nouns, places, known junk).
     *   3. Multi-word OR the name appears capitalized as a proper noun in the
     *      article body — a single lowercased common word ("sale") fails both
     *      and is rejected.
     */
    public function isValidName(string $name, string $plainBody): bool
    {
        $name = trim($name);
        $minLen = (int) config('blog.creator_discovery.min_name_length', 4);

        if (mb_strlen($name) < $minLen) {
            return false;
        }

        // Reject anything with no letters (numbers, symbols, emoji only).
        if (! preg_match('/\p{L}/u', $name)) {
            return false;
        }

        $denylist = array_map('strtolower', (array) config('blog.creator_discovery.denylist', []));
        $tokens = preg_split('/\s+/', mb_strtolower($name)) ?: [];
        $tokens = array_values(array_filter($tokens, fn ($t) => $t !== ''));

        if ($tokens === []) {
            return false;
        }

        // Any denylisted token sinks the whole name ("African Tech" -> out).
        foreach ($tokens as $token) {
            if (in_array($token, $denylist, true)) {
                return false;
            }
        }

        $isMultiWord = count($tokens) >= 2;

        // Single-word names must prove themselves as a proper noun by
        // appearing capitalized in the body (word-boundary, case-SENSITIVE).
        if (! $isMultiWord) {
            $pattern = '/\b'.preg_quote($name, '/').'\b/u';
            if (! preg_match($pattern, $plainBody)) {
                return false;
            }
            // And the body occurrence must start with an uppercase letter.
            if (! preg_match('/\p{Lu}/u', mb_substr($name, 0, 1))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create a creator in 'pending' status with the name + any category/
     * country hint the AI supplied. Slug auto-generates via the model's
     * creating hook. Returns null on failure (logged, never throws).
     *
     * @param  array{category?: ?string, country?: ?string}  $row
     */
    private function createPending(string $name, array $row): ?Creator
    {
        try {
            // bio/country/category are NOT NULL with no DB default, so we seed
            // placeholders here. EnrichCreatorJob fills the real bio + (often)
            // a better country in the background — its enrich step only writes
            // bio when it's currently blank, so the empty string is the signal
            // to enrich rather than a value we're committing to.
            return Creator::create([
                'name' => $name,
                'status' => 'pending',
                'bio' => '',
                'category' => $this->cleanHint($row['category'] ?? null) ?? 'other',
                'country' => $this->cleanHint($row['country'] ?? null) ?? 'Unknown',
            ]);
        } catch (Throwable $e) {
            // A unique-slug collision means a concurrent job already created
            // it — recover by returning the existing row rather than failing.
            $existing = Creator::query()->where('slug', Str::slug($name))->first();
            if ($existing !== null) {
                return $existing;
            }

            Log::error('CreatorDiscoveryService: failed to create creator', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function cleanHint(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 100);
    }
}
