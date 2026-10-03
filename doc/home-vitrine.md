# ویترین اپ هوم (Home Builder — انواع `vt_*`)

سیستم نمایشی یکدست اپ هوم، سبک هیگزفیلد. تأیید ترکیب: ۱۱ مهر ۱۴۰۵. ورژن داشبورد ۶۶۱.

## قوانین ظاهری
- فقط سه نوع کارت: کارت محصول ۳:۴ (`partials/vt-card`)، کارت ویدیو ۹:۱۶، کاشی ابزار/دسته.
- یک هاور: زوم نرم تصویر + دکمه لیمویی «همین رو بساز» (فقط دسکتاپ).
- برچسب‌ها خودکار: پرطرفدار (`is_trending`)، جدید (`is_new`)، ویدیو (`media_type`).
- رنگ فقط از توکن‌های `--vt-*` (`doc/app-site-theme.md`)؛ مقدار دوم `var()` فقط برای پیش‌نمایش داشبورد.
- تصاویر `<img loading="lazy">`؛ ویدیوها فقط هنگام هاور/دیده‌شدن منبع می‌گیرند.

## فایل‌ها
- تعریف انواع و فیلدها: `config/home_builder.php` (اول فهرست، برچسب «ویترین ·»)
- داده: `app/Services/HomeBuilder/HomeSectionRenderService.php` (بخش «ویترین»)
- قالب‌ها: `resources/views/app/home-builder/sections/vt_*.blade.php` و `sections/partials/vt-*.blade.php`
- استایل و رفتار: `public/css/home-vitrine.css` و `public/js/home-vitrine.js` (از `partials/styles` لود می‌شوند، در پیش‌نمایش داشبورد هم)
- چیدمان اولیه: `database/migrations/home-builder/2026_10_03_000001_apply_vitrine_home_layout.php`
- تست: `tests/Feature/HomeVitrineTest.php`

## انواع
| نوع | کار | تنظیم مهم |
|---|---|---|
| `vt_hero` | هیرو ۱ بزرگ + ۲ کوچک؛ موبایل اسلایدی | `placement=top` بالای جست‌وجو می‌نشیند و تیتر خوشامد را برمی‌دارد |
| `vt_tools` | کاشی دسته‌ها | `tile_overrides`: «نام دسته \| عنوان \| برچسب»؛ چیپ‌های زیر جست‌وجو را مخفی می‌کند |
| `vt_row` | ردیف محصول (`default` یا `marquee`) | `avoid_duplicates` |
| `vt_tabs` | تب دسته + ردیف | تب با محصول کمتر از `min_products_per_tab` مخفی |
| `vt_before_after` | مقایسه کشیدنی | «قبل» = اولین `before_images` محصول؛ بدون آن کارت ساخته نمی‌شود |
| `vt_video_row` | ویدیوهای ۹:۱۶ | کمتر از `min_items` ویدیو ← سکشن نمایش داده نمی‌شود |
| `vt_cta_banner` | بنر دعوت | سه محصول برای کلاژ |
| `vt_occasions` | کارت دسته با تعداد | `min_products` |
| `vt_masonry` | موزاییک + «مشاهده همه :count محصول» | `sort=random` |

حذف تکرار: `vt_row` و `vt_masonry` محصولاتی را که سکشن‌های ویترین بالاتر نشان داده‌اند حذف می‌کنند.

## ترتیب فعال‌شده
هیرو (بالا) · جست‌وجو · ابزارها · ترندها · صنف (تب) · قبل/بعد · ویدیو · بنر کسب‌وکار · استوری و پست (پیوسته) · مناسبت‌ها · پرتره (تب) · همه ایده‌ها.
تا وقتی کمتر از ۴ محصول ویدیویی فعال هست، سکشن قبلی «ویدیوی داستانی» به‌جای `vt_video_row` می‌ماند و `vt_video_row` پیش‌نویس است.

## برگشت
سکشن‌های قبلی حذف نشده‌اند (مخفی‌اند، وضعیت قبلی در `settings._vitrine_prev`).
برگشت کامل: `php artisan migrate:rollback --path=database/migrations/home-builder/2026_10_03_000001_apply_vitrine_home_layout.php --force`

## رفع جانبی
`Product::scopeHideUnavailableProductModes` حالا کاربر را از گارد `web` می‌خواند؛ قبلاً در پنل مدیریت (گارد admin) پیش‌نمایش سکشن‌های محصول خطای ۵۰۰ می‌داد.
