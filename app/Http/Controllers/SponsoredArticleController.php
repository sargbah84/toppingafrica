<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\VerifiesTurnstile;
use App\Models\PromotionRequest;
use App\Services\Promotions\PromotionCheckout;
use App\Services\Promotions\PromotionPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Sponsored articles: a signed-in, email-verified user submits their own
 * finished article. It is always reviewed first, then paid for, then
 * published by staff from Admin → Promotions.
 */
class SponsoredArticleController extends Controller
{
    use VerifiesTurnstile;

    public function __construct(
        private readonly PromotionPricing $pricing,
        private readonly PromotionCheckout $checkout,
    ) {}

    /** The user's submissions, under /dashboard. */
    public function index(Request $request): View
    {
        return view('dashboard.sponsored', [
            'user' => $request->user(),
            'submissions' => $request->user()->promotionRequests()
                ->where('promo_type', PromotionRequest::SPONSORED_ARTICLE)
                ->with('post')
                ->latest()
                ->get(),
            'canPayOnline' => $this->checkout->isEnabled(),
        ]);
    }

    public function create(Request $request): View
    {
        $packages = config('promotions.sponsored_packages');
        $package = (string) $request->query('package');

        return view('promote.sponsored', [
            'packages' => $packages,
            'selectedPackage' => isset($packages[$package]) ? $package : array_key_first($packages),
            'minWords' => (int) config('promotions.sponsored_min_words'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $packages = config('promotions.sponsored_packages');
        $minWords = (int) config('promotions.sponsored_min_words');
        $maxWords = (int) ($packages[$request->input('package')]['max_words'] ?? 0);

        $data = $request->validate([
            'package' => ['required', 'string', Rule::in(array_keys($packages))],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'subject_name' => ['required', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:200'],
            'article_body' => ['required', 'string', 'max:30000', function (string $attribute, mixed $value, \Closure $fail) use ($minWords, $maxWords) {
                $words = self::wordCount((string) $value);

                if ($words < $minWords) {
                    $fail("Your article is {$words} words. It needs at least {$minWords}.");
                } elseif ($maxWords > 0 && $words > $maxWords) {
                    $fail("Your article is {$words} words. This package allows up to {$maxWords}.");
                }
            }],
            'primary_url' => ['required', 'url:http,https', 'max:500'],
            'anchor_text' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assets' => ['nullable', 'array', 'max:3'],
            'assets.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'rights_confirmed' => ['accepted'],
            'terms' => ['accepted'],
        ], [
            'rights_confirmed.accepted' => 'Please confirm you own or have permission to publish this article.',
            'terms.accepted' => 'Please accept the sponsored article terms.',
        ]);

        $this->verifyTurnstile($request, 'sponsored_article');

        $user = $request->user();

        $submission = PromotionRequest::create([
            ...collect($data)->except(['assets', 'terms'])->all(),
            'user_id' => $user->id,
            // The account's verified address, not a typed one.
            'email' => $user->email,
            'promo_type' => PromotionRequest::SPONSORED_ARTICLE,
            'article_body' => trim(strip_tags($data['article_body'])),
            'description' => $data['description'] ?? '',
            'addons' => [],
            'amount' => $this->pricing->total($data['package'], []),
            'currency' => config('promotions.currency'),
            'rights_confirmed' => true,
            // Reviewed before any payment is taken, whatever the checkout mode.
            'status' => 'inquiry',
        ]);

        foreach ($request->file('assets', []) as $file) {
            $submission->addMedia($file)->toMediaCollection('assets');
        }

        // Single-role rule — only plain accounts become contributors;
        // creators, authors and staff keep the role they have.
        if ($user->roles->isEmpty() || $user->hasRole('regular')) {
            $user->syncRoles(['contributor']);
        }

        $this->checkout->notifyInquiry($submission);

        return redirect()->route('dashboard.sponsored')
            ->with('success', "Thanks! Your article is in review (reference {$submission->reference}). We'll email you within 2 business days.");
    }

    public static function wordCount(string $text): int
    {
        return count(preg_split('/\s+/u', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY));
    }
}
