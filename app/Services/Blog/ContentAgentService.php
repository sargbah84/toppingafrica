<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\ContentIdea;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ContentAgentService
{
    public function isEnabled(): bool
    {
        return (bool) filter_var(Setting::get('content_agent_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function config(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'posts_per_day' => (int) Setting::get('content_agent_posts_per_day', 4),
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
     * Pick the top N ideas eligible for today's run.
     * Prefers approved, then highest seo_score, then most recent.
     */
    public function pickIdeas(int $limit): Collection
    {
        return ContentIdea::query()
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q): void {
                $q->where('status', 'approved')
                    ->orWhere(function ($q2): void {
                        $q2->where('status', 'pending')->where('expires_at', '>', now());
                    });
            })
            ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
            ->orderByDesc('seo_score')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Compute N randomized scheduled_at slots for today within the window,
     * with a randomized gap (min..max hours) between each, plus ±15min jitter.
     *
     * @return CarbonImmutable[]
     */
    public function buildSlots(int $count, ?CarbonImmutable $baseDay = null): array
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

        // First slot: start anywhere in the first hour of the window (with jitter).
        $cursor = $windowStart->addMinutes(random_int(0, 59));

        // If today's window has already started, advance to "now + 5 min" earliest.
        $earliest = CarbonImmutable::now($tz)->addMinutes(5);
        if ($cursor->lt($earliest)) {
            $cursor = $earliest;
        }

        $slots = [];
        for ($i = 0; $i < $count; $i++) {
            if ($cursor->gt($windowEnd)) {
                break;
            }

            // Apply ±15min jitter per slot
            $jittered = $cursor->addMinutes(random_int(-15, 15));
            if ($jittered->lt($earliest)) {
                $jittered = $earliest;
            }
            if ($jittered->gt($windowEnd)) {
                $jittered = $windowEnd;
            }

            $slots[] = $jittered;

            // Advance cursor by a random gap inside [min, max]
            $gap = random_int($minGapMin, $maxGapMin);
            $cursor = $cursor->addMinutes($gap);
        }

        return $slots;
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
