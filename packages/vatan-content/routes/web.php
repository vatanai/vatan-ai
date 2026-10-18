<?php

/*
| کارخانه‌ی محتوای وطن — روت‌ها (پیش از catch-all ادمین میزبان ثبت می‌شوند)
*/

use Illuminate\Support\Facades\Route;
use Vatan\Content\Http\Controllers as C;

$host = config('content-factory.host');

Route::middleware($host['middleware'])
    ->prefix($host['route_prefix'])
    ->name($host['route_name'])
    ->group(function () {
        Route::get('/', C\OverviewController::class)->name('overview');

        Route::get('/calendar', [C\CalendarController::class, 'index'])->name('calendar');
        Route::patch('/calendar/items/{item}', [C\CalendarController::class, 'updateItem'])->name('calendar.item');
        Route::get('/calendar/items/{item}', [C\ItemController::class, 'show'])->name('item');
        Route::get('/calendar/items/{item}/status', [C\ItemController::class, 'status'])->name('item.status');
        Route::post('/calendar/items/{item}/save', [C\ItemController::class, 'save'])->name('item.save');
        Route::post('/calendar/items/{item}/order', [C\ItemController::class, 'build'])->name('item.build');
        Route::post('/calendar/items/{item}/generate', [C\ItemController::class, 'generate'])->name('item.generate');
        Route::post('/calendar/items/{item}/undo', [C\ItemController::class, 'undo'])->name('item.undo');
        Route::post('/calendar/items/{item}/prompt-reset', [C\ItemController::class, 'promptReset'])->name('item.prompt-reset');
        Route::post('/calendar/items/{item}/metrics', [C\ItemController::class, 'metrics'])->name('item.metrics');
        Route::post('/calendar/items/{item}/snapshot', [C\ItemController::class, 'snapshot'])->name('item.snapshot');
        Route::post('/calendar/items/{item}/build', [C\CalendarController::class, 'build'])->name('calendar.build');
        Route::post('/calendar/{calendar}/approve', [C\CalendarController::class, 'approve'])->name('calendar.approve');
        Route::post('/calendar/{calendar}/new-version', [C\CalendarController::class, 'newVersion'])->name('calendar.version');

        Route::get('/productions', [C\ProductionController::class, 'index'])->name('productions');
        Route::get('/productions/feed', [C\ProductionController::class, 'feed'])->name('productions.feed');
        Route::get('/productions/{production}', [C\ProductionController::class, 'show'])->whereNumber('production')->name('productions.show');
        Route::post('/productions/{production}/approve', [C\ProductionController::class, 'approve'])->name('productions.approve');
        Route::post('/productions/{production}/reject', [C\ProductionController::class, 'reject'])->name('productions.reject');
        Route::post('/productions/{production}/edits', [C\ProductionController::class, 'segmentEdit'])->name('productions.edit');
        Route::post('/productions/{production}/published', [C\ProductionController::class, 'published'])->name('productions.published');
        Route::patch('/productions/{production}/caption', [C\ProductionController::class, 'caption'])->name('productions.caption');
        Route::post('/productions/{production}/caption/regenerate', [C\ProductionController::class, 'regenerateCaption'])->name('productions.caption.regen');
        Route::post('/productions/{production}/rebuild', [C\ProductionController::class, 'rebuild'])->name('productions.rebuild');
        Route::post('/productions/{production}/instagram', [C\ProductionController::class, 'instagram'])->name('productions.instagram');

        Route::get('/styles', [C\StyleController::class, 'index'])->name('styles');
        Route::post('/styles', [C\StyleController::class, 'store'])->name('styles.store');
        Route::get('/styles/create', [C\StyleController::class, 'create'])->name('styles.create');
        Route::get('/styles/{style}', [C\StyleController::class, 'show'])->whereNumber('style')->name('styles.show');
        Route::delete('/styles/{style}', [C\StyleController::class, 'destroy'])->whereNumber('style')->name('styles.destroy');
        Route::post('/styles/{style}/duplicate', [C\StyleController::class, 'duplicate'])->whereNumber('style')->name('styles.duplicate');
        Route::delete('/styles/references/{reference}', [C\StyleController::class, 'deleteReference'])->name('styles.reference.delete');
        Route::patch('/styles/{style}', [C\StyleController::class, 'update'])->name('styles.update');
        Route::patch('/styles/references/{reference}', [C\StyleController::class, 'assign'])->name('styles.assign');

        Route::get('/music', [C\MusicController::class, 'index'])->name('music');
        Route::patch('/music/{track}', [C\MusicController::class, 'update'])->name('music.update');

        Route::get('/prompts', [C\PromptController::class, 'index'])->name('prompts');
        Route::post('/prompts', [C\PromptController::class, 'store'])->name('prompts.store');
        Route::post('/prompts/layers', [C\PromptController::class, 'addLayer'])->name('prompts.layer');
        Route::delete('/prompts/layers/{layer}', [C\PromptController::class, 'deleteLayer'])->name('prompts.layer.delete');
        Route::delete('/prompts/{prompt}', [C\PromptController::class, 'destroy'])->whereNumber('prompt')->name('prompts.destroy');
        Route::post('/prompts/{prompt}/activate', [C\PromptController::class, 'activate'])->name('prompts.activate');
        Route::post('/prompts/{prompt}/test', [C\PromptController::class, 'test'])->name('prompts.test');

        Route::get('/insights', [C\InsightController::class, 'index'])->name('insights');
        Route::post('/insights/{insight}/decide', [C\InsightController::class, 'decide'])->name('insights.decide');

        Route::post('/jobs', [C\JobController::class, 'store'])->name('jobs.store');
        Route::get('/jobs/feed', [C\JobController::class, 'feed'])->name('jobs.feed');
        Route::post('/jobs/{job}/cancel', [C\JobController::class, 'cancel'])->name('jobs.cancel');

        Route::get('/settings', [C\SettingsController::class, 'index'])->name('settings');
        Route::post('/settings/sync', [C\SettingsController::class, 'sync'])->name('settings.sync');
        Route::post('/settings/telegram', [C\SettingsController::class, 'telegram'])->name('settings.telegram');
        Route::post('/settings/agent-install', [C\SettingsController::class, 'agentInstall'])->name('settings.agent-install');
        Route::post('/settings/agent-token', [C\SettingsController::class, 'agentToken'])->name('settings.agent-token');

        Route::get('/asset/{file}', C\AssetController::class)->where('file', '[a-z0-9\-]+\.(css|js)')->name('asset');
    });

// API ایجنت مک و وب‌هوک تلگرام (بدون سشن و CSRF)
Route::middleware(['api'])->prefix('api/content-factory')->name('cf.api.')->group(function () {
    Route::post('/agent/hello', [C\AgentController::class, 'hello'])->name('hello');
    Route::post('/agent/next', [C\AgentController::class, 'next'])->name('next');
    Route::post('/agent/jobs/{job}/progress', [C\AgentController::class, 'progress'])->name('progress');
    Route::post('/agent/jobs/{job}/complete', [C\AgentController::class, 'complete'])->name('complete');
    Route::post('/agent/upload', [C\AgentController::class, 'upload'])->name('upload');
    Route::post('/agent/chunk', [C\AgentController::class, 'chunk'])->name('chunk');
    Route::post('/agent/reference', [C\AgentController::class, 'reference'])->name('reference');
    Route::post('/agent/music', [C\AgentController::class, 'music'])->name('music');
    Route::post('/agent/moved', [C\AgentController::class, 'moved'])->name('moved');
    Route::post('/agent/assignments', [C\AgentController::class, 'assignments'])->name('assignments');
    Route::post('/agent/productions/{production}/version', [C\AgentController::class, 'version'])->name('version');
    Route::post('/telegram/{secret}', C\TelegramWebhookController::class)->name('telegram');
});
