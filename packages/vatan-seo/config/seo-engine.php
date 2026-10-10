<?php

/*
|--------------------------------------------------------------------------
| Vatan SEO Engine — تنظیمات اصلی
|--------------------------------------------------------------------------
| این فایل «آداپتور میزبان» است: هر چیزی که به پروژه‌ی میزبان وابسته است
| (لایوت، هدر، گارد احراز هویت، مدل محصول و مقاله) فقط از همین‌جا خوانده
| می‌شود. برای نصب روی پروژه‌ی دیگر، فقط همین بخش‌ها را عوض کنید.
| مقادیر حساس (توکن و کلید) همیشه از .env خوانده می‌شوند.
*/

return [

    'version' => trim((string) @file_get_contents(__DIR__.'/../VERSION')) ?: '0.0.0',

    /* ------------------------------------------------------------------ */
    /* میزبان (Host Adapter)                                              */
    /* ------------------------------------------------------------------ */
    'host' => [
        'name' => env('SEO_HOST_NAME', 'وطن ای‌آی'),
        'url' => env('SEO_SITE_URL', env('APP_URL', 'https://aivatan.com')),
        'layout' => env('SEO_LAYOUT', 'layouts.admin'),
        'header_partial' => env('SEO_HEADER_PARTIAL', 'admin.partials.header'),
        // ساختار اجباری پنل وطن: <main class="..."> + هدر مشترک
        'main_class' => 'mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0',
        'route_prefix' => env('SEO_ROUTE_PREFIX', 'admin/seo'),
        'route_name' => 'seo.',
        'middleware' => ['web', 'auth:admin'],
        'admin_guard' => 'admin',
        'timezone' => env('SEO_TIMEZONE', 'Asia/Tehran'),

        // مدل‌های میزبان برای «کشف کلمه از محصولات» و «انتشار مقاله»
        'product_model' => \App\Models\Product::class,
        'product_fields' => [
            'title' => 'name_fa',
            'description' => 'description_fa',
            'category' => 'category',
            'keywords' => 'meta_keywords',
            'slug' => 'slug',
            'status_column' => 'status',
            'active_values' => ['active', 'published'],
        ],
        'product_route' => 'app.product', // اگر روت نام‌دار وجود داشت از آن ساخته می‌شود
        'product_url' => '/product/{slug}', // در غیر این صورت از این الگو
        'article_model' => \App\Models\Article::class,
        'article_category_model' => \App\Models\ArticleCategory::class,
        'article_author_model' => \App\Models\ArticleAuthor::class,
        'article_route' => 'articles.show',
        'article_url' => '/articles/{slug}',
        // انتشار: draft = پیش‌نویس در سیستم مقالات (بازبینی نهایی در پنل مقالات) | published = انتشار مستقیم
        'publish_status' => env('SEO_PUBLISH_STATUS', 'published'),
        'sitemap_url' => '/sitemap.xml',
    ],

    /* ------------------------------------------------------------------ */
    /* هوش مصنوعی — OpenRouter                                            */
    /* ------------------------------------------------------------------ */
    'ai' => [
        // اگر خالی باشد از services.openrouter پروژه (همان کلید و پل کلادفلر) استفاده می‌شود.
        'api_key' => env('OPENROUTER_API_KEY'),
        // کلید اختصاصی سئو با سقف خرج جدا (اختیاری، پیشنهادی) — در OpenRouter: Keys ← Create Key ← Credit limit
        'dedicated_key' => env('SEO_OPENROUTER_API_KEY'),
        'base_urls' => env('SEO_OPENROUTER_BASE_URLS', env('OPENROUTER_BASE_URLS', env('OPENROUTER_BASE_URL'))),
        'gateway_secret' => env('SEO_OPENROUTER_GATEWAY_SECRET', env('OPENROUTER_GATEWAY_SECRET')),
        'timeout' => (int) env('SEO_AI_TIMEOUT', 120),

        /*
         | نقش‌ها → فهرست مدل‌های کاندید (به ترتیب ترجیح).
         | موتور هر روز فهرست مدل‌های زنده‌ی OpenRouter را می‌گیرد و اولین مدل موجود
         | را انتخاب می‌کند؛ پس اگر مدلی بازنشسته شد، سیستم نمی‌خوابد.
         | پروفایل بودجه (budget-tiers.php) می‌تواند این فهرست را برای هر سایت عوض کند.
         */
        'roles' => [
            // بررسی‌شده با فهرست زنده‌ی OpenRouter در ۱۸ مهر ۱۴۰۵ (قیمت: دلار به ازای میلیون توکن ورودی/خروجی)
            'fast' => ['anthropic/claude-haiku-5.5', 'openai/gpt-6-luna', 'google/gemini-2.5-flash-lite', 'qwen/qwen3.7-flash'],          // 0.1/0.5 — فارسی عالی، ارزان
            'strategist' => ['anthropic/claude-sonnet-5.5', '~anthropic/claude-sonnet-latest', 'openai/gpt-6-sol', 'anthropic/claude-haiku-5.5'], // 2/10
            'writer' => ['anthropic/claude-sonnet-5.5', '~anthropic/claude-sonnet-latest', 'openai/gpt-6-sol', 'anthropic/claude-haiku-5.5'],     // 2/10 — نگارش فارسی
            'research' => ['x-ai/grok-4.7', '~x-ai/grok-latest', 'x-ai/grok-4.3', 'openai/gpt-4o-mini'],                                          // 2/6 + جستجوی وب
            'research_lite' => ['x-ai/grok-4.3', 'x-ai/grok-4.20', '~x-ai/grok-latest', 'openai/gpt-4o-mini'],                                    // 1.25/2.5 — برای بودجه‌ی کم
            'free' => ['google/gemma-4-31b-it:free', 'nvidia/nemotron-3-super-120b-a12b:free', 'thinkingmachines/inkling:free', 'apodex/apodex-1.1-mini:free'],
        ],
        // قیمت تقریبی برای پیش‌برآورد قبل از تماس (دلار به ازای یک میلیون توکن).
        // هزینه‌ی واقعی همیشه از پاسخ OpenRouter (usage.cost) ثبت می‌شود.
        'fallback_price_per_million' => ['in' => 3.0, 'out' => 12.0],
        // ذخیره‌ی ۱۵٪ از سقف ماهانه برای کارهای حیاتی (گزارش و هشدار)
        'budget_reserve_ratio' => 0.15,
    ],

    /* ------------------------------------------------------------------ */
    /* منابع داده                                                         */
    /* ------------------------------------------------------------------ */
    'google' => [
        // فایل JSON سرویس‌اکانت گوگل (مسیر نسبی به storage/app یا مطلق). در تنظیمات پنل هم قابل آپلود است.
        'service_account_path' => env('SEO_GOOGLE_SA_PATH', 'seo-engine/google-service-account.json'),
        'gsc_property' => env('SEO_GSC_PROPERTY'), // مثل sc-domain:aivatan.com
        'ga4_property' => env('SEO_GA4_PROPERTY'), // مثل properties/123456789
        'pagespeed_key' => env('SEO_PAGESPEED_KEY'), // اختیاری؛ بدون کلید هم با سهمیه‌ی کم کار می‌کند
        // اگر سرور به گوگل دسترسی مستقیم ندارد، آدرس Worker در cloudflare/seo-gateway
        'gateway_url' => env('SEO_GOOGLE_GATEWAY_URL'),
        'gateway_secret' => env('SEO_GOOGLE_GATEWAY_SECRET'),
        'country' => 'irn',
        'language' => 'fa',
    ],

    'dataforseo' => [
        'login' => env('SEO_DATAFORSEO_LOGIN'),
        'password' => env('SEO_DATAFORSEO_PASSWORD'),
        'location_code' => (int) env('SEO_DATAFORSEO_LOCATION', 2364), // ایران
        'language_code' => 'fa',
    ],

    'mizfa' => [
        // میزفا تولز: ردیاب رتبه‌ی گوگل ایران + حجم جستجوی فارسی (ارتقای آینده)
        'api_key' => env('SEO_MIZFA_API_KEY'),
    ],

    'indexnow' => [
        'key' => env('SEO_INDEXNOW_KEY'),
    ],

    /* ------------------------------------------------------------------ */
    /* تلگرام — بات اختصاصی سئو                                            */
    /* ------------------------------------------------------------------ */
    'telegram' => [
        'bot_token' => env('SEO_TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('SEO_TELEGRAM_BOT_USERNAME', 'seovatanai_bot'),
        'webhook_secret' => env('SEO_TELEGRAM_WEBHOOK_SECRET'),
        'api_base' => env('SEO_TELEGRAM_API_BASE', 'https://api.telegram.org'),
    ],

    /* ------------------------------------------------------------------ */
    /* خزنده (Crawler) و محدودیت‌ها                                        */
    /* ------------------------------------------------------------------ */
    'crawler' => [
        'user_agent' => 'VatanSeoBot/1.0 (+https://aivatan.com)',
        'timeout' => 15,
        'concurrency_delay_ms' => 250,
        // مسیرهایی که خزیده نمی‌شوند (صفحات حساب کاربری، ادمین، سبد خرید)
        'exclude' => '#/(login|register|logout|password|admin|cart|checkout|account|profile)(/|$|\?)#i',
        // پارامترهای مجاز در URL (بقیه‌ی آدرس‌های دارای ? خزیده نمی‌شوند تا نسخه‌ی تکراری ساخته نشود)
        'allowed_query' => ['page'],
    ],

    // تیک زمان‌بند: هر چند دقیقه سناریوهای سررسیدشده اجرا شوند
    'tick_every_minutes' => 5,
];
