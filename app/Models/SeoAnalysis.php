<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeoAnalysis extends Model
{
    protected $fillable = [
        'post_id',
        'overall_score',
        'grade',
        'content_quality_score',
        'technical_seo_score',
        'readability_score',
        'user_engagement_score',
        'on_page_elements_score',
        'content_quality_details',
        'technical_seo_details',
        'readability_details',
        'user_engagement_details',
        'on_page_elements_details',
        'strengths',
        'improvements',
        'critical_issues',
        'keyword_suggestions',
        'content_recommendations',
        'google_trends_data',
        'applied_recommendations',
        'word_count_at_analysis',
        'focus_keyword_at_analysis',
    ];

    protected $casts = [
        'overall_score' => 'integer',
        'content_quality_score' => 'integer',
        'technical_seo_score' => 'integer',
        'readability_score' => 'integer',
        'user_engagement_score' => 'integer',
        'on_page_elements_score' => 'integer',
        'content_quality_details' => 'array',
        'technical_seo_details' => 'array',
        'readability_details' => 'array',
        'user_engagement_details' => 'array',
        'on_page_elements_details' => 'array',
        'strengths' => 'array',
        'improvements' => 'array',
        'critical_issues' => 'array',
        'keyword_suggestions' => 'array',
        'content_recommendations' => 'array',
        'google_trends_data' => 'array',
        'applied_recommendations' => 'array',
        'word_count_at_analysis' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function getGradeColorAttribute(): string
    {
        return match (true) {
            in_array($this->grade, ['A+', 'A']) => 'green',
            in_array($this->grade, ['B+', 'B']) => 'blue',
            in_array($this->grade, ['C+', 'C']) => 'amber',
            default => 'red',
        };
    }
}
