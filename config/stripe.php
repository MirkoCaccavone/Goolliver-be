<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe API Keys
    |--------------------------------------------------------------------------
    |
    | The Stripe publishable and secret keys from your Stripe dashboard.
    | These are used to authenticate requests with Stripe's API.
    |
    */

    'public_key' => env('STRIPE_PUBLIC_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe API Version
    |--------------------------------------------------------------------------
    |
    | The Stripe API version to use. You should generally leave this alone
    | unless you have a specific reason to use an older version.
    |
    */

    'api_version' => '2023-10-16',

    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    |
    | Default currency for payments
    |
    */

    'currency' => 'eur',
    'currency_symbol' => '€',

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe webhook handling
    |
    */

    'webhook' => [
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],
];
