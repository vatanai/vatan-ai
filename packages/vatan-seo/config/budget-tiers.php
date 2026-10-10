<?php

/*
|--------------------------------------------------------------------------
| پروفایل‌های بودجه — فایل جامع «مسیر بر اساس مبلغ»
|--------------------------------------------------------------------------
| برای هر سایت فقط یک عدد ثبت می‌شود: «سقف ماهانه‌ی هوش مصنوعی (دلار)».
| موتور بزرگ‌ترین پروفایلی را انتخاب می‌کند که min_usd آن ≤ این عدد باشد و
| همه‌ی رفتارها (مدل‌ها، تعداد مقاله، تحقیق زنده، تعداد کلمه‌ی هدف، عمق خزش،
| تناوب سناریوها و منبع رتبه) خودکار از همان پروفایل خوانده می‌شود.
|
| مثال: وطن ای‌آی = ۲۰ دلار → starter      |  مشتری = ۳۰ دلار → pro
|
| هر سایت می‌تواند هر کلید را در seo_sites.settings['overrides'] بازنویسی کند،
| بدون اینکه این فایل عوض شود. سقف دلاری همیشه سخت (Hard Cap) است:
| BudgetGuard قبل از هر تماس، هزینه‌ی ماه جاری + برآورد تماس را با سقف می‌سنجد.
*/

return [

    'free' => [
        'label' => 'رایگان',
        'min_usd' => 0,
        'description' => 'فقط ابزارهای رایگان و مدل‌های بدون هزینه. تحلیل و پایش کامل، بدون تولید خودکار مقاله.',
        'models' => [
            'fast' => 'free',
            'strategist' => 'free',
            'writer' => 'free',
            'research' => null, // تحقیق زنده‌ی وب خاموش
        ],
        'limits' => [
            'tracked_keywords' => 20,
            'articles_per_month' => 0,      // فقط بریف و پیشنهاد
            'briefs_per_month' => 8,
            'research_calls_per_month' => 0,
            'crawl_pages' => 150,
            'discovery_runs_per_month' => 2,
        ],
        'rank_source' => 'gsc',
        'schedules' => ['crawl' => 'weekly', 'pagespeed' => 'weekly', 'discovery' => 'monthly'],
    ],

    'starter' => [
        'label' => 'پایه — ۲۰ دلار',
        'min_usd' => 5,
        'description' => 'مدل ارزان برای کارهای تکراری، مدل خوب برای نگارش، تحقیق زنده‌ی محدود. مناسب شروع (وطن ای‌آی).',
        'models' => [
            'fast' => 'fast',
            'strategist' => 'fast',
            'writer' => 'writer',
            'research' => 'research_lite',
        ],
        'limits' => [
            'tracked_keywords' => 20,
            'articles_per_month' => 8,
            'briefs_per_month' => 20,
            'research_calls_per_month' => 25,
            'crawl_pages' => 400,
            'discovery_runs_per_month' => 4,
        ],
        'rank_source' => 'gsc',
        'schedules' => ['crawl' => 'weekly', 'pagespeed' => 'weekly', 'discovery' => 'weekly'],
    ],

    'pro' => [
        'label' => 'حرفه‌ای — ۳۰ دلار',
        'min_usd' => 25,
        'description' => 'استراتژیست قوی، مقاله‌ی بیشتر، تحقیق زنده‌ی بیشتر و کلمات هدف بیشتر. مناسب مشتری‌ها.',
        'models' => [
            'fast' => 'fast',
            'strategist' => 'strategist',
            'writer' => 'writer',
            'research' => 'research',
        ],
        'limits' => [
            'tracked_keywords' => 40,
            'articles_per_month' => 14,
            'briefs_per_month' => 30,
            'research_calls_per_month' => 60,
            'crawl_pages' => 800,
            'discovery_runs_per_month' => 6,
        ],
        'rank_source' => 'gsc',
        'schedules' => ['crawl' => 'twice_weekly', 'pagespeed' => 'twice_weekly', 'discovery' => 'weekly'],
    ],

    'growth' => [
        'label' => 'رشد — ۶۰ دلار',
        'min_usd' => 50,
        'description' => 'رتبه‌ی روزانه‌ی دقیق (در صورت اتصال DataForSEO)، تولید محتوای پرحجم و خزش عمیق.',
        'models' => [
            'fast' => 'fast',
            'strategist' => 'strategist',
            'writer' => 'writer',
            'research' => 'research',
        ],
        'limits' => [
            'tracked_keywords' => 100,
            'articles_per_month' => 30,
            'briefs_per_month' => 60,
            'research_calls_per_month' => 150,
            'crawl_pages' => 2000,
            'discovery_runs_per_month' => 10,
        ],
        'rank_source' => 'dataforseo_if_connected',
        'schedules' => ['crawl' => 'daily', 'pagespeed' => 'twice_weekly', 'discovery' => 'twice_weekly'],
    ],
];
