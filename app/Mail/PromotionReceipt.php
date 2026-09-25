<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PromotionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the buyer once Stripe confirms payment. */
class PromotionReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PromotionRequest $promotion) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "We've got your promotion request ({$this->promotion->reference})");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.promotion',
            with: [
                'promotion' => $this->promotion,
                'greeting' => "Hi {$this->promotion->name},",
                'paragraphs' => [
                    "Thank you — your payment of {$this->promotion->formatted_amount} for <strong>{$this->promotion->package_name}</strong> is confirmed.",
                    'Our editorial team will review your submission and start production. If we need anything else (photos, quotes, clips), we will reply to this email. We will email you again with links as soon as your promotion is live.',
                ],
                'showDetails' => true,
                'ctaUrl' => null,
                'ctaLabel' => null,
            ],
        );
    }
}
