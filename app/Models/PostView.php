<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'post_id',
        'user_id',
        'ip_hash',
        'user_agent',
        'referer',
        'device_type',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function recordView(Post $post, string $ip, ?int $userId = null, ?string $userAgent = null, ?string $referer = null): void
    {
        // Skip excluded IPs and user agents
        $rules = json_decode(Setting::get('exclusion_rules', '[]'), true) ?: [];
        foreach ($rules as $rule) {
            if (!($rule['active'] ?? true)) {
                continue;
            }
            if ($rule['type'] === 'ip' && $rule['value'] === $ip) {
                return;
            }
            if ($rule['type'] === 'user_agent' && $userAgent && stripos($userAgent, $rule['value']) !== false) {
                return;
            }
        }

        $ipHash = hash('sha256', $ip . config('app.key'));

        $recentView = static::where('post_id', $post->id)
            ->where('ip_hash', $ipHash)
            ->where('viewed_at', '>', now()->subDay())
            ->exists();

        if (!$recentView) {
            static::create([
                'post_id' => $post->id,
                'user_id' => $userId,
                'ip_hash' => $ipHash,
                'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
                'referer' => $referer ? substr($referer, 0, 255) : null,
                'device_type' => static::detectDeviceType($userAgent),
                'viewed_at' => now(),
            ]);

            $post->incrementViewsCount();
        }
    }

    public static function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'desktop';
        }

        $ua = strtolower($userAgent);

        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    public function scopeForPost($query, Post $post)
    {
        return $query->where('post_id', $post->id);
    }

    public function scopeInPeriod($query, \DateTimeInterface $from, ?\DateTimeInterface $to = null)
    {
        $query->where('viewed_at', '>=', $from);

        if ($to) {
            $query->where('viewed_at', '<=', $to);
        }

        return $query;
    }
}
