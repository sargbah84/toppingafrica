<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    protected $fillable = [
        'user_id',
        'post_id',
        'provider',
        'model',
        'feature',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'estimated_cost',
        'duration_ms',
        'is_successful',
        'error_message',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
        'duration_ms' => 'integer',
        'is_successful' => 'boolean',
    ];

    // Cost per 1M tokens (input/output) — update as pricing changes
    public const PRICING = [
        'openai' => [
            'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
            'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
        ],
        'perplexity' => [
            'sonar-pro' => ['input' => 3.00, 'output' => 15.00],
            'sonar' => ['input' => 1.00, 'output' => 1.00],
        ],
        'anthropic' => [
            'claude-sonnet-4-6' => ['input' => 3.00, 'output' => 15.00],
            'claude-haiku-4-5' => ['input' => 0.80, 'output' => 4.00],
        ],
    ];

    public static function calculateCost(string $provider, string $model, int $inputTokens, int $outputTokens): float
    {
        $pricing = self::PRICING[$provider][$model] ?? null;

        if (!$pricing) {
            return 0.0;
        }

        return ($inputTokens / 1_000_000 * $pricing['input'])
             + ($outputTokens / 1_000_000 * $pricing['output']);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
