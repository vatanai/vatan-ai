<?php

/*
|--------------------------------------------------------------------------
| اینستاگرام هوشمند
|--------------------------------------------------------------------------
| همه‌ی کلیدهای حساس فقط از .env خوانده می‌شوند. ارسال واقعی پیام به‌صورت
| پیش‌فرض خاموش است (پروپوزال، فاز ۱: بدون ارسال خودکار) و باید صریحاً روشن شود.
*/

return [
    'workspace_slug' => env('SMART_INSTAGRAM_WORKSPACE', 'vatan'),

    // کلید اصلی ارسال؛ تا false است هیچ پیامی (انسانی/هوشمند/اتومیشن) به ارائه‌دهنده نمی‌رود.
    'outbound_enabled' => (bool) env('SMART_INSTAGRAM_OUTBOUND_ENABLED', false),

    // دریافت کامنت/دایرکت و ارسال پاسخ روی صف جدای «instagram» با ورکر اختصاصی (chabok-pre-start.sh)
    // تا کارهای طولانی صف default (تولید تصویر/ویدیو تا ۱۵ دقیقه) پاسخ اینستاگرام را عقب نیندازند.
    // ورکر اصلی هم این صف را با اولویت پایین‌تر می‌خواند تا در نبود ورکر اختصاصی چیزی نماند.
    'queues' => [
        'ingest' => env('SMART_INSTAGRAM_QUEUE_INGEST', 'instagram'),
        'media' => env('SMART_INSTAGRAM_QUEUE_MEDIA', 'default'),
        'ai' => env('SMART_INSTAGRAM_QUEUE_AI', 'default'),
        'outbound' => env('SMART_INSTAGRAM_QUEUE_OUTBOUND', 'instagram'),
    ],

    // ورودی امضاشده برای n8n / Composio (هدر X-Vatan-Signature = sha256=HMAC(timestamp.body))
    'ingest' => [
        'secret' => env('SMART_INSTAGRAM_INGEST_SECRET'),
        'tolerance_seconds' => (int) env('SMART_INSTAGRAM_INGEST_TOLERANCE', 300),
    ],

    'gateways' => [
        'meta' => \App\Services\SmartInstagram\Gateways\MetaInstagramGateway::class,
        'composio' => \App\Services\SmartInstagram\Gateways\ComposioInstagramGateway::class,
        'sandbox' => \App\Services\SmartInstagram\Gateways\SandboxInstagramGateway::class,
    ],

    'composio' => [
        'enabled' => (bool) env('COMPOSIO_API_KEY'),
        'connected_account_id' => env('COMPOSIO_CONNECTED_ACCOUNT_ID'),
        'user_id' => env('COMPOSIO_USER_ID'),
        'instagram_user_id' => env('COMPOSIO_INSTAGRAM_USER_ID', 'me'),
        'toolkit_version' => env('COMPOSIO_TOOLKIT_VERSION', 'latest'),
    ],

    'ai' => [
        'enabled' => (bool) env('SMART_INSTAGRAM_AI_ENABLED', true),
        'model' => env('SMART_INSTAGRAM_AI_MODEL', env('TELEGRAM_PRODUCT_AI_MODEL', 'openai/gpt-4o-mini')),
        'timeout' => (int) env('SMART_INSTAGRAM_AI_TIMEOUT', 40),
        'context_messages' => 20,
        'knowledge_chunks' => 6,
        // اگر قیمت/عدد در پاسخ هست ولی در منابع بازیابی‌شده نیست، پاسخ «تأییدنشده» علامت می‌خورد.
        'guard_numbers' => true,
    ],

    'policy' => [
        'dm_window_hours' => 24,          // پنجره‌ی استاندارد پیام‌رسانی متا
        'private_reply_window_days' => 7, // پاسخ خصوصی به کامنت
        'automation_max_per_contact_per_day' => 3,
        'duplicate_window_minutes' => 30,
        'max_attempts' => 4,
    ],

    'knowledge' => [
        'max_upload_kb' => 4096,
        'extensions' => ['txt', 'md', 'csv', 'json', 'html', 'htm', 'docx'],
        'chunk_chars' => 900,
        'chunk_overlap' => 120,
        'categories' => [
            'brand' => 'معرفی برند و لحن',
            'products' => 'محصولات و خدمات',
            'pricing' => 'قیمت و اعتبار',
            'faq' => 'پرسش‌های رایج',
            'policy' => 'سیاست ارسال، بازگشت و شرایط',
            'samples' => 'نمونه‌کار',
            'approved_reply' => 'پاسخ تأییدشده',
            'general' => 'سایر',
        ],
    ],

    'media' => [
        'disk' => env('SMART_INSTAGRAM_MEDIA_DISK', 'local'),
        'max_mb' => 25,
        'transcription_enabled' => (bool) env('SMART_INSTAGRAM_TRANSCRIPTION_ENABLED', false),
    ],

    'pipeline_stages' => [
        'new' => 'ورودی جدید',
        'qualified' => 'واجد شرایط',
        'discovery' => 'نیازسنجی',
        'proposal' => 'پیشنهاد ارسال شد',
        'waiting' => 'در انتظار مشتری',
        'ready' => 'آماده‌ی خرید',
        'won' => 'برنده',
        'lost' => 'از دست‌رفته',
        'after_sale' => 'پس از خرید',
    ],

    'conversation_statuses' => [
        'new' => 'جدید',
        'unanswered' => 'بی‌پاسخ',
        'waiting_customer' => 'در انتظار مشتری',
        'assigned' => 'واگذارشده',
        'closed' => 'بسته',
    ],

    'intents' => [
        'price' => 'قیمت',
        'samples' => 'نمونه‌کار',
        'order' => 'سفارش',
        'availability' => 'موجودی',
        'shipping' => 'ارسال',
        'partnership' => 'همکاری',
        'complaint' => 'شکایت',
        'support' => 'پشتیبانی',
        'greeting' => 'سلام و شروع',
        'unknown' => 'نامشخص',
    ],

    'customer_stages' => [
        'aware' => 'آشنا',
        'interested' => 'علاقه‌مند',
        'evaluating' => 'در حال ارزیابی',
        'ready' => 'آماده‌ی خرید',
        'customer' => 'پس از خرید',
        'churn_risk' => 'در خطر ریزش',
    ],

    'lead_statuses' => [
        'new' => 'جدید',
        'qualified' => 'واجد شرایط',
        'hot' => 'داغ',
        'customer' => 'مشتری',
        'lost' => 'از دست‌رفته',
    ],

    'sources' => [
        'dm' => 'دایرکت',
        'comment' => 'کامنت',
        'story_reply' => 'پاسخ استوری',
        'mention' => 'منشن',
        'ad' => 'تبلیغ',
    ],

    'roles' => [
        'owner' => 'مالک برند',
        'sales_manager' => 'مدیر فروش',
        'operator' => 'اپراتور فروش',
        'content_manager' => 'مدیر محتوا',
        'viewer' => 'ناظر',
    ],
];
