<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\RecaptchaService;

trait HasRecaptcha
{
    public string $recaptchaToken = '';

    protected function validateRecaptcha(string $action): bool
    {
        $recaptchaService = app(RecaptchaService::class);

        if (!$recaptchaService->isEnabled()) {
            return true;
        }

        $isAuthenticated = auth()->check();
        $result = $recaptchaService->verify($this->recaptchaToken, $action, $isAuthenticated);

        if (!$result['success']) {
            $this->addError('recaptcha', $result['error'] ?? 'reCAPTCHA verification failed.');
            return false;
        }

        $this->recaptchaToken = '';

        return true;
    }

    public function getRecaptchaSiteKey(): ?string
    {
        return app(RecaptchaService::class)->getSiteKey();
    }

    public function isRecaptchaEnabled(): bool
    {
        return app(RecaptchaService::class)->isEnabled();
    }
}
