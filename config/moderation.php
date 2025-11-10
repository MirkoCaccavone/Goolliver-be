<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Content Moderation Configuration
    |--------------------------------------------------------------------------
    |
    | Configurazione per il sistema di moderazione contenuti AI
    |
    */

    'enabled' => env('MODERATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | Provider AI da utilizzare per default: openai, google, aws
    |
    */
    'default_provider' => env('MODERATION_DEFAULT_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Moderation Thresholds
    |--------------------------------------------------------------------------
    |
    | Soglie per determinare l'azione automatica sui contenuti
    | Valori da 0.0 (sicuro) a 1.0 (inappropriato)
    |
    */
    'thresholds' => [
        'auto_approve' => env('MODERATION_AUTO_APPROVE', 0.2),
        'manual_review' => env('MODERATION_MANUAL_REVIEW_THRESHOLD', 0.7),
        'auto_reject' => env('MODERATION_AUTO_REJECT', 0.9),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual Review Settings
    |--------------------------------------------------------------------------
    |
    | Configurazioni per la revisione manuale
    |
    */
    'manual_review' => [
        'required' => env('MODERATION_REQUIRE_MANUAL_REVIEW', true),
        'timeout_hours' => env('MODERATION_REVIEW_TIMEOUT', 24),
        'default_action' => env('MODERATION_REVIEW_DEFAULT_ACTION', 'pending'), // approve, reject, pending
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Configurations
    |--------------------------------------------------------------------------
    |
    | Configurazioni specifiche per ogni provider AI
    |
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_VISION_MODEL', 'gpt-4-vision-preview'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 300),
            'detail' => env('OPENAI_IMAGE_DETAIL', 'low'), // low, high, auto
            'timeout' => env('OPENAI_TIMEOUT', 30),
        ],

        'google' => [
            'api_key' => env('GOOGLE_VISION_API_KEY'),
            'project_id' => env('GOOGLE_PROJECT_ID'),
            'features' => [
                'SAFE_SEARCH_DETECTION',
                'OBJECT_LOCALIZATION',
                'TEXT_DETECTION'
            ],
            'timeout' => env('GOOGLE_VISION_TIMEOUT', 30),
        ],

        'aws' => [
            'access_key_id' => env('AWS_ACCESS_KEY_ID'),
            'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_REKOGNITION_REGION', 'us-east-1'),
            'version' => env('AWS_REKOGNITION_VERSION', 'latest'),
            'timeout' => env('AWS_REKOGNITION_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Categories
    |--------------------------------------------------------------------------
    |
    | Categorie di contenuti da rilevare e relative soglie
    |
    */
    'categories' => [
        'adult' => [
            'enabled' => true,
            'threshold' => 0.3,
            'action' => 'reject', // approve, review, reject
            'description' => 'Contenuto per adulti',
        ],
        'violence' => [
            'enabled' => true,
            'threshold' => 0.4,
            'action' => 'review',
            'description' => 'Violenza',
        ],
        'hatred' => [
            'enabled' => true,
            'threshold' => 0.2,
            'action' => 'reject',
            'description' => 'Contenuti di odio',
        ],
        'harassment' => [
            'enabled' => true,
            'threshold' => 0.3,
            'action' => 'review',
            'description' => 'Molestie',
        ],
        'self_harm' => [
            'enabled' => true,
            'threshold' => 0.2,
            'action' => 'reject',
            'description' => 'Autolesionismo',
        ],
        'illegal' => [
            'enabled' => true,
            'threshold' => 0.1,
            'action' => 'reject',
            'description' => 'Contenuto illegale',
        ],
        'spam' => [
            'enabled' => true,
            'threshold' => 0.5,
            'action' => 'review',
            'description' => 'Spam o pubblicità',
        ],
        'inappropriate' => [
            'enabled' => true,
            'threshold' => 0.4,
            'action' => 'review',
            'description' => 'Contenuto inappropriato',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | Comportamento quando tutti i provider falliscono
    |
    */
    'fallback' => [
        'action' => env('MODERATION_FALLBACK_ACTION', 'review'), // approve, review, reject
        'log_failures' => true,
        'notify_admin' => env('MODERATION_NOTIFY_ADMIN_ON_FAILURE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache per ottimizzare le performance
    |
    */
    'cache' => [
        'enabled' => env('MODERATION_CACHE_ENABLED', true),
        'ttl' => env('MODERATION_CACHE_TTL', 3600), // 1 ora
        'store' => env('MODERATION_CACHE_STORE', 'redis'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging and Monitoring
    |--------------------------------------------------------------------------
    |
    | Configurazione logging e monitoring
    |
    */
    'logging' => [
        'enabled' => env('MODERATION_LOGGING_ENABLED', true),
        'level' => env('MODERATION_LOG_LEVEL', 'info'),
        'channel' => env('MODERATION_LOG_CHANNEL', 'daily'),
        'include_images' => env('MODERATION_LOG_INCLUDE_IMAGES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Limitazione rate per provider AI
    |
    */
    'rate_limiting' => [
        'enabled' => env('MODERATION_RATE_LIMITING_ENABLED', true),
        'requests_per_minute' => env('MODERATION_REQUESTS_PER_MINUTE', 60),
        'burst_limit' => env('MODERATION_BURST_LIMIT', 10),
    ],
];
