<?php

declare(strict_types=1);

namespace App\Mail;

use App\Livewire\Admin\ManagePromotions;
use App\Models\PromotionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the buyer when an admin marks the promotion live. */
class PromotionLive extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PromotionRequest $promotion) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your promotion is live on Topping Africa 🎉');
    }

    public function content(): Content
    {
        $links = collect([
            'Article' => $this->promotion->post?->status === 'published' ? route('blog.show', $this->promotion->post->slug) : null,
            ...collect($this->promotion->deliverables ?? [])
                ->mapWithKeys(fn ($url, $key) => [ManagePromotions::DELIVERABLES[$key] ?? $key => $url])
                ->all(),
        ])->filter();

        $list = $links->map(fn ($url, $label) => "<strong>{$label}:</strong> <a href=\"".e($url).'">'.e($url).'</a>')->implode('<br>');

        return new Content(
            view: 'emails.promotion',
            with: [
                'promotion' => $this->promotion,
                'greeting' => "Hi {$this->promotion->name},",
                'paragraphs' => array_filter([
                    "Great news — your <strong>{$this->promotion->package_name}</strong> promotion for ".e($this->promotion->subject_name).' is now live.',
                    $list ?: null,
                    'Share these links with your fans — every share helps your story travel further. Thank you for promoting with Topping Africa!',
                ]),
                'showDetails' => false,
                'ctaUrl' => $links->first(),
                'ctaLabel' => 'View your feature',
            ],
        );
    }
}
