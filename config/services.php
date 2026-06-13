<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Search Console / programmatic Google APIs — separate client.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'search_console_refresh_token' => env('GOOGLE_SEARCH_CONSOLE_REFRESH_TOKEN'),
        'search_console_site_url' => env('GOOGLE_SEARCH_CONSOLE_SITE_URL'),
    ],

    // User-facing Google Sign-In (Socialite). Dedicated OAuth client so its
    // consent screen only requests email/profile scopes.
    'google_oauth' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_OAUTH_REDIRECT_URI'),
    ],

    'google_search' => [
        'api_key' => env('GOOGLE_SEARCH_API_KEY'),
        'cx' => env('GOOGLE_SEARCH_CX'),
    ],

    'serper' => [
        'api_key' => env('SERPER_API_KEY'),
    ],

    'perplexity' => [
        'key' => env('PERPLEXITY_API_KEY'),
    ],

    'anthropic' => [
        'key' => env('ANTHROPIC_API_KEY'),
    ],

    // External Blog Content API. A single static bearer token shared with the
    // trusted external site that pushes/pulls posts. Rotate by changing the
    // env value. Author of all externally-created posts is resolved to the
    // system user identified by `author_email`.
    'blog_api' => [
        'token' => env('BLOG_API_TOKEN'),
        'author_email' => env('BLOG_API_AUTHOR_EMAIL', 'api@toppingafrica.com'),
        'author_name' => env('BLOG_API_AUTHOR_NAME', 'Topping Africa API'),
    ],

];
