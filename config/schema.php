<?php

declare(strict_types=1);

return [
    'organization' => [
        'name' => 'Topping Africa',
        'legal_name' => 'Topping Africa',
        'url' => env('APP_URL', 'https://toppingafrica.com'),
        'logo' => env('APP_URL', 'https://toppingafrica.com') . '/images/logo.png',
        'description' => 'Topping Africa is a leading African news and magazine platform covering breaking news, entertainment, business, technology, sports, politics, culture, and lifestyle from across the African continent.',
        'founding_date' => '2024',
        'contact_email' => env('CONTACT_EMAIL', 'hello@toppingafrica.com'),
        'social_profiles' => [
            'twitter' => env('SOCIAL_TWITTER', 'toppingafrica'),
            'facebook' => env('SOCIAL_FACEBOOK', 'https://facebook.com/toppingafrica'),
            'instagram' => env('SOCIAL_INSTAGRAM', 'https://instagram.com/toppingafrica'),
        ],
    ],

    'website' => [
        'name' => 'Topping Africa',
        'alternate_name' => 'Topping Africa - African News & Magazine',
        'search_url_template' => env('APP_URL', 'https://toppingafrica.com') . '/search?q={search_term_string}',
    ],

    'publisher' => [
        'name' => 'Topping Africa',
        'logo' => env('APP_URL', 'https://toppingafrica.com') . '/images/logo.png',
        'url' => env('APP_URL', 'https://toppingafrica.com'),
    ],
];
