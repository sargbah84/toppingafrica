<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Mail\PromotionLive;
use App\Models\Post;
use App\Models\PromotionRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class ManagePromotions extends Component
{
    use WithPagination;

    /** Where each deliverable was published. */
    public const DELIVERABLES = [
        'facebook_post' => 'Facebook post',
        'instagram_post' => 'Instagram post',
        'youtube_video' => 'YouTube video',
        'tiktok_video' => 'TikTok video',
        'instagram_reel' => 'Instagram Reel',
    ];

    /** Numbers entered by hand for the buyer's performance report. */
    public const METRICS = [
        'facebook_reach' => 'Facebook reach',
        'instagram_reach' => 'Instagram reach',
        'youtube_views' => 'YouTube views',
        'tiktok_views' => 'TikTok views',
        'boost_spend' => 'Boost spend ($)',
    ];

    #[Url]
    public string $filter = 'inquiries';

    #[Url]
    public string $search = '';

    /** Reference of the order shown in the detail panel (deep-linkable from email). */
    #[Url(as: 'open')]
    public string $openReference = '';

    // Detail form
    public string $status = '';

    public string $postLookup = '';

    public array $deliverables = [];

    public array $metrics = [];

    public string $adminNotes = '';

    public function mount(): void
    {
        if ($this->openReference !== '') {
            $this->open($this->openReference);
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function open(string $reference): void
    {
        $promotion = PromotionRequest::with('post')->where('reference', $reference)->first();

        if (! $promotion) {
            $this->openReference = '';

            return;
        }

        $this->openReference = $promotion->reference;
        $this->status = $promotion->status;
        $this->postLookup = $promotion->post ? (string) $promotion->post->id : '';
        $this->deliverables = array_merge(array_fill_keys(array_keys(self::DELIVERABLES), ''), $promotion->deliverables ?? []);
        $this->metrics = array_merge(array_fill_keys(array_keys(self::METRICS), ''), array_map('strval', $promotion->metrics ?? []));
        $this->adminNotes = (string) $promotion->admin_notes;
        $this->resetValidation();
    }

    public function close(): void
    {
        $this->openReference = '';
    }

    public function save(): void
    {
        $promotion = $this->current();

        $this->validate([
            'status' => ['required', Rule::in(array_keys(PromotionRequest::STATUSES))],
            'postLookup' => ['nullable', 'string', 'max:255'],
            'deliverables' => ['array'],
            'deliverables.*' => ['nullable', 'url:http,https', 'max:500'],
            'metrics' => ['array'],
            'metrics.*' => ['nullable', 'numeric', 'min:0'],
            'adminNotes' => ['nullable', 'string', 'max:5000'],
        ]);

        $post = null;
        if (trim($this->postLookup) !== '') {
            $post = $this->resolvePost(trim($this->postLookup));

            if (! $post) {
                $this->addError('postLookup', 'No post found with that ID, slug or URL.');

                return;
            }
        }

        $becameLive = $this->status === 'live' && $promotion->live_at === null;

        // Invoices are paid outside the site; moving past "paid" records it.
        $markPaid = $promotion->paid_at === null
            && in_array($this->status, ['paid', 'in_production', 'live', 'completed'], true);

        $promotion->forceFill([
            'status' => $this->status,
            'post_id' => $post?->id,
            'deliverables' => array_filter(array_intersect_key($this->deliverables, self::DELIVERABLES)),
            'metrics' => array_map('floatval', array_filter(
                array_intersect_key($this->metrics, self::METRICS),
                fn ($v) => $v !== '' && $v !== null,
            )),
            'admin_notes' => $this->adminNotes ?: null,
            'live_at' => $becameLive ? now() : $promotion->live_at,
            'paid_at' => $markPaid ? now() : $promotion->paid_at,
        ])->save();

        if ($post && ! $post->is_sponsored) {
            $post->update(['is_sponsored' => true]);
        }

        if ($becameLive) {
            try {
                Mail::to($promotion->email)->send(new PromotionLive($promotion->load('post')));
                session()->flash('success', "Saved. {$promotion->name} was emailed their live links.");
            } catch (\Throwable $e) {
                Log::error('Promotion live email failed', ['reference' => $promotion->reference, 'error' => $e->getMessage()]);
                session()->flash('error', 'Saved, but the "you\'re live" email failed to send.');
            }
        } else {
            session()->flash('success', "Order {$promotion->reference} saved.");
        }

        $this->open($promotion->reference);
    }

    /** Creates a sponsored draft pre-filled from the order and opens it in the editor. */
    public function createDraftPost(): mixed
    {
        $promotion = $this->current();

        if ($promotion->post_id) {
            return redirect()->route('admin.blog.posts.edit', $promotion->post_id);
        }

        $links = collect(['Main link' => $promotion->primary_url, ...($promotion->links ?? [])])
            ->map(fn ($url, $label) => '<li>'.e(ucfirst((string) $label)).': <a href="'.e($url).'">'.e($url).'</a></li>')
            ->implode('');

        $post = Post::create([
            'author_id' => auth()->id(),
            'title' => "{$promotion->subject_name} — {$promotion->title}",
            'content' => '<p>'.nl2br(e($promotion->description)).'</p><ul>'.$links.'</ul>',
            'post_type' => $promotion->promo_type === 'video' ? 'video' : 'article',
            'status' => 'draft',
            'is_sponsored' => true,
        ]);

        $promotion->forceFill([
            'post_id' => $post->id,
            'status' => $promotion->status === 'paid' ? 'in_production' : $promotion->status,
        ])->save();

        return redirect()->route('admin.blog.posts.edit', $post->id);
    }

    protected function current(): PromotionRequest
    {
        return PromotionRequest::where('reference', $this->openReference)->firstOrFail();
    }

    protected function resolvePost(string $lookup): ?Post
    {
        if (ctype_digit($lookup)) {
            return Post::find((int) $lookup);
        }

        $slug = trim((string) parse_url($lookup, PHP_URL_PATH), '/') ?: $lookup;

        return Post::where('slug', basename($slug))->first();
    }

    public function render(): View
    {
        $filters = [
            'inquiries' => ['inquiry'],
            'awaiting_payment' => ['invoiced', 'pending_payment'],
            'open' => ['paid', 'in_production'],
            'live' => ['live'],
            'completed' => ['completed'],
            'closed' => ['declined', 'cancelled'],
        ];

        $counts = collect($filters)->map(fn ($statuses) => PromotionRequest::whereIn('status', $statuses)->count());
        $counts['all'] = PromotionRequest::count();

        $promotions = PromotionRequest::query()
            ->when(isset($filters[$this->filter]), fn ($q) => $q->whereIn('status', $filters[$this->filter]))
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(fn ($q) => $q->where('reference', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('subject_name', 'like', $term)
                    ->orWhere('title', 'like', $term));
            })
            ->latest()
            ->paginate(20);

        return view('livewire.admin.manage-promotions', [
            'promotions' => $promotions,
            'counts' => $counts,
            'revenue' => PromotionRequest::whereNotNull('paid_at')->whereNotIn('status', ['declined'])->sum('amount'),
            'selected' => $this->openReference !== ''
                ? PromotionRequest::with(['post', 'media'])->where('reference', $this->openReference)->first()
                : null,
        ]);
    }
}
