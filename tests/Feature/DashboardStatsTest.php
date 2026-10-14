<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard\StatsOverview;
use App\Models\Post;
use App\Models\View;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 12:00:00');

        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subMonth()]);

        $view = fn (string $at, string $ip, string $device, ?string $referer = null) => View::create([
            'viewable_type' => Post::class,
            'viewable_id' => $post->id,
            'ip_hash' => $ip,
            'device_type' => $device,
            'referer' => $referer,
            'viewed_at' => $at,
        ]);

        // Today: 3 views from 2 visitors.
        $view('2026-10-14 08:00:00', 'a', 'desktop', 'https://www.google.com/search?q=x');
        $view('2026-10-14 09:00:00', 'a', 'mobile', 'https://www.google.com/search?q=y');
        $view('2026-10-14 10:00:00', 'b', 'mobile', 'https://t.co/abc');
        // Two days ago, at both ends of the day.
        $view('2026-10-12 00:00:00', 'c', 'desktop');
        $view('2026-10-12 23:59:59', 'c', 'tablet', '');
        // Outside the 30-day window.
        $view('2026-09-01 12:00:00', 'd', 'desktop');
    }

    public function test_daily_series_fills_every_day_in_the_window(): void
    {
        $component = new StatsOverview;

        $chart = $component->viewsGrowthChart();

        $this->assertCount(30, $chart);
        $this->assertSame(['date' => 'Sep 15', 'views' => 0], $chart[0]);
        $this->assertSame(['date' => 'Oct 12', 'views' => 2], $chart[27]);
        $this->assertSame(['date' => 'Oct 14', 'views' => 3], $chart[29]);
        $this->assertSame(5, array_sum(array_column($chart, 'views')));

        $traffic = $component->trafficByDay();

        $this->assertSame(['date' => 'Oct 14', 'views' => 3, 'unique' => 2], $traffic[29]);
        $this->assertSame(['date' => 'Oct 12', 'views' => 2, 'unique' => 1], $traffic[27]);
    }

    public function test_referrers_are_grouped_by_host(): void
    {
        $component = new StatsOverview;

        $referrers = $component->topReferrers()->map(fn ($r) => [$r->domain, $r->count])->all();

        $this->assertSame([['www.google.com', 2], ['t.co', 1]], $referrers);
    }

    public function test_device_breakdown_uses_period_totals(): void
    {
        $component = new StatsOverview;

        $this->assertSame([
            ['type' => 'Desktop', 'count' => 2, 'percentage' => 40.0],
            ['type' => 'Mobile', 'count' => 2, 'percentage' => 40.0],
            ['type' => 'Tablet', 'count' => 1, 'percentage' => 20.0],
        ], $component->deviceBreakdown());
    }
}
