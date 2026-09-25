<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\VerifiesTurnstile;
use App\Models\Page;
use App\Models\PromotionRequest;
use App\Services\Promotions\PromotionCheckout;
use App\Services\Promotions\PromotionPricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class PromotionController extends Controller
{
    use VerifiesTurnstile;

    /** Social/profile links buyers can add besides the primary link. */
    public const LINK_FIELDS = [
        'youtube' => 'YouTube',
        'spotify' => 'Spotify / Apple Music',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'website' => 'Website',
    ];

    public function __construct(
        private readonly PromotionPricing $pricing,
        private readonly PromotionCheckout $checkout,
    ) {}

    /**
     * Rendered by BlogController::show() for the CMS page using the `promote`
     * template, so its slug, SEO fields and header-menu entry are editable.
     */
    public function index(Page $page): View
    {
        $online = $this->checkout->isEnabled();

        return view('promote.index', [
            'page' => $page,
            'packages' => config('promotions.packages'),
            'addons' => config('promotions.addons'),
            'steps' => [
                ['1', 'Pick a package', 'Choose what fits your budget — from a quick news post to a full video spotlight.'],
                ['2', 'Tell us your story', 'Share your links, photos and the details that make your release special.'],
                $online
                    ? ['3', 'Pay securely', 'Checkout by card, Apple Pay or Google Pay through Stripe, from anywhere in the world.']
                    : ['3', 'Get your invoice', 'We review your request and email you an invoice with a secure payment link.'],
                ['4', 'Go live', 'Our editors produce and publish, then email you every link to share with your fans.'],
            ],
            'faqs' => [
                ['Who can promote with Topping Africa?', 'Musicians, filmmakers, creators, event organisers, startups and brands — from Africa and the diaspora. We focus on positive, creative and entrepreneurial stories and don\'t accept political campaigns, gambling, adult content or unverified financial offers.'],
                ['Will my post be marked as sponsored?', 'Yes. Paid features carry a "Sponsored" label on our site, and social posts use the platforms\' paid-partnership labels. It keeps our readers\' trust — which is what makes the promotion worth it.'],
                ['Do I get to approve the article?', 'Our editors write the story using what you send us. We\'ll happily fix factual errors, but Topping Africa keeps final editorial control over wording and headlines.'],
                ['How does the paid boost work?', 'For Social Boost and Spotlight, we run the included ad budget on Facebook and Instagram for about 7 days, targeting audiences most likely to engage with your release. You can add more budget with the Extra Paid Boost add-on.'],
                $online
                    ? ['What if you decline my request?', 'If your submission doesn\'t fit our editorial guidelines, we\'ll let you know within 2 business days and refund you in full.']
                    : ['When do I pay?', 'Not until we\'ve reviewed your request. Within 2 business days we\'ll email you an invoice with a secure payment link. Production starts once it\'s paid.'],
                ['What about music rights on YouTube?', 'Spotlight videos may include clips of your song. Please make sure your distributor won\'t block it — if your music is registered with YouTube Content ID, ask your distributor to allowlist the Topping Africa channel.'],
                ['Which currencies and payment methods do you accept?', 'Prices are in US dollars. We accept all major cards plus Apple Pay and Google Pay, and your bank converts from your local currency.'],
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $package = (string) $request->query('package');
        $packages = config('promotions.packages');
        $addons = config('promotions.addons');

        return view('promote.checkout', [
            'packages' => $packages,
            'addons' => $addons,
            // Display-only mirror for the live total; the server recomputes on submit.
            'pricing' => [
                'packages' => array_map(fn (array $p) => $p['price'], $packages),
                'addons' => array_map(fn (array $a) => [
                    'price' => $a['price'],
                    'excluded' => $a['excluded_packages'] ?? [],
                ], $addons),
            ],
            'promoTypes' => config('promotions.promo_types'),
            'linkFields' => self::LINK_FIELDS,
            'selectedPackage' => $this->pricing->packageExists($package) ? $package : 'boost',
            'onlinePayments' => $this->checkout->isEnabled(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'package' => ['required', 'string', Rule::in(array_keys(config('promotions.packages')))],
            'addons' => ['nullable', 'array'],
            'addons.*' => ['string', Rule::in(array_keys(config('promotions.addons')))],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'promo_type' => ['required', 'string', Rule::in(array_keys(config('promotions.promo_types')))],
            'subject_name' => ['required', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'min:40', 'max:5000'],
            'primary_url' => ['required', 'url:http,https', 'max:500'],
            'links' => ['nullable', 'array'],
            'links.*' => ['nullable', 'url:http,https', 'max:500'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assets' => ['nullable', 'array', 'max:5'],
            'assets.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'rights_confirmed' => ['accepted'],
            'terms' => ['accepted'],
        ], [
            'rights_confirmed.accepted' => 'Please confirm you own or have permission to use this content.',
            'terms.accepted' => 'Please accept the promotion terms.',
            'description.min' => 'Tell us a little more (at least 40 characters) so we can write a great story.',
        ]);

        $this->verifyTurnstile($request, 'promotion_request');

        $online = $this->checkout->isEnabled();
        $addons = $this->pricing->sanitizeAddons($data['package'], $data['addons'] ?? []);

        $promotion = PromotionRequest::create([
            ...collect($data)->except(['addons', 'links', 'assets', 'terms'])->all(),
            'user_id' => $request->user()?->id,
            'addons' => $addons,
            'links' => array_filter(
                array_intersect_key($data['links'] ?? [], self::LINK_FIELDS)
            ),
            'amount' => $this->pricing->total($data['package'], $addons),
            'currency' => config('promotions.currency'),
            'rights_confirmed' => true,
            'status' => $online ? 'pending_payment' : 'inquiry',
        ]);

        foreach ($request->file('assets', []) as $file) {
            $promotion->addMedia($file)->toMediaCollection('assets');
        }

        if ($online) {
            return $this->redirectToCheckout($promotion);
        }

        $this->checkout->notifyInquiry($promotion);

        return redirect()->to(URL::signedRoute('promote.thanks', $promotion));
    }

    /** Signed link used as Stripe's cancel URL — lets the buyer retry payment. */
    public function pay(PromotionRequest $promotion): RedirectResponse|View
    {
        if ($promotion->status !== 'pending_payment') {
            return redirect(template_url('promote'))
                ->with('status', "Order {$promotion->reference} is already paid. We'll be in touch by email.");
        }

        return view('promote.pay', ['promotion' => $promotion]);
    }

    public function startPayment(PromotionRequest $promotion): RedirectResponse
    {
        if ($promotion->status !== 'pending_payment') {
            return redirect(template_url('promote'));
        }

        return $this->redirectToCheckout($promotion);
    }

    /** Reached via a signed link (inquiries) or Stripe's success redirect. */
    public function thanks(Request $request, PromotionRequest $promotion): View
    {
        $sessionId = (string) $request->query('session_id');
        $fromStripe = $sessionId !== '' && hash_equals((string) $promotion->stripe_session_id, $sessionId);

        abort_unless($fromStripe || $request->hasValidSignature(), 404);

        // The webhook normally gets here first; this covers a delayed webhook.
        if ($fromStripe && ! $promotion->isPaid()) {
            try {
                $this->checkout->fulfil($promotion, $this->checkout->retrieveSession($sessionId));
                $promotion->refresh();
            } catch (\Throwable $e) {
                Log::error('Promotion thanks-page session lookup failed', [
                    'reference' => $promotion->reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return view('promote.thanks', ['promotion' => $promotion]);
    }

    public function webhook(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                (string) config('services.stripe.webhook_secret'),
            );
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return response('Invalid signature', 400);
        }

        if (in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            /** @var Session $session */
            $session = $event->data->object;
            $reference = $session->metadata['promotion_reference'] ?? null;

            if ($reference && $promotion = PromotionRequest::where('reference', $reference)->first()) {
                $this->checkout->fulfil($promotion, $session);
            }
        }

        return response('ok');
    }

    protected function redirectToCheckout(PromotionRequest $promotion): RedirectResponse
    {
        try {
            return redirect()->away($this->checkout->createSession($promotion));
        } catch (\Throwable $e) {
            Log::error('Stripe checkout session failed', ['reference' => $promotion->reference, 'error' => $e->getMessage()]);

            return redirect()->to($promotion->payUrl())
                ->withErrors(['payment' => "We couldn't start the payment. Please try again in a moment."]);
        }
    }
}
