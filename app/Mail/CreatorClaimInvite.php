<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Creator;
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
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Claim your Topping Africa creator profile',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.creator-claim-invite',
            with: [
                'creator' => $this->creator,
                'claimUrl' => url('/creators/claim/' . $this->creator->claim_token),
            ],
        );
    }
}
