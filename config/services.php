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

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID', env('ZIBAL_MERCHANT')),
        'request_url' => env('ZARINPAL_REQUEST_URL', 'https://api.zarinpal.com/pg/v4/payment/request.json'),
        'verify_url' => env('ZARINPAL_VERIFY_URL', 'https://api.zarinpal.com/pg/v4/payment/verify.json'),
        'start_url' => env('ZARINPAL_START_URL', 'https://www.zarinpal.com/pg/StartPay'),
        'rial_multiplier' => (int) env('ZARINPAL_RIAL_MULTIPLIER', 10),
        'timeout' => (int) env('ZARINPAL_TIMEOUT', 15),
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

    'n8n' => [
        'video_studio_webhook' => env('N8N_VIDEO_STUDIO_WEBHOOK_URL'),
        'video_studio_preview_webhook' => env('N8N_VIDEO_STUDIO_PREVIEW_WEBHOOK'),
        'video_studio_status_secret' => env('N8N_VIDEO_STUDIO_STATUS_SECRET'),
        'video_studio_telegram_chat_id' => env('N8N_VIDEO_STUDIO_TELEGRAM_CHAT_ID'),
        'video_studio_telegram_instagram_thread_id' => env('N8N_VIDEO_STUDIO_TELEGRAM_INSTAGRAM_THREAD_ID'),
        'video_studio_telegram_channel_thread_id' => env('N8N_VIDEO_STUDIO_TELEGRAM_CHANNEL_THREAD_ID'),
        'video_studio_telegram_music_thread_id' => env('N8N_VIDEO_STUDIO_TELEGRAM_MUSIC_THREAD_ID'),
        'video_studio_telegram_linkedin_thread_id' => env('N8N_VIDEO_STUDIO_TELEGRAM_LINKEDIN_THREAD_ID', '29'),
        'video_studio_telegram_aparat_thread_id' => env('N8N_VIDEO_STUDIO_TELEGRAM_APARAT_THREAD_ID', '31'),
        'video_studio_telegram_youtube_thread_id' => env('N8N_VIDEO_STUDIO_TELEGRAM_YOUTUBE_THREAD_ID', '33'),
        'video_studio_callback_base_url' => env('N8N_VIDEO_STUDIO_CALLBACK_BASE_URL'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'channel_id' => env('TELEGRAM_CHANNEL_ID'),
        'channel_username' => env('TELEGRAM_CHANNEL_USERNAME', 'ai_vatan'),
        'channel_invite_url' => env('TELEGRAM_CHANNEL_INVITE_URL'),
        'mini_app_url' => env('TELEGRAM_MINI_APP_URL'),
        'init_data_max_age' => (int) env('TELEGRAM_INIT_DATA_MAX_AGE', 86400),
        'otp_resend_seconds' => (int) env('TELEGRAM_OTP_RESEND_SECONDS', 60),
        'broadcast_enabled' => (bool) env('TELEGRAM_BROADCAST_ENABLED', false),
        'broadcast_rate_per_second' => (int) env('TELEGRAM_BROADCAST_RATE_PER_SECOND', 25),
    ],

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'webhook_verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
        'graph_url' => env('META_GRAPH_URL', 'https://graph.instagram.com'),
        'graph_version' => env('META_GRAPH_VERSION', 'v24.0'),
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
