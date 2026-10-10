<?php

/*
| Vatan SEO Engine — روت‌ها
| پیشوند و میدل‌ویر از config('seo-engine.host') خوانده می‌شود تا روی هر داشبوردی قابل نصب باشد.
| این فایل در boot پکیج ثبت می‌شود، یعنی قبل از روت catch-all ادمین میزبان.
*/

use Illuminate\Support\Facades\Route;
use Vatan\Seo\Http\Controllers as C;
use Vatan\Seo\Telegram\BotController;

$host = config('seo-engine.host');

Route::middleware($host['middleware'])
    ->prefix($host['route_prefix'])
    ->name($host['route_name'])
    ->group(function () {
        Route::get('/', C\OverviewController::class)->name('overview');

        Route::get('/keywords', [C\KeywordController::class, 'index'])->name('keywords.index');
        Route::post('/keywords', [C\KeywordController::class, 'store'])->name('keywords.store');
        Route::post('/keywords/discover', [C\KeywordController::class, 'discover'])->name('keywords.discover');
        Route::post('/keywords/bulk', [C\KeywordController::class, 'bulk'])->name('keywords.bulk');
        Route::get('/keywords/{keyword}', [C\KeywordController::class, 'show'])->name('keywords.show');
        Route::patch('/keywords/{keyword}', [C\KeywordController::class, 'update'])->name('keywords.update');
        Route::post('/keywords/{keyword}/advice', [C\KeywordController::class, 'advice'])->name('keywords.advice');

        Route::get('/plan', [C\PlanController::class, 'index'])->name('plan');
        Route::patch('/tasks/{task}', [C\PlanController::class, 'update'])->name('tasks.update');
        Route::post('/tasks/{task}/check', [C\PlanController::class, 'check'])->name('tasks.check');
        Route::post('/tasks/check-all', [C\PlanController::class, 'checkAll'])->name('tasks.check-all');
        Route::post('/plan/rebuild', [C\PlanController::class, 'rebuild'])->name('plan.rebuild');

        Route::get('/technical', [C\TechnicalController::class, 'index'])->name('technical');
        Route::post('/technical/llms', [C\TechnicalController::class, 'llms'])->name('technical.llms');
        Route::post('/technical/geo-probe', [C\TechnicalController::class, 'geoProbe'])->name('technical.geo');
        Route::post('/technical/competitors', [C\TechnicalController::class, 'competitors'])->name('technical.competitors');

        Route::get('/content', [C\ContentController::class, 'index'])->name('content.index');
        Route::post('/content/generate', [C\ContentController::class, 'generate'])->name('content.generate');
        Route::get('/content/{item}', [C\ContentController::class, 'show'])->name('content.show');
        Route::post('/content/{item}/publish', [C\ContentController::class, 'publish'])->name('content.publish');
        Route::post('/content/{item}/reject', [C\ContentController::class, 'reject'])->name('content.reject');
        Route::post('/content/{item}/redraft', [C\ContentController::class, 'redraft'])->name('content.redraft');

        Route::get('/scenarios', [C\ScenarioController::class, 'index'])->name('scenarios.index');
        Route::patch('/scenarios/{scenario}', [C\ScenarioController::class, 'update'])->name('scenarios.update');
        Route::post('/scenarios/{scenario}/run', [C\ScenarioController::class, 'run'])->name('scenarios.run');

        Route::get('/activity', C\ActivityController::class)->name('activity');

        Route::get('/settings', [C\SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/site', [C\SettingsController::class, 'saveSite'])->name('settings.site');
        Route::post('/settings/google', [C\SettingsController::class, 'google'])->name('settings.google');
        Route::post('/settings/connector', [C\SettingsController::class, 'connector'])->name('settings.connector');
        Route::post('/settings/telegram/code', [C\SettingsController::class, 'telegramCode'])->name('settings.telegram.code');
        Route::post('/settings/telegram/setup', [C\SettingsController::class, 'telegramSetup'])->name('settings.telegram.setup');
        Route::post('/settings/telegram/test', [C\SettingsController::class, 'telegramTest'])->name('settings.telegram.test');
        Route::post('/settings/test/{service}', [C\SettingsController::class, 'test'])->name('settings.test');

        Route::get('/assets/{file}', C\AssetController::class)->where('file', '[a-z0-9\-]+\.(css|js)')->name('asset');
    });

// وب‌هوک بات تلگرام سئو (بدون CSRF؛ با secret_token تلگرام محافظت می‌شود)
Route::post('/webhooks/seo-telegram', BotController::class)
    ->middleware('throttle:120,1')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name($host['route_name'].'telegram.webhook');

// llms.txt تولیدشده از پنل (فقط اگر فایل فیزیکی در public نباشد این روت دیده می‌شود)
Route::get('/llms.txt', C\PublicFilesController::class.'@llms')->name($host['route_name'].'llms');

// فایل کلید IndexNow
if ($key = config('seo-engine.indexnow.key')) {
    Route::get('/'.$key.'.txt', fn () => response($key, 200, ['Content-Type' => 'text/plain']))->name($host['route_name'].'indexnow');
}
