<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
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

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Toggle a reaction for a user on a post.
     *
     * - Same type clicked again → remove (returns null)
     * - Different type clicked → switch (returns new type)
     * - No existing reaction → create (returns type)
     */
    public static function toggle(Post $post, int $userId, string $type, string $ip): ?string
    {
        $validTypes = array_keys(config('blog.reactions', []));

        if (! in_array($type, $validTypes, true)) {
            return null;
        }

        $existing = static::where('post_id', $post->id)
            ->where('user_id', $userId)
            ->first();

        $ipHash = hash('sha256', $ip.config('app.key'));

        if ($existing) {
            if ($existing->type === $type) {
                $existing->delete();

                return null;
            }

            $existing->update([
                'type' => $type,
                'ip_hash' => $ipHash,
            ]);

            return $type;
        }

        static::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'type' => $type,
            'ip_hash' => $ipHash,
            'created_at' => now(),
        ]);

        return $type;
    }
}
