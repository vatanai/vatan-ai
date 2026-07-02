#!/bin/bash
set -e

# ساخت دایرکتوری‌های storage اگه وجود نداشتن (مخصوصاً اولین دیپلوی)
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# symlink پوبلیک storage
if [ ! -L public/storage ]; then
    ln -sf /var/www/html/storage/app/public public/storage
fi

# پرمیشن‌ها
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# migrate و seed
php artisan migrate --force
php artisan db:seed --force

# اجرای Apache
exec apache2-foreground
