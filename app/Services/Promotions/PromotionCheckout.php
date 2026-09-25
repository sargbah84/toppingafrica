<?php

declare(strict_types=1);

namespace App\Services\Promotions;

use App\Mail\PromotionAdminAlert;
use App\Mail\PromotionInquiryReceived;
use App\Mail\PromotionReceipt;
use App\Models\PromotionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class PromotionCheckout
{
    public function __construct(private readonly PromotionPricing $pricing) {}

    /**
     * Whether buyers pay by Stripe at submission. When off, submissions are
     * inquiries and the team sends invoices by hand.
     */
    public function isEnabled(): bool
    {
        return (bool) config('promotions.online_payments') && filled(config('services.stripe.secret'));
    }

    /** Confirms an inquiry to the buyer and alerts the team. */
    public function notifyInquiry(PromotionRequest $request): void
    {
        activity()->performedOn($request)->log("Promotion inquiry {$request->reference} ({$request->package_name}, {$request->formatted_amount})");

        try {
            Mail::to($request->email)->send(new PromotionInquiryReceived($request));
            Mail::to($this->notifyAddress())->send(new PromotionAdminAlert($request));
        } catch (\Throwable $e) {
            Log::error('Promotion inquiry email failed', ['reference' => $request->reference, 'error' => $e->getMessage()]);
        }
    }

    protected function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }

    /** Creates a Stripe Checkout session and returns its hosted URL. */
    public function createSession(PromotionRequest $request): string
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $request->email,
            'client_reference_id' => $request->reference,
            'metadata' => ['promotion_reference' => $request->reference],
            'payment_intent_data' => [
                'description' => "Topping Africa promotion {$request->reference}",
                'metadata' => ['promotion_reference' => $request->reference],
            ],
            'line_items' => array_map(fn (array $item) => [
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($request->currency),
                    'unit_amount' => $item['amount'],
                    'product_data' => ['name' => $item['name']],
                ],
            ], $this->pricing->lineItems($request->package, $request->addons ?? [])),
            'success_url' => route('promote.thanks', $request).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $request->payUrl(),
        ]);

        $request->forceFill(['stripe_session_id' => $session->id])->save();

        return $session->url;
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId);
    }

    /**
     * Marks the request paid when the session is paid and belongs to it.
     * Called from both the success redirect and the webhook — only the first
     * call flips the state and sends email.
     */
    public function fulfil(PromotionRequest $request, Session $session): bool
    {
        if ($session->payment_status !== 'paid'
            || ($session->metadata['promotion_reference'] ?? null) !== $request->reference
            || (int) $session->amount_total !== $request->amount) {
            return false;
        }

        $marked = DB::transaction(function () use ($request, $session) {
            $locked = PromotionRequest::whereKey($request->id)->lockForUpdate()->first();

            if ($locked->paid_at !== null) {
                return false;
            }

            $paymentIntent = $session->payment_intent;

            $locked->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'stripe_session_id' => $session->id,
                'stripe_payment_intent' => is_string($paymentIntent) ? $paymentIntent : $paymentIntent?->id,
            ])->save();

            return true;
        });

        if (! $marked) {
            return false;
        }

        $request->refresh();

        activity()->performedOn($request)->log("Promotion {$request->reference} paid ({$request->formatted_amount})");

        try {
            Mail::to($request->email)->send(new PromotionReceipt($request));
            Mail::to($this->notifyAddress())->send(new PromotionAdminAlert($request));
        } catch (\Throwable $e) {
            Log::error('Promotion paid email failed', ['reference' => $request->reference, 'error' => $e->getMessage()]);
        }

        return true;
    }

    protected function notifyAddress(): string
    {
        return config('promotions.notify_email') ?: (string) config('mail.from.address');
    }
}
