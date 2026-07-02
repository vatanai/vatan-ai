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
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();