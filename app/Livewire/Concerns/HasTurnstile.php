<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\TurnstileService;

/**
 * Pairs with <x-turnstile /> inside a Livewire view: the widget writes its
 * token into $turnstileToken, and every verification attempt resets the
 * widget because Turnstile tokens are single-use.
 */
trait HasTurnstile
{
    public mixed $turnstileToken = '';

    protected function validateTurnstile(string $action): bool
    {
        $service = app(TurnstileService::class);

        if (! $service->isEnabled()) {
            return true;
        }

        $token = is_string($this->turnstileToken) ? $this->turnstileToken : '';
        $result = $service->verify($token, $action, request()->ip());

        $this->turnstileToken = '';
        $this->dispatch('turnstile-reset');

        if (! $result['success']) {
            $this->addError('turnstile', $result['error'] ?? 'Security check failed.');

            return false;
        }

        return true;
    }
}
