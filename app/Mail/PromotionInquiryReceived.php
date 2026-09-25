<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PromotionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the buyer when they submit a request while online payment is off. */
class PromotionInquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PromotionRequest $promotion) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "We've received your promotion request ({$this->promotion->reference})");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.promotion',
            with: [
                'promotion' => $this->promotion,
                'greeting' => "Hi {$this->promotion->name},",
                'paragraphs' => [
                    "Thank you for choosing Topping Africa! We've received your request for <strong>{$this->promotion->package_name}</strong>.",
                    'There is nothing to pay yet. Our team will review your submission and, within 2 business days, send you a separate email with your <strong>invoice and a secure payment link</strong>. Please keep an eye on your inbox (and your spam folder, just in case).',
                    'Once payment is received we start production and will email you every link as soon as your promotion is live.',
                ],
                'showDetails' => true,
                'ctaUrl' => null,
                'ctaLabel' => null,
            ],
        );
    }
}
