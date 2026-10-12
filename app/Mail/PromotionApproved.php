<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PromotionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the buyer when a sponsored article passes review and can be paid online. */
class PromotionApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PromotionRequest $promotion) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Your article is approved — pay to publish ({$this->promotion->reference})");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.promotion',
            with: [
                'promotion' => $this->promotion,
                'greeting' => "Hi {$this->promotion->name},",
                'paragraphs' => [
                    'Good news — our editors have approved <strong>'.e($this->promotion->title).'</strong> for publication on Topping Africa.',
                    "To go ahead, pay <strong>{$this->promotion->formatted_amount}</strong> securely by card using the button below. We publish your article once payment is received and email you the link.",
                ],
                'showDetails' => true,
                'ctaUrl' => $this->promotion->checkoutUrl(),
                'ctaLabel' => "Pay {$this->promotion->formatted_amount}",
            ],
        );
    }
}
