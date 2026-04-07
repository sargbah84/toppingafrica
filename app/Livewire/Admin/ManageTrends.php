<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Jobs\FetchAfricaTrendsJob;
use App\Models\Trend;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ManageTrends extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $categoryFilter = 'all';

    public bool $fetching = false;

    // Edit modal state
    public bool $showEditModal = false;
    public ?int $editingTrendId = null;
    public string $editTitle = '';
    public string $editSummary = '';
    public string $editCategory = 'Culture';
    public string $editCountry = '';
    public string $editCountryCode = '';
    public string $editSourceLabel = '';
    public string $editSourceUrl = '';
    public bool $editIsActive = true;

    protected array $categories = ['Music', 'Business', 'Sports', 'Culture', 'Tech', 'Lifestyle'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    // ── Fetch Now ─────────────────────────────────────────────

    public function fetchNow(): void
    {
        $this->fetching = true;

        try {
            // Run synchronously so admin sees immediate results even if queue/scheduler is broken
            $created = (new FetchAfricaTrendsJob)->handle();

            if ($created > 0) {
                session()->flash('success', "Fetched {$created} new trend(s).");
            } else {
                session()->flash('error', 'Fetch returned no new trends. Check storage/logs/laravel.log for API errors (Perplexity / Anthropic).');
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Fetch failed: ' . $e->getMessage());
        } finally {
            $this->fetching = false;
            $this->resetPage();
        }
    }

    // ── Toggle / Delete ───────────────────────────────────────

    public function toggleActive(int $id): void
    {
        $trend = Trend::findOrFail($id);
        $trend->update(['is_active' => ! $trend->is_active]);

        session()->flash('success', $trend->is_active ? 'Trend activated.' : 'Trend deactivated.');
    }

    public function delete(int $id): void
    {
        $trend = Trend::findOrFail($id);
        $title = $trend->title;
        $trend->delete();

        session()->flash('success', "Trend '{$title}' deleted.");
    }

    // ── Edit ──────────────────────────────────────────────────

    public function edit(int $id): void
    {
        $trend = Trend::findOrFail($id);

        $this->editingTrendId = $trend->id;
        $this->editTitle = $trend->title;
        $this->editSummary = $trend->summary;
        $this->editCategory = $trend->category;
        $this->editCountry = $trend->country;
        $this->editCountryCode = $trend->country_code ?? '';
        $this->editSourceLabel = $trend->source_label ?? '';
        $this->editSourceUrl = $trend->source_url ?? '';
        $this->editIsActive = $trend->is_active;

        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editTitle' => 'required|string|max:255',
            'editSummary' => 'required|string',
            'editCategory' => 'required|in:' . implode(',', $this->categories),
            'editCountry' => 'required|string|max:100',
            'editCountryCode' => 'nullable|string|size:2',
            'editSourceLabel' => 'nullable|string|max:255',
            'editSourceUrl' => 'nullable|url|max:500',
        ]);

        $trend = Trend::findOrFail($this->editingTrendId);

        $trend->update([
            'title' => $this->editTitle,
            'summary' => $this->editSummary,
            'category' => $this->editCategory,
            'country' => $this->editCountry,
            'country_code' => $this->editCountryCode ? strtoupper($this->editCountryCode) : null,
            'source_label' => $this->editSourceLabel ?: null,
            'source_url' => $this->editSourceUrl ?: null,
            'is_active' => $this->editIsActive,
        ]);

        $this->showEditModal = false;

        session()->flash('success', 'Trend updated.');
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingTrendId = null;
    }

    // ── Data ──────────────────────────────────────────────────

    public function getTrends(): LengthAwarePaginator
    {
        $query = Trend::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('summary', 'like', "%{$this->search}%")
                    ->orWhere('country', 'like', "%{$this->search}%");
            });
        }

        $query = match ($this->filter) {
            'active' => $query->where('is_active', true)->where('expires_at', '>', now()),
            'inactive' => $query->where('is_active', false),
            'expired' => $query->where('expires_at', '<=', now()),
            'today' => $query->whereDate('trend_date', today()),
            default => $query,
        };

        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        return $query->latest('trend_date')->latest('created_at')->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.admin.manage-trends', [
            'trends' => $this->getTrends(),
            'categories' => $this->categories,
            'counts' => [
                'all' => Trend::count(),
                'active' => Trend::where('is_active', true)->where('expires_at', '>', now())->count(),
                'inactive' => Trend::where('is_active', false)->count(),
                'expired' => Trend::where('expires_at', '<=', now())->count(),
                'today' => Trend::whereDate('trend_date', today())->count(),
            ],
            'lastFetched' => Trend::latest('created_at')->value('created_at'),
        ])->title('Manage Trends');
    }
}
