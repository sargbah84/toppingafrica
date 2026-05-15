<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Creator extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity;

    protected $fillable = [
        'user_id',
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

    /**
     * The User who has claimed and registered this creator profile.
     * Null when the creator has not yet been claimed, or has been claimed
     * via the email-only flow without account registration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Spatie Activitylog config — track all meaningful field changes so
     * the admin "Pending Edits" review modal can show diffs and roll
     * back individual entries. Skips noisy/internal fields (timestamps,
     * claim tokens, the pending_claim_edit flag itself).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'bio',
                'country',
                'category',
                'contact_email',
                'profile_image_url',
                'follower_count',
                'follower_platform',
                'is_rising',
                'is_trending',
                'is_featured',
                'status',
                'user_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Creator {$eventName}");
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'creator_follows')->withTimestamps();
    }

    /**
     * Polymorphic page-view records for this creator. The same View model
     * is shared with Post; ViewTracker handles the recording.
     */
    public function views(): MorphMany
    {
        return $this->morphMany(View::class, 'viewable');
    }

    /**
     * Whether the given user is allowed to edit this creator's profile via
     * the public/creator-facing edit form. True if the user owns the
     * creator (linked via user_id) or holds the super-admin flag.
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->is_super_admin || $user->is_staff) {
            return true;
        }

        return $this->user_id !== null && $this->user_id === $user->id;
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

    public function getDisplayFollowerCountAttribute(): ?int
    {
        $max = $this->socialLinks->max('follower_count');

        if ($max !== null && $max > 0) {
            return (int) $max;
        }

        return $this->follower_count;
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
        return $query->where(fn ($q) => $q
            ->where('name', 'like', "%{$search}%")
            ->orWhere('category', 'like', "%{$search}%")
            ->orWhere('bio', 'like', "%{$search}%")
        );
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
