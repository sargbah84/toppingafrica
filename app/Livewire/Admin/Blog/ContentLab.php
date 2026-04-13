<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Blog;

use App\Jobs\GenerateContentIdeaPostJob;
use App\Jobs\ResearchContentIdeasJob;
use App\Models\ContentIdea;
use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ContentLab extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $nicheFilter = 'all';

    public int $fetchCount = 5;

    public bool $fetching = false;

    // Generation tracking — job-based, no progress modal needed
    public ?int $generatingIdeaId = null;

    // Detail modal
    public bool $showDetailModal = false;

    public ?int $detailIdeaId = null;

    // Settings modal
    public bool $showSettingsModal = false;

    /** @var array<string, bool> Niche key => enabled */
    public array $nicheToggles = [];

    public string $newNicheSlug = '';

    public string $newNicheLabel = '';

    public string $researchContext = '';

    public bool $researchAfterSave = false;

    public bool $isSavingSettings = false;

    public int $enabledNicheCount = 0;

    // Dismiss modal
    public bool $showDismissModal = false;

    public ?int $dismissingIdeaId = null;

    public ?string $dismissingIdeaTitle = null;

    public string $dismissalReason = '';

    public const DISMISSAL_REASONS = [
        'off_brand' => 'Off brand / not aligned with our voice',
        'not_relevant' => 'Not relevant to our audience',
        'too_generic' => 'Too generic or broad',
        'already_covered' => 'Already covered this topic',
        'wrong_timing' => 'Wrong timing / not timely',
        'low_interest' => 'Low audience interest expected',
        'too_controversial' => 'Too controversial',
        'other' => 'Other',
    ];

    // ── Detail Modal ────────────────────────────────────────

    public function viewIdea(int $ideaId): void
    {
        $this->detailIdeaId = $ideaId;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailIdeaId = null;
    }

    // ── Settings ─────────────────────────────────────────────

    public function openSettings(): void
    {
        $allNiches = self::getAllNiches();
        $enabledKeys = self::getEnabledNicheKeys();

        $this->nicheToggles = [];
        foreach ($allNiches as $key => $label) {
            $this->nicheToggles[$key] = in_array($key, $enabledKeys, true);
        }

        $this->researchContext = (string) Setting::get('content_lab_context', '');
        $this->newNicheSlug = '';
        $this->newNicheLabel = '';
        $this->researchAfterSave = false;
        $this->showSettingsModal = true;
    }

    public function addCustomNiche(): void
    {
        $input = trim($this->newNicheLabel);

        if ($input === '') {
            session()->flash('settings-error', 'Please enter a niche name.');

            return;
        }

        // Support comma-separated input
        $labels = array_filter(array_map('trim', explode(',', $input)));

        if (empty($labels)) {
            session()->flash('settings-error', 'Please enter a niche name.');

            return;
        }

        $customNiches = json_decode((string) Setting::get('content_lab_custom_niches', '[]'), true) ?: [];
        $defaults = config('blog.ai.niches', []);
        $added = [];
        $skipped = [];

        foreach ($labels as $label) {
            $slug = Str::slug($label);

            if ($slug === '') {
                continue;
            }

            if (isset($defaults[$slug]) || isset($customNiches[$slug])) {
                $skipped[] = $label;

                continue;
            }

            $customNiches[$slug] = $label;
            $this->nicheToggles[$slug] = true;
            $added[] = $label;
        }

        Setting::set('content_lab_custom_niches', json_encode($customNiches));

        $this->newNicheSlug = '';
        $this->newNicheLabel = '';

        $messages = [];
        if ($added) {
            $messages[] = 'Added: '.implode(', ', $added);
        }
        if ($skipped) {
            $messages[] = 'Already exist: '.implode(', ', $skipped);
        }

        session()->flash($added ? 'settings-success' : 'settings-error', implode('. ', $messages).'.');
    }

    public function removeCustomNiche(string $slug): void
    {
        $customNiches = json_decode((string) Setting::get('content_lab_custom_niches', '[]'), true) ?: [];

        if (! isset($customNiches[$slug])) {
            return;
        }

        $label = $customNiches[$slug];
        unset($customNiches[$slug]);
        Setting::set('content_lab_custom_niches', json_encode($customNiches));
        unset($this->nicheToggles[$slug]);

        session()->flash('settings-success', "Niche \"{$label}\" removed.");
    }

    public function saveSettings(): void
    {
        // Save enabled niches
        $enabled = array_keys(array_filter($this->nicheToggles));
        Setting::set('content_lab_enabled_niches', json_encode($enabled));

        // Save research context
        Setting::set('content_lab_context', trim($this->researchContext));

        if (! $this->researchAfterSave) {
            $this->showSettingsModal = false;
            session()->flash('success', 'Content Lab settings saved.');

            return;
        }

        // Keep modal open and show progress
        $this->isSavingSettings = true;
        $this->enabledNicheCount = count($enabled);

        try {
            $count = max(1, min(10, $this->fetchCount));
            $result = (new ResearchContentIdeasJob($count))->handle();

            $this->isSavingSettings = false;
            $this->showSettingsModal = false;

            if ($result['error']) {
                session()->flash('error', "Settings saved. Research error: {$result['error']}");
            } elseif ($result['created'] > 0) {
                session()->flash('success', "Settings saved & research complete. Discovered {$result['created']} new idea(s). Cleaned up {$result['cleaned_up']} expired idea(s).");
            } else {
                session()->flash('success', "Settings saved & research complete. No new ideas found. Cleaned up {$result['cleaned_up']} expired idea(s).");
            }
        } catch (\Throwable $e) {
            $this->isSavingSettings = false;
            $this->showSettingsModal = false;
            session()->flash('error', 'Settings saved but research failed: '.$e->getMessage());
        } finally {
            $this->resetPage();
        }
    }

    public function closeSettings(): void
    {
        $this->showSettingsModal = false;
    }

    /**
     * Get all niches: defaults merged with custom.
     *
     * @return array<string, string>
     */
    public static function getAllNiches(): array
    {
        $defaults = config('blog.ai.niches', []);
        $custom = json_decode((string) Setting::get('content_lab_custom_niches', '[]'), true) ?: [];

        return array_merge($defaults, $custom);
    }

    /**
     * Get only the enabled niche keys. If no setting exists yet, all niches are enabled.
     *
     * @return string[]
     */
    public static function getEnabledNicheKeys(): array
    {
        $raw = Setting::get('content_lab_enabled_niches');

        if ($raw === null) {
            // First time — all niches enabled by default
            return array_keys(self::getAllNiches());
        }

        return json_decode((string) $raw, true) ?: [];
    }

    /**
     * Get the enabled niches as key => label.
     *
     * @return array<string, string>
     */
    public static function getEnabledNiches(): array
    {
        $all = self::getAllNiches();
        $enabled = self::getEnabledNicheKeys();

        return array_intersect_key($all, array_flip($enabled));
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedNicheFilter(): void
    {
        $this->resetPage();
    }

    // ── Research Now ─────────────────────────────────────────

    public function fetchNow(): void
    {
        $this->fetching = true;

        $count = max(1, min(10, $this->fetchCount));

        try {
            $result = (new ResearchContentIdeasJob($count))->handle();

            if ($result['error']) {
                session()->flash('error', $result['error']);
            } elseif ($result['created'] > 0) {
                session()->flash('success', "Discovered {$result['created']} new content idea(s). Cleaned up {$result['cleaned_up']} expired idea(s).");
            } else {
                session()->flash('success', "Research complete but no new ideas found (all may already exist). Cleaned up {$result['cleaned_up']} expired idea(s).");
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Research failed: '.$e->getMessage());
        } finally {
            $this->fetching = false;
            $this->resetPage();
        }
    }

    // ── Generate ─────────────────────────────────────────────

    /**
     * Dispatch a background job to generate the post.
     * The idea status changes to 'generating' immediately,
     * and the UI polls to detect completion.
     */
    public function generate(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);

        // Close detail modal if open
        $this->showDetailModal = false;
        $this->detailIdeaId = null;

        $previousStatus = $idea->status;
        $previousPostId = $idea->generated_post_id;

        $idea->markAsGenerating(auth()->id());

        GenerateContentIdeaPostJob::dispatch(
            $ideaId,
            auth()->id(),
            $previousStatus,
            $previousPostId,
        );

        session()->flash('success', "Generating article for \"{$idea->title}\" in the background...");
    }

    /**
     * Dismiss the generation error so the idea returns to its previous state.
     */
    public function dismissGenerationError(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);

        if ($idea->generation_error) {
            $idea->update(['generation_error' => null]);
        }
    }

    // ── Idea Actions ─────────────────────────────────────────

    public function approve(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);
        $idea->approve();

        session()->flash('success', 'Idea approved. The AI will look for similar topics in the future.');
    }

    public function openDismissModal(int $ideaId): void
    {
        // Close detail modal if open
        $this->showDetailModal = false;
        $this->detailIdeaId = null;

        $idea = ContentIdea::findOrFail($ideaId);
        $this->dismissingIdeaId = $ideaId;
        $this->dismissingIdeaTitle = $idea->title;
        $this->dismissalReason = '';
        $this->showDismissModal = true;
    }

    public function closeDismissModal(): void
    {
        $this->showDismissModal = false;
        $this->dismissingIdeaId = null;
        $this->dismissingIdeaTitle = null;
        $this->dismissalReason = '';
    }

    public function confirmDismiss(): void
    {
        if (! $this->dismissingIdeaId) {
            return;
        }

        $idea = ContentIdea::findOrFail($this->dismissingIdeaId);
        $idea->dismiss($this->dismissalReason ?: null);

        $this->closeDismissModal();
        session()->flash('success', 'Idea dismissed. The AI will learn from this feedback.');
    }

    public function restoreIdea(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);
        $idea->restore();

        session()->flash('success', 'Idea restored.');
    }

    public function delete(int $ideaId): void
    {
        $idea = ContentIdea::findOrFail($ideaId);
        $idea->delete();

        session()->flash('success', 'Idea deleted.');
    }

    public function cleanup(): void
    {
        $deleted = ContentIdea::where(function ($q) {
            $q->where('expires_at', '<', now())
                ->whereNotIn('status', ['generated', 'approved']);
        })->orWhere('status', 'dismissed')->delete();

        session()->flash('success', "Cleaned up {$deleted} old/dismissed idea(s).");
        $this->resetPage();
    }

    // ── Data ─────────────────────────────────────────────────

    public function getIdeas(): LengthAwarePaginator
    {
        $query = ContentIdea::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhere('suggested_keyword', 'like', "%{$this->search}%");
            });
        }

        $query = match ($this->filter) {
            'pending' => $query->where('status', 'pending')->where('expires_at', '>', now()),
            'approved' => $query->where('status', 'approved'),
            'generated' => $query->where('status', 'generated'),
            'dismissed' => $query->where('status', 'dismissed'),
            'expired' => $query->where('expires_at', '<=', now())->whereNotIn('status', ['generated', 'approved']),
            default => $query,
        };

        if ($this->nicheFilter !== 'all') {
            $query->where('niche', $this->nicheFilter);
        }

        return $query->orderByDesc('seo_score')->latest('researched_at')->paginate(15);
    }

    public function render(): View
    {
        $allNiches = self::getAllNiches();
        $defaultNicheKeys = array_keys(config('blog.ai.niches', []));

        return view('livewire.admin.blog.content-lab', [
            'ideas' => $this->getIdeas(),
            'niches' => $allNiches,
            'defaultNicheKeys' => $defaultNicheKeys,
            'counts' => [
                'all' => ContentIdea::count(),
                'pending' => ContentIdea::where('status', 'pending')->where('expires_at', '>', now())->count(),
                'approved' => ContentIdea::where('status', 'approved')->count(),
                'generated' => ContentIdea::where('status', 'generated')->count(),
                'dismissed' => ContentIdea::where('status', 'dismissed')->count(),
                'expired' => ContentIdea::where('expires_at', '<=', now())->whereNotIn('status', ['generated', 'approved'])->count(),
            ],
            'lastResearched' => ContentIdea::latest('researched_at')->value('researched_at'),
        ])->title('Content Lab');
    }
}
