<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PollVote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'user_id',
        'option_index',
        'ip_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function castVote(Post $post, int $userId, int $optionIndex, string $ip): bool
    {
        $endsAt = $post->type_data['ends_at'] ?? null;
        if ($endsAt && now()->greaterThan($endsAt)) {
            return false;
        }

        $options = $post->type_data['options'] ?? [];
        if ($optionIndex < 0 || $optionIndex >= count($options)) {
            return false;
        }

        $existing = static::where('post_id', $post->id)
            ->where('user_id', $userId)
            ->exists();

        if ($existing) {
            return false;
        }

        static::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'option_index' => $optionIndex,
            'ip_hash' => hash('sha256', $ip.config('app.key')),
            'created_at' => now(),
        ]);

        return true;
    }
}
