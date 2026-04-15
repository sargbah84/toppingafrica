<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Jobs\DiscoverCreatorsJob;
use App\Jobs\RepullCreatorJob;
use App\Mail\CreatorClaimInvite;
use App\Models\Creator;
use App\Services\ClaudeBioService;
use App\Services\CreatorSocialLinkBuilder;
use App\Services\PerplexityService;
use App\Services\WikimediaService;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ManageCreators extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $countryFilter = '';

    #[Url]
    public string $followerFilter = '';

    #[Url]
    public int $perPage = 20;

    // Edit modal state
    public bool $showEditModal = false;
    public ?int $editingCreatorId = null;
    public string $editName = '';
    public string $editBio = '';
    public string $editCountry = '';
    public string $editCategory = '';
    public string $editContactEmail = '';
    // Base64 data URL of the chosen photo (Livewire temp uploads are unreliable on Windows/Laragon)
    public ?string $editPhotoData = null;
    public ?string $editPhotoName = null;
    public string $editBadge = ''; // '', 'rising', 'trending', 'featured'
    public array $editSocialLinks = [];

    // Read-only display of AI-estimated follower data (not editable in the modal,
    // but refreshed when the admin clicks "Re-pull AI Data" inside the modal).
    public ?int $editFollowerCount = null;
    public ?string $editFollowerPlatform = null;

    // Review modal state — for the Pending Edits tab
    public bool $showReviewModal = false;
    public ?int $reviewingCreatorId = null;
    public ?string $reviewingCreatorName = null;

    // Generate modal state
    public bool $showGenerateModal = false;
    public array $generateNiches = [];
    public array $generateCountries = [];
    public bool $useQueue = true;

    // Synchronous run state
    public bool $generating = false;
    public string $generateStatus = '';
    public int $generatedCount = 0;
    public int $skippedCount = 0;
    public int $generateProgress = 0;
    public int $generateTotal = 0;

    // Queued run state
    public ?string $generateBatchId = null;
    public int $generateBatchTotal = 0;
    public int $generateBatchPending = 0;
    public int $generateBatchFailed = 0;
    public bool $generateBatchFinished = false;
    public int $generateCreatorsBefore = 0;

    // Add Creator (by search) modal state
    public bool $showAddModal = false;
    public string $addSearchQuery = '';
    public array $addCandidates = [];
    public array $addSelected = [];
    public string $addStatus = '';
    public int $addSavedCount = 0;
    public int $addSkippedCount = 0;
    public bool $addSearched = false;

    // Manual entry fallback (shown when AI can't find the creator)
    public bool $showManualForm = false;
    public string $manualName = '';
    public string $manualCountry = '';
    public string $manualCategory = '';
    public string $manualContactEmail = '';
    public string $manualBio = '';
    public array $manualSocialLinks = [];

    // Bulk actions
    public array $selected = [];
    public bool $selectAll = false;

    protected array $categories = [
        'Comedy', 'Fashion', 'Food', 'Music', 'Lifestyle',
        'Travel', 'Tech', 'Beauty', 'Sports', 'Education',
        'Finance', 'Health', 'Art', 'Gaming', 'Photography',
    ];

    protected array $africanCountries = [
        'Nigeria', 'Ghana', 'Kenya', 'South Africa', 'Tanzania',
        'Ethiopia', 'Egypt', 'Morocco', 'Senegal', 'Cameroon',
        'Ivory Coast', 'Uganda', 'Rwanda', 'Zimbabwe', 'Mozambique',
        'Angola', 'DR Congo', 'Algeria', 'Tunisia', 'Liberia',
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedCountryFilter(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        $this->selected = [];
        $this->selectAll = false;
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selected = $value
            ? $this->getCreators()->pluck('id')->map(fn ($id) => (string) $id)->toArray()
            : [];
    }

    // ── Add Creator (search by name/handle) ──────────────────

    public function openAddModal(): void
    {
        $this->showAddModal = true;
        $this->addSearchQuery = '';
        $this->addCandidates = [];
        $this->addSelected = [];
        $this->addStatus = '';
        $this->addSavedCount = 0;
        $this->addSkippedCount = 0;
        $this->addSearched = false;
        $this->resetManualForm();
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->addCandidates = [];
        $this->addSelected = [];
        $this->addStatus = '';
        $this->addSearched = false;
        $this->resetManualForm();
    }

    private function resetManualForm(): void
    {
        $this->showManualForm = false;
        $this->manualName = '';
        $this->manualCountry = '';
        $this->manualCategory = '';
        $this->manualContactEmail = '';
        $this->manualBio = '';
        $this->manualSocialLinks = [];
        $this->resetErrorBag([
            'manualName', 'manualCountry', 'manualCategory',
            'manualContactEmail', 'manualBio',
        ]);
    }

    public function openManualForm(): void
    {
        $this->showManualForm = true;
        // Pre-fill name from the search query if the admin already typed something
        if ($this->manualName === '' && $this->addSearchQuery !== '') {
            $this->manualName = ltrim($this->addSearchQuery, '@');
        }
    }

    public function cancelManualForm(): void
    {
        $this->resetManualForm();
    }

    public function addManualSocialLink(): void
    {
        $this->manualSocialLinks[] = [
            'platform' => 'instagram',
            'url' => '',
            'handle' => '',
            'follower_count' => null,
        ];
    }

    public function removeManualSocialLink(int $index): void
    {
        unset($this->manualSocialLinks[$index]);
        $this->manualSocialLinks = array_values($this->manualSocialLinks);
    }

    public function saveManualCreator(WikimediaService $wikimedia): void
    {
        $this->validate([
            'manualName' => 'required|string|max:255',
            'manualCountry' => 'required|string|max:100',
            'manualCategory' => 'required|string|in:' . implode(',', $this->categories),
            'manualContactEmail' => 'nullable|email|max:255',
            'manualBio' => 'required|string|min:10',
            'manualSocialLinks.*.platform' => 'required|in:instagram,tiktok,youtube,twitter,facebook,website',
            'manualSocialLinks.*.url' => 'required|url|max:500',
            'manualSocialLinks.*.handle' => 'nullable|string|max:100',
            'manualSocialLinks.*.follower_count' => 'nullable|integer|min:0',
        ], [
            'manualName.required' => 'Name is required.',
            'manualCountry.required' => 'Country is required.',
            'manualCategory.required' => 'Category is required.',
            'manualCategory.in' => 'Pick a category from the list.',
            'manualBio.required' => 'Bio is required.',
            'manualBio.min' => 'Bio must be at least 10 characters.',
            'manualSocialLinks.*.url.required' => 'Enter a URL for each social link, or remove the row.',
            'manualSocialLinks.*.url.url' => 'Enter a valid URL (including https://).',
        ]);

        $name = trim($this->manualName);
        $slug = Str::slug($name);

        if (Creator::where('name', $name)->orWhere('slug', $slug)->exists()) {
            $this->addStatus = "A creator named \"{$name}\" already exists.";
            return;
        }

        $image = $wikimedia->searchCreatorImage($name);

        $creator = Creator::create([
            'name' => $name,
            'bio' => trim($this->manualBio),
            'country' => trim($this->manualCountry),
            'category' => $this->manualCategory,
            'contact_email' => \App\Jobs\DiscoverCreatorsJob::normalizeContactEmail($this->manualContactEmail ?: null),
            'status' => 'pending',
            'profile_image_url' => $image['image_url'] ?? null,
            'profile_image_attribution' => $image['attribution'] ?? null,
            'profile_image_license' => $image['license'] ?? null,
        ]);

        foreach ($this->manualSocialLinks as $linkData) {
            if (empty($linkData['url'])) {
                continue;
            }

            $followerCount = isset($linkData['follower_count']) && $linkData['follower_count'] !== ''
                ? (int) $linkData['follower_count']
                : null;

            $creator->socialLinks()->create([
                'platform' => $linkData['platform'],
                'url' => trim($linkData['url']),
                'handle' => ! empty($linkData['handle']) ? ltrim(trim($linkData['handle']), '@') : null,
                'follower_count' => $followerCount,
            ]);
        }

        session()->flash('success', "{$creator->name} added manually.");
        $this->closeAddModal();
    }

    public function searchForCreator(PerplexityService $perplexity): void
    {
        $this->validate([
            'addSearchQuery' => 'required|string|min:2|max:120',
        ], [
            'addSearchQuery.required' => 'Enter a creator name or handle to search.',
            'addSearchQuery.min' => 'Enter at least 2 characters.',
        ]);

        $this->addCandidates = [];
        $this->addSelected = [];
        $this->addStatus = '';
        $this->addSearched = true;

        $results = $perplexity->searchCreatorByName($this->addSearchQuery, 5);

        $candidates = [];
        foreach ($results as $r) {
            $name = $r['name'] ?? null;
            if (! $name) {
                continue;
            }

            $slug = Str::slug($name);
            $exists = Creator::where('name', $name)->orWhere('slug', $slug)->exists();

            $candidates[] = [
                'name' => $name,
                'country' => $r['country'] ?? null,
                'category' => $r['category'] ?? null,
                'bio_summary' => $r['bio_summary'] ?? null,
                'contact_email' => $r['contact_email'] ?? null,
                'follower_count' => \App\Jobs\DiscoverCreatorsJob::normalizeFollowerCount($r['estimated_follower_count'] ?? null),
                'follower_platform' => \App\Jobs\DiscoverCreatorsJob::normalizeFollowerPlatform($r['follower_platform'] ?? null),
                'instagram_handle' => $r['instagram_handle'] ?? null,
                'tiktok_handle' => $r['tiktok_handle'] ?? null,
                'youtube_channel_url' => $r['youtube_channel_url'] ?? null,
                'twitter_handle' => $r['twitter_handle'] ?? null,
                'facebook_url' => $r['facebook_url'] ?? null,
                'website_url' => $r['website_url'] ?? null,
                'already_exists' => $exists,
                'raw' => $r,
            ];
        }

        $this->addCandidates = $candidates;

        if (empty($candidates)) {
            $this->addStatus = 'No matches found. Try a different spelling or a social handle.';
        }
    }

    public function saveSelectedCandidates(
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
        CreatorSocialLinkBuilder $linkBuilder,
    ): void {
        if (empty($this->addSelected)) {
            $this->addStatus = 'Select at least one profile to save.';
            return;
        }

        $saved = 0;
        $skipped = 0;

        foreach ($this->addSelected as $index) {
            $i = (int) $index;
            if (! isset($this->addCandidates[$i])) {
                continue;
            }

            $candidate = $this->addCandidates[$i];
            $name = $candidate['name'];
            $slug = Str::slug($name);

            if (Creator::where('name', $name)->orWhere('slug', $slug)->exists()) {
                $skipped++;
                continue;
            }

            $raw = $candidate['raw'];
            $bio = $claude->generateBio($raw);
            $image = $wikimedia->searchCreatorImage($name);

            $creator = Creator::create([
                'name' => $name,
                'bio' => $bio,
                'country' => $candidate['country'] ?? ($raw['country'] ?? ''),
                'category' => $candidate['category'] ?? ($raw['category'] ?? ''),
                'contact_email' => \App\Jobs\DiscoverCreatorsJob::normalizeContactEmail($raw['contact_email'] ?? null),
                'status' => 'pending',
                'profile_image_url' => $image['image_url'] ?? null,
                'profile_image_attribution' => $image['attribution'] ?? null,
                'profile_image_license' => $image['license'] ?? null,
                'follower_count' => $candidate['follower_count'],
                'follower_platform' => $candidate['follower_platform'],
            ]);

            $linkBuilder->build($creator, $raw);
            $saved++;
        }

        $this->addSavedCount = $saved;
        $this->addSkippedCount = $skipped;

        $parts = [];
        if ($saved > 0) {
            $parts[] = "{$saved} creator(s) added to the pending queue.";
        }
        if ($skipped > 0) {
            $parts[] = "{$skipped} skipped (already exist).";
        }
        $this->addStatus = implode(' ', $parts) ?: 'Nothing saved.';

        if ($saved > 0) {
            session()->flash('success', "{$saved} creator(s) added.");
            $this->closeAddModal();
        }
    }

    // ── Generate ─────────────────────────────────────────────

    public function openGenerateModal(): void
    {
        $this->showGenerateModal = true;
        $this->generateNiches = [];
        $this->generateCountries = [];
        $this->resetGenerateState();
    }

    public function toggleAllNiches(): void
    {
        $this->generateNiches = count($this->generateNiches) === count($this->categories)
            ? []
            : $this->categories;
    }

    public function toggleAllCountries(): void
    {
        $this->generateCountries = count($this->generateCountries) === count($this->africanCountries)
            ? []
            : $this->africanCountries;
    }

    public function generate(
        PerplexityService $perplexity,
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
        CreatorSocialLinkBuilder $linkBuilder,
    ): void {
        $this->validate([
            'generateNiches' => 'required|array|min:1',
            'generateCountries' => 'required|array|min:1',
        ], [
            'generateNiches.required' => 'Select at least one niche.',
            'generateNiches.min' => 'Select at least one niche.',
            'generateCountries.required' => 'Select at least one country.',
            'generateCountries.min' => 'Select at least one country.',
        ]);

        if ($this->useQueue) {
            $this->dispatchBatch();

            return;
        }

        $this->runSynchronously($perplexity, $claude, $wikimedia, $linkBuilder);
    }

    private function dispatchBatch(): void
    {
        $this->generateCreatorsBefore = Creator::count();

        $jobs = [];
        foreach ($this->generateCountries as $country) {
            foreach ($this->generateNiches as $niche) {
                $jobs[] = new DiscoverCreatorsJob($niche, $country);
            }
        }

        $batch = Bus::batch($jobs)
            ->name('Discover creators: ' . count($this->generateNiches) . ' niche(s) x ' . count($this->generateCountries) . ' country/countries')
            ->allowFailures()
            ->dispatch();

        $this->generateBatchId = $batch->id;
        $this->generateBatchTotal = $batch->totalJobs;
        $this->generateBatchPending = $batch->pendingJobs;
        $this->generateBatchFailed = 0;
        $this->generateBatchFinished = false;
    }

    private function runSynchronously(
        PerplexityService $perplexity,
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
        CreatorSocialLinkBuilder $linkBuilder,
    ): void {
        $this->generating = true;
        $this->generatedCount = 0;
        $this->skippedCount = 0;
        $this->generateProgress = 0;

        $combinations = [];
        foreach ($this->generateCountries as $country) {
            foreach ($this->generateNiches as $niche) {
                $combinations[] = ['country' => $country, 'niche' => $niche];
            }
        }

        $this->generateTotal = count($combinations);

        foreach ($combinations as $combo) {
            $this->generateProgress++;
            $this->generateStatus = "Discovering {$combo['niche']} creators in {$combo['country']}... ({$this->generateProgress}/{$this->generateTotal})";

            $creators = $perplexity->discoverCreators($combo['niche'], $combo['country']);

            if (empty($creators)) {
                continue;
            }

            foreach ($creators as $creatorData) {
                $name = $creatorData['name'] ?? null;

                if (! $name) {
                    continue;
                }

                $slug = Str::slug($name);

                if (Creator::where('name', $name)->orWhere('slug', $slug)->exists()) {
                    $this->skippedCount++;

                    continue;
                }

                $bio = $claude->generateBio($creatorData);
                $image = $wikimedia->searchCreatorImage($name);

                $creator = Creator::create([
                    'name' => $name,
                    'bio' => $bio,
                    'country' => $creatorData['country'] ?? $combo['country'],
                    'category' => $creatorData['category'] ?? $combo['niche'],
                    'contact_email' => \App\Jobs\DiscoverCreatorsJob::normalizeContactEmail($creatorData['contact_email'] ?? null),
                    'status' => 'pending',
                    'profile_image_url' => $image['image_url'] ?? null,
                    'profile_image_attribution' => $image['attribution'] ?? null,
                    'profile_image_license' => $image['license'] ?? null,
                    'follower_count' => \App\Jobs\DiscoverCreatorsJob::normalizeFollowerCount($creatorData['estimated_follower_count'] ?? null),
                    'follower_platform' => \App\Jobs\DiscoverCreatorsJob::normalizeFollowerPlatform($creatorData['follower_platform'] ?? null),
                ]);

                $linkBuilder->build($creator, $creatorData);
                $this->generatedCount++;
            }
        }

        $status = "{$this->generatedCount} creators added to the pending queue.";

        if ($this->skippedCount > 0) {
            $status .= " {$this->skippedCount} skipped (already exist).";
        }

        $this->generateStatus = $status;
        $this->generating = false;
    }

    public function refreshBatchStatus(): void
    {
        if (! $this->generateBatchId) {
            return;
        }

        $batch = Bus::findBatch($this->generateBatchId);

        if (! $batch) {
            return;
        }

        $this->generateBatchTotal = $batch->totalJobs;
        $this->generateBatchPending = $batch->pendingJobs;
        $this->generateBatchFailed = $batch->failedJobs;
        $this->generateBatchFinished = $batch->finished();
    }

    public function clearBatch(): void
    {
        $this->resetGenerateState();
    }

    private function resetGenerateState(): void
    {
        $this->generateBatchId = null;
        $this->generateBatchTotal = 0;
        $this->generateBatchPending = 0;
        $this->generateBatchFailed = 0;
        $this->generateBatchFinished = false;
        $this->generateCreatorsBefore = 0;
        $this->generating = false;
        $this->generateStatus = '';
        $this->generatedCount = 0;
        $this->skippedCount = 0;
        $this->generateProgress = 0;
        $this->generateTotal = 0;
    }

    public function getBatchProcessedProperty(): int
    {
        return max(0, $this->generateBatchTotal - $this->generateBatchPending);
    }

    public function getBatchProgressPercentProperty(): int
    {
        if ($this->generateBatchTotal === 0) {
            return 0;
        }

        return (int) round(($this->batchProcessed / $this->generateBatchTotal) * 100);
    }

    public function getCreatorsAddedProperty(): int
    {
        if (! $this->generateBatchId) {
            return 0;
        }

        return max(0, Creator::count() - $this->generateCreatorsBefore);
    }

    // ── CRUD ─────────────────────────────────────────────────

    public function approve(int $id): void
    {
        $creator = Creator::findOrFail($id);
        $creator->update(['status' => 'published']);

        if ($creator->user_id) {
            $creator->user->syncRoles(['creator']);
        } else {
            $this->sendClaimInviteIfNeeded($creator);
        }

        session()->flash('success', "{$creator->name} has been published.");
    }

    public function bulkRepull(): void
    {
        $ids = array_map('intval', $this->selected);

        if (empty($ids)) {
            return;
        }

        // Dispatch one job per selection — the queue handles the rate.
        foreach ($ids as $id) {
            RepullCreatorJob::dispatch($id);
        }

        $count = count($ids);
        $this->selected = [];
        $this->selectAll = false;

        session()->flash('success', "{$count} creator re-pull job(s) queued. Watch the Job Monitor for progress.");
    }

    public function bulkApprove(): void
    {
        $creators = Creator::whereIn('id', $this->selected)->where('status', 'pending')->get();

        foreach ($creators as $creator) {
            $creator->update(['status' => 'published']);

            if ($creator->user_id) {
                $creator->user->syncRoles(['creator']);
            } else {
                $this->sendClaimInviteIfNeeded($creator);
            }
        }

        $this->selected = [];
        $this->selectAll = false;

        session()->flash('success', "{$creators->count()} creators published.");
    }

    public function approveClaim(int $id): void
    {
        $creator = Creator::findOrFail($id);
        $creator->update([
            'status' => 'claimed',
            'pending_claim_edit' => false,
            'claimed_at' => now(),
        ]);

        session()->flash('success', "{$creator->name}'s claim has been approved.");
    }

    // ── Pending Edit Review ──────────────────────────────────

    /**
     * Open the review modal for a creator with pending claim edits.
     * Shows the activity log diffs so staff can see what changed and
     * roll back individual edits if needed.
     */
    public function openReviewModal(int $id): void
    {
        $creator = Creator::findOrFail($id);
        $this->reviewingCreatorId = $creator->id;
        $this->reviewingCreatorName = $creator->name;
        $this->showReviewModal = true;
    }

    public function closeReviewModal(): void
    {
        $this->showReviewModal = false;
        $this->reviewingCreatorId = null;
        $this->reviewingCreatorName = null;
    }

    /**
     * Roll back a single activity log entry — applies the "old" values
     * back to the creator. Logs a new activity entry as the staff user
     * (so the rollback itself is auditable).
     */
    public function rollbackActivity(int $activityId): void
    {
        $activity = \Spatie\Activitylog\Models\Activity::query()
            ->where('id', $activityId)
            ->where('subject_type', Creator::class)
            ->firstOrFail();

        $creator = Creator::findOrFail($activity->subject_id);

        $changes = $activity->changes();
        $oldValues = $changes['old'] ?? [];

        if (empty($oldValues)) {
            session()->flash('error', 'Nothing to roll back — this entry has no recorded "old" values.');
            return;
        }

        // Filter out fields we don't want admins to roll back via this UI
        // (e.g. user_id changes are managed elsewhere).
        $rollbackable = collect($oldValues)
            ->except(['user_id', 'status'])
            ->toArray();

        if (empty($rollbackable)) {
            session()->flash('error', 'Nothing rollbackable in this entry.');
            return;
        }

        $creator->update($rollbackable);

        session()->flash('success', "Rolled back " . count($rollbackable) . " field(s) on {$creator->name}.");
    }

    /**
     * Mark a pending edit as reviewed without rolling back — used when
     * staff have eyeballed the changes and they look fine.
     */
    public function markReviewed(int $id): void
    {
        $creator = Creator::findOrFail($id);
        $creator->update(['pending_claim_edit' => false]);

        $this->closeReviewModal();
        session()->flash('success', "{$creator->name}'s changes marked as reviewed.");
    }

    /**
     * Computed property: the activity log entries for the creator
     * currently being reviewed in the modal.
     */
    public function getReviewActivitiesProperty()
    {
        if (! $this->reviewingCreatorId) {
            return collect();
        }

        return \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_type', Creator::class)
            ->where('subject_id', $this->reviewingCreatorId)
            ->where('description', 'like', 'Creator updated%')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function edit(int $id): void
    {
        $creator = Creator::with('socialLinks')->findOrFail($id);

        $this->editingCreatorId = $creator->id;
        $this->editName = $creator->name;
        $this->editBio = $creator->bio;
        $this->editCountry = $creator->country;
        $this->editCategory = $creator->category;
        $this->editContactEmail = $creator->contact_email ?? '';
        $this->editFollowerCount = $creator->follower_count;
        $this->editFollowerPlatform = $creator->follower_platform;
        $this->editPhotoData = null;
        $this->editPhotoName = null;

        $this->editBadge = match (true) {
            $creator->is_featured => 'featured',
            $creator->is_trending => 'trending',
            $creator->is_rising => 'rising',
            default => '',
        };

        $this->editSocialLinks = $creator->socialLinks->map(fn ($link) => [
            'id' => $link->id,
            'platform' => $link->platform,
            'url' => $link->url,
            'handle' => $link->handle ?? '',
            'follower_count' => $link->follower_count,
        ])->toArray();

        $this->showEditModal = true;
    }

    public function addSocialLink(): void
    {
        $this->editSocialLinks[] = [
            'id' => null,
            'platform' => 'instagram',
            'url' => '',
            'handle' => '',
            'follower_count' => null,
        ];
    }

    public function removeSocialLink(int $index): void
    {
        unset($this->editSocialLinks[$index]);
        $this->editSocialLinks = array_values($this->editSocialLinks);
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editName' => 'required|string|max:255',
            'editBio' => 'required|string',
            'editCountry' => 'required|string|max:100',
            'editCategory' => 'required|string',
            'editContactEmail' => 'nullable|email|max:255',
            'editPhotoData' => 'nullable|string',
            'editBadge' => 'nullable|in:rising,trending,featured',
            'editSocialLinks.*.follower_count' => 'nullable|integer|min:0',
        ]);

        $creator = Creator::findOrFail($this->editingCreatorId);

        $creator->update([
            'name' => $this->editName,
            'bio' => $this->editBio,
            'country' => $this->editCountry,
            'category' => $this->editCategory,
            'contact_email' => $this->editContactEmail ?: null,
            'is_rising' => $this->editBadge === 'rising',
            'is_trending' => $this->editBadge === 'trending',
            'is_featured' => $this->editBadge === 'featured',
        ]);

        // Handle photo upload via Spatie MediaLibrary (S3) using base64 data URL
        if ($this->editPhotoData) {
            $this->savePhotoFromBase64($creator);
        }

        // Sync social links
        $existingIds = [];
        foreach ($this->editSocialLinks as $linkData) {
            if (empty($linkData['url'])) {
                continue;
            }

            $followerCount = isset($linkData['follower_count']) && $linkData['follower_count'] !== ''
                ? (int) $linkData['follower_count']
                : null;

            if (! empty($linkData['id'])) {
                $creator->socialLinks()->where('id', $linkData['id'])->update([
                    'platform' => $linkData['platform'],
                    'url' => $linkData['url'],
                    'handle' => $linkData['handle'] ?: null,
                    'follower_count' => $followerCount,
                ]);
                $existingIds[] = $linkData['id'];
            } else {
                $link = $creator->socialLinks()->create([
                    'platform' => $linkData['platform'],
                    'url' => $linkData['url'],
                    'handle' => $linkData['handle'] ?: null,
                    'follower_count' => $followerCount,
                ]);
                $existingIds[] = $link->id;
            }
        }

        $creator->socialLinks()->whereNotIn('id', $existingIds)->delete();

        $this->showEditModal = false;

        session()->flash('success', "{$creator->name} updated.");
    }

    /**
     * Decode the base64 photo data URL to a temp file and attach it to the creator
     * via Spatie MediaLibrary (which routes to S3 based on the collection's disk).
     */
    protected function savePhotoFromBase64(Creator $creator): void
    {
        $data = $this->editPhotoData;

        // Strip the data URL prefix if present (e.g. "data:image/png;base64,...")
        if (preg_match('/^data:(image\/[a-zA-Z+.-]+);base64,(.+)$/', $data, $matches)) {
            $mime = $matches[1];
            $payload = $matches[2];
        } else {
            $mime = 'image/jpeg';
            $payload = $data;
        }

        $binary = base64_decode($payload, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 image data.');
        }

        // Enforce a 5MB cap on the decoded payload.
        if (strlen($binary) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Image is larger than 5MB.');
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };

        $tmpPath = tempnam(sys_get_temp_dir(), 'creator_photo_') . '.' . $extension;
        file_put_contents($tmpPath, $binary);

        try {
            $creator->clearMediaCollection('profile_image');
            $creator->addMedia($tmpPath)
                ->usingFileName($this->editPhotoName ?: ('photo.' . $extension))
                ->toMediaCollection('profile_image');
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }

        $this->editPhotoData = null;
        $this->editPhotoName = null;
    }

    public function enhanceBio(ClaudeBioService $claude): void
    {
        if (! $this->editingCreatorId) {
            return;
        }

        $bio = trim((string) $this->editBio);

        if ($bio === '') {
            session()->flash('error', 'Add some bio text first, then click Enhance.');

            return;
        }

        $this->editBio = $claude->enhanceBio($bio, [
            'name' => $this->editName,
            'country' => $this->editCountry,
            'category' => $this->editCategory,
        ]);

        session()->flash('success', 'Bio enhanced. Review and click Save Changes to apply.');
    }

    public function repullIntoForm(
        PerplexityService $perplexity,
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
    ): void {
        if (! $this->editingCreatorId) {
            return;
        }

        $results = $perplexity->discoverCreators($this->editCategory, $this->editCountry, 1);

        $match = collect($results)->first(function ($r) {
            return isset($r['name']) && Str::lower($r['name']) === Str::lower($this->editName);
        }) ?? ($results[0] ?? null);

        if (! $match) {
            session()->flash('error', "Could not re-pull data for {$this->editName}.");

            return;
        }

        $match['name'] = $this->editName;

        $bio = $claude->generateBio($match);
        $image = $wikimedia->searchCreatorImage($this->editName);

        // Update form fields (not saved until admin clicks Save Changes)
        $this->editBio = $bio;

        // Only overwrite the existing email if the AI returned a syntactically
        // valid one — garbage in the field (e.g. "example.com") would
        // otherwise clobber a previously-valid value.
        $newEmail = \App\Jobs\DiscoverCreatorsJob::normalizeContactEmail($match['contact_email'] ?? null);
        if ($newEmail !== null) {
            $this->editContactEmail = $newEmail;
        }

        // Refresh social links in the form (preserve any unsaved manual edits by replacing entirely)
        $platforms = [
            'instagram' => ['handle_keys' => ['instagram_handle', 'instagram', 'ig'], 'url_keys' => ['instagram_url'], 'url_template' => 'https://instagram.com/{handle}'],
            'tiktok' => ['handle_keys' => ['tiktok_handle', 'tiktok'], 'url_keys' => ['tiktok_url'], 'url_template' => 'https://tiktok.com/@{handle}'],
            'youtube' => ['handle_keys' => ['youtube_handle'], 'url_keys' => ['youtube_channel_url', 'youtube_url', 'youtube'], 'url_template' => 'https://youtube.com/@{handle}'],
            'twitter' => ['handle_keys' => ['twitter_handle', 'twitter', 'x_handle'], 'url_keys' => ['twitter_url', 'x_url'], 'url_template' => 'https://x.com/{handle}'],
            'facebook' => ['handle_keys' => ['facebook_handle'], 'url_keys' => ['facebook_url', 'facebook'], 'url_template' => 'https://facebook.com/{handle}'],
            'website' => ['handle_keys' => [], 'url_keys' => ['website_url', 'website'], 'url_template' => null],
        ];

        $newLinks = [];
        foreach ($platforms as $platform => $cfg) {
            $handle = null;
            foreach ($cfg['handle_keys'] as $k) {
                if (! empty($match[$k]) && is_string($match[$k])) {
                    $handle = ltrim(trim($match[$k]), '@');
                    break;
                }
            }
            $url = null;
            foreach ($cfg['url_keys'] as $k) {
                if (! empty($match[$k]) && is_string($match[$k])) {
                    $url = trim($match[$k]);
                    break;
                }
            }

            if (! $url && $handle && $cfg['url_template']) {
                $url = str_replace('{handle}', $handle, $cfg['url_template']);
            }

            if ($url) {
                $newLinks[] = [
                    'id' => null,
                    'platform' => $platform,
                    'url' => $url,
                    'handle' => $handle ?? '',
                ];
            }
        }

        if (! empty($newLinks)) {
            $this->editSocialLinks = $newLinks;
        }

        // Update image and follower estimate directly (these aren't part of the
        // editable form fields — image is stored server-side, follower data is
        // read-only in the modal — so we persist them immediately rather than
        // wait for a Save Changes click that wouldn't touch them anyway).
        $creator = Creator::find($this->editingCreatorId);
        if ($creator) {
            $updates = [];
            if (! empty($image['image_url'])) {
                $updates['profile_image_url'] = $image['image_url'];
                $updates['profile_image_attribution'] = $image['attribution'] ?? null;
                $updates['profile_image_license'] = $image['license'] ?? null;
            }

            $newFollowerCount = \App\Jobs\DiscoverCreatorsJob::normalizeFollowerCount($match['estimated_follower_count'] ?? null);
            $newFollowerPlatform = \App\Jobs\DiscoverCreatorsJob::normalizeFollowerPlatform($match['follower_platform'] ?? null);

            if ($newFollowerCount !== null) {
                $updates['follower_count'] = $newFollowerCount;
                $updates['follower_platform'] = $newFollowerPlatform;
                $this->editFollowerCount = $newFollowerCount;
                $this->editFollowerPlatform = $newFollowerPlatform;
            }

            if (! empty($updates)) {
                $creator->update($updates);
            }
        }

        session()->flash('success', "Form refreshed with new AI data. Review and click Save Changes to apply.");
    }

    public function repullCreator(
        int $id,
        PerplexityService $perplexity,
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
        CreatorSocialLinkBuilder $linkBuilder,
    ): void {
        $creator = Creator::findOrFail($id);

        // Ask Perplexity for fresh data on this specific creator (count=1, niche=their category)
        $results = $perplexity->discoverCreators($creator->category, $creator->country, 1);

        // Try to find a match by name in case Perplexity returns multiple
        $match = collect($results)->first(function ($r) use ($creator) {
            return isset($r['name']) && Str::lower($r['name']) === Str::lower($creator->name);
        }) ?? ($results[0] ?? null);

        if (! $match) {
            session()->flash('error', "Could not re-pull data for {$creator->name}.");

            return;
        }

        // Override the name to keep the existing one (avoid mismatched re-pulls)
        $match['name'] = $creator->name;

        $bio = $claude->generateBio($match);
        $image = $wikimedia->searchCreatorImage($creator->name);

        $newFollowerCount = \App\Jobs\DiscoverCreatorsJob::normalizeFollowerCount($match['estimated_follower_count'] ?? null);
        $newFollowerPlatform = \App\Jobs\DiscoverCreatorsJob::normalizeFollowerPlatform($match['follower_platform'] ?? null);
        $newContactEmail = \App\Jobs\DiscoverCreatorsJob::normalizeContactEmail($match['contact_email'] ?? null);

        $creator->update([
            'bio' => $bio,
            // Only overwrite on a confident new value — an invalid AI result
            // must not wipe previously-good data (same pattern as follower fields).
            'contact_email' => $newContactEmail ?? $creator->contact_email,
            'profile_image_url' => $image['image_url'] ?? $creator->getRawOriginal('profile_image_url'),
            'profile_image_attribution' => $image['attribution'] ?? $creator->profile_image_attribution,
            'profile_image_license' => $image['license'] ?? $creator->profile_image_license,
            'follower_count' => $newFollowerCount ?? $creator->follower_count,
            'follower_platform' => $newFollowerPlatform ?? $creator->follower_platform,
        ]);

        // Re-build social links (updateOrCreate prevents duplicates)
        $linkBuilder->build($creator, $match);

        session()->flash('success', "{$creator->name} re-pulled from AI.");
    }

    public function delete(int $id): void
    {
        $creator = Creator::findOrFail($id);
        $name = $creator->name;
        $creator->socialLinks()->delete();
        $creator->delete();

        session()->flash('success', "{$name} deleted.");
    }

    // ── Helpers ───────────────────────────────────────────────

    public function getCreators(): LengthAwarePaginator
    {
        $query = Creator::with('socialLinks');

        if ($this->search) {
            $query->search($this->search);
        }

        if ($this->countryFilter !== '') {
            $query->where('country', $this->countryFilter);
        }

        if ($this->followerFilter !== '') {
            $query = match ($this->followerFilter) {
                'none' => $query->where(fn ($q) => $q->whereNull('follower_count')->orWhere('follower_count', 0)),
                '1k' => $query->where('follower_count', '>=', 1000)->where('follower_count', '<', 10000),
                '10k' => $query->where('follower_count', '>=', 10000)->where('follower_count', '<', 50000),
                '50k' => $query->where('follower_count', '>=', 50000)->where('follower_count', '<', 100000),
                '100k' => $query->where('follower_count', '>=', 100000)->where('follower_count', '<', 500000),
                '500k' => $query->where('follower_count', '>=', 500000),
                default => $query,
            };
        }

        $query = match ($this->filter) {
            'pending' => $query->where('status', 'pending'),
            'published' => $query->where('status', 'published'),
            'claimed' => $query->where('status', 'claimed'),
            'pending_edits' => $query->where('pending_claim_edit', true),
            default => $query,
        };

        $perPage = in_array($this->perPage, [10, 20, 50, 100, 200], true) ? $this->perPage : 20;

        return $query->latest()->paginate($perPage);
    }

    public function render(): View
    {
        return view('livewire.admin.manage-creators', [
            'creators' => $this->getCreators(),
            'categories' => $this->categories,
            'africanCountries' => $this->africanCountries,
            'availableCountries' => Creator::query()
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->distinct()
                ->orderBy('country')
                ->pluck('country')
                ->all(),
            'counts' => [
                'all' => Creator::count(),
                'pending' => Creator::where('status', 'pending')->count(),
                'published' => Creator::where('status', 'published')->count(),
                'claimed' => Creator::where('status', 'claimed')->count(),
                'pending_edits' => Creator::where('pending_claim_edit', true)->count(),
            ],
        ])->title('Manage Creators');
    }

    private function sendClaimInviteIfNeeded(Creator $creator): void
    {
        if (! $creator->contact_email) {
            return;
        }

        // Belt-and-braces: even if something slipped past the ingestion
        // normalizers, never hand Symfony Mime an invalid address — it
        // throws an RfcComplianceException that crashes the queue worker.
        if (! filter_var($creator->contact_email, FILTER_VALIDATE_EMAIL)) {
            \Log::warning('sendClaimInviteIfNeeded: skipping invalid email', [
                'creator_id' => $creator->id,
                'email' => $creator->contact_email,
            ]);
            return;
        }

        $creator->update([
            'claim_token' => Str::uuid()->toString(),
            'claim_token_expires_at' => now()->addHours(48),
        ]);

        Mail::to($creator->contact_email)->queue(new CreatorClaimInvite($creator->fresh()));
    }

}
