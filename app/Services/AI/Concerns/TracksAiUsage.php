<?php

declare(strict_types=1);

namespace App\Services\AI\Concerns;

use App\Models\AiUsageLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

trait TracksAiUsage
{
    protected function trackUsage(
        Response $response,
        string $providerSlug,
        string $model,
        string $feature,
        bool $isSuccessful = true,
        ?int $durationMs = null,
        ?string $errorMessage = null,
    ): void {
        try {
            [$inputTokens, $outputTokens] = $this->extractTokenCounts($response, $providerSlug);
            $totalTokens = $inputTokens + $outputTokens;
            $estimatedCost = AiUsageLog::calculateCost($providerSlug, $model, $inputTokens, $outputTokens);

            AiUsageLog::create([
                'user_id' => auth()->id(),
                'provider' => $providerSlug,
                'model' => $model,
                'feature' => $feature,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost' => $estimatedCost,
                'duration_ms' => $durationMs,
                'is_successful' => $isSuccessful,
                'error_message' => $errorMessage ? mb_substr($errorMessage, 0, 500) : null,
            ]);

            Log::info('AI usage tracked', [
                'provider' => $providerSlug,
                'model' => $model,
                'feature' => $feature,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'total_tokens' => $totalTokens,
                'estimated_cost' => $estimatedCost,
                'duration_ms' => $durationMs,
                'is_successful' => $isSuccessful,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI usage tracking failed', [
                'provider' => $providerSlug,
                'feature' => $feature,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{0: int, 1: int} [inputTokens, outputTokens]
     */
    private function extractTokenCounts(Response $response, string $providerSlug): array
    {
        $data = $response->json() ?? [];

        return match ($providerSlug) {
            'anthropic' => [
                (int) ($data['usage']['input_tokens'] ?? 0),
                (int) ($data['usage']['output_tokens'] ?? 0),
            ],
            'openai', 'perplexity' => [
                (int) ($data['usage']['prompt_tokens'] ?? 0),
                (int) ($data['usage']['completion_tokens'] ?? 0),
            ],
            default => [0, 0],
        };
    }
}
