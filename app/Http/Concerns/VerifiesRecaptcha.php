<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use App\Services\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controller-side counterpart to App\Livewire\Concerns\HasRecaptcha.
 *
 * Use in controllers that handle plain HTML form posts to verify a
 * reCAPTCHA Enterprise token submitted in the `recaptcha_token` field.
 */
trait VerifiesRecaptcha
{
    /**
     * Verify the reCAPTCHA token on the incoming request.
     *
     * Throws a ValidationException bound to the `recaptcha` field on failure
     * so the calling form can display the error via @error('recaptcha').
     * No-ops if the service is disabled.
     */
    protected function verifyRecaptcha(Request $request, string $action): void
    {
        $service = app(RecaptchaService::class);

        if (! $service->isEnabled()) {
            return;
        }

        $token = (string) $request->input('recaptcha_token', '');
        $result = $service->verify($token, $action, auth()->check());

        if (! $result['success']) {
            throw ValidationException::withMessages([
                'recaptcha' => [$result['error'] ?? 'reCAPTCHA verification failed. Please try again.'],
            ]);
        }
    }
}
