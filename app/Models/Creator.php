<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Creator extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'bio',
        'country',
        'category',
        'contact_email',
        'status',
        'profile_image_url',
        'profile_image_attribution',
        'profile_image_license',
        'claim_token',
        'claim_token_expires_at',
        'claimed_at',
        'claimed_by_email',
        'pending_claim_edit',
        'is_rising',
        'is_trending',
        'is_featured',
        'follower_count',
        'follower_platform',
    ];

    protected $casts = [
        'claim_token_expires_at' => 'datetime',
        'claimed_at' => 'datetime',
        'pending_claim_edit' => 'boolean',
        'is_rising' => 'boolean',
        'is_trending' => 'boolean',
        'is_featured' => 'boolean',
        'follower_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Creator $creator): void {
            if (empty($creator->slug)) {
                $slug = Str::slug($creator->name);
                $original = $slug;
                $count = 1;

                while (static::where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }

                $creator->slug = $slug;
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_image')->singleFile();
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(CreatorSocialLink::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'creator_follows')->withTimestamps();
    }

    public function isFollowedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->followers()->whereKey($user->id)->exists();
    }

    public function getFollowersCountAttribute(): int
    {
        return $this->followers()->count();
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMediaUrl('profile_image');

        if ($media) {
            return $media;
        }

        return $this->attributes['profile_image_url'] ?? null;
    }

    public function getPublicUrlAttribute(): string
    {
        return '/creators/' . $this->slug;
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);
        $initials = strtoupper(substr($parts[0], 0, 1));

        if (count($parts) > 1) {
            $initials .= strtoupper(substr(end($parts), 0, 1));
        }

        return $initials;
    }

    public function getAvatarColorAttribute(): string
    {
        $colors = [
            '#6366F1', '#8B5CF6', '#EC4899', '#EF4444', '#F97316',
            '#EAB308', '#22C55E', '#14B8A6', '#06B6D4', '#3B82F6',
        ];

        return $colors[crc32($this->name) % count($colors)];
    }
}
