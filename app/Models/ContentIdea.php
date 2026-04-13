<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentIdea extends Model
{
    protected $fillable = [
        'title',
        'description',
        'niche',
        'category',
        'suggested_keyword',
        'seo_score',
        'search_interest',
        'competition',
        'relevance_score',
        'source',
        'source_url',
        'suggested_tone',
        'suggested_length',
        'suggested_post_type',
        'status',
        'dismissal_reason',
        'generated_post_id',
        'expires_at',
        'researched_at',
        'responded_at',
    ];

    protected $casts = [
        'seo_score' => 'integer',
        'relevance_score' => 'integer',
        'expires_at' => 'datetime',
        'researched_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    // ── Scopes ───────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeByNiche(Builder $query, string $niche): Builder
    {
        return $query->where('niche', $niche);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->orderByDesc('seo_score');
    }

    // ── Relationships ────────────────────────────────────────

    public function generatedPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'generated_post_id');
    }

    // ── Status Methods ───────────────────────────────────────

    public function markAsGenerating(): void
    {
        $this->update(['status' => 'generating']);
    }

    public function markAsGenerated(int $postId): void
    {
        $this->update([
            'status' => 'generated',
            'generated_post_id' => $postId,
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'responded_at' => now(),
        ]);
    }

    public function dismiss(?string $reason = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'dismissal_reason' => $reason,
            'responded_at' => now(),
        ]);
    }

    public function restore(): void
    {
        $this->update([
            'status' => 'pending',
            'dismissal_reason' => null,
            'responded_at' => null,
        ]);
    }
}
