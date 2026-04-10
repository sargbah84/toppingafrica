<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CreatorClaimInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Creator $creator,
        public bool $isReferral = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isReferral
            ? "You've been featured on Topping Africa — claim your profile"
            : 'Claim your Topping Africa Creator Profile';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        // Personalize the greeting only when we can — i.e. when the email
        // address on the claim already belongs to a registered user. For
        // first-time anonymous claimants we don't know the human's name,
        // so the view falls back to "Hi there," rather than the misleading
        // "Hi {creator name}" the old template used.
        $recipientName = null;
        if ($this->creator->claimed_by_email) {
            $recipientName = User::where('email', $this->creator->claimed_by_email)->value('name');
        }

        return new Content(
            view: 'emails.creator-claim-invite',
            with: [
                'creator' => $this->creator,
                'claimUrl' => url('/creators/claim/' . $this->creator->claim_token),
                'recipientName' => $recipientName,
                'isReferral' => $this->isReferral,
            ],
        );
    }
}
