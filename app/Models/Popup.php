<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class Popup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'content',
        'is_active',
        'trigger_type',
        'trigger_value',
        'frequency_days',
        'display_on',
        'show_on_pages',
        'exclude_pages',
        'device',
        'visibility',
        'priority',
        'position',
        'width_px',
        'max_width_vw',
        'animation',
        'show_overlay',
        'close_on_overlay_click',
        'show_close_button',
        'shadow',
        'border_radius',
        'border_width',
        'border_color',
        'padding',
        'bg_color',
        'overlay_color',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_overlay' => 'boolean',
            'close_on_overlay_click' => 'boolean',
            'show_close_button' => 'boolean',
            'show_on_pages' => 'array',
            'exclude_pages' => 'array',
            'frequency_days' => 'integer',
            'priority' => 'integer',
            'width_px' => 'integer',
            'max_width_vw' => 'integer',
            'border_radius' => 'integer',
            'border_width' => 'integer',
            'padding' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Popup $popup) {
            if (empty($popup->slug)) {
                $popup->slug = Str::slug($popup->name) . '-' . Str::random(6);
            }
        });

        $clearCache = function () {
            Cache::forget('popups:frontend:desktop');
            Cache::forget('popups:frontend:mobile');
            Cache::forget('popups:both:desktop');
            Cache::forget('popups:both:mobile');
            Cache::forget('popups:backend:desktop');
            Cache::forget('popups:backend:mobile');
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyScheduled(Builder $query): Builder
    {
        $now = now();

        return $query->where(function (Builder $q) use ($now) {
            $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
        })->where(function (Builder $q) use ($now) {
            $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
        });
    }

    public function scopeForDevice(Builder $query, string $device): Builder
    {
        return $query->where(function (Builder $q) use ($device) {
            $q->where('device', 'all')->orWhere('device', $device);
        });
    }

    public function scopeForVisibility(Builder $query, string $context): Builder
    {
        return $query->where(function (Builder $q) use ($context) {
            $q->where('visibility', 'both')->orWhere('visibility', $context);
        });
    }

    public function getScheduleStatus(): string
    {
        if (! $this->is_active) {
            return 'Inactive';
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'Scheduled';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'Expired';
        }

        return 'Active';
    }

    public function getCookieName(): string
    {
        return 'popup_' . $this->slug . '_dismissed';
    }

    public function matchesPage(string $path): bool
    {
        // Check exclude pages first
        if (! empty($this->exclude_pages)) {
            foreach ($this->exclude_pages as $pattern) {
                if ($this->pathMatchesPattern($path, $pattern)) {
                    return false;
                }
            }
        }

        // If display on all pages, it matches
        if ($this->display_on === 'all_pages') {
            return true;
        }

        // Check specific pages
        if (! empty($this->show_on_pages)) {
            foreach ($this->show_on_pages as $pattern) {
                if ($this->pathMatchesPattern($path, $pattern)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pathMatchesPattern(string $path, string $pattern): bool
    {
        $pattern = trim($pattern, '/');
        $path = trim($path, '/');

        if ($pattern === $path) {
            return true;
        }

        // Support wildcard patterns like "blog/*"
        $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));

        return (bool) preg_match('/^' . $regex . '$/', $path);
    }
}
