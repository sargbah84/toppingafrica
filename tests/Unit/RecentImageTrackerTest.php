<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Blog\RecentImageTracker;
use Tests\TestCase;

class RecentImageTrackerTest extends TestCase
{
    public function test_ranks_higher_resolution_candidate_first(): void
    {
        $tracker = new RecentImageTracker;

        // A barely-passing thumbnail appears first in raw search order; a
        // crisp landscape hero appears last. We should reorder so the hero
        // wins, instead of taking the first reachable candidate.
        $ranked = $tracker->rankCandidates([
            ['url' => 'https://example.com/small.jpg', 'width' => 640, 'height' => 480],
            ['url' => 'https://example.com/hero.jpg', 'width' => 1920, 'height' => 1280],
        ]);

        $this->assertSame('https://example.com/hero.jpg', $ranked[0]['url']);
        $this->assertSame('https://example.com/small.jpg', $ranked[1]['url']);
    }

    public function test_drops_sub_threshold_dimensions(): void
    {
        $tracker = new RecentImageTracker;

        $ranked = $tracker->rankCandidates([
            ['url' => 'https://example.com/tiny.jpg', 'width' => 300, 'height' => 200],
            ['url' => 'https://example.com/ok.jpg', 'width' => 1200, 'height' => 800],
        ]);

        $this->assertCount(1, $ranked);
        $this->assertSame('https://example.com/ok.jpg', $ranked[0]['url']);
    }

    public function test_keeps_candidates_with_unknown_dimensions(): void
    {
        $tracker = new RecentImageTracker;

        // Google sometimes omits dimensions — we can't judge size, so keep it
        // rather than dropping a potentially good hit.
        $ranked = $tracker->rankCandidates([
            ['url' => 'https://example.com/unknown.jpg'],
        ]);

        $this->assertCount(1, $ranked);
    }

    public function test_excludes_also_skip_urls_case_insensitively(): void
    {
        $tracker = new RecentImageTracker;

        $ranked = $tracker->rankCandidates(
            [
                ['url' => 'https://example.com/Used.jpg', 'width' => 1200, 'height' => 800],
                ['url' => 'https://example.com/fresh.jpg', 'width' => 1200, 'height' => 800],
            ],
            ['https://example.com/used.jpg'],
        );

        $this->assertCount(1, $ranked);
        $this->assertSame('https://example.com/fresh.jpg', $ranked[0]['url']);
    }
}
