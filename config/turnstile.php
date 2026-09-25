<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Bot protection for public forms (login, register, comments, newsletter,
    | creator submissions/claims). Create a widget in the Cloudflare dashboard
    | under Turnstile and paste its site + secret key here. Verification is
    | skipped entirely when disabled or when either key is missing.
    |
    | Local dev can use Cloudflare's always-pass test keys:
    |   site:   1x00000000000000000000AA
    |   secret: 1x0000000000000000000000000000000AA
    |
    */

    'enabled' => env('TURNSTILE_ENABLED', true),

    'site_key' => env('TURNSTILE_SITE_KEY'),

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

];
