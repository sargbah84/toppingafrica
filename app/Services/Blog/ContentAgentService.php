<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\ContentIdea;
use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ContentAgentService
{
    public function isEnabled(): bool
    {
        return (bool) filter_var(Setting::get('content_agent_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function config(): array
    {
        // Back-compat: callers that previously set a single posts_per_day get
        // upgraded transparently. Min and max both default to whatever the
        // old key holds (or 4 if neither was set), so existing installs keep
        // the same fixed-count behavior until the admin widens the range.
        $legacyCount = (int) Setting::get('content_agent_posts_per_day', 4);
        $min = (int) Setting::get('content_agent_posts_per_day_min', $legacyCount);
        $max = (int) Setting::get('content_agent_posts_per_day_max', $legacyCount);
        if ($max < $min) {
            $max = $min;
        }

        return [
            'enabled' => $this->isEnabled(),
            'posts_per_day_min' => $min,
            'posts_per_day_max' => $max,
            'window_start' => (int) Setting::get('content_agent_window_start', 7),
            'window_end' => (int) Setting::get('content_agent_window_end', 21),
            'min_gap' => (float) Setting::get('content_agent_min_gap', 1.5),
            'max_gap' => (float) Setting::get('content_agent_max_gap', 2.5),
            'run_time' => (string) Setting::get('content_agent_run_time', '06:00'),
            'min_seo_score' => (int) Setting::get('content_agent_min_seo_score', 70),
            'max_improve_attempts' => (int) Setting::get('content_agent_max_improve_attempts', 3),
            'instructions' => (string) Setting::get('content_agent_instructions', ''),
            'avoid_topics' => (string) Setting::get('content_agent_avoid_topics', ''),
            'emphasize_topics' => (string) Setting::get('content_agent_emphasize_topics', ''),
        ];
    }

    /**
     * Choose how many posts the agent will produce on the given Lagos day.
     * Deterministic — same date always yields the same count — so dry-run
     * previews match what the actual run will do.
     */
    public function rollPostsCount(?CarbonImmutable $baseDay = null): int
    {
        $config = $this->config();
        $min = max(1, $config['posts_per_day_min']);
        $max = max($min, $config['posts_per_day_max']);

        if ($min === $max) {
            return $min;
        }

        $tz = 'Africa/Lagos';
        $baseDay = ($baseDay ?? CarbonImmutable::now($tz))->setTimezone($tz);
        // Use a distinct counter offset from buildSlots() so we don't draw
        // the same value the slot-builder uses for its first jitter.
        $seed = (int) $baseDay->format('Ymd');
        $value = ($seed * 1664525 + 1013904223) & 0x7fffffff;

        return $min + ($value % ($max - $min + 1));
    }

    /**
     * Pick the top N ideas using a weighted scoring algorithm that combines:
     * - Perplexity seo_score (base, 0-100)
     * - +30 if approved by a human
     * - +20 if matches Emphasize lists
     * - HARD EXCLUDE if matches Avoid lists
     * - -25 if title >70% similar to one published in last 7 days
     * - -25 if title >70% similar to one already picked in this run
     * - -15 per same-niche idea already picked in this run (encourages diversity)
     * - +5 * log(avg_views_for_this_niche) (light historical performance nudge)
     *
     * @return EloquentCollection<int, ContentIdea>
     */
    public function pickIdeas(int $limit): EloquentCollection
    {
        $candidates = ContentIdea::query()
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q): void {
                $q->where('status', 'approved')
                    ->orWhere(function ($q2): void {
                        $q2->where('status', 'pending')->where('expires_at', '>', now());
                    });
            })
            ->get();

        if ($candidates->isEmpty()) {
            return new EloquentCollection;
        }

        $config = $this->config();
        $avoidTerms = $this->splitLines($config['avoid_topics']);
        $emphasizeTerms = $this->splitLines($config['emphasize_topics']);
        $recentTitles = $this->recentlyPublishedTitles();
        $nichePerformance = $this->nichePerformanceLookup();

        // Drop hard-excluded candidates up front so they never compete.
        $candidates = $candidates->reject(fn (ContentIdea $i) => $this->matchesAny($i, $avoidTerms))->values();

        $picked = collect();
        $pickedNiches = collect();

        for ($i = 0; $i < $limit; $i++) {
            if ($candidates->isEmpty()) {
                break;
            }

            $best = null;
            $bestScore = -INF;

            foreach ($candidates as $candidate) {
                $score = $this->scoreIdea(
                    $candidate,
                    $emphasizeTerms,
                    $recentTitles,
                    $picked,
                    $pickedNiches,
                    $nichePerformance,
                );

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $candidate;
                }
            }

            if ($best === null) {
                break;
            }

            $picked->push($best);
            if ($best->niche) {
                $pickedNiches->push($best->niche);
            }
            $candidates = $candidates->reject(fn (ContentIdea $c) => $c->id === $best->id)->values();
        }

        return new EloquentCollection($picked->all());
    }

    private function scoreIdea(
        ContentIdea $idea,
        array $emphasizeTerms,
        Collection $recentTitles,
        Collection $picked,
        Collection $pickedNiches,
        Collection $nichePerformance,
    ): float {
        $score = (float) ($idea->seo_score ?? 0);

        if ($idea->status === 'approved') {
            $score += 30;
        }

        if ($this->matchesAny($idea, $emphasizeTerms)) {
            $score += 20;
        }

        $title = (string) $idea->title;

        foreach ($recentTitles as $publishedTitle) {
            if ($this->titlesSimilar($title, (string) $publishedTitle, 70)) {
                $score -= 25;
                break;
            }
        }

        foreach ($picked as $alreadyPicked) {
            if ($this->titlesSimilar($title, (string) $alreadyPicked->title, 70)) {
                $score -= 25;
                break;
            }
        }

        if ($idea->niche) {
            $samenicheCount = $pickedNiches->filter(fn ($n) => $n === $idea->niche)->count();
            $score -= 15 * $samenicheCount;

            $avgViews = (float) $nichePerformance->get($idea->niche, 0);
            if ($avgViews > 1) {
                $score += 5 * log($avgViews);
            }
        }

        return $score;
    }

    /**
     * @return Collection<int, string>
     */
    private function recentlyPublishedTitles(): Collection
    {
        return Post::query()
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays(7))
            ->pluck('title');
    }

    /**
     * Map of niche => avg views_count across last 90 days of published posts.
     * Only populated if posts carry a `niche` metadata or category we can join on.
     * For now returns empty when no niche signal exists on posts.
     *
     * @return Collection<string, float>
     */
    private function nichePerformanceLookup(): Collection
    {
        // ContentIdea.niche is a free-text label (e.g. 'tech-startups'). Posts
        // don't carry niche directly, so we approximate via the category names
        // most ideas were generated under. If you later add a `niche` column
        // to posts, replace this with a direct GROUP BY on it.
        return collect();
    }

    private function matchesAny(ContentIdea $idea, array $terms): bool
    {
        if ($terms === []) {
            return false;
        }

        $haystack = strtolower(implode(' ', array_filter([
            $idea->title,
            $idea->niche,
            $idea->suggested_keyword,
            $idea->description,
        ])));

        foreach ($terms as $term) {
            $needle = strtolower(trim($term));
            if ($needle !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function titlesSimilar(string $a, string $b, int $threshold): bool
    {
        $normA = $this->normalizeTitle($a);
        $normB = $this->normalizeTitle($b);

        if ($normA === '' || $normB === '') {
            return false;
        }

        similar_text($normA, $normB, $percent);

        return $percent >= $threshold;
    }

    private function normalizeTitle(string $title): string
    {
        return Str::of($title)
            ->lower()
            ->replaceMatches('/[^a-z0-9 ]/i', ' ')
            ->squish()
            ->value();
    }

    /**
     * Compute N randomized scheduled_at slots for today within the window,
     * with a randomized gap (min..max hours) between each, plus ±15min jitter.
     *
     * @return CarbonImmutable[]
     */
    public function buildSlots(int $count, ?CarbonImmutable $baseDay = null, bool $deterministic = false): array
    {
        if ($count <= 0) {
            return [];
        }

        $config = $this->config();
        $minGapMin = (int) round($config['min_gap'] * 60);
        $maxGapMin = max($minGapMin, (int) round($config['max_gap'] * 60));

        $tz = 'Africa/Lagos';
        $baseDay = ($baseDay ?? CarbonImmutable::now($tz))->setTimezone($tz);

        $windowStart = $baseDay->setTime($config['window_start'], 0);
        $windowEnd = $baseDay->setTime($config['window_end'], 0);

        // When called from dry-run we want stable, repeatable output so the
        // preview matches what tomorrow's actual run will produce. We use
        // a date-seeded PRNG: same calendar day -> same slots, every click.
        $rng = $deterministic
            ? $this->seededRandom((int) $baseDay->format('Ymd'))
            : null;
        $rand = fn (int $min, int $max): int => $rng ? $rng($min, $max) : random_int($min, $max);

        // First slot: start anywhere in the first hour of the window (with jitter).
        $cursor = $windowStart->addMinutes($rand(0, 59));

        // If today's window has already started, advance to "now + 5 min" earliest.
        // Skipped for deterministic mode so a dry-run preview always reflects the
        // FULL day's plan even if we're past noon.
        $earliest = $deterministic ? $windowStart : CarbonImmutable::now($tz)->addMinutes(5);
        if ($cursor->lt($earliest)) {
            $cursor = $earliest;
        }

        $slots = [];
        for ($i = 0; $i < $count; $i++) {
            if ($cursor->gt($windowEnd)) {
                break;
            }

            // Apply ±15min jitter per slot
            $jittered = $cursor->addMinutes($rand(-15, 15));
            if ($jittered->lt($earliest)) {
                $jittered = $earliest;
            }
            if ($jittered->gt($windowEnd)) {
                $jittered = $windowEnd;
            }

            $slots[] = $jittered;

            // Advance cursor by a random gap inside [min, max]
            $gap = $rand($minGapMin, $maxGapMin);
            $cursor = $cursor->addMinutes($gap);
        }

        return $slots;
    }

    /**
     * Return a deterministic random-int generator seeded by the given integer.
     * mt_srand is global state; we restore the previous seed via mt_rand after.
     */
    private function seededRandom(int $seed): callable
    {
        $counter = 0;

        return function (int $min, int $max) use ($seed, &$counter): int {
            // Linear congruential mix: same seed + same counter -> same value.
            // Counter is captured by reference so successive calls within ONE
            // buildSlots() invocation produce different (but deterministic) values,
            // while two separate dry-runs on the same day produce the same sequence.
            $counter++;
            $value = ($seed * 1103515245 + 12345 + ($counter * 2654435761)) & 0x7fffffff;

            return $min + ($value % ($max - $min + 1));
        };
    }

    /**
     * Compose the editorial guidance block injected into AI prompts.
     * Returns empty string when no guidance has been configured.
     */
    public function buildEditorialGuidance(): string
    {
        $config = $this->config();
        $sections = [];

        if (trim($config['instructions']) !== '') {
            $sections[] = "House style and instructions:\n".trim($config['instructions']);
        }

        $emphasize = $this->splitLines($config['emphasize_topics']);
        if ($emphasize !== []) {
            $sections[] = "Emphasize these themes when relevant:\n- ".implode("\n- ", $emphasize);
        }

        $avoid = $this->splitLines($config['avoid_topics']);
        if ($avoid !== []) {
            $sections[] = "Avoid these themes entirely:\n- ".implode("\n- ", $avoid);
        }

        if ($sections === []) {
            return '';
        }

        return "\n\nEDITORIAL GUIDANCE (overrides defaults; follow strictly):\n".implode("\n\n", $sections)."\n\n";
    }

    /**
     * @return string[]
     */
    private function splitLines(string $raw): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }
}
