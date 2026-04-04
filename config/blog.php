<?php

// Topping Africa - Blog & Interactive Content Configuration

return [

    /*
    |--------------------------------------------------------------------------
    | Blog Configuration
    |--------------------------------------------------------------------------
    */

    'per_page' => 12,

    'cache_duration' => 3600,

    'reading_speed' => 200,

    'featured_posts_count' => 1,

    'related_posts_count' => 3,

    'excerpt_length' => 160,

    /*
    |--------------------------------------------------------------------------
    | Post Types
    |--------------------------------------------------------------------------
    */

    'post_types' => [
        'article' => ['name' => 'Article', 'icon' => 'newspaper'],
        'video' => ['name' => 'Video Post', 'icon' => 'play-circle'],
        'gallery' => ['name' => 'Gallery', 'icon' => 'images'],
        'quiz' => ['name' => 'Quiz', 'icon' => 'help-circle'],
        'trivia' => ['name' => 'Trivia', 'icon' => 'lightbulb'],
        'listicle' => ['name' => 'Listicle', 'icon' => 'list-ordered'],
        'poll' => ['name' => 'Poll', 'icon' => 'bar-chart-2'],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Generation Settings
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'providers' => [
            'openai' => [
                'enabled' => (bool) env('OPENAI_API_KEY'),
                'api_key' => env('OPENAI_API_KEY', ''),
                'model' => env('OPENAI_BLOG_MODEL', 'gpt-4o'),
                'max_tokens' => 4000,
            ],
            'perplexity' => [
                'enabled' => (bool) env('PERPLEXITY_API_KEY'),
                'api_key' => env('PERPLEXITY_API_KEY', ''),
                'model' => env('PERPLEXITY_MODEL', 'sonar-pro'),
                'max_tokens' => 4000,
            ],
            'anthropic' => [
                'enabled' => (bool) env('ANTHROPIC_API_KEY'),
                'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
                'max_tokens' => 4000,
            ],
        ],

        'lengths' => [
            'short' => 800,
            'medium' => 1500,
            'long' => 2500,
        ],

        'tones' => [
            'professional' => 'Professional and authoritative',
            'conversational' => 'Friendly and conversational',
            'technical' => 'Technical and detailed',
            'beginner' => 'Beginner-friendly and educational',
        ],

        'niches' => [
            'africa-news' => 'Africa News',
            'entertainment' => 'Entertainment',
            'business' => 'Business & Economy',
            'technology' => 'Technology',
            'sports' => 'Sports',
            'health' => 'Health & Wellness',
            'politics' => 'Politics & Governance',
        ],

        'max_tags' => 10,
        'max_category_suggestions' => 3,
        'max_internal_links' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Settings
    |--------------------------------------------------------------------------
    */

    'media' => [
        'disk' => 'public',
        'path' => 'blog/media',

        'max_upload_size' => 5120,

        'allowed_mimes' => [
            'jpg', 'jpeg', 'png', 'gif', 'webp',
        ],

        'image_sizes' => [
            'thumbnail' => [150, 150],
            'medium' => [600, 400],
            'large' => [1200, 800],
        ],

        'optimize' => true,
        'quality' => 85,

        'pexels' => [
            'enabled' => (bool) env('PEXELS_API_KEY'),
            'api_key' => env('PEXELS_API_KEY', ''),
            'results_per_page' => 20,
        ],

        'unsplash' => [
            'enabled' => (bool) env('UNSPLASH_ACCESS_KEY'),
            'access_key' => env('UNSPLASH_ACCESS_KEY', ''),
            'results_per_page' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SEO Settings
    |--------------------------------------------------------------------------
    */

    'seo' => [
        'meta_description_length' => 160,
        'generate_og_image' => true,
        'sitemap_priority' => 0.8,
        'sitemap_change_frequency' => 'weekly',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Sharing
    |--------------------------------------------------------------------------
    */

    'social_sharing' => [
        'platforms' => ['twitter', 'linkedin', 'facebook'],

        'twitter' => [
            'max_length' => 280,
            'hashtag_count' => 3,
        ],

        'linkedin' => [
            'min_length' => 150,
            'max_length' => 200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Categories
    |--------------------------------------------------------------------------
    */

    'default_categories' => [
        [
            'name' => 'Africa News',
            'slug' => 'africa-news',
            'description' => 'Breaking news and current affairs from across the African continent',
            'color' => '#DC2626',
            'icon' => 'globe',
        ],
        [
            'name' => 'Entertainment',
            'slug' => 'entertainment',
            'description' => 'African music, film, celebrities, and pop culture',
            'color' => '#E1306C',
            'icon' => 'music',
        ],
        [
            'name' => 'Business & Economy',
            'slug' => 'business',
            'description' => 'African business news, startups, trade, and economic development',
            'color' => '#059669',
            'icon' => 'briefcase',
        ],
        [
            'name' => 'Technology',
            'slug' => 'technology',
            'description' => 'Tech innovation, startups, and digital transformation in Africa',
            'color' => '#3B82F6',
            'icon' => 'cpu',
        ],
        [
            'name' => 'Sports',
            'slug' => 'sports',
            'description' => 'African sports news, football, athletics, and more',
            'color' => '#F59E0B',
            'icon' => 'trophy',
        ],
        [
            'name' => 'Health & Wellness',
            'slug' => 'health',
            'description' => 'Health news, medical breakthroughs, and wellness across Africa',
            'color' => '#10B981',
            'icon' => 'heart',
        ],
        [
            'name' => 'Politics & Governance',
            'slug' => 'politics',
            'description' => 'Political developments, governance, and policy across Africa',
            'color' => '#6366F1',
            'icon' => 'landmark',
        ],
        [
            'name' => 'Culture & Lifestyle',
            'slug' => 'culture',
            'description' => 'African culture, fashion, food, travel, and lifestyle',
            'color' => '#8B5CF6',
            'icon' => 'palette',
        ],
        [
            'name' => 'Opinion & Editorial',
            'slug' => 'opinion',
            'description' => 'Expert analysis, commentary, and editorial perspectives',
            'color' => '#64748B',
            'icon' => 'message-square',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'ttl' => 3600, // 1 hour
    ],

];
