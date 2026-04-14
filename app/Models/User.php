<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'username',
        'avatar',
        'bio',
        'website',
        'social_links',
        'is_staff',
        'is_super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'social_links' => 'array',
            'is_staff' => 'boolean',
            'is_super_admin' => 'boolean',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function postViews(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    /**
     * Creator profiles this user has claimed and registered for.
     * One user may own multiple creator pages.
     */
    public function claimedCreators(): HasMany
    {
        return $this->hasMany(Creator::class);
    }

    public function getAvatarUrlAttribute(): string
    {
        return $this->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=000000&color=ffffff';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_staff', 'is_super_admin'])
            ->logOnlyDirty()
            // Defensive — suppresses log rows where no tracked field changed.
            // Also has the side effect of suppressing "User deleted" events
            // (they have empty changes by nature); we accept that trade-off
            // because the alternative is noise-heavy logs. If auditable user
            // deletion is needed later, add a dedicated soft-delete observer.
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "User {$eventName}");
    }
}
