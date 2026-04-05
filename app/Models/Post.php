<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'post_type',
        'type_data',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'og_meta',
        'social_sharing',
        'status',
        'published_at',
        'scheduled_at',
        'views_count',
        'reading_time',
        'is_featured',
        'ai_provider',
        'generation_params',
    ];

    protected $casts = [
        'type_data' => 'array',
        'og_meta' => 'array',
        'social_sharing' => 'array',
        'generation_params' => 'array',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'views_count' => 'integer',
        'reading_time' => 'integer',
        'is_featured' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Post $post) {
            if (empty($post->slug)) {
                $post->slug = static::generateUniqueSlug($post->title);
            }
        });

        static::updating(function (Post $post) {
            if ($post->isDirty('title') && !$post->isDirty('slug')) {
                $post->slug = static::generateUniqueSlug($post->title, $post->id);
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('content_images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)->height(150)->sharpen(10)->nonQueued();

        $this->addMediaConversion('medium')
            ->width(600)->height(400)->sharpen(10)->nonQueued();

        $this->addMediaConversion('large')
            ->width(1200)->height(800)->sharpen(10)->nonQueued();

        $this->addMediaConversion('og_image')
            ->width(1200)->height(630)->sharpen(10)->nonQueued();
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('featured_image');
        if ($media) {
            return $media->getUrl();
        }

        return $this->buildS3Url($this->featured_image);
    }

    public function getFeaturedImageThumbnailAttribute(): ?string
    {
        $media = $this->getFirstMedia('featured_image');
        if ($media) {
            return $media->getUrl('thumbnail');
        }

        return $this->buildS3Url($this->featured_image);
    }

    private function buildS3Url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Already a full URL
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $bucket = config('filesystems.disks.s3.bucket', env('AWS_BUCKET'));
        return "https://{$bucket}.s3.amazonaws.com/{$path}";
    }

    protected static function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $count = 1;

        while (static::withTrashed()->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }

    // Relationships

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function seoAnalyses(): HasMany
    {
        return $this->hasMany(SeoAnalysis::class);
    }

    public function latestSeoAnalysis(): HasOne
    {
        return $this->hasOne(SeoAnalysis::class)->latestOfMany();
    }

    // Status helpers

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at?->isPast();
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at?->isFuture();
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'scheduled_at' => null,
        ]);
    }

    public function unpublish(): void
    {
        $this->update(['status' => 'draft', 'published_at' => null]);
    }

    public function schedule(\DateTimeInterface $at): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $at,
            'published_at' => null,
        ]);
    }

    public function incrementViewsCount(): void
    {
        $this->increment('views_count');
    }

    public function getFormattedReadingTimeAttribute(): string
    {
        return ($this->reading_time ?? 1) . ' min read';
    }

    // Scopes

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')->where('scheduled_at', '>', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('post_type', $type);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('views_count');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(fn ($q) => $q
            ->where('title', 'like', "%{$term}%")
            ->orWhere('excerpt', 'like', "%{$term}%")
            ->orWhere('content', 'like', "%{$term}%")
        );
    }

    public function scopeReadyToPublish($query)
    {
        return $query->where('status', 'scheduled')->where('scheduled_at', '<=', now());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'author_id', 'post_type'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Post {$eventName}: {$this->title}");
    }
}
