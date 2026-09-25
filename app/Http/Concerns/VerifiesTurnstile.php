<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use App\Services\TurnstileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Controller-side counterpart to App\Livewire\Concerns\HasTurnstile.
 *
 * <x-turnstile /> inside a plain HTML form submits its token in the
 * `cf-turnstile-response` field; this verifies it.
 */
trait VerifiesTurnstile
{
    /**
     * Throws a ValidationException bound to the `turnstile` field on failure
     * so the form can display it via @error('turnstile').
     */
    protected function verifyTurnstile(Request $request, string $action): void
    {
        $result = app(TurnstileService::class)->verify(
            (string) $request->input('cf-turnstile-response', ''),
            $action,
            $request->ip(),
        );

        if (! $result['success']) {
            throw ValidationException::withMessages([
                'turnstile' => [$result['error'] ?? 'Security check failed. Please try again.'],
            ]);
        }
    }
}
