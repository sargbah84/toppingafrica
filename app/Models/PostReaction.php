<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'user_id',
        'type',
        'ip_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const TYPES = ['like', 'love', 'wow', 'sad', 'angry'];

    public const EMOJIS = [
        'like' => "\u{1F44D}",
        'love' => "\u{2764}\u{FE0F}",
        'wow' => "\u{1F62E}",
        'sad' => "\u{1F622}",
        'angry' => "\u{1F621}",
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function toggle(Post $post, int $userId, string $type, string $ip): array
    {
        if (! in_array($type, self::TYPES)) {
            return ['action' => 'invalid'];
        }

        $existing = static::where('post_id', $post->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            if ($existing->type === $type) {
                $existing->delete();

                return ['action' => 'removed'];
            }

            $existing->update(['type' => $type]);

            return ['action' => 'changed'];
        }

        static::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'type' => $type,
            'ip_hash' => hash('sha256', $ip.config('app.key')),
            'created_at' => now(),
        ]);

        return ['action' => 'added'];
    }

    public static function countsForPost(int $postId): array
    {
        $counts = static::where('post_id', $postId)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $result = [];
        foreach (self::TYPES as $type) {
            $result[$type] = $counts[$type] ?? 0;
        }

        return $result;
    }
}
