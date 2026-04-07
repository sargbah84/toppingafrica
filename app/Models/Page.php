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

    /**
     * Available page templates.
     *
     * `dynamic`   – the template renders generated content (listings, feeds…)
     *               on top of the page's intro copy. The catch-all `/{slug}`
     *               route dispatches to a custom renderer when one of these
     *               templates is detected.
     * `singleton` – only one published page may use this template at a time.
     *               Used for the canonical built-in sections (trending,
     *               creators) so internal links can resolve unambiguously
     *               via Page::byTemplate().
     * `protected` – the page row cannot be deleted from the admin panel
     *               because internal code links to it via the template helper.
     */
    public const TEMPLATES = [
        'default' => [
            'label' => 'Default',
            'description' => 'Standard content page.',
            'dynamic' => false,
            'singleton' => false,
            'protected' => false,
        ],
        'full-width' => [
            'label' => 'Full Width',
            'description' => 'Edge-to-edge content layout.',
            'dynamic' => false,
            'singleton' => false,
            'protected' => false,
        ],
        'sidebar' => [
            'label' => 'With Sidebar',
            'description' => 'Content with a sidebar column.',
            'dynamic' => false,
            'singleton' => false,
            'protected' => false,
        ],
        'blog' => [
            'label' => 'Blog Listing',
            'description' => 'Renders blog post cards filtered by a linked category or tag.',
            'dynamic' => true,
            'singleton' => false,
            'protected' => false,
        ],
        'trending' => [
            'label' => 'Trending',
            'description' => 'Renders the dynamic trending dashboard. Optional intro copy is shown above.',
            'dynamic' => true,
            'singleton' => true,
            'protected' => true,
        ],
        'creators' => [
            'label' => 'Creators Directory',
            'description' => 'Renders the public creators directory. Optional intro copy is shown above.',
            'dynamic' => true,
            'singleton' => true,
            'protected' => true,
        ],
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

    public function isTrendingTemplate(): bool
    {
        return $this->template === 'trending';
    }

    public function isCreatorsTemplate(): bool
    {
        return $this->template === 'creators';
    }

    public function isDynamicTemplate(): bool
    {
        return (bool) (self::TEMPLATES[$this->template]['dynamic'] ?? false);
    }

    public function isProtectedTemplate(): bool
    {
        return (bool) (self::TEMPLATES[$this->template]['protected'] ?? false);
    }

    public function isSingletonTemplate(): bool
    {
        return (bool) (self::TEMPLATES[$this->template]['singleton'] ?? false);
    }

    /**
     * Find the canonical published page for a given template key.
     * Used by template_url() and the route-name redirects so internal links
     * automatically follow whatever slug the admin has chosen.
     */
    public static function byTemplate(string $template): ?self
    {
        return static::query()
            ->where('template', $template)
            ->where('status', 'published')
            ->orderBy('id')
            ->first();
    }
}
