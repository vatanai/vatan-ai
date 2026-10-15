<?php
/*
| Vatan Content Factory — بارگذار مستقل (مثل packages/vatan-seo)
| اگر composer dump-autoload زده نشده باشد، کلاس‌های Vatan\Content را مستقیم از src بارگذاری می‌کند.
*/
spl_autoload_register(static function (string $class): void {
    $prefix = 'Vatan\\Content\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__.'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_file($file)) {
        require $file;
    }
});
