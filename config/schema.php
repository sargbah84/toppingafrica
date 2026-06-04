<?php

declare(strict_types=1);

// Note: Setting::get() is used at runtime in views/services.
// This config provides fallback defaults only — the DB settings take priority.

return [
    'organization' => [
        'name' => env('APP_NAME', 'My Blog'),
        'legal_name' => env('APP_NAME', 'My Blog'),
        'url' => env('APP_URL', 'https://example.com'),
        'logo' => env('APP_URL', 'https://example.com') . '/images/logo.png',
        'description' => env('APP_NAME', 'My Blog') . ' - Discover creators across Africa and African creators around the world, plus the latest in African business, development, innovation and culture.',
        'founding_date' => '2024',
        'contact_email' => env('CONTACT_EMAIL', 'hello@example.com'),
        'social_profiles' => [
            'twitter' => env('SOCIAL_TWITTER', ''),
            'facebook' => env('SOCIAL_FACEBOOK', ''),
            'instagram' => env('SOCIAL_INSTAGRAM', ''),
        ],
    ],

    'website' => [
        'name' => env('APP_NAME', 'My Blog'),
        'alternate_name' => env('APP_NAME', 'My Blog'),
        'search_url_template' => env('APP_URL', 'https://example.com') . '/search?q={search_term_string}',
    ],

    'publisher' => [
        'name' => env('APP_NAME', 'My Blog'),
        'logo' => env('APP_URL', 'https://example.com') . '/images/logo.png',
        'url' => env('APP_URL', 'https://example.com'),
    ],
];
