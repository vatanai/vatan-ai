# CLAUDE.md — کارخانه‌ی محتوای وطن (packages/vatan-content)

پکیج مستقل (الگوی `packages/vatan-seo`) برای سیستم یادگیرنده‌ی تولید محتوای اینستاگرام. سند مرجع: `doc/video creator/instagram-content-intelligence-proposal.md`.

## قوانین
- فقط `var(--...)` از `design-tokens.css`؛ هیچ hex ثابت. کلاس‌ها با پیشوند `cf-`.
- نقاط اتصال به میزبان فقط: `bootstrap/providers.php` (require + Provider)، `composer.json` (psr-4)، یک خط `@includeIf('cf::partials.sidebar')` داخل «استودیو تولید» در `sidebar-menu.blade.php`.
- همه‌ی جدول‌ها پیشوند `cf_` دارند و `workspace_id` (فعلاً ۱ = وطن) تا بعداً برای هر مشتری کسب‌وکار کار کند.
- تصمیم‌های ادمین (وضعیت سبک، مجوز موسیقی، تأیید تولید، قفل تقویم) را همگام‌سازی هرگز بازنویسی نمی‌کند.
- تقویم قفل‌شده قابل ویرایش نیست؛ تغییر = نسخه‌ی جدید. تأیید تقویم برای هر ردیف یک «تولید» می‌سازد.
- پرامپت فعال فقط با تأیید ادمین عوض می‌شود (پیش‌نویس ← فعال؛ نسخه‌ی قبلی بایگانی و قابل بازگشت).
- بعد از هر تغییر: `packages/vatan-content/VERSION` + `CHANGELOG.md` + «ورژن داشبورد» در `VERSION` ریشه.

## جریان داده
`content-lab/` (مک، فایل‌های حجیم، خارج از git) ← `php artisan content:sync-lab` ← دیتابیس + پیش‌نمایش‌ها در دیسک public (`content-factory/`).
- `00-inbox` ← `_system/tools/ingest.py` (ffprobe، برش، فریم، کانتکت‌شیت، رنگ، ریتم) ← `_system/cache/<slug>/meta.json`
- `01-styles/styles.json` · `05-calendar/calendar-v*.json` · `06-productions/<code>/manifest.json`
- رندر: `content-lab/_system/render` (Remotion: `BeforeAfterReel`، `TextMotionReel`، `Story`، `Cover`).

## تست
`DB_DATABASE=<sqlite> vendor/bin/phpunit packages/vatan-content/tests`
