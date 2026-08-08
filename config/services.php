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

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'management_key' => env('OPENROUTER_MANAGEMENT_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'base_urls' => env('OPENROUTER_BASE_URLS'), // چند Endpoint با کاما برای Failover (اختیاری)
        'max_attempts' => env('OPENROUTER_MAX_ATTEMPTS', 5), // تعداد تلاش مجدد روی هر Endpoint هنگام خطای شبکه
        'gateway_secret' => env('OPENROUTER_GATEWAY_SECRET'),
        'timeout' => env('OPENROUTER_TIMEOUT', 60),
    ],

    'liara' => [
        'api_key'  => env('LIARA_AI_API_KEY'),
        'base_url' => env('LIARA_AI_BASE_URL', 'https://ai.liara.ir/api/v1'),
        'timeout'  => (int) env('LIARA_AI_TIMEOUT', 120),
        'account_api_token' => env('LIARA_API_TOKEN'),
        'account_api_url' => env('LIARA_ACCOUNT_API_URL', 'https://api.liara.ir'),
    ],

    'fal' => [
        'api_key' => env('FAL_API_KEY', env('FAL_KEY')),
        'base_url' => env('FAL_BASE_URL', 'https://queue.fal.run'),
        'platform_base_url' => env('FAL_PLATFORM_BASE_URL', 'https://api.fal.ai'),
        'timeout' => (int) env('FAL_TIMEOUT', env('AI_PROVIDER_TIMEOUT', 600)),
        'max_retries' => (int) env('FAL_MAX_RETRIES', env('AI_PROVIDER_MAX_RETRIES', 2)),
    ],

    'replicate' => [
        'api_token' => env('REPLICATE_API_TOKEN'),
        'base_url' => env('REPLICATE_BASE_URL', 'https://api.replicate.com/v1'),
        'timeout' => (int) env('REPLICATE_TIMEOUT', env('AI_PROVIDER_TIMEOUT', 600)),
        'max_retries' => (int) env('REPLICATE_MAX_RETRIES', env('AI_PROVIDER_MAX_RETRIES', 2)),
    ],

    'ai' => [
        'webhook_base_url' => env('AI_WEBHOOK_BASE_URL'),
        'webhook_secret' => env('AI_WEBHOOK_SECRET'),
        'timeout' => (int) env('AI_PROVIDER_TIMEOUT', 600),
        'max_retries' => (int) env('AI_PROVIDER_MAX_RETRIES', 2),
        'catalog_sync_on_migrate' => (bool) env('AI_CATALOG_SYNC_ON_MIGRATE', true),
    ],

    'melipayamak' => [
        'api_key' => env('MELIPAYAMAK_API_KEY'),
        'base_url' => env('MELIPAYAMAK_BASE_URL', 'https://console.melipayamak.com/api'),
        'from' => env('MELIPAYAMAK_FROM'),
        'timeout' => (int) env('MELIPAYAMAK_TIMEOUT', 15),
    ],

    'nanobanana' => [
        'api_key' => env('NANOBANANA_API_KEY'),
    ],

    'exchange_rate' => [
        'url' => env('EXCHANGE_RATE_URL', 'https://api.nobitex.ir/v3/orderbook/USDTIRT'),
        'backup_url' => env('EXCHANGE_RATE_BACKUP_URL', 'https://api.wallex.ir/v1/markets'),
        'fallback' => (float) env('USD_IRR_FALLBACK', 0),
    ],

];
