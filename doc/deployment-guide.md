# راهنمای استقرار وطن روی Cloudiva

## مقصد رسمی

سرویس رسمی پروژه `vatanai-laravel-cloudiva` روی `Cloudiva` است و تمام استقرارها باید از پنل یا `CLI` رسمی `Cloudiva/Chabokan` انجام شوند.

## روال استاندارد

```bash
cd "/Users/mohsenmac/01. mohsen/VATAN WEB/01. vatan ai/website/vatan-ai"
git add -A
git commit -m "توضیح تغییرات"
git push origin crm-integration
```

در صورت فعال‌بودن `CI/CD`، push به شاخه‌ی `crm-integration` استقرار را آغاز می‌کند. token استقرار باید فقط در secretهای `CI/CD` نگه‌داری شود.

بعد از پایان استقرار، در یک کنسول تازه‌ی سرویس اجرا شود:

```bash
php artisan migrate --force --no-interaction
```

## بررسی سلامت صف

```bash
php artisan queue:failed
php artisan queue:work database --queue=default --sleep=3 --tries=1 --timeout=900 --memory=256 --max-time=3600
```

صف پردازش و خطاهای آن از مسیر پنل مدیریت `admin/jobs` با داده‌ی واقعی پایگاه داده نمایش داده می‌شوند.
