<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'site.page' => \App\Http\Middleware\ApplySitePageSettings::class,
        ]);

        $middleware->validateCsrfTokens(except: ['webhooks/ai/*']);

        // مدیریت هوشمند هدایت کاربران مهمان (احراز هویت نشده) بر اساس آدرس درخواست
        $middleware->redirectGuestsTo(function (Request $request) {
            // اگر آدرس درخواست مربوط به بخش ادمین بود
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }
            
            // ریدایرکت پیش‌فرض برای سایر کاربران سایت
            return route('login');
        });

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || ($request->isMethod('post') && $request->is('admin/lab/*')),
        );

        // ═══ ترمیم خودکار کش خرابِ Blade ═══
        // روی هاست‌هایی مثل لیارا که «storage» روی یک دیسک دائمی مونت می‌شود، فایل‌های
        // کامپایل‌شده‌ی Blade (storage/framework/views) و/یا OPcache می‌توانند بین دیپلوی‌ها
        // قدیمی/ناهماهنگ با سورس فعلی بمانند و همین باعث خطای
        // "ParseError ... syntax error, unexpected token @" می‌شود، حتی وقتی خودِ فایل blade
        // درست است. اینجا وقتی دقیقاً همین نوع خطا رخ بدهد، به‌صورت خودکار کش ویوها و کانفیگ
        // پاک و OPcache ریست می‌شود و (فقط برای GET) همان آدرس یک‌بار خودکار دوباره لود
        // می‌شود — یعنی کاربر چیزی جز رفرش شدن خودکار صفحه نمی‌بیند.
        // اگر بعد از این ترمیم خودکار باز همان خطا تکرار شد، یعنی مشکل واقعاً یک باگ سینتکسی
        // در فایل blade است، نه کش قدیمی — و پارامتر __viewcache_recovered=1 در آدرس از
        // حلقه‌ی بی‌نهایت جلوگیری می‌کند.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->expectsJson() || ($request->isMethod('post') && $request->is('admin/lab/*'))) {
                report($e);

                return response()->json([
                    'message' => 'اجرای آزمایش به‌دلیل یک خطای داخلی شروع نشد. لطفاً دوباره تلاش کنید.',
                ], 500);
            }

            $root = $e;
            while ($root->getPrevious()) {
                $root = $root->getPrevious();
            }

            $isStaleViewCacheError = $root instanceof \ParseError
                && str_contains($root->getFile(), 'views');

            if (! $isStaleViewCacheError
                || ! $request->isMethod('get')
                || $request->boolean('__viewcache_recovered')) {
                return null;
            }

            try {
                \Illuminate\Support\Facades\Artisan::call('view:clear');
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                \Illuminate\Support\Facades\Artisan::call('route:clear');
            } catch (\Throwable $ignored) {
                // اگر خودِ پاک‌سازی هم خطا داد، بگذار همان صفحه‌ی خطای معمولی نمایش داده شود
                return null;
            }

            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }

            \Illuminate\Support\Facades\Log::warning('Stale Blade view cache auto-recovered.', [
                'url' => $request->fullUrl(),
                'original_error' => $root->getMessage(),
            ]);

            return redirect($request->fullUrlWithQuery(['__viewcache_recovered' => 1]));
        });
    })->create();
