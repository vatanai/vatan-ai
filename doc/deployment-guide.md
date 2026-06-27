# راهنمای کامل دیپلوی — پروژه AIVATAN

---

## مشخصات پروژه

| آیتم | مقدار |
|------|-------|
| Framework | Laravel 13 |
| PHP | 8.3 |
| Platform لیارا | Docker |
| Database | MySQL 8 |
| App ID لیارا | `aivatan` |
| DB ID لیارا | `aivatan-db` |
| شبکه خصوصی | `project-net` |
| آدرس پروداکشن | https://aivatan.com |

---

## تنظیمات لیارا (یک‌بار انجام شده — دست نزن)

### متغیرهای محیطی (Environment Variables)
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:32jIgGhEsC3jaIMY4Mq2/obi6KVcQCig08205UGBkoQ=
APP_URL=https://aivatan.com
LOG_CHANNEL=errorlog
SESSION_DRIVER=cookie
CACHE_STORE=file
DB_CONNECTION=mysql
DB_HOST=aivatan-db
DB_DATABASE=focused_williams
DB_USERNAME=root
DB_PASSWORD=5nctswamfEbAaVlMsTIum4jF
DB_PORT=3306
```

### تنظیمات پلتفرم
- **فایل‌سیستم Read Only:** غیرفعال ✓ (مهم — اگه فعال بشه سایت 500 میده)
- **شبکه خصوصی:** project-net ✓

---

## فایل‌های مهم پروژه

### `liara.json`
```json
{
  "port": 80,
  "app": "aivatan",
  "platform": "docker",
  "deploy": {
    "command": "php artisan migrate --force"
  }
}
```
> ⚠️ هرگز `platform` رو به `laravel` تغییر نده — سرور لیارا به GitHub دسترسی نداره و build fail میشه.

### `Dockerfile`
```dockerfile
FROM php:8.3-apache
RUN a2enmod rewrite headers
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip libpng-dev libjpeg-dev libwebp-dev \
    libxml2-dev libonig-dev && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_mysql mbstring zip xml bcmath opcache
RUN echo '<VirtualHost *:80>\n    DocumentRoot /var/www/html/public\n    <Directory /var/www/html/public>\n        AllowOverride All\n        Require all granted\n    </Directory>\n</VirtualHost>' > /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
COPY . .
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache
EXPOSE 80
CMD ["apache2-foreground"]
```

### `.gitignore` — نکته مهم
خط `/vendor` باید **کامنت** باشه تا vendor هنگام دیپلوی آپلود بشه:
```
#/vendor
```

### `.liaraignore`
```
node_modules
.git
npm-debug.log
.env
```

---

## دیپلوی روی لیارا

```bash
cd "/Users/mohsenmac/01. mohsen/VATAN WEB/01. vatan ai/website/ai-vatan-v4"
liara deploy
```

> - از ترمینال مک یا VS Code بزن — از VS Code Extension استفاده نکن
> - حجم آپلود حدود ۳۰ مگ هست (vendor داخلشه) — نرماله
> - بعد از deploy، لیارا خودش `php artisan migrate --force` رو اجرا می‌کنه

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
| خطای ۵۰۰ بعد از دیپلوی | در داشبورد لیارا، Read Only filesystem رو چک کن — باید غیرفعال باشه |
| `platform: laravel` در خطا | مطمئن شو `liara.json` داره `platform: docker` |
| composer timeout | نرمال نیست — لیارا به GitHub دسترسی نداره، باید Docker platform بمونه |
| `{{variable}}` در Blade خطا میده | بنویس `@{{variable}}` تا Blade اون رو PHP تفسیر نکنه |
| تغییرات CSS/JS اعمال نشده | `npm run build` بزن، بعد دیپلوی کن |

---

## چک‌لیست قبل از هر دیپلوی

- [ ] فایل‌های diagnostic مثل `health.php` از `public/` حذف شده
- [ ] `APP_DEBUG=false` توی لیارا
- [ ] `liara.json` روی `platform: docker` هست
- [ ] `/vendor` در `.gitignore` کامنته (`#/vendor`)
- [ ] CSS/JS با `npm run build` ساخته شده
