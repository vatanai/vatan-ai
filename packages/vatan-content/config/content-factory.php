<?php
/*
| کارخانه‌ی هوشمند محتوای وطن — تنظیمات
| بخش host «آداپتور میزبان» است؛ برای نصب روی داشبورد دیگر فقط همین بخش عوض می‌شود.
| workspace: هر کسب‌وکار (فعلاً فقط خود وطن = ۱) یک فضای کاری جدا دارد.
*/
return [
    'version' => trim((string) @file_get_contents(__DIR__.'/../VERSION')) ?: '0.0.0',
    'name' => 'کارخانه‌ی محتوای وطن',

    'host' => [
        'layout' => env('CF_LAYOUT', 'layouts.admin'),
        'header_partial' => env('CF_HEADER_PARTIAL', 'admin.partials.header'),
        'main_class' => 'mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0',
        'route_prefix' => env('CF_ROUTE_PREFIX', 'admin/content-factory'),
        'route_name' => 'cf.',
        'middleware' => ['web', 'auth:admin'],
        'admin_guard' => 'admin',
        'timezone' => 'Asia/Tehran',
        'fallback_tokens' => false,
        'product_model' => \App\Models\Product::class,
        'product_url' => env('CF_PRODUCT_URL', 'https://aivatan.com/product/{slug}'),
    ],

    // اجرای کارها: inline = دکمه‌ی داشبورد همان لحظه ایجنت را در پس‌زمینه روشن می‌کند (روی مک).
    'runner' => [
        'inline' => (bool) env('CF_RUN_INLINE', true),
        'python' => env('CF_PYTHON', 'python3'),
        // آدرسی که ایجنت لوکال با آن به همین داشبورد وصل می‌شود (پیش‌فرض APP_URL)
        'local_url' => env('CF_LOCAL_URL', env('APP_URL')),
        // مسیرهای رایج مک (Homebrew اینتل و اپل‌سیلیکون) چون PHP وب‌سرور PATH کامل ندارد
        'extra_paths' => ['/opt/homebrew/bin', '/usr/local/bin', '/usr/bin', '/bin'],
    ],

    'workspace_id' => (int) env('CF_WORKSPACE_ID', 1),

    // توکن ایجنت مک (اگر خالی باشد، در صفحه‌ی تنظیمات ساخته و نگه‌داری می‌شود)
    'agent_token' => env('CF_AGENT_TOKEN'),

    // پوشه‌ی content-lab روی مک (ویدیوهای مرجع، موسیقی، رندرها). فقط لوکال.
    'lab_path' => env('CF_LAB_PATH', base_path('content-lab')),
    // دیسک عمومی برای پیش‌نمایش‌ها (کانتکت‌شیت، کاور، ویدیوی نهایی)
    'disk' => env('CF_DISK', 'public'),
    'disk_root' => 'content-factory',

    'ai' => [
        // مدل‌ها از OpenRouter؛ Opus فقط برای داوری و گزارش هفتگی
        'roles' => [
            'fast' => env('CF_MODEL_FAST', 'anthropic/claude-haiku-5.5'),
            'writer' => env('CF_MODEL_WRITER', 'anthropic/claude-sonnet-5.5'),
            'judge' => env('CF_MODEL_JUDGE', 'anthropic/claude-opus-5.5'),
            'vision' => env('CF_MODEL_VISION', 'anthropic/claude-sonnet-5.5'),
        ],
        'monthly_budget_usd' => (float) env('CF_AI_BUDGET_USD', 30),
    ],

    'telegram' => [
        'api_base' => env('CF_TELEGRAM_API_BASE', 'https://api.telegram.org'),
        'bot_token' => env('CONTENT_TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('CONTENT_TELEGRAM_BOT_USERNAME', 'creatvideovatanai_bot'),
        'chat_id' => env('CONTENT_TELEGRAM_CHAT_ID'),
        'webhook_secret' => env('CONTENT_TELEGRAM_WEBHOOK_SECRET'),
        // شناسه‌ی تاپیک‌ها بعد از ساخت گروه با دستور content:telegram setup پر می‌شود (در cf_settings)
        'topics' => ['styles' => 'سبک‌ها و نمونه‌ها', 'music' => 'موسیقی', 'hooks' => 'هوک‌ها', 'prompts' => 'پرامپت‌ها',
            'review' => 'در انتظار تأیید', 'revisions' => 'اصلاحیه‌ها', 'final' => 'نسخه‌های نهایی', 'learning' => 'گزارش یادگیری'],
    ],

    // بازه‌های برداشت آمار پس از انتشار (دقیقه)
    'metric_windows' => ['10m' => 10, '30m' => 30, '1h' => 60, '3h' => 180, '6h' => 360, '12h' => 720, '24h' => 1440, '72h' => 4320, '7d' => 10080, '28d' => 40320],

    'labels' => [
        'production_status' => ['queued' => 'در صف', 'scripting' => 'سناریو', 'rendering' => 'در حال ساخت', 'review' => 'در انتظار تأیید', 'revision' => 'اصلاحیه', 'approved' => 'تأییدشده', 'published' => 'منتشرشده', 'rejected' => 'ردشده'],
        'calendar_status' => ['draft' => 'پیش‌نویس', 'approved' => 'تأییدشده و قفل', 'archived' => 'بایگانی'],
        'style_status' => ['pending' => 'در انتظار تأیید', 'active' => 'فعال', 'testing' => 'آزمایشی', 'archived' => 'بایگانی', 'locked' => 'قفل'],
        'license_status' => ['approved' => 'مجوز تأییدشده', 'unknown' => 'نامشخص', 'forbidden' => 'ممنوع'],
        'prompt_status' => ['draft' => 'پیش‌نویس', 'active' => 'فعال', 'archived' => 'بایگانی'],
    ],
];
