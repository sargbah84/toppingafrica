<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'post_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public static function toggle(int $userId, int $postId): bool
    {
        $existing = static::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        static::create([
            'user_id' => $userId,
            'post_id' => $postId,
            'created_at' => now(),
        ]);

        return true;
    }
}
