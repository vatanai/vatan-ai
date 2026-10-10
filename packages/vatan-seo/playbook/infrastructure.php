<?php

/*
|--------------------------------------------------------------------------
| پلی‌بوک — ستون ۱: زیرساخت (Technical Foundation)
|--------------------------------------------------------------------------
| تسک‌های «یک‌باره» (Audit) که پایه‌ی فنی سایت را می‌سازند. هر تسک:
|   key        شناسه‌ی ثابت (هرگز عوض نشود؛ نتایج و تاریخچه به آن وصل است)
|   category   crawl | index | onpage | schema | performance | mobile | trust | geo | analytics
|   impact     ۱ تا ۵ — اثر روی رتبه/ترافیک
|   effort     ۱ تا ۵ — زحمت اجرا
|   automation auto (ایجنت خودش بررسی و تیک می‌زند) | assisted (ایجنت پیشنهاد می‌دهد، انسان تأیید/اجرا) | manual
|   check      نام بررسی خودکار در Checks\CheckRegistry (اگر auto/assisted)
|   due_day    روز سررسید از شروع پروژه (برای برنامه‌ی ماه اول)
|   why        چرا مهم است (در راهنمای کلیکی نمایش داده می‌شود)
| اولویت = (impact × 2) − effort + (automation=auto ? 1 : 0)
| منبع: چک‌لیست سئوی ماه اول وطن‌وب (۱۲۵ آیتم) + استانداردهای ۲۰۲۶ (INP، GEO، ربات‌های AI).
*/

return [
    // ── خزش و ایندکس ─────────────────────────────────────────────
    ['key' => 'infra.gsc_connect', 'title' => 'اتصال سرچ کنسول و ثبت مالکیت دامنه', 'category' => 'analytics', 'impact' => 5, 'effort' => 1, 'automation' => 'assisted', 'check' => 'gsc_connected', 'due_day' => 1,
        'why' => 'بدون داده‌ی سرچ کنسول هیچ تصمیم داده‌محوری ممکن نیست؛ رتبه، کلیک و ایندکس از همین‌جا می‌آید.'],
    ['key' => 'infra.ga4_connect', 'title' => 'اتصال Google Analytics 4', 'category' => 'analytics', 'impact' => 3, 'effort' => 1, 'automation' => 'assisted', 'check' => 'ga4_connected', 'due_day' => 2,
        'why' => 'رفتار کاربر بعد از ورود (نرخ تعامل، تبدیل) را نشان می‌دهد تا صفحات «پرکلیک اما بی‌اثر» پیدا شوند.'],
    ['key' => 'infra.robots', 'title' => 'سلامت robots.txt — مسیرهای مهم مسدود نباشند', 'category' => 'crawl', 'impact' => 5, 'effort' => 1, 'automation' => 'auto', 'check' => 'robots', 'due_day' => 1,
        'why' => 'یک خط اشتباه در robots.txt می‌تواند کل سایت را از گوگل حذف کند.'],
    ['key' => 'infra.ai_crawlers', 'title' => 'سیاست ربات‌های هوش مصنوعی (GPTBot، Google-Extended، PerplexityBot و...)', 'category' => 'geo', 'impact' => 3, 'effort' => 1, 'automation' => 'auto', 'check' => 'ai_crawlers', 'due_day' => 5,
        'why' => 'دیده‌شدن در پاسخ‌های ChatGPT و AI Overviews گوگل به اجازه‌ی خزش این ربات‌ها وابسته است.'],
    ['key' => 'infra.sitemap', 'title' => 'نقشه‌ی سایت XML معتبر، فقط صفحات قابل ایندکس و HTTPS', 'category' => 'crawl', 'impact' => 5, 'effort' => 2, 'automation' => 'auto', 'check' => 'sitemap', 'due_day' => 2,
        'why' => 'نقشه‌ی سایت مسیر سریع کشف صفحات جدید است؛ URL خراب یا noindex در آن اعتماد گوگل را کم می‌کند.'],
    ['key' => 'infra.sitemap_submit', 'title' => 'ثبت نقشه‌ی سایت در سرچ کنسول', 'category' => 'index', 'impact' => 4, 'effort' => 1, 'automation' => 'auto', 'check' => 'sitemap_submitted', 'due_day' => 3,
        'why' => 'گوگل از وضعیت پردازش نقشه گزارش می‌دهد و خطاها زود دیده می‌شوند.'],
    ['key' => 'infra.https', 'title' => 'همه‌ی صفحات HTTPS و ریدایرکت ۳۰۱ از HTTP', 'category' => 'trust', 'impact' => 4, 'effort' => 1, 'automation' => 'auto', 'check' => 'https', 'due_day' => 1,
        'why' => 'HTTPS سیگنال رتبه و شرط اعتماد کاربر است؛ نسخه‌ی HTTP نباید جدا ایندکس شود.'],
    ['key' => 'infra.canonical_host', 'title' => 'یکسان‌سازی دامنه (www / بدون www) با ریدایرکت ۳۰۱', 'category' => 'index', 'impact' => 4, 'effort' => 1, 'automation' => 'auto', 'check' => 'canonical_host', 'due_day' => 2,
        'why' => 'دو نسخه از یک دامنه یعنی تقسیم اعتبار و محتوای تکراری.'],
    ['key' => 'infra.canonical_tags', 'title' => 'تگ canonical درست در صفحات اصلی، دسته و محصول', 'category' => 'index', 'impact' => 4, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:canonical', 'due_day' => 5,
        'why' => 'canonical به گوگل می‌گوید نسخه‌ی اصلی کدام است (به‌خصوص برای فیلتر و صفحه‌بندی).'],
    ['key' => 'infra.noindex_audit', 'title' => 'noindex برای صفحات بی‌ارزش (ورود، جستجوی داخلی، فیلترها)', 'category' => 'index', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:noindex', 'due_day' => 7,
        'why' => 'بودجه‌ی خزش صرف صفحات مهم می‌شود و کیفیت کلی سایت در چشم گوگل بالا می‌رود.'],
    ['key' => 'infra.status_codes', 'title' => 'رفع لینک‌های داخلی ۴۰۴ و خطاهای ۵xx', 'category' => 'crawl', 'impact' => 4, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:status', 'due_day' => 5,
        'why' => 'لینک شکسته هم تجربه‌ی کاربر را خراب می‌کند هم اعتبار لینک را هدر می‌دهد.'],
    ['key' => 'infra.redirect_chains', 'title' => 'حذف زنجیره‌ی ریدایرکت و ریدایرکت‌های حلقه‌ای', 'category' => 'crawl', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:redirects', 'due_day' => 7,
        'why' => 'هر پرش اضافه سرعت و اعتبار منتقل‌شده را کم می‌کند.'],
    ['key' => 'infra.index_coverage', 'title' => 'بررسی وضعیت ایندکس صفحات کلیدی (URL Inspection)', 'category' => 'index', 'impact' => 5, 'effort' => 1, 'automation' => 'auto', 'check' => 'gsc_inspect', 'due_day' => 4,
        'why' => 'صفحه‌ای که ایندکس نیست، هیچ‌وقت رتبه نمی‌گیرد؛ باید زود بفهمیم چرا.'],
    ['key' => 'infra.indexnow', 'title' => 'راه‌اندازی IndexNow برای اعلام فوری صفحات جدید', 'category' => 'index', 'impact' => 2, 'effort' => 1, 'automation' => 'assisted', 'check' => 'indexnow', 'due_day' => 10,
        'why' => 'بینگ و یاندکس (و موتورهای هوش مصنوعی مبتنی بر بینگ مثل ChatGPT Search) صفحات جدید را در چند دقیقه می‌بینند.'],

    // ── سئوی داخلی صفحات ─────────────────────────────────────────
    ['key' => 'infra.titles', 'title' => 'عنوان (title) یکتا و ۳۰ تا ۶۰ کاراکتر در همه‌ی صفحات', 'category' => 'onpage', 'impact' => 4, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:titles', 'due_day' => 6,
        'why' => 'عنوان مهم‌ترین سیگنال درون‌صفحه‌ای و اولین چیزی است که کاربر در نتایج می‌بیند.'],
    ['key' => 'infra.meta_desc', 'title' => 'توضیحات متا یکتا و ۷۰ تا ۱۶۰ کاراکتر', 'category' => 'onpage', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:descriptions', 'due_day' => 8,
        'why' => 'رتبه را مستقیم بالا نمی‌برد ولی نرخ کلیک (CTR) را زیاد می‌کند.'],
    ['key' => 'infra.h1', 'title' => 'دقیقاً یک H1 گویا در هر صفحه', 'category' => 'onpage', 'impact' => 3, 'effort' => 1, 'automation' => 'auto', 'check' => 'crawl:h1', 'due_day' => 6,
        'why' => 'H1 موضوع اصلی صفحه را برای گوگل و کاربر روشن می‌کند.'],
    ['key' => 'infra.img_alt', 'title' => 'متن جایگزین (alt) برای تصاویر محتوایی', 'category' => 'onpage', 'impact' => 2, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:alt', 'due_day' => 12,
        'why' => 'برای جستجوی تصویری و دسترس‌پذیری لازم است؛ در سایتی مثل وطن که خروجی تصویری دارد اهمیت بیشتری دارد.'],
    ['key' => 'infra.duplicate_titles', 'title' => 'رفع عنوان‌ها و محتوای تکراری بین صفحات', 'category' => 'onpage', 'impact' => 3, 'effort' => 3, 'automation' => 'auto', 'check' => 'crawl:duplicates', 'due_day' => 12,
        'why' => 'صفحات مشابه با هم رقابت می‌کنند و هیچ‌کدام رتبه‌ی خوب نمی‌گیرند.'],
    ['key' => 'infra.thin_content', 'title' => 'شناسایی صفحات کم‌محتوا (کمتر از ۲۵۰ کلمه) و تقویت یا ادغام', 'category' => 'onpage', 'impact' => 3, 'effort' => 3, 'automation' => 'auto', 'check' => 'crawl:thin', 'due_day' => 15,
        'why' => 'به‌روزرسانی Helpful Content صفحات کم‌ارزش را در سطح کل سایت جریمه می‌کند.'],
    ['key' => 'infra.url_structure', 'title' => 'ساختار URL: کوتاه، خوانا، حروف کوچک، خط تیره و بدون پارامتر اضافه', 'category' => 'onpage', 'impact' => 2, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:urls', 'due_day' => 10,
        'why' => 'URL خوانا CTR را بالا می‌برد و از ایجاد چند نشانی برای یک محتوا جلوگیری می‌کند.'],
    ['key' => 'infra.orphans', 'title' => 'صفحات یتیم: هر صفحه‌ی مهم حداقل یک لینک داخلی ورودی', 'category' => 'onpage', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:orphans', 'due_day' => 14,
        'why' => 'صفحه‌ای که لینک داخلی ندارد دیر کشف می‌شود و اعتبار نمی‌گیرد.'],
    ['key' => 'infra.depth', 'title' => 'عمق کلیک حداکثر ۳ از صفحه‌ی اصلی برای صفحات مهم', 'category' => 'onpage', 'impact' => 3, 'effort' => 3, 'automation' => 'auto', 'check' => 'crawl:depth', 'due_day' => 14,
        'why' => 'هرچه صفحه دورتر باشد، کمتر خزیده می‌شود و اعتبار کمتری می‌گیرد.'],
    ['key' => 'infra.lang_dir', 'title' => 'زبان و جهت صفحه (lang="fa"، dir="rtl") و viewport موبایل', 'category' => 'mobile', 'impact' => 2, 'effort' => 1, 'automation' => 'auto', 'check' => 'crawl:lang', 'due_day' => 3,
        'why' => 'به گوگل کمک می‌کند صفحه را برای کاربر فارسی‌زبان و موبایل درست نمایش دهد.'],
    ['key' => 'infra.og_tags', 'title' => 'تگ‌های Open Graph و تصویر اشتراک‌گذاری', 'category' => 'onpage', 'impact' => 2, 'effort' => 1, 'automation' => 'auto', 'check' => 'crawl:og', 'due_day' => 15,
        'why' => 'ظاهر لینک در تلگرام، واتساپ و شبکه‌های اجتماعی و در نتیجه کلیک را بهتر می‌کند.'],

    // ── داده‌ی ساختاریافته ────────────────────────────────────────
    ['key' => 'infra.schema_org', 'title' => 'اسکیمای Organization و WebSite در صفحه‌ی اصلی', 'category' => 'schema', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'schema:organization', 'due_day' => 8,
        'why' => 'هویت برند را برای گوگل و موتورهای هوش مصنوعی روشن می‌کند (پنل دانش، لوگو).'],
    ['key' => 'infra.schema_product', 'title' => 'اسکیمای Product در صفحات محصول', 'category' => 'schema', 'impact' => 4, 'effort' => 2, 'automation' => 'auto', 'check' => 'schema:product', 'due_day' => 10,
        'why' => 'نتیجه‌ی غنی (قیمت، امتیاز) در گوگل و خوانایی بهتر برای AI.'],
    ['key' => 'infra.schema_article', 'title' => 'اسکیمای Article / BlogPosting با نویسنده و تاریخ', 'category' => 'schema', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'schema:article', 'due_day' => 12,
        'why' => 'برای E-E-A-T و نمایش در Discover و AI Overviews کمک می‌کند.'],
    ['key' => 'infra.schema_breadcrumb', 'title' => 'Breadcrumb و اسکیمای BreadcrumbList', 'category' => 'schema', 'impact' => 2, 'effort' => 2, 'automation' => 'auto', 'check' => 'schema:breadcrumb', 'due_day' => 14,
        'why' => 'مسیر صفحه در نتایج گوگل نمایش داده می‌شود و ساختار سایت روشن‌تر می‌شود.'],

    // ── سرعت و Core Web Vitals ────────────────────────────────────
    ['key' => 'infra.cwv_mobile', 'title' => 'Core Web Vitals موبایل: LCP ≤ ۲.۵ث، INP ≤ ۲۰۰ms، CLS ≤ ۰.۱', 'category' => 'performance', 'impact' => 4, 'effort' => 4, 'automation' => 'auto', 'check' => 'pagespeed:cwv', 'due_day' => 10,
        'why' => 'سیگنال رسمی تجربه‌ی صفحه؛ بیشتر کاربران وطن موبایلی هستند.'],
    ['key' => 'infra.images_modern', 'title' => 'تصاویر با فرمت WebP/AVIF، ابعاد واقعی و Lazy Load', 'category' => 'performance', 'impact' => 4, 'effort' => 3, 'automation' => 'auto', 'check' => 'pagespeed:images', 'due_day' => 12,
        'why' => 'تصاویر بیشترین سهم حجم صفحه را دارند؛ مخصوصاً در سایت تصویرمحور.'],
    ['key' => 'infra.render_blocking', 'title' => 'حذف منابع مسدودکننده‌ی رندر و پیش‌بارگذاری فونت', 'category' => 'performance', 'impact' => 3, 'effort' => 3, 'automation' => 'auto', 'check' => 'pagespeed:render', 'due_day' => 15,
        'why' => 'CSS/JS سنگین در head زمان نمایش اولین محتوا را زیاد می‌کند.'],
    ['key' => 'infra.compression_cache', 'title' => 'فشرده‌سازی Brotli/Gzip و هدرهای کش برای فایل‌های ثابت', 'category' => 'performance', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'headers', 'due_day' => 6,
        'why' => 'بازدید دوم سریع‌تر و مصرف پهنای باند کمتر می‌شود.'],
    ['key' => 'infra.page_weight', 'title' => 'حجم صفحه‌ی موبایل کمتر از ۱.۵ مگابایت', 'category' => 'performance', 'impact' => 3, 'effort' => 3, 'automation' => 'auto', 'check' => 'pagespeed:weight', 'due_day' => 15,
        'why' => 'در اینترنت موبایل ایران، حجم صفحه مستقیم روی نرخ خروج اثر دارد.'],
    ['key' => 'infra.mobile_ux', 'title' => 'اندازه‌ی دکمه‌ها (۴۴px)، فونت ۱۶px و نبود اسکرول افقی در موبایل', 'category' => 'mobile', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'pagespeed:mobile', 'due_day' => 9,
        'why' => 'گوگل نسخه‌ی موبایل را ملاک ایندکس قرار می‌دهد (Mobile-first).'],
    ['key' => 'infra.popups', 'title' => 'پاپ‌آپ مزاحم در موبایل محتوای اصلی را نپوشاند', 'category' => 'mobile', 'impact' => 2, 'effort' => 2, 'automation' => 'manual', 'check' => null, 'due_day' => 5,
        'why' => 'پاپ‌آپ تمام‌صفحه در موبایل سیگنال منفی تجربه‌ی کاربر است.'],

    // ── اعتماد، امنیت و E-E-A-T ────────────────────────────────────
    ['key' => 'infra.security_headers', 'title' => 'هدرهای امنیتی (HSTS، X-Content-Type-Options، Referrer-Policy)', 'category' => 'trust', 'impact' => 2, 'effort' => 1, 'automation' => 'auto', 'check' => 'headers:security', 'due_day' => 10,
        'why' => 'امنیت و اعتماد؛ HSTS از دسترسی ناخواسته به نسخه‌ی HTTP جلوگیری می‌کند.'],
    ['key' => 'infra.trust_pages', 'title' => 'صفحات درباره‌ما، تماس، قوانین و حریم خصوصی با لینک در فوتر', 'category' => 'trust', 'impact' => 3, 'effort' => 2, 'automation' => 'auto', 'check' => 'crawl:trust_pages', 'due_day' => 7,
        'why' => 'از پایه‌های E-E-A-T (اعتمادپذیری) در دستورالعمل ارزیاب‌های کیفیت گوگل.'],
    ['key' => 'infra.author_pages', 'title' => 'صفحه‌ی نویسنده با بیو، تخصص و لینک شبکه‌های اجتماعی', 'category' => 'trust', 'impact' => 3, 'effort' => 2, 'automation' => 'assisted', 'check' => null, 'due_day' => 20,
        'why' => 'نشان‌دادن «تجربه و تخصص» پشت محتوا؛ برای مقالات آموزشی ضروری است.'],
    ['key' => 'infra.not_found', 'title' => 'صفحه‌ی ۴۰۴ سفارشی با کد وضعیت درست و لینک به بخش‌های اصلی', 'category' => 'trust', 'impact' => 2, 'effort' => 1, 'automation' => 'auto', 'check' => 'not_found', 'due_day' => 8,
        'why' => 'کاربر گم‌شده را نگه می‌دارد و از soft-404 جلوگیری می‌کند.'],

    // ── آمادگی جستجوی هوش مصنوعی (GEO) ─────────────────────────────
    ['key' => 'infra.llms_txt', 'title' => 'فایل llms.txt با معرفی برند و مسیرهای اصلی', 'category' => 'geo', 'impact' => 2, 'effort' => 1, 'automation' => 'assisted', 'check' => 'llms_txt', 'due_day' => 18,
        'why' => 'استاندارد نوظهور برای کمک به مدل‌های زبانی در فهم ساختار سایت.'],
    ['key' => 'infra.citability', 'title' => 'ساختار قابل‌نقل: پاسخ مستقیم در ۴۰ تا ۶۰ کلمه‌ی اول، جدول و فهرست', 'category' => 'geo', 'impact' => 3, 'effort' => 3, 'automation' => 'assisted', 'check' => null, 'due_day' => 25,
        'why' => 'موتورهای پاسخ‌گو (AI Overviews، ChatGPT) پاراگراف‌های خودبسنده و دقیق را نقل می‌کنند.'],
];
