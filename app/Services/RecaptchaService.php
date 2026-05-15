<?php

declare(strict_types=1);

namespace App\Services;

use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Illuminate\Support\Facades\Log;

final class RecaptchaService
{
    private ?RecaptchaEnterpriseServiceClient $client = null;

    public function __construct(
        private readonly string $siteKey,
        private readonly string $projectId,
        private readonly float $scoreThreshold,
        private readonly ?string $credentialsPath,
        private readonly bool $enabled,
    ) {}

    /**
     * Verify a reCAPTCHA token.
     *
     * @param  string  $token  The reCAPTCHA token from the frontend
     * @param  string  $action  The expected action name (e.g., 'login', 'register', 'comment')
     * @param  bool  $isAuthenticated  Whether the user is authenticated (allows fallback tokens)
     * @return array{success: bool, score: float|null, error: string|null}
     */
    public function verify(string $token, string $action, bool $isAuthenticated = false): array
    {
        if (! $this->enabled) {
            return [
                'success' => true,
                'score' => 1.0,
                'error' => null,
            ];
        }

        if (empty($this->siteKey) || empty($this->projectId)) {
            Log::warning('reCAPTCHA: Missing configuration, skipping verification');

            return [
                'success' => true,
                'score' => 1.0,
                'error' => null,
            ];
        }

        if (empty($token)) {
            return [
                'success' => false,
                'score' => null,
                'error' => 'reCAPTCHA verification failed. Please try again.',
            ];
        }

        // Handle fallback / sanitized-invalid tokens (script couldn't load, or
        // server received a non-string value from a tampered request).
        if (in_array($token, ['RECAPTCHA_FAILED', 'RECAPTCHA_NOT_LOADED', 'RECAPTCHA_INVALID'], true)) {
            if ($isAuthenticated) {
                Log::info('reCAPTCHA: Client-side failure for authenticated user, allowing request', [
                    'action' => $action,
                    'token' => $token,
                ]);

                return [
                    'success' => true,
                    'score' => null,
                    'error' => null,
                ];
            }

            Log::warning('reCAPTCHA: Client-side failure for guest, blocking request', [
                'action' => $action,
                'token' => $token,
            ]);

            return [
                'success' => false,
                'score' => null,
                'error' => 'reCAPTCHA failed to load. Please refresh the page and try again. If the problem persists, try disabling your ad blocker.',
            ];
        }

        try {
            $client = $this->getClient();
            $assessment = $this->createAssessment($client, $token, $action);
            $score = $assessment->getRiskAnalysis()->getScore();

            Log::info('reCAPTCHA assessment', [
                'action' => $action,
                'score' => $score,
                'threshold' => $this->scoreThreshold,
                'token_valid' => $assessment->getTokenProperties()->getValid(),
            ]);

            if (! $assessment->getTokenProperties()->getValid()) {
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'reCAPTCHA verification failed. Please refresh the page and try again.',
                ];
            }

            $expectedAction = $assessment->getTokenProperties()->getAction();
            if ($expectedAction !== $action) {
                Log::warning('reCAPTCHA: Action mismatch', [
                    'expected' => $action,
                    'received' => $expectedAction,
                ]);

                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'reCAPTCHA verification failed. Please try again.',
                ];
            }

            if ($score < $this->scoreThreshold) {
                return [
                    'success' => false,
                    'score' => $score,
                    'error' => 'We detected suspicious activity. Please try again or contact support if this persists.',
                ];
            }

            return [
                'success' => true,
                'score' => $score,
                'error' => null,
            ];
        } catch (\Exception $e) {
            Log::error('reCAPTCHA verification error', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            // Fail open in case of API errors to not block legitimate users
            return [
                'success' => true,
                'score' => null,
                'error' => null,
            ];
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled && ! empty($this->siteKey) && ! empty($this->projectId);
    }

    public function getSiteKey(): ?string
    {
        return $this->enabled ? $this->siteKey : null;
    }

    private function getClient(): RecaptchaEnterpriseServiceClient
    {
        if ($this->client === null) {
            $options = [];

            if ($this->credentialsPath && file_exists($this->credentialsPath)) {
                $options['credentials'] = $this->credentialsPath;
            }

            $this->client = new RecaptchaEnterpriseServiceClient($options);
        }

        return $this->client;
    }

    private function createAssessment(
        RecaptchaEnterpriseServiceClient $client,
        string $token,
        string $action,
    ): Assessment {
        $event = (new Event)
            ->setSiteKey($this->siteKey)
            ->setToken($token)
            ->setExpectedAction($action);

        $assessment = (new Assessment)
            ->setEvent($event);

        $projectPath = sprintf('projects/%s', $this->projectId);

        $request = (new CreateAssessmentRequest)
            ->setParent($projectPath)
            ->setAssessment($assessment);

        return $client->createAssessment($request);
    }
}
