<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TurnstileService
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly ?string $siteKey,
        private readonly ?string $secretKey,
        private readonly bool $enabled,
    ) {}

    /**
     * Verify a Turnstile token server-side. Tokens are single-use and expire
     * after five minutes, so the widget must be reset after every attempt.
     *
     * @return array{success: bool, error: string|null}
     */
    public function verify(string $token, string $action, ?string $ip = null): array
    {
        if (! $this->isEnabled()) {
            return ['success' => true, 'error' => null];
        }

        if ($token === '') {
            return ['success' => false, 'error' => 'Please complete the security check and try again.'];
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, array_filter([
                'secret' => $this->secretKey,
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (ConnectionException $e) {
            // Fail open only when Cloudflare itself is unreachable — a bot
            // cannot trigger this path, unlike a malformed token.
            Log::error('Turnstile unreachable, allowing request', ['action' => $action, 'error' => $e->getMessage()]);

            return ['success' => true, 'error' => null];
        }

        if ($response->serverError()) {
            Log::error('Turnstile server error, allowing request', ['action' => $action, 'status' => $response->status()]);

            return ['success' => true, 'error' => null];
        }

        $body = $response->json() ?? [];

        if (! ($body['success'] ?? false)) {
            return ['success' => false, 'error' => 'Security check failed. Please try again.'];
        }

        // A token minted for one form must not be replayable on another.
        if (($body['action'] ?? $action) !== $action) {
            return ['success' => false, 'error' => 'Security check failed. Please refresh the page and try again.'];
        }

        return ['success' => true, 'error' => null];
    }

    public function isEnabled(): bool
    {
        return $this->enabled && ! empty($this->siteKey) && ! empty($this->secretKey);
    }

    public function getSiteKey(): ?string
    {
        return $this->isEnabled() ? $this->siteKey : null;
    }
}
