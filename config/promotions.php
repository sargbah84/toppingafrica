<?php

/*
|--------------------------------------------------------------------------
| Paid Promotions
|--------------------------------------------------------------------------
|
| Packages and add-ons sold on the public /promote page. Prices are in the
| smallest currency unit (cents) and are charged through Stripe Checkout
| using inline price_data, so nothing needs to be created in the Stripe
| dashboard — changing a price here takes effect on the next checkout.
|
| `boost_budget` is the paid-ads spend (in cents) included in a package; it
| is shown to buyers so they know how much of the price goes to ad reach.
|
*/

return [

    'currency' => env('PROMOTIONS_CURRENCY', 'usd'),

    // Off: submissions are inquiries and invoices are sent by hand.
    // On (with Stripe keys set): buyers pay via Stripe Checkout on submit.
    'online_payments' => (bool) env('PROMOTIONS_ONLINE_PAYMENTS', false),

    // Where new paid-request alerts are sent. Falls back to MAIL_FROM_ADDRESS.
    'notify_email' => env('PROMOTIONS_NOTIFY_EMAIL'),

    'packages' => [
        'starter' => [
            'name' => 'Starter Post',
            'price' => 2900,
            'tagline' => 'Try us out — get your release on the record.',
            'turnaround' => '5 business days',
            'boost_budget' => 0,
            'features' => [
                'News brief (300–400 words) on toppingafrica.com',
                'Link to your music, video, product or event',
                'Embedded YouTube / Spotify / SoundCloud player',
                'Permanent article — indexed by Google',
            ],
        ],
        'feature' => [
            'name' => 'Feature Story',
            'price' => 7900,
            'tagline' => 'A full editorial feature, shared with our Facebook community.',
            'turnaround' => '3 business days',
            'boost_budget' => 0,
            'features' => [
                'Full feature article (700–1,000 words) by our editors',
                'Up to 3 photos plus embedded player',
                'Linked to your Topping Africa creator profile',
                'Shared on the Topping Africa Facebook page',
            ],
        ],
        'boost' => [
            'name' => 'Social Boost',
            'price' => 17900,
            'tagline' => 'Your feature, pushed to a wider audience with paid reach.',
            'turnaround' => '3 business days',
            'boost_budget' => 5000,
            'popular' => true,
            'features' => [
                'Everything in Feature Story',
                'Shared on Facebook and Instagram',
                '$50 paid boost on Facebook & Instagram (7 days)',
                'Pinned on the homepage for 3 days',
            ],
        ],
        'spotlight' => [
            'name' => 'Spotlight',
            'price' => 39900,
            'tagline' => 'The full treatment — video, social, homepage and a results report.',
            'turnaround' => '5–7 business days',
            'boost_budget' => 7500,
            'features' => [
                'Everything in Social Boost',
                'YouTube Spotlight video (1–3 min) on our channel',
                'Short-form cut for TikTok and Instagram Reels',
                '$75 paid boost on Facebook & Instagram (7 days)',
                'Pinned on the homepage for 7 days',
                'Performance report (views, clicks, reach)',
            ],
        ],
    ],

    'addons' => [
        'youtube_video' => [
            'name' => 'YouTube Spotlight Video',
            'price' => 17900,
            'description' => '1–3 min spotlight video on our YouTube channel, plus a TikTok / Reels cut.',
            // Already included in these packages, so it isn't offered there.
            'excluded_packages' => ['spotlight'],
        ],
        'extra_boost' => [
            'name' => 'Extra Paid Boost',
            'price' => 5900,
            'description' => '+$50 of Facebook & Instagram ad spend for more reach.',
            'excluded_packages' => [],
        ],
        'extra_social_post' => [
            'name' => 'Extra Social Post',
            'price' => 2500,
            'description' => 'One more post on our Facebook page (e.g. a release-day reminder).',
            'excluded_packages' => [],
        ],
        'homepage_pin' => [
            'name' => 'Homepage Pin (+7 days)',
            'price' => 3900,
            'description' => 'Keep your story pinned on the homepage for an extra week.',
            'excluded_packages' => [],
        ],
        'rush' => [
            'name' => 'Rush Delivery',
            'price' => 3900,
            'description' => 'Article published within 48 hours of payment (video excluded).',
            'excluded_packages' => [],
        ],
    ],

    'promo_types' => [
        'music' => 'Music release',
        'video' => 'Music video / film',
        'event' => 'Event',
        'business' => 'Business / startup',
        'product' => 'Product launch',
        'personal_brand' => 'Creator / personal brand',
        'other' => 'Other',
    ],

];
