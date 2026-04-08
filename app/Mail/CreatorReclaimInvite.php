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

/**
 * One-time outreach email sent to creators who claimed profiles under the
 * old claim flow (before the opt-in account registration was implemented).
 *
 * Sent as a follow-up via the production cleanup script, NOT automatically
 * wired into any controller. Uses its own template and subject line so it's
 * clearly distinct from the regular CreatorClaimInvite that goes out on
 * first-claim requests.
 */
class CreatorReclaimInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Creator $creator,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Manage your {$this->creator->name} profile on Topping Africa",
        );
    }

    public function content(): Content
    {
        // Same personalization strategy as CreatorClaimInvite: look up the
        // recipient by email and use their User.name if they're registered,
        // otherwise fall back to "Hi there" rather than the misleading
        // {creator name} greeting the old template used.
        $recipientName = null;
        if ($this->creator->claimed_by_email) {
            $recipientName = User::where('email', $this->creator->claimed_by_email)->value('name');
        }

        return new Content(
            view: 'emails.creator-reclaim-invite',
            with: [
                'creator' => $this->creator,
                'claimUrl' => url('/creators/claim/' . $this->creator->claim_token),
                'recipientName' => $recipientName,
            ],
        );
    }
}
