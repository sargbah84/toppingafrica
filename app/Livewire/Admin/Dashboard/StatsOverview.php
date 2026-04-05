<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Dashboard;

use App\Models\AiUsageLog;
use App\Models\Comment;
use App\Models\NewsletterSubscriber;
use App\Models\Post;
use App\Models\PostView;
use App\Models\SearchConsoleData;
use App\Models\SeoAnalysis;
use App\Models\User;
use App\Services\GoogleSearchConsoleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StatsOverview extends Component
{
    public string $activeTab = 'overview';

    public string $timePeriod = 'month';

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['overview', 'traffic', 'search', 'tools', 'content', 'growth', 'engagement'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function setTimePeriod(string $period): void
    {
        if (in_array($period, ['today', 'week', 'month', 'all'], true)) {
            $this->timePeriod = $period;
            $this->dateFrom = null;
            $this->dateTo = null;
        }
    }

    public function setDateRange(): void
    {
        if ($this->dateFrom && $this->dateTo) {
            $this->timePeriod = 'custom';
        }
    }

    private function periodStart(): ?Carbon
    {
        return match ($this->timePeriod) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'custom' => $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null,
            'all' => null,
        };
    }

    private function periodEnd(): ?Carbon
    {
        if ($this->timePeriod === 'custom' && $this->dateTo) {
            return Carbon::parse($this->dateTo)->endOfDay();
        }

        return null;
    }

    private function viewsQuery()
    {
        $query = PostView::query();
        $start = $this->periodStart();
        $end = $this->periodEnd();

        if ($start) {
            $query->where('viewed_at', '>=', $start);
        }
        if ($end) {
            $query->where('viewed_at', '<=', $end);
        }

        return $query;
    }

    // ── Stat Cards ──

    #[Computed]
    public function totalPosts(): int
    {
        return Post::count();
    }

    #[Computed]
    public function totalUsers(): int
    {
        return User::count();
    }

    #[Computed]
    public function pageViews(): int
    {
        return $this->viewsQuery()->count();
    }

    #[Computed]
    public function totalSubscribers(): int
    {
        return NewsletterSubscriber::count();
    }

    // ── Overview Tab ──

    #[Computed]
    public function viewsGrowthChart(): array
    {
        $days = 30;
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = [
                'date' => $date->format('M d'),
                'views' => PostView::whereDate('viewed_at', $date->toDateString())->count(),
            ];
        }

        return $data;
    }

    #[Computed]
    public function postsByStatus(): array
    {
        return [
            'published' => Post::where('status', 'published')->count(),
            'draft' => Post::where('status', 'draft')->count(),
            'scheduled' => Post::where('status', 'scheduled')->count(),
        ];
    }

    #[Computed]
    public function topPages(): Collection
    {
        $query = PostView::query()
            ->join('posts', 'post_views.post_id', '=', 'posts.id')
            ->select('posts.title', 'posts.slug', DB::raw('COUNT(post_views.id) as view_count'));

        $start = $this->periodStart();
        $end = $this->periodEnd();

        if ($start) {
            $query->where('post_views.viewed_at', '>=', $start);
        }
        if ($end) {
            $query->where('post_views.viewed_at', '<=', $end);
        }

        return $query->groupBy('posts.id', 'posts.title', 'posts.slug')
            ->orderByDesc('view_count')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function topReferrers(): Collection
    {
        $query = PostView::query()
            ->whereNotNull('referer')
            ->where('referer', '!=', '');

        $start = $this->periodStart();
        $end = $this->periodEnd();

        if ($start) {
            $query->where('viewed_at', '>=', $start);
        }
        if ($end) {
            $query->where('viewed_at', '<=', $end);
        }

        return $query->select(DB::raw('referer, COUNT(*) as count'))
            ->groupBy('referer')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $parsed = parse_url($item->referer);
                $item->domain = $parsed['host'] ?? $item->referer;

                return $item;
            });
    }

    #[Computed]
    public function deviceBreakdown(): array
    {
        $query = $this->viewsQuery();
        $total = $query->count();

        if ($total === 0) {
            return [
                ['type' => 'Desktop', 'count' => 0, 'percentage' => 0],
                ['type' => 'Mobile', 'count' => 0, 'percentage' => 0],
                ['type' => 'Tablet', 'count' => 0, 'percentage' => 0],
            ];
        }

        $devices = $this->viewsQuery()
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->pluck('count', 'device_type')
            ->toArray();

        return collect(['desktop', 'mobile', 'tablet'])->map(function ($type) use ($devices, $total) {
            $count = $devices[$type] ?? 0;

            return [
                'type' => ucfirst($type),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        })->filter(fn ($d) => $d['count'] > 0)->values()->toArray();
    }

    // ── Traffic Tab ──

    #[Computed]
    public function trafficByDay(): array
    {
        $days = match ($this->timePeriod) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            default => 30,
        };

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayViews = PostView::whereDate('viewed_at', $date->toDateString())->count();
            $uniqueViews = PostView::whereDate('viewed_at', $date->toDateString())->distinct('ip_hash')->count('ip_hash');
            $data[] = [
                'date' => $date->format('M d'),
                'views' => $dayViews,
                'unique' => $uniqueViews,
            ];
        }

        return $data;
    }

    #[Computed]
    public function uniqueVisitors(): int
    {
        return $this->viewsQuery()->distinct('ip_hash')->count('ip_hash');
    }

    #[Computed]
    public function avgViewsPerDay(): float
    {
        $days = match ($this->timePeriod) {
            'today' => 1,
            'week' => 7,
            'month' => 30,
            'all' => max(1, (int) PostView::min('viewed_at') ? now()->diffInDays(PostView::min('viewed_at')) : 1),
            default => 30,
        };

        return round($this->pageViews / max($days, 1), 1);
    }

    // ── Content Tab ──

    #[Computed]
    public function recentPosts(): Collection
    {
        return Post::query()
            ->with('author:id,name')
            ->select(['id', 'title', 'slug', 'status', 'author_id', 'views_count', 'created_at', 'published_at'])
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function postsByType(): Collection
    {
        return Post::query()
            ->select('post_type', DB::raw('COUNT(*) as count'))
            ->groupBy('post_type')
            ->orderByDesc('count')
            ->get();
    }

    #[Computed]
    public function topCategories(): Collection
    {
        return DB::table('category_post')
            ->join('categories', 'category_post.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('COUNT(*) as post_count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('post_count')
            ->limit(10)
            ->get();
    }

    // ── Engagement Tab ──

    #[Computed]
    public function recentComments(): Collection
    {
        return Comment::query()
            ->with(['post:id,title,slug', 'user:id,name'])
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function commentStats(): array
    {
        return [
            'total' => Comment::count(),
            'approved' => Comment::where('status', 'approved')->count(),
            'pending' => Comment::where('status', 'pending')->count(),
        ];
    }

    #[Computed]
    public function popularPosts(): Collection
    {
        return Post::query()
            ->published()
            ->select(['id', 'title', 'slug', 'views_count', 'published_at'])
            ->orderByDesc('views_count')
            ->limit(10)
            ->get();
    }

    // ── Tools (AI Usage) Tab ──

    #[Computed]
    public function aiTotalRequests(): int
    {
        return AiUsageLog::count();
    }

    #[Computed]
    public function aiTotalTokens(): int
    {
        return (int) AiUsageLog::sum('total_tokens');
    }

    #[Computed]
    public function aiTotalCost(): float
    {
        return (float) AiUsageLog::sum('estimated_cost');
    }

    #[Computed]
    public function aiSuccessRate(): float
    {
        $total = AiUsageLog::count();
        if ($total === 0) {
            return 0;
        }

        return round((AiUsageLog::where('is_successful', true)->count() / $total) * 100, 1);
    }

    #[Computed]
    public function aiUsageByProvider(): Collection
    {
        return AiUsageLog::select(
            'provider',
            DB::raw('COUNT(*) as requests'),
            DB::raw('SUM(input_tokens) as input_tokens'),
            DB::raw('SUM(output_tokens) as output_tokens'),
            DB::raw('SUM(total_tokens) as total_tokens'),
            DB::raw('SUM(estimated_cost) as total_cost'),
            DB::raw('AVG(duration_ms) as avg_duration'),
        )
            ->groupBy('provider')
            ->orderByDesc('requests')
            ->get();
    }

    #[Computed]
    public function aiUsageByFeature(): Collection
    {
        return AiUsageLog::select(
            'feature',
            DB::raw('COUNT(*) as requests'),
            DB::raw('SUM(total_tokens) as total_tokens'),
            DB::raw('SUM(estimated_cost) as total_cost'),
        )
            ->groupBy('feature')
            ->orderByDesc('requests')
            ->get();
    }

    #[Computed]
    public function aiUsageTrend(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayLogs = AiUsageLog::whereDate('created_at', $date->toDateString());
            $data[] = [
                'date' => $date->format('M d'),
                'requests' => $dayLogs->count(),
                'tokens' => (int) $dayLogs->sum('total_tokens'),
                'cost' => (float) $dayLogs->sum('estimated_cost'),
            ];
        }

        return $data;
    }

    #[Computed]
    public function aiRecentLogs(): Collection
    {
        return AiUsageLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit(15)
            ->get();
    }

    // ── Search (GSC) Tab ──

    #[Computed]
    public function gscIsConfigured(): bool
    {
        return GoogleSearchConsoleService::isConfigured();
    }

    #[Computed]
    public function gscNeedsConnection(): bool
    {
        return GoogleSearchConsoleService::needsConnection();
    }

    #[Computed]
    public function gscOAuthUrl(): string
    {
        return GoogleSearchConsoleService::hasCredentials()
            ? GoogleSearchConsoleService::getOAuthUrl()
            : '';
    }

    #[Computed]
    public function gscTopQueries(): Collection
    {
        return SearchConsoleData::select(
            'query',
            DB::raw('SUM(clicks) as total_clicks'),
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('AVG(position) as avg_position'),
            DB::raw('AVG(ctr) as avg_ctr'),
        )
            ->whereNotNull('query')
            ->groupBy('query')
            ->orderByDesc('total_clicks')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function gscTopPages(): Collection
    {
        return SearchConsoleData::select(
            'page',
            DB::raw('SUM(clicks) as total_clicks'),
            DB::raw('SUM(impressions) as total_impressions'),
            DB::raw('AVG(position) as avg_position'),
            DB::raw('AVG(ctr) as avg_ctr'),
        )
            ->whereNotNull('page')
            ->groupBy('page')
            ->orderByDesc('total_clicks')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function gscTotals(): array
    {
        return [
            'clicks' => (int) SearchConsoleData::sum('clicks'),
            'impressions' => (int) SearchConsoleData::sum('impressions'),
            'avg_position' => round((float) SearchConsoleData::avg('position'), 1),
            'avg_ctr' => round((float) SearchConsoleData::avg('ctr') * 100, 2),
        ];
    }

    #[Computed]
    public function gscTrend(): array
    {
        return SearchConsoleData::select(
            'date',
            DB::raw('SUM(clicks) as clicks'),
            DB::raw('SUM(impressions) as impressions'),
            DB::raw('AVG(position) as position'),
        )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date->format('M d'),
                'clicks' => (int) $row->clicks,
                'impressions' => (int) $row->impressions,
                'position' => round((float) $row->position, 1),
            ])
            ->toArray();
    }

    #[Computed]
    public function gscDeviceBreakdown(): Collection
    {
        return SearchConsoleData::select(
            'device',
            DB::raw('SUM(clicks) as total_clicks'),
            DB::raw('SUM(impressions) as total_impressions'),
        )
            ->whereNotNull('device')
            ->groupBy('device')
            ->orderByDesc('total_clicks')
            ->get();
    }

    public function syncSearchConsole(): void
    {
        $service = GoogleSearchConsoleService::fromConfig();
        if ($service && $service->fetchAndStore()) {
            session()->flash('message', 'Search Console data synced successfully.');
        } else {
            session()->flash('error', 'Failed to sync Search Console data.');
        }
    }

    // ── SEO Tab ──

    #[Computed]
    public function seoOverview(): array
    {
        $analyses = SeoAnalysis::query();
        $total = $analyses->count();

        if ($total === 0) {
            return ['total' => 0, 'avg_score' => 0, 'grades' => []];
        }

        $avgScore = (int) round((float) SeoAnalysis::avg('overall_score'));

        $grades = SeoAnalysis::select('grade', DB::raw('COUNT(*) as count'))
            ->groupBy('grade')
            ->orderByDesc('count')
            ->pluck('count', 'grade')
            ->toArray();

        return [
            'total' => $total,
            'avg_score' => $avgScore,
            'grades' => $grades,
        ];
    }

    #[Computed]
    public function seoScoreDistribution(): array
    {
        return [
            'content' => (int) round((float) SeoAnalysis::avg('content_quality_score')),
            'technical' => (int) round((float) SeoAnalysis::avg('technical_seo_score')),
            'readability' => (int) round((float) SeoAnalysis::avg('readability_score')),
            'engagement' => (int) round((float) SeoAnalysis::avg('user_engagement_score')),
            'on_page' => (int) round((float) SeoAnalysis::avg('on_page_elements_score')),
        ];
    }

    #[Computed]
    public function postsNeedingSeo(): Collection
    {
        return Post::query()
            ->published()
            ->whereDoesntHave('seoAnalyses')
            ->select(['id', 'title', 'slug', 'published_at'])
            ->latest('published_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function lowSeoScorePosts(): Collection
    {
        return Post::query()
            ->published()
            ->select(['posts.id', 'posts.title', 'posts.slug'])
            ->join('seo_analyses', 'posts.id', '=', 'seo_analyses.post_id')
            ->where('seo_analyses.overall_score', '<', 60)
            ->orderBy('seo_analyses.overall_score')
            ->limit(10)
            ->get()
            ->each(function ($post) {
                $post->seo_score = SeoAnalysis::where('post_id', $post->id)->latest()->value('overall_score');
                $post->seo_grade = SeoAnalysis::where('post_id', $post->id)->latest()->value('grade');
            });
    }

    // ── Growth Tab ──

    #[Computed]
    public function subscriberGrowth(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = [
                'date' => $date->format('M d'),
                'count' => NewsletterSubscriber::whereDate('created_at', $date->toDateString())->count(),
            ];
        }

        return $data;
    }

    #[Computed]
    public function userGrowth(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = [
                'date' => $date->format('M d'),
                'count' => User::whereDate('created_at', $date->toDateString())->count(),
            ];
        }

        return $data;
    }

    #[Computed]
    public function growthStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $usersThisMonth = User::where('created_at', '>=', $thisMonth)->count();
        $usersLastMonth = User::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        $subsThisMonth = NewsletterSubscriber::where('created_at', '>=', $thisMonth)->count();
        $subsLastMonth = NewsletterSubscriber::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        $postsThisMonth = Post::where('created_at', '>=', $thisMonth)->count();
        $postsLastMonth = Post::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();

        $viewsThisMonth = PostView::where('viewed_at', '>=', $thisMonth)->count();
        $viewsLastMonth = PostView::whereBetween('viewed_at', [$lastMonth, $lastMonthEnd])->count();

        return [
            'users' => ['current' => $usersThisMonth, 'previous' => $usersLastMonth],
            'subscribers' => ['current' => $subsThisMonth, 'previous' => $subsLastMonth],
            'posts' => ['current' => $postsThisMonth, 'previous' => $postsLastMonth],
            'views' => ['current' => $viewsThisMonth, 'previous' => $viewsLastMonth],
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.stats-overview');
    }
}
