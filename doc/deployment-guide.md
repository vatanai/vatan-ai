# راهنمای کامل دیپلوی — پروژه AIVATAN

---

## سرویس تولیدی کلودیوا

سایت اصلی روی سرویس `vatanai-laravel-cloudiva` در `Cloudiva` با دامنهٔ `aivatan.com` اجرا می‌شود.

### روال دیپلوی

```bash
cd "/Users/mohsenmac/01. mohsen/VATAN WEB/01. vatan ai/website/vatan-ai"
git add -A
git commit -m "توضیح تغییرات"
git push origin crm-integration
npx -y @cloudiva.net/cli deploy --service vatanai-laravel-cloudiva
```

بعد از موفق شدن استقرار، در کنسول تازهٔ همان سرویس اجرا شود:

```bash
php artisan migrate --force
```

---

## مشخصات پروژه

| آیتم | مقدار |
|------|-------|
| Framework | Laravel 13 |
| PHP | 8.3 |
| پلتفرم | Laravel |
| Database | MySQL 8 |
| سرویس | `vatanai-laravel-cloudiva` |
| آدرس پروداکشن | https://aivatan.com |

---

## فایل‌های مهم پروژه

### فایل‌های نادیده‌گرفته‌شدهٔ دیپلوی

فایل‌های سنگین و محرمانه نباید در بستهٔ استقرار قرار بگیرند:

### `.divaignore`
```
node_modules
.git
npm-debug.log
.env
```

---

## Git Workflow

### اولین بار (راه‌اندازی)
```bash
git init
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git add .
git commit -m "Initial commit"
git push -u origin main
```

### هر بار که تغییر دادی
```bash
git add .
git commit -m "توضیح تغییرات"
git push
```

### کلون کردن روی سیستم جدید
```bash
git clone https://github.com/YOUR_USERNAME/YOUR_REPO.git
cd YOUR_REPO
cp .env.example .env
php artisan key:generate
composer install
npm install
npm run build
php artisan migrate
php artisan serve
```

---

## توسعه لوکال

### اجرای پروژه
```bash
# ترمینال ۱ — سرور PHP
php artisan serve

# ترمینال ۲ — Vite (اختیاری، فقط اگه CSS/JS رو تغییر دادی)
npm run dev
```

### بعد از pull کردن تغییرات جدید
```bash
git pull
composer install
npm install
npm run build
php artisan migrate
```

---

## رفع مشکلات رایج

| مشکل | راه‌حل |
|------|--------|
| خطای ۵۰۰ بعد از دیپلوی | لاگ‌های سرویس `vatanai-laravel-cloudiva` و وضعیت فایل‌سیستم را در پنل `Cloudiva` بررسی کن |
| خطای migration | از کنسول تازهٔ سرویس `php artisan migrate --force` را اجرا کن |
| `{{variable}}` در Blade خطا میده | بنویس `@{{variable}}` تا Blade اون رو PHP تفسیر نکنه |
| تغییرات CSS/JS اعمال نشده | `npm run build` بزن، بعد دیپلوی کن |
| خطای CORS رو `localhost:5173` یا `[::1]:5173` توی پروداکشن | فایل `public/hot` (باقی‌مونده از `npm run dev`) رفته بالا؛ آن را حذف کن و در `.divaignore` قرار بده |

---

## چک‌لیست قبل از هر دیپلوی

- [ ] فایل‌های diagnostic مثل `health.php` از `public/` حذف شده
- [ ] `APP_DEBUG=false` در متغیرهای محیطی `Cloudiva`
- [ ] سرویس مقصد `vatanai-laravel-cloudiva` است
- [ ] `.env` و `node_modules` در `.divaignore` هستند
- [ ] CSS/JS با `npm run build` ساخته شده
