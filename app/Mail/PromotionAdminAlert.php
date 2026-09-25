<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PromotionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Internal alert for a new inquiry (invoice needed) or a paid online order. */
class PromotionAdminAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PromotionRequest $promotion) {}

    public function envelope(): Envelope
    {
        $kind = $this->promotion->isPaid() ? 'New paid promotion' : 'New promotion inquiry';

        return new Envelope(
            subject: "{$kind}: {$this->promotion->package_name} — {$this->promotion->subject_name} ({$this->promotion->formatted_amount})",
            replyTo: [$this->promotion->email],
        );
    }

    public function content(): Content
    {
        $who = e($this->promotion->name).' ('.e($this->promotion->email).')';

        return new Content(
            view: 'emails.promotion',
            with: [
                'promotion' => $this->promotion,
                'greeting' => $this->promotion->isPaid() ? 'New paid promotion' : 'New promotion inquiry',
                'paragraphs' => [
                    $this->promotion->isPaid()
                        ? "{$who} paid {$this->promotion->formatted_amount}."
                        : "{$who} submitted a request ({$this->promotion->formatted_amount}). Review it, then email them an invoice / payment link and set the status to <strong>Invoice sent</strong>.",
                    nl2br(e($this->promotion->description)),
                ],
                'showDetails' => true,
                'ctaUrl' => route('admin.promotions.index', ['open' => $this->promotion->reference]),
                'ctaLabel' => 'Open in admin',
            ],
        );
    }
}
