<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | reCAPTCHA Enterprise Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Google reCAPTCHA Enterprise for bot protection on authentication
    | pages and public comment forms. Score-based (invisible) verification is used.
    |
    */

    'enabled' => env('RECAPTCHA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Site Key (Public)
    |--------------------------------------------------------------------------
    |
    | The site key is used in the frontend JavaScript to generate tokens.
    | Get this from Google Cloud Console > reCAPTCHA Enterprise > Keys
    |
    */

    'site_key' => env('RECAPTCHA_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Google Cloud Project ID
    |--------------------------------------------------------------------------
    |
    | Your Google Cloud project ID where reCAPTCHA Enterprise is enabled.
    |
    */

    'project_id' => env('RECAPTCHA_PROJECT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Score Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum score (0.0 - 1.0) required to pass verification.
    | 1.0 = very likely a good interaction, 0.0 = very likely a bot.
    | Recommended: 0.5 for balanced protection.
    |
    */

    'score_threshold' => (float) env('RECAPTCHA_SCORE_THRESHOLD', 0.5),

    /*
    |--------------------------------------------------------------------------
    | Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Google Cloud service account JSON key file.
    | Create a service account with "reCAPTCHA Enterprise Agent" role.
    |
    | For production, you can use GOOGLE_CREDENTIALS_JSON with base64-encoded JSON.
    |
    */

    'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    'credentials_json' => env('GOOGLE_CREDENTIALS_JSON'),

];
