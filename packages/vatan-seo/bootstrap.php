<?php

/*
| Vatan SEO Engine — بارگذار مستقل
| اگر پروژه‌ی میزبان هنوز `composer dump-autoload` نزده باشد، این فایل کلاس‌های
| فضای نام Vatan\Seo را مستقیم از پوشه‌ی src بارگذاری می‌کند تا نصب پکیج بدون
| هیچ دستور اضافه‌ای کار کند. با وجود ورودی psr-4 در composer.json میزبان، این
| بارگذار عملاً بی‌اثر است (کلاس‌ها قبلاً پیدا شده‌اند).
*/

spl_autoload_register(static function (string $class): void {
    $prefix = 'Vatan\\Seo\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__.'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;
    }
});
