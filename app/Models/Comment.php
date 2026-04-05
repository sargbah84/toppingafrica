<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Comment extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id',
        'guest_name',
        'guest_email',
        'body',
        'status',
        'ip_address',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function getAuthorNameAttribute(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Anonymous';
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'post_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Comment {$eventName}");
    }
}
