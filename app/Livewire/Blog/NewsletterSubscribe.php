<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Livewire\Concerns\HasRecaptcha;
use App\Models\NewsletterSubscriber;
use Illuminate\View\View;
use Livewire\Component;

class NewsletterSubscribe extends Component
{
    use HasRecaptcha;

    public string $email = '';
    public string $name = '';
    public string $successMessage = '';
    public string $errorMessage = '';

    public function subscribe(): void
    {
        $this->successMessage = '';
        $this->errorMessage = '';

        if (! $this->validateRecaptcha('newsletter_subscribe')) {
            return;
        }

        $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $existing = NewsletterSubscriber::where('email', $this->email)->first();

        if ($existing) {
            if ($existing->status === 'subscribed') {
                $this->errorMessage = 'This email is already subscribed.';
                return;
            }

            // Re-subscribe
            $existing->update([
                'status' => 'subscribed',
                'name' => $this->name ?: $existing->name,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        } else {
            NewsletterSubscriber::create([
                'email' => $this->email,
                'name' => $this->name ?: null,
                'status' => 'subscribed',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'subscribed_at' => now(),
            ]);
        }

        $this->email = '';
        $this->name = '';
        $this->successMessage = 'Thank you for subscribing!';
    }

    public function render(): View
    {
        return view('livewire.blog.newsletter-subscribe');
    }
}
