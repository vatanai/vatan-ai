# استودیو محصول (پک شات) — راهنمای فنی و عملیاتی

> پیاده‌سازی تسک‌های 3.A تا 3.S سند `doc/product-registration-new-style.md`. تاریخ: ۱۰ مهر ۱۴۰۵.
> قانون طلایی رعایت شده: مسیر چهره‌محور هیچ تغییر رفتاری ندارد؛ همه‌ی تغییرات additive و پشت فلگ.

## خلاصه

کاربر ۱ عکس محصول (+ تا ۲ زاویه) می‌دهد ← فیلتر کیفیت رایگان ← «بسته‌ی آماده» شات‌ها (قابل تیک) ← هر شات یک درخواست جدا، با سفارش و رزرو اعتبار مستقل ← کاشی‌های پیش‌رونده ← دانلود تکی/همه.

## فایل‌ها

| بخش | فایل |
|---|---|
| مایگریشن‌ها | `database/migrations/2026_10_02_0900*` (product_mode، shot_library، product_shots، shot_batches، shot_batch_items، product_shot_settings، seed ۸ شات آرایشی) |
| مدل‌ها | `ShotLibrary`, `ProductShot`, `ShotBatch`, `ShotBatchItem`, `ProductShotSetting` + متدهای افزودنی در `Product` |
| سرویس‌ها | `app/Services/ProductShots/`: `ShotGrammar` (زبان شات)، `ShotPromptBuilder` (+ وفاداری محصول)، `ShotVisionService` (فیلتر ورودی و QC)، `ShotImageStore`، `ShotPackService`، `ShotGenerationService`، `ProductShotFeature` (فلگ) |
| کنترلرها | `ProductShotController` (کاربر)، `Admin\ProductShotAdminController` (پنل) |
| روت‌ها | `routes/product-shots.php` (از `web.php` پیش از catch-all ادمین require می‌شود) |
| ویوها | `app/product-pack.blade.php`، `app/partials/pack-card|pack-line-tabs`، `admin/product-shots/*` |
| استایل/اسکریپت | `public/css/product-pack.css`، `public/js/product-pack.js`، `public/admin/css/product-shots.css` (فقط توکن‌های موجود؛ رنگ جدید اضافه نشده) |
| فرمان | `php artisan product-shots:prune` (روزانه ۰۴:۱۰ تهران) |
| تست‌ها | `tests/Feature/ProductShots/*`، `tests/Unit/ProductShots/*` (۳۴ تست) |

## تغییرات کوچک در کد قبلی (همه بی‌اثر وقتی محصول پروداکتی وجود ندارد)

- `ProductGenerateController::create` و `show`: شاخه‌ی `isShotProduct()`؛ پایه‌ی استودیو هرگز محصول پک را انتخاب نمی‌کند (`withoutShotProducts`).
- فهرست‌های عمومی (کاتالوگ، جستجوی هوم، اکسپلور، ترندز، هوم‌بیلدر، سایت‌مپ، «بساز»، مشابه‌ها): اسکوپ `hideUnavailableProductModes()`.
- `Admin\ProductController::create`: محصول پروداکتی به فرم جدید هدایت می‌شود.
- فرم ۵ گامی: دو کارت «چهره‌محور / پروداکتی» فقط وقتی ماژول روشن است.
- سایدبار، breadcrumb و منوی موبایل ادمین: آیتم «استودیو محصول».
- `OpenRouterService::analyzeImagesJson` (متد عمومی جدید).
- `routes/console.php`: زمان‌بندی prune.

## فلگ و عرضه

1. کلید اصلی env: `PRODUCT_SHOTS_ENABLED` (پیش‌فرض true؛ برای قطع اضطراری false کن).
2. پنل: مدیریت محصولات ← استودیو محصول ← «روشن بودن» + مخاطب:
   - **فقط ادمین**: کسی که در همان مرورگر وارد پنل است (پایلوت).
   - **لیست سفید**: ادمین + شناسه/موبایل کاربران (3.Q: ۵ کاربر منتخب).
   - **همه**.
3. پیش‌فرض بعد از مایگریشن: خاموش. با فلگ خاموش، صفحه‌ها ۴۰۴ و فهرست‌ها بدون محصولات پک (تست snapshot سبز).

## نکات عملیاتی

- **هزینه:** سقف روزانه‌ی API (پیش‌فرض ۵ دلار)؛ بعد از سقف ساخت بدون کسر کردیت متوقف می‌شود. کارت «هزینه به درآمد» هشدار بالای ۵۰٪ می‌دهد.
- **QC:** اگر مدل بینایی بگوید محصول عوض شده، یک بار خودکار دوباره می‌سازد؛ کاربر فقط یک بار پرداخت می‌کند (هزینه‌ی واقعی هر دو در `cost_usd` ثبت می‌شود).
- **سرور ۱ گیگی:** هم‌زمانی مرورگر پیش‌فرض ۱. هر درخواست فقط یک شات است (حداکثر ~۱–۲ دقیقه).
- **عکس‌های ورودی:** هر batch نسخه‌ی خودش را دارد؛ پس از اتمام موفق پاک می‌شود و بقیه با prune بعد از ۴۸ ساعت.
- **هندلر خطای JSON:** هندلر سراسری فعلی هر HttpException را برای درخواست JSON به ۵۰۰ تبدیل می‌کند (باگ قبلی، خارج از این تسک). روت‌های این ماژول ۴۰۴ صریح برمی‌گردانند.

## اجرا روی لوکال

```bash
php artisan migrate            # فقط لوکال؛ روی production فقط با تأیید محسن
php artisan test --filter=ProductShot
```

## دیپلوی (فقط با تأیید صریح محسن)

روال `CLAUDE.md`: chmod پرمیشن‌ها ← commit ← push `crm-integration` ← deploy کلودیوا ← `php artisan migrate --force`. بعد از مایگریشن، فلگ خاموش است؛ از پنل روی «فقط ادمین» روشن کن.
