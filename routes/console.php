<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\AiCatalogSyncService;

Schedule::command('credits:sync')
    ->everyMinute()
    ->withoutOverlapping(10);

Artisan::command('ai:sync-catalog {provider=all}', function (string $provider, AiCatalogSyncService $syncer) {
    $this->info('همگام‌سازی کاتالوگ مدل‌های عکس و ویدیو شروع شد.');
    $result = $syncer->sync($provider);
    foreach ($result as $name => $stats) {
        $this->line($name . ': ' . json_encode($stats, JSON_UNESCAPED_UNICODE));
    }
})->purpose('همگام‌سازی مدل‌های عکس و ویدیو از Fal.ai و Replicate');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * شماره نسخه‌ی داشبورد (ردیف «ورژن داشبورد» توی فایل مشترک VERSION، نمایش داده‌شده
 * در هدر پنل به شکل V.<عدد>) را یک واحد افزایش می‌دهد. طبق قانون پروژه (CLAUDE.md،
 * بند ۹)، این دستور باید بعد از هر تغییری روی resources/views/admin/** یا
 * layouts/admin.blade.php اجرا بشه تا بعد از دیپلوی بشه فهمید کد جدید واقعاً روی
 * سایت اومده یا نه. عدد جلوی «ورژن داشبورد» رو می‌شه دستی هم توی فایل VERSION
 * عوض کرد — تغییر بلافاصله توی هدر پنل هم اعمال می‌شه.
 */
Artisan::command('admin:bump-version', function () {
    $path = base_path('VERSION');
    $content = is_file($path) ? file_get_contents($path) : '';

    $current = 0;
    if (preg_match('/ورژن داشبورد\s*:\s*(\d+)/u', $content, $matches)) {
        $current = (int) $matches[1];
    }
    $next = $current + 1;

    if (preg_match('/ورژن داشبورد\s*:\s*\d+/u', $content)) {
        $content = preg_replace('/(ورژن داشبورد\s*:\s*)\d+/u', '${1}' . $next, $content, 1);
    } else {
        $content = rtrim($content, "\n") . "\nورژن داشبورد:   {$next}\n";
    }

    file_put_contents($path, $content);

    $this->info("نسخه داشبورد از {$current} به {$next} افزایش یافت.");
})->purpose('یک واحد به شماره نسخه‌ی داشبورد (ردیف «ورژن داشبورد» در فایل VERSION) اضافه می‌کند');
