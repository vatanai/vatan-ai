<?php

/*
|--------------------------------------------------------------------------
| پلی‌بوک — سناریوها (کارهای تکراری کاملاً خودکار)
|--------------------------------------------------------------------------
| سناریو تأیید نمی‌خواهد؛ فقط کنترل می‌شود: روشن/خاموش، تغییر زمان، ویرایش
| تنظیمات و دیدن لاگ هر اجرا. زمان‌بندی از دیتابیس خوانده می‌شود (seo_scenarios)
| پس بدون تغییر کد از پنل قابل ویرایش است.
|
| frequency: daily | twice_weekly | weekly | monthly
| at:        ساعت اجرا به وقت تهران (HH:MM)
| weekday:   برای هفتگی — 6=شنبه ... 5=جمعه (استاندارد Carbon: 0=یکشنبه)
| day:       برای ماهانه — روز ماه میلادی
| handler:   کلاس در src/Scenarios
| profile_key: اگر تعریف شود، تناوب از پروفایل بودجه (schedules.*) خوانده می‌شود
*/

return [
    ['key' => 'gsc_sync', 'title' => 'همگام‌سازی روزانه‌ی سرچ کنسول', 'pillar' => 'goals', 'frequency' => 'daily', 'at' => '05:30', 'handler' => 'GscSync',
        'why' => 'کلیک، ایمپرشن، CTR و رتبه‌ی هر کوئری و صفحه را هر روز ذخیره می‌کند (داده‌ی گوگل ۲ تا ۳ روز تأخیر دارد).'],
    ['key' => 'rank_update', 'title' => 'به‌روزرسانی رتبه‌ی کلمات هدف و تشخیص رشد/افت', 'pillar' => 'goals', 'frequency' => 'daily', 'at' => '06:00', 'handler' => 'RankUpdate',
        'config' => ['drop_alert' => 3],
        'why' => 'رتبه‌ی امروز هر کلمه‌ی هدف را ثبت و افت ۳ پله یا بیشتر را فوری در تلگرام هشدار می‌دهد.'],
    ['key' => 'health_check', 'title' => 'پایش سلامت: دسترسی سایت، robots، نقشه‌ی سایت و HTTPS', 'pillar' => 'infrastructure', 'frequency' => 'daily', 'at' => '07:00', 'handler' => 'HealthCheck',
        'why' => 'اگر robots.txt یا نقشه‌ی سایت ناگهان خراب شود، قبل از اینکه گوگل متوجه شود باخبر می‌شوید.'],
    ['key' => 'strategist_review', 'title' => 'استراتژیست هفتگی: برنامه‌ی ۵ اقدام اولویت‌دار هفته', 'pillar' => 'goals', 'frequency' => 'weekly', 'weekday' => 6, 'at' => '08:30', 'handler' => 'StrategistReview',
        'why' => 'هر شنبه داده‌های واقعی (رتبه، کلیک، فرصت‌ها، خطاهای فنی، تسک‌های باز) را تحلیل می‌کند، ۵ اقدام اولویت‌دار هفته را به برنامه اضافه و برای تلگرام می‌فرستد. هر دو هفته دیده‌شدن برند در پاسخ‌های هوش مصنوعی را هم پایش می‌کند.'],
    ['key' => 'daily_plan', 'title' => 'برنامه‌ی کار امروز و خلاصه‌ی صبحگاهی تلگرام', 'pillar' => 'goals', 'frequency' => 'daily', 'at' => '09:00', 'handler' => 'DailyPlan',
        'why' => 'استراتژیست تسک‌های امروز را بر اساس اولویت انتخاب، تسک‌های خودکار را اجرا و خلاصه را ارسال می‌کند.'],
    ['key' => 'audit_runner', 'title' => 'اجرای خودکار بررسی‌های فنی باز', 'pillar' => 'infrastructure', 'frequency' => 'daily', 'at' => '03:30', 'handler' => 'AuditRunner',
        'why' => 'تسک‌های زیرساختی خودکار را بررسی و در صورت قبولی تیک می‌زند؛ موارد رد شده با جزئیات خطا باز می‌مانند.'],
    ['key' => 'crawl_audit', 'title' => 'خزش کامل سایت و ممیزی فنی', 'pillar' => 'infrastructure', 'frequency' => 'weekly', 'weekday' => 6, 'at' => '02:30', 'handler' => 'CrawlAudit', 'profile_key' => 'crawl',
        'why' => 'لینک شکسته، عنوان تکراری، صفحات یتیم، کم‌محتوا و مشکلات canonical را پیدا می‌کند.'],
    ['key' => 'pagespeed', 'title' => 'سنجش سرعت و Core Web Vitals صفحات کلیدی', 'pillar' => 'infrastructure', 'frequency' => 'weekly', 'weekday' => 0, 'at' => '04:00', 'handler' => 'PageSpeedAudit', 'profile_key' => 'pagespeed',
        'why' => 'امتیاز موبایل، LCP، INP و CLS صفحه‌ی اصلی، یک صفحه‌ی محصول و یک مقاله را می‌سنجد.'],
    ['key' => 'opportunities', 'title' => 'کشف فرصت‌ها: کلمات نزدیک صفحه‌ی اول، CTR پایین، کوئری‌های تازه', 'pillar' => 'goals', 'frequency' => 'weekly', 'weekday' => 1, 'at' => '08:00', 'handler' => 'Opportunities',
        'why' => 'سریع‌ترین مسیر رشد: صفحاتی که الان دیده می‌شوند ولی کلیک نمی‌گیرند یا کمی با صفحه‌ی اول فاصله دارند.'],
    ['key' => 'content_pipeline', 'title' => 'خط تولید محتوا: بریف و پیش‌نویس از روی تقویم', 'pillar' => 'goals', 'frequency' => 'twice_weekly', 'weekday' => 6, 'at' => '10:00', 'handler' => 'ContentPipeline',
        'why' => 'تا سقف پروفایل بودجه، از روی کلمات هدف بریف و پیش‌نویس می‌سازد و برای تأیید به تلگرام می‌فرستد.'],
    ['key' => 'weekly_report', 'title' => 'گزارش هفتگی تلگرام', 'pillar' => 'goals', 'frequency' => 'weekly', 'weekday' => 4, 'at' => '18:00', 'handler' => 'WeeklyReport',
        'why' => 'خلاصه‌ی کلیک، رتبه، کلمات برنده/بازنده، کارهای انجام‌شده و هزینه‌ی هوش مصنوعی هفته.'],
    ['key' => 'discovery', 'title' => 'کشف دوره‌ای کلمات کلیدی جدید', 'pillar' => 'goals', 'frequency' => 'monthly', 'day' => 1, 'at' => '05:00', 'handler' => 'Discovery', 'profile_key' => 'discovery',
        'why' => 'محصولات تازه و کوئری‌های جدید سرچ کنسول را به فهرست پیشنهادها اضافه می‌کند.'],
    ['key' => 'cannibalization', 'title' => 'بررسی ماهانه‌ی همنوع‌خواری کلمات', 'pillar' => 'goals', 'frequency' => 'monthly', 'day' => 3, 'at' => '05:00', 'handler' => 'Cannibalization',
        'why' => 'کوئری‌هایی را که چند صفحه‌ی سایت برایشان رقابت می‌کنند پیدا و راه‌حل پیشنهاد می‌کند.'],
    ['key' => 'content_decay', 'title' => 'شناسایی محتوای افت‌کرده و پیشنهاد به‌روزرسانی', 'pillar' => 'goals', 'frequency' => 'monthly', 'day' => 5, 'at' => '05:00', 'handler' => 'ContentDecay',
        'why' => 'صفحاتی که کلیکشان نسبت به ۲۸ روز قبل بیش از ۳۰٪ افت کرده را برای بازنویسی صف می‌کند.'],
];
