<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Jobs\GenerateContentIdeaPostJob;
use App\Models\Category;
use App\Models\ContentIdea;
use App\Models\Post;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

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

    protected array $allowedStatuses = ['all', 'published', 'scheduled', 'draft'];

    public const DRAFTS_PER_CELL = 3;

    public function mount(): void
    {
        if ($this->month === null || ! $this->isValidMonth($this->month)) {
            $this->month = CarbonImmutable::now()->format('Y-m');
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
