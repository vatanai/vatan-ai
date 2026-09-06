<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\AiCatalogSyncService;
use App\Services\Finance\FinanceSyncService;
use App\Services\Payments\PlanPaymentService;
use App\Models\Product;
use App\Services\TelegramDeepLinkService;
use App\Services\ProductBackupService;
use App\Services\SiteBackupService;
use App\Jobs\CleanupExpiredUserGalleryItems;
use App\Jobs\GenerateUserGallerySuggestions;

Schedule::command('credits:sync')
    ->everyMinute()
    ->withoutOverlapping(10);

Schedule::command('site:backup')->dailyAt('03:20')->withoutOverlapping(60);

Schedule::job(new CleanupExpiredUserGalleryItems())
    ->dailyAt('03:40')
    ->withoutOverlapping(30);

Schedule::job(new GenerateUserGallerySuggestions())
    ->dailyAt('04:10')
    ->withoutOverlapping(30);

Artisan::command('user-gallery:cleanup', function () {
    $this->info('حذف تصاویر منقضی‌شده گالری شخصی شروع شد.');
    dispatch_sync(new CleanupExpiredUserGalleryItems());
    $this->info('پاک‌سازی گالری شخصی انجام شد.');
})->purpose('حذف فایل‌ها و رکوردهای منقضی‌شده گالری شخصی کاربران');

Artisan::command('products:backup', function (ProductBackupService $backups) {
    $path = $backups->create();
    $this->info('بک‌آپ محصولات ساخته شد: ' . basename($path));
})->purpose('ساخت بک‌آپ نسخه‌دار از محصولات و رسانه‌های آن‌ها');

Artisan::command('site:backup', function (SiteBackupService $backups) {
    $path = $backups->create(['all']);
    $this->info('پشتیبان کامل سایت ساخته شد: ' . basename($path));
})->purpose('ساخت پشتیبان کامل از داده‌های کاربردی و فایل‌های سایت');

Schedule::call(function (): void {
    $payments = app(PlanPaymentService::class);
    $payments->reconcileUnfinishedPurchases();
    $payments->expireStalePurchases();
})
    ->name('plans-expire-pending-payments')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10);

Artisan::command('plans:expire-pending-payments', function (PlanPaymentService $payments) {
    $reconciled = $payments->reconcileUnfinishedPurchases();
    $count = $payments->expireStalePurchases();
    $this->info("{$reconciled['attempted']} سفارش بازبینی شد؛ {$reconciled['completed']} پرداخت بازیابی و {$count} سفارش پرداخت‌نشده منقضی شد.");
})->purpose('منقضی‌کردن سفارش‌های رهاشده در درگاه پرداخت');

Artisan::command('ai:sync-catalog {provider=all}', function (string $provider, AiCatalogSyncService $syncer) {
    $this->info('همگام‌سازی کاتالوگ مدل‌های عکس و ویدیو شروع شد.');
    $result = $syncer->sync($provider);
    foreach ($result as $name => $stats) {
        $this->line($name . ': ' . json_encode($stats, JSON_UNESCAPED_UNICODE));
    }
})->purpose('همگام‌سازی مدل‌های عکس و ویدیو از OpenRouter، Fal.ai و Replicate');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('finance:sync', function (FinanceSyncService $sync) {
    $counts = $sync->syncExisting();
    $this->info("همگام‌سازی مالی کامل شد: {$counts['purchases']} خرید، {$counts['orders']} سفارش، {$counts['provider_requests']} اجرای مدل.");
})->purpose('ساخت یا بروزرسانی اسنپ‌شات‌های مالی داده‌های فعلی');

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

Artisan::command('telegram:product-link {product} {source=channel} {--channel=} {--campaign=} {--message=}', function (
    string $product,
    string $source,
    TelegramDeepLinkService $deepLinks,
) {
    $model = Product::query()->where('status', 'active')
        ->where(fn ($query) => $query->where('route_slug', $product)->orWhere('slug', $product))
        ->firstOrFail();
    $this->line($deepLinks->productLink($model, $source, [
        'channel' => $this->option('channel'),
        'campaign' => $this->option('campaign'),
        'message' => $this->option('message'),
    ]));
})->purpose('ساخت لینک مخفی deep link برای دکمه‌های محصول در کانال تلگرام');

Artisan::command('telegram:product-manager {action} {--telegram-id=} {--name=} {--admin-id=} ', function (string $action) {
    $action = strtolower(trim($action));
    if ($action === 'list') {
        App\Models\TelegramProductManager::query()->orderBy('id')->get()->each(function ($manager): void {
            $this->line(sprintf('%s | %s | %s | %s', $manager->id, $manager->name, $manager->telegram_id ?: 'بدون شناسه', $manager->is_active ? 'فعال' : 'غیرفعال'));
        });
        return;
    }
    if (! in_array($action, ['add', 'disable', 'enable'], true)) {
        $this->error('عملیات مجاز: list، add، enable، disable');
        return 1;
    }
    $telegramId = (int) $this->option('telegram-id');
    $manager = App\Models\TelegramProductManager::query()->where('telegram_id', $telegramId)->first();
    if (! $manager && $action === 'add') {
        if ($telegramId < 1 || trim((string) $this->option('name')) === '') {
            $this->error('برای add، گزینه‌های --telegram-id و --name الزامی هستند.');
            return 1;
        }
        $manager = App\Models\TelegramProductManager::query()->create([
            'telegram_id' => $telegramId,
            'name' => trim((string) $this->option('name')),
            'admin_id' => $this->option('admin-id') ? (int) $this->option('admin-id') : null,
            'is_active' => true,
        ]);
    }
    if (! $manager) {
        $this->error('مدیر با این شناسه پیدا نشد.');
        return 1;
    }
    $manager->forceFill(['is_active' => $action !== 'disable'])->save();
    $this->info($action === 'add' ? 'مدیر ثبت محصول اضافه شد.' : 'وضعیت مدیر ثبت محصول تغییر کرد.');
})->purpose('افزودن و مدیریت مدیران مجاز ثبت محصول در تلگرام');
