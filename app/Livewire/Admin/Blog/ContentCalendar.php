<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Jobs\GenerateContentIdeaPostJob;
use App\Models\Category;
use App\Models\ContentIdea;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Services\Blog\ContentAgentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('components.layouts.admin')]
class ContentCalendar extends Component
{
    #[Url(as: 'm')]
    public ?string $month = null;

    #[Url(as: 'status')]
    public string $statusFilter = 'all';

    #[Url(as: 'cat')]
    public ?int $categoryId = null;

    #[Url(as: 'author')]
    public ?int $authorId = null;

    public ?int $selectedPostId = null;

    public ?string $openDayDate = null;

    public ?string $ideaPickerDate = null;

    public string $ideaSearch = '';

    // Agent settings panel
    public bool $showAgentSettings = false;

    public bool $agentEnabled = false;

    public int $agentPostsPerDayMin = 4;

    public int $agentPostsPerDayMax = 4;

    public int $agentWindowStartHour = 7;

    public int $agentWindowEndHour = 21;

    public float $agentMinGapHours = 1.5;

    public float $agentMaxGapHours = 2.5;

    public string $agentRunTime = '06:00';

    public int $agentMinSeoScore = 70;

    public int $agentMaxImproveAttempts = 3;

    public string $agentInstructions = '';

    public string $agentAvoidTopics = '';

    public string $agentEmphasizeTopics = '';

    public int $agentSpotlightWeeklyCap = 3;

    public int $agentSpotlightScoreBoost = 25;

    public bool $creatorDiscoveryEnabled = true;

    public bool $agentSettingsSaved = false;

    public bool $showDryRun = false;

    public bool $showActivityFeed = false;

    protected array $allowedStatuses = ['all', 'published', 'scheduled', 'draft'];

    public const DRAFTS_PER_CELL = 3;

    public const AGENT_SETTING_KEYS = [
        'agentEnabled' => ['key' => 'content_agent_enabled', 'default' => false, 'cast' => 'bool'],
        'agentPostsPerDayMin' => ['key' => 'content_agent_posts_per_day_min', 'default' => 4, 'cast' => 'int'],
        'agentPostsPerDayMax' => ['key' => 'content_agent_posts_per_day_max', 'default' => 4, 'cast' => 'int'],
        'agentWindowStartHour' => ['key' => 'content_agent_window_start', 'default' => 7, 'cast' => 'int'],
        'agentWindowEndHour' => ['key' => 'content_agent_window_end', 'default' => 21, 'cast' => 'int'],
        'agentMinGapHours' => ['key' => 'content_agent_min_gap', 'default' => 1.5, 'cast' => 'float'],
        'agentMaxGapHours' => ['key' => 'content_agent_max_gap', 'default' => 2.5, 'cast' => 'float'],
        'agentRunTime' => ['key' => 'content_agent_run_time', 'default' => '06:00', 'cast' => 'string'],
        'agentMinSeoScore' => ['key' => 'content_agent_min_seo_score', 'default' => 70, 'cast' => 'int'],
        'agentMaxImproveAttempts' => ['key' => 'content_agent_max_improve_attempts', 'default' => 3, 'cast' => 'int'],
        'agentInstructions' => ['key' => 'content_agent_instructions', 'default' => '', 'cast' => 'string'],
        'agentAvoidTopics' => ['key' => 'content_agent_avoid_topics', 'default' => '', 'cast' => 'string'],
        'agentEmphasizeTopics' => ['key' => 'content_agent_emphasize_topics', 'default' => '', 'cast' => 'string'],
        'agentSpotlightWeeklyCap' => ['key' => 'content_agent_spotlight_weekly_cap', 'default' => 3, 'cast' => 'int'],
        'agentSpotlightScoreBoost' => ['key' => 'content_agent_spotlight_score_boost', 'default' => 25, 'cast' => 'int'],
        'creatorDiscoveryEnabled' => ['key' => 'creator_discovery_enabled', 'default' => true, 'cast' => 'bool'],
    ];

    public function mount(): void
    {
        if ($this->month === null || ! $this->isValidMonth($this->month)) {
            $this->month = CarbonImmutable::now()->format('Y-m');
        }

        $this->loadAgentSettings();
    }

    public function toggleAgentSettings(): void
    {
        $this->showAgentSettings = ! $this->showAgentSettings;
    }

    public function openDryRun(): void
    {
        $this->showDryRun = true;
    }

    public function closeDryRun(): void
    {
        $this->showDryRun = false;
    }

    public function openActivityFeed(): void
    {
        $this->showActivityFeed = true;
    }

    public function closeActivityFeed(): void
    {
        $this->showActivityFeed = false;
    }

    #[Computed]
    public function agentLastRun(): array
    {
        $summary = Setting::get('content_agent_last_run_summary');
        $ranAt = Setting::get('content_agent_last_run_at');

        if (! $summary) {
            return ['has_run' => false];
        }

        $decoded = is_string($summary) ? json_decode($summary, true) : $summary;
        $decoded = is_array($decoded) ? $decoded : [];

        // Count generation failures logged since the run kicked off.
        // The orchestrator only dispatches jobs; the post-creation work
        // happens asynchronously, so failures land in activity log after
        // the run summary was already written.
        $failures = 0;
        $failedTitles = [];
        if ($ranAt) {
            $events = Activity::query()
                ->where('log_name', 'content_agent')
                ->where('event', 'generation_failed')
                ->where('created_at', '>=', $ranAt)
                ->latest()
                ->limit(10)
                ->get();
            $failures = $events->count();
            $failedTitles = $events->map(fn ($e) => (string) ($e->properties['idea_title'] ?? '—'))->all();
        }

        return array_merge([
            'has_run' => true,
            'ran_at' => $ranAt,
            'failures' => $failures,
            'failed_titles' => $failedTitles,
        ], $decoded);
    }

    #[Computed]
    public function dryRunPreview(): array
    {
        $agent = app(ContentAgentService::class);
        $config = $agent->config();

        // Deterministic for both count AND slots, against tomorrow's Lagos
        // date — so this preview is what tomorrow's actual run will produce.
        $tomorrow = CarbonImmutable::now('Africa/Lagos')->addDay();
        $count = $agent->rollPostsCount($tomorrow);

        $ideas = $agent->pickIdeas($count);
        $slots = $agent->buildSlots($ideas->count(), $tomorrow, deterministic: true);

        return [
            'config' => $config,
            'ideas' => $ideas,
            'slots' => $slots,
            'rolled_count' => $count,
            'spotlight_quota_remaining' => $agent->spotlightQuotaRemaining(
                (int) $config['spotlight_weekly_cap'],
            ),
        ];
    }

    #[Computed]
    public function agentActivity(): EloquentCollection
    {
        return Activity::query()
            ->where('log_name', 'content_agent')
            ->latest()
            ->limit(30)
            ->get();
    }

    public function saveAgentSettings(): void
    {
        $this->validate([
            'agentPostsPerDayMin' => 'integer|min:1|max:10',
            'agentPostsPerDayMax' => 'integer|min:1|max:10|gte:agentPostsPerDayMin',
            'agentWindowStartHour' => 'integer|min:0|max:23',
            'agentWindowEndHour' => 'integer|min:1|max:23|gt:agentWindowStartHour',
            'agentMinGapHours' => 'numeric|min:0.25|max:12',
            'agentMaxGapHours' => 'numeric|min:0.25|max:12|gte:agentMinGapHours',
            'agentRunTime' => ['regex:/^([01]\d|2[0-3]):([0-5]\d)$/'],
            'agentMinSeoScore' => 'integer|min:0|max:100',
            'agentMaxImproveAttempts' => 'integer|min:1|max:5',
            'agentInstructions' => 'string|max:5000',
            'agentAvoidTopics' => 'string|max:2000',
            'agentEmphasizeTopics' => 'string|max:2000',
            'agentSpotlightWeeklyCap' => 'integer|min:0|max:14',
            'agentSpotlightScoreBoost' => 'integer|min:0|max:50',
        ]);

        foreach (self::AGENT_SETTING_KEYS as $property => $meta) {
            Setting::set($meta['key'], $this->{$property});
        }

        $this->agentSettingsSaved = true;
        $this->dispatch('notify', type: 'success', message: 'Agent settings saved.');
    }

    public function resetAgentSettings(): void
    {
        foreach (self::AGENT_SETTING_KEYS as $property => $meta) {
            $this->{$property} = $meta['default'];
        }

        $this->agentSettingsSaved = false;
    }

    protected function loadAgentSettings(): void
    {
        // Back-compat: existing installs may only have the legacy single
        // posts_per_day value. Upgrade transparently by using it as the
        // default for both min and max until the admin saves the new fields.
        $legacyPostsPerDay = Setting::get('content_agent_posts_per_day');
        $rangeDefaults = $legacyPostsPerDay !== null
            ? ['content_agent_posts_per_day_min' => $legacyPostsPerDay, 'content_agent_posts_per_day_max' => $legacyPostsPerDay]
            : [];

        foreach (self::AGENT_SETTING_KEYS as $property => $meta) {
            $default = $rangeDefaults[$meta['key']] ?? $meta['default'];
            $raw = Setting::get($meta['key'], $default);

            $this->{$property} = match ($meta['cast']) {
                'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
                'int' => (int) $raw,
                'float' => (float) $raw,
                default => (string) $raw,
            };
        }
    }

    public function goToToday(): void
    {
        $this->month = CarbonImmutable::now()->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->month = $this->currentMonth()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->currentMonth()->addMonth()->format('Y-m');
    }

    public function setStatus(string $status): void
    {
        if (in_array($status, $this->allowedStatuses, true)) {
            $this->statusFilter = $status;
        }
    }

    public function openPost(int $postId): void
    {
        $this->selectedPostId = $postId;
        $this->openDayDate = null;
    }

    public function closePost(): void
    {
        $this->selectedPostId = null;
    }

    public function openDay(string $date): void
    {
        $this->openDayDate = $date;
    }

    public function closeDay(): void
    {
        $this->openDayDate = null;
    }

    public function openIdeaPicker(string $date): void
    {
        $this->ideaPickerDate = $date;
        $this->ideaSearch = '';
    }

    public function closeIdeaPicker(): void
    {
        $this->ideaPickerDate = null;
        $this->ideaSearch = '';
    }

    public function movePost(int $postId, string $date): void
    {
        $post = Post::findOrFail($postId);

        if (! in_array($post->status, ['scheduled', 'draft'], true)) {
            return;
        }

        try {
            $target = CarbonImmutable::createFromFormat('Y-m-d', $date);
        } catch (\Throwable) {
            return;
        }

        if ($post->status === 'scheduled') {
            $original = $post->scheduled_at ?? now();
            $newDate = $target->setTime($original->hour, $original->minute, $original->second);

            if ($newDate->isPast()) {
                $this->dispatch('notify', type: 'error', message: 'Scheduled date must be in the future.');

                return;
            }

            $post->update(['scheduled_at' => $newDate]);
            $this->dispatch('notify', type: 'success', message: "Moved to {$target->format('M j, Y')}.");

            return;
        }

        $original = $post->created_at ?? now();
        $newDate = $target->setTime($original->hour, $original->minute, $original->second);

        $post->created_at = $newDate;
        $post->save();
        $this->dispatch('notify', type: 'success', message: "Moved to {$target->format('M j, Y')}.");
    }

    public function generateFromIdea(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);

        $previousStatus = $idea->status;
        $previousPostId = $idea->generated_post_id;

        $idea->markAsGenerating(auth()->id());

        GenerateContentIdeaPostJob::dispatch(
            $ideaId,
            auth()->id(),
            $previousStatus,
            $previousPostId,
        );

        $this->closeIdeaPicker();

        session()->flash('success', "Generating article for \"{$idea->title}\" in the background...");
    }

    #[Computed]
    public function currentMonthLabel(): string
    {
        return $this->currentMonth()->translatedFormat('F Y');
    }

    /**
     * @return Collection<int, array{date: CarbonImmutable, in_month: bool, is_today: bool, posts: EloquentCollection}>
     */
    #[Computed]
    public function calendarDays(): Collection
    {
        $monthStart = $this->currentMonth()->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        $gridStart = $monthStart->startOfWeek(CarbonImmutable::SUNDAY);
        $gridEnd = $monthEnd->endOfWeek(CarbonImmutable::SATURDAY);

        $posts = $this->postsInRange($gridStart, $gridEnd);

        $bucketed = $posts->groupBy(fn (Post $post) => $this->anchorDate($post)->toDateString());

        $today = CarbonImmutable::now()->startOfDay();

        $days = collect();
        $cursor = $gridStart;
        while ($cursor->lte($gridEnd)) {
            $days->push([
                'date' => $cursor,
                'in_month' => $cursor->month === $monthStart->month,
                'is_today' => $cursor->isSameDay($today),
                'posts' => $bucketed->get($cursor->toDateString(), collect()),
            ]);
            $cursor = $cursor->addDay();
        }

        return $days;
    }

    #[Computed]
    public function monthCounts(): array
    {
        $monthStart = $this->currentMonth()->startOfMonth();
        $monthEnd = $this->currentMonth()->endOfMonth();

        $published = Post::where('status', 'published')
            ->whereBetween('published_at', [$monthStart, $monthEnd])
            ->count();

        $scheduled = Post::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$monthStart, $monthEnd])
            ->count();

        $draft = Post::where('status', 'draft')
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        return [
            'published' => $published,
            'scheduled' => $scheduled,
            'draft' => $draft,
            'total' => $published + $scheduled + $draft,
        ];
    }

    #[Computed]
    public function categories(): EloquentCollection
    {
        return Category::orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function authors(): EloquentCollection
    {
        return User::whereHas('posts')->orderBy('name')->get(['id', 'name']);
    }

    #[Computed]
    public function openDayPosts(): EloquentCollection
    {
        if (! $this->openDayDate) {
            return new EloquentCollection;
        }

        $day = CarbonImmutable::createFromFormat('Y-m-d', $this->openDayDate)->startOfDay();
        $start = $day;
        $end = $day->endOfDay();

        $query = Post::query()
            ->with(['categories:id,name', 'author:id,name'])
            ->withCount('views');

        $this->applyStatusFilter($query, $start, $end);
        $this->applyRelationFilters($query);

        return $query->get()->sortBy(fn (Post $post) => $this->anchorDate($post)->timestamp)->values();
    }

    #[Computed]
    public function pickerIdeas(): EloquentCollection
    {
        $query = ContentIdea::query()
            ->whereNotIn('status', ['dismissed', 'generating', 'generated'])
            ->where(function ($q): void {
                $q->where('created_at', '>=', now()->subDays(7))
                    ->orWhere('seo_score', '>=', 70)
                    ->orWhere('status', 'approved');
            })
            ->orderByRaw("CASE WHEN status = 'approved' THEN 0 ELSE 1 END")
            ->orderByDesc('seo_score')
            ->orderByDesc('created_at');

        if ($this->ideaSearch !== '') {
            $query->where('title', 'like', '%'.$this->ideaSearch.'%');
        }

        return $query->limit(50)->get();
    }

    #[Computed]
    public function selectedPost(): ?Post
    {
        if (! $this->selectedPostId) {
            return null;
        }

        return Post::with(['author', 'categories', 'tags', 'latestSeoAnalysis'])
            ->withCount(['views', 'reactions', 'comments'])
            ->find($this->selectedPostId);
    }

    public function render(): View
    {
        return view('livewire.admin.blog.content-calendar');
    }

    protected function currentMonth(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m', (string) $this->month)->startOfMonth();
    }

    protected function isValidMonth(string $value): bool
    {
        try {
            CarbonImmutable::createFromFormat('Y-m', $value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function postsInRange(CarbonImmutable $start, CarbonImmutable $end): EloquentCollection
    {
        $query = Post::query()
            ->with(['categories:id,name', 'author:id,name'])
            ->withCount('views');

        $this->applyStatusFilter($query, $start, $end);
        $this->applyRelationFilters($query);

        return $query->get();
    }

    protected function applyStatusFilter(Builder $query, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $publishedClause = fn (Builder $q) => $q->where('status', 'published')
            ->whereBetween('published_at', [$start, $end]);

        $scheduledClause = fn (Builder $q) => $q->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$start, $end]);

        $draftClause = fn (Builder $q) => $q->where('status', 'draft')
            ->whereBetween('created_at', [$start, $end]);

        match ($this->statusFilter) {
            'published' => $query->where($publishedClause),
            'scheduled' => $query->where($scheduledClause),
            'draft' => $query->where($draftClause),
            default => $query->where(function (Builder $q) use ($publishedClause, $scheduledClause, $draftClause): void {
                $q->where($publishedClause)
                    ->orWhere($scheduledClause)
                    ->orWhere($draftClause);
            }),
        };
    }

    protected function applyRelationFilters(Builder $query): void
    {
        if ($this->categoryId) {
            $query->whereHas('categories', fn (Builder $q) => $q->where('categories.id', $this->categoryId));
        }

        if ($this->authorId) {
            $query->where('author_id', $this->authorId);
        }
    }

    protected function anchorDate(Post $post): CarbonImmutable
    {
        $raw = match ($post->status) {
            'published' => $post->published_at ?? $post->created_at,
            'scheduled' => $post->scheduled_at ?? $post->created_at,
            default => $post->created_at,
        };

        return CarbonImmutable::instance($raw)->startOfDay();
    }
}
