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

    /*
    |--------------------------------------------------------------------------
    | Hero Section (homepage)
    |--------------------------------------------------------------------------
    |
    | The homepage hero pulls `is_featured = true` posts published within
    | `hero_recency_days`. If there aren't enough featured posts in that
    | window the remaining slots are topped up with the newest published
    | posts so the hero never goes stale.
    |
    */

    'hero' => [
        'slots' => 3,
        'recency_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pinned Sections
    |--------------------------------------------------------------------------
    |
    | Editors can pin a post to one curated homepage rail via
    | `posts.pinned_section`. Pins occupy the top slots of that rail and the
    | remainder is filled organically (by weekly views or recency, depending
    | on the rail). A pin expires when `pinned_until` passes; leaving it null
    | pins indefinitely. Keys here are the stored values; `slots` caps how
    | many pins one rail will honour so a pin can never crowd out the whole
    | rail.
    |
    */

    'pinned_sections' => [
        'hero' => ['label' => 'Hero (top of homepage)', 'slots' => 2],
        'most_popular' => ['label' => 'Most Popular', 'slots' => 2],
        'featured_videos' => ['label' => 'Featured Videos', 'slots' => 2],
        'tv' => ['label' => "Explore What's On TV", 'slots' => 2],
        'editors_picked' => ['label' => "Editor's Picked", 'slots' => 2],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-feature Rubric (used by ProcessIdeaJob)
    |--------------------------------------------------------------------------
    |
    | The AI content pipeline auto-flags `is_featured = true` only when ALL
    | of the following are true:
    |   - its final SEO score is at least `min_seo_score`
    |   - its `post_type` is in `eligible_post_types`
    |   - fewer than `weekly_cap` posts have been featured in the past 7 days
    |   - AND it meets one of:
    |       * attached to a creator that has both a bio and a profile image
    |       * lands in one of `priority_category_ids`
    |
    | Tune these without redeploying. Bias is strict — better to under-feature
    | than flood the hero. The recency-window fallback in BlogController
    | covers any dry spells.
    |
    */

    'auto_feature' => [
        'enabled' => true,
        'min_seo_score' => 85,
        'weekly_cap' => 3,
        'eligible_post_types' => ['article', 'listicle', 'video', 'gallery', 'quiz', 'trivia', 'spotlight'],
        // News & Updates, Features & Opinions, Spotlight, Africa-news-style lanes
        'priority_category_ids' => [24, 25, 26, 13, 23, 17],
    ],

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
        'spotlight' => ['name' => 'Creator Spotlight', 'icon' => 'star'],
        'poll' => ['name' => 'Poll', 'icon' => 'bar-chart-2'],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Generation Settings
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'providers' => [
            // max_tokens budgets the response: blog body + meta fields + JSON
            // overhead. A 1500-word body (~2000 tokens) plus tags/categories/
            // structured fields lands around 5000-6000 tokens; previous 4000
            // ceiling truncated responses mid-string and broke JSON parsing.
            // Bumping to 8000 leaves comfortable headroom for 'long' lengths.
            'openai' => [
                'enabled' => (bool) env('OPENAI_API_KEY'),
                'api_key' => env('OPENAI_API_KEY', ''),
                'model' => env('OPENAI_BLOG_MODEL', 'gpt-4o'),
                'max_tokens' => 8000,
            ],
            'perplexity' => [
                'enabled' => (bool) env('PERPLEXITY_API_KEY'),
                'api_key' => env('PERPLEXITY_API_KEY', ''),
                'model' => env('PERPLEXITY_MODEL', 'sonar-pro'),
                'max_tokens' => 8000,
            ],
            'anthropic' => [
                'enabled' => (bool) env('ANTHROPIC_API_KEY'),
                'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
                'max_tokens' => 8000,
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
    | Creator Discovery
    |--------------------------------------------------------------------------
    |
    | When the AI generates a post it returns `mentioned_creators` — real
    | people referenced by name who aren't yet in our creators table. We
    | auto-create the ones that pass a strict name guard (as `pending`, so
    | they stay out of public listings until an editor reviews) and enrich
    | them in the background. The guard exists because auto-creation runs on
    | every post; a weak guard would flood the table with junk names.
    |
    */

    'creator_discovery' => [
        'enabled' => (bool) env('CREATOR_DISCOVERY_ENABLED', true),

        // Cap creators auto-created per post so one runaway article can't
        // spawn dozens of pending records.
        'max_per_post' => (int) env('CREATOR_DISCOVERY_MAX_PER_POST', 5),

        // Names shorter than this are rejected outright (catches initials and
        // common short words like "ruth", "tina").
        'min_name_length' => 4,

        // Lowercased denylist of common nouns / stop-words / known-junk names
        // that must never become creators even if the AI emits them. Extend
        // this as junk surfaces in the pending queue.
        'denylist' => [
            // generic roles / words
            'creator', 'creators', 'influencer', 'influencers', 'artist',
            'artists', 'musician', 'singer', 'rapper', 'actor', 'actress',
            'founder', 'ceo', 'star', 'celebrity', 'team', 'group', 'brand',
            'company', 'startup', 'channel', 'page', 'account', 'fans',
            // places / common topical nouns
            'africa', 'african', 'nigeria', 'ghana', 'kenya', 'tech',
            'music', 'film', 'fashion', 'sports', 'news', 'video', 'photo',
            // known-junk common-noun names already in our data
            'last', 'sale', 'ruth', 'tina', 'sophia',
        ],
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

    /*
    |--------------------------------------------------------------------------
    | Reactions
    |--------------------------------------------------------------------------
    |
    | Emoji reactions available on blog posts. Add or remove types here —
    | no code or migration changes needed.
    |
    */

    'reactions' => [
        'fire' => ['emoji' => "\u{1F525}", 'label' => 'Fire'],
        'love' => ['emoji' => "\u{2764}\u{FE0F}", 'label' => 'Love'],
        'clap' => ['emoji' => "\u{1F44F}", 'label' => 'Clap'],
        'wow' => ['emoji' => "\u{1F62E}", 'label' => 'Wow'],
        'sad' => ['emoji' => "\u{1F622}", 'label' => 'Sad'],
        'angry' => ['emoji' => "\u{1F621}", 'label' => 'Angry'],
    ],

];
