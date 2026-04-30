<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | SEO Intelligence Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Blog SEO Intelligence System.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('SEO_INTELLIGENCE_CACHE', true),
        'ttl' => env('SEO_INTELLIGENCE_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'seo_intelligence_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'cooldown_seconds' => 1800, // 1 analysis per post per 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Weights (must sum to 1.0)
    |--------------------------------------------------------------------------
    */
    'weights' => [
        'content_quality' => 0.30,
        'technical_seo' => 0.25,
        'readability' => 0.20,
        'user_engagement' => 0.15,
        'on_page_elements' => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Letter Grade Thresholds
    |--------------------------------------------------------------------------
    */
    'grades' => [
        'A+' => 95,
        'A' => 90,
        'B+' => 85,
        'B' => 80,
        'C+' => 75,
        'C' => 70,
        'D' => 60,
        'F' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Target Thresholds
    |--------------------------------------------------------------------------
    */
    'targets' => [
        'word_count' => [
            'min' => 1500,
            'max' => 2500,
        ],
        'keyword_density' => [
            'min' => 1.0,
            'max' => 2.0,
        ],
        'flesch_score' => [
            'min' => 60,
            'max' => 70,
        ],
        'meta_title_length' => [
            'min' => 50,
            'max' => 60,
        ],
        'meta_description_length' => [
            'min' => 150,
            'max' => 160,
        ],
        'internal_links' => [
            'min' => 3,
            'max' => 5,
        ],
        'external_links' => [
            'min' => 2,
            'max' => 3,
        ],
        'avg_sentence_length' => [
            'min' => 15,
            'max' => 20,
        ],
        'avg_paragraph_length' => [
            'min' => 3,
            'max' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Change Threshold for Auto-Analysis
    |--------------------------------------------------------------------------
    | Re-analyze if content changes by more than this percentage.
    */
    'content_change_threshold' => 20,

];
