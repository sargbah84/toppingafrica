<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic page-view tracking. Replaces the previous post-only PostView
 * model. Any model can be tracked by setting it as the morphTo subject of
 * recordView(); see App\Services\ViewTracker for the integration entry point.
 *
 * The IP exclusion rules in recordView() are sourced from the global
 * 'exclusion_rules' setting and apply to ALL viewable types — adding a
 * staff IP filters it from posts AND creators automatically, no per-type
 * configuration needed.
 *
 * Backward compatibility: App\Models\PostView is a thin subclass that
 * forwards to View for the (Post $post, ...) signature, so existing
 * call sites in StatsOverview, ReadingHistory, etc. continue to work
 * during the transition.
 */
class View extends Model
{
    protected $table = 'views';

    public $timestamps = false;

    protected $fillable = [
        'viewable_type',
        'viewable_id',
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

    public function viewable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a view for any model. Deduplicates per (subject, ip_hash) within
     * a 24-hour window so a single visitor refreshing the page doesn't inflate
     * the count. Skips views from IPs/user-agents in the exclusion_rules
     * setting (typically used to filter staff and bots).
     */
    /**
     * Known bot user-agent patterns. Checked before the configurable
     * exclusion_rules so crawlers are filtered without admin setup.
     */
    protected static array $botPatterns = [
        // Search engines
        'Googlebot', 'GoogleOther', 'Bingbot', 'bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot',
        'Sogou', 'Exabot', 'ia_archiver',
        // SEO / analytics crawlers
        'AhrefsBot', 'SemrushBot', 'DotBot', 'MJ12bot', 'PetalBot', 'Bytespider',
        'serpstatbot', 'MegaIndex', 'BLEXBot', 'DataForSeoBot',
        // Social media previews
        'facebookexternalhit', 'Twitterbot', 'LinkedInBot', 'WhatsApp', 'TelegramBot',
        'Slackbot', 'Discordbot',
        // Generic patterns
        'bot', 'crawl', 'spider', 'scraper', 'headless', 'phantom', 'puppet',
        // Monitoring / uptime
        'UptimeRobot', 'pingdom', 'StatusCake', 'NewRelicPinger',
        // AI / LLM
        'GPTBot', 'ChatGPT', 'Claude-Web', 'anthropic-ai', 'CCBot', 'PerplexityBot',
    ];

    public static function recordView(Model $subject, string $ip, ?int $userId = null, ?string $userAgent = null, ?string $referer = null): void
    {
        // Skip known bots by user-agent
        if ($userAgent) {
            $ua = strtolower($userAgent);
            foreach (static::$botPatterns as $pattern) {
                if (str_contains($ua, strtolower($pattern))) {
                    return;
                }
            }
        }

        // Skip excluded IPs and user agents
        $rules = json_decode(Setting::get('exclusion_rules', '[]'), true) ?: [];
        foreach ($rules as $rule) {
            if (! ($rule['active'] ?? true)) {
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
        $viewableType = $subject->getMorphClass();
        $viewableId = $subject->getKey();

        $recentView = static::query()
            ->where('viewable_type', $viewableType)
            ->where('viewable_id', $viewableId)
            ->where('ip_hash', $ipHash)
            ->where('viewed_at', '>', now()->subDay())
            ->exists();

        if (! $recentView) {
            static::create([
                'viewable_type' => $viewableType,
                'viewable_id' => $viewableId,
                'user_id' => $userId,
                'ip_hash' => $ipHash,
                'user_agent' => $userAgent ? substr($userAgent, 0, 255) : null,
                'referer' => $referer ? substr($referer, 0, 255) : null,
                'device_type' => static::detectDeviceType($userAgent),
                'viewed_at' => now(),
            ]);
        }
    }

    public static function detectDeviceType(?string $userAgent): string
    {
        if (! $userAgent) {
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

    /**
     * Scope: limit to views of a specific subject (Post, Creator, etc.).
     */
    public function scopeForViewable($query, Model $subject)
    {
        return $query
            ->where('viewable_type', $subject->getMorphClass())
            ->where('viewable_id', $subject->getKey());
    }

    /**
     * Scope: limit to views of a specific Post. Kept as a thin wrapper
     * around scopeForViewable for backward compatibility with existing
     * call sites in StatsOverview/ViewTracker.
     */
    public function scopeForPost($query, Post $post)
    {
        return $query->forViewable($post);
    }

    /**
     * Scope: limit to views of any model class (e.g. only posts, only creators).
     */
    public function scopeOfType($query, string $modelClass)
    {
        return $query->where('viewable_type', $modelClass);
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
