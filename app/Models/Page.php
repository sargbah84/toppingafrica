<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'template',
        'linked_category_id',
        'linked_tag_id',
        'meta_title',
        'meta_description',
        'status',
        'order',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Page $page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function linkedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'linked_category_id');
    }

    public function linkedTag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'linked_tag_id');
    }

    public function isBlogTemplate(): bool
    {
        return $this->template === 'blog';
    }
}
