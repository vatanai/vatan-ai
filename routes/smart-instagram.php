<?php

/*
| «اینستاگرام هوشمند» — استودیو تولید › اینستاگرام هوشمند
| این فایل از routes/web.php (پیش از گروه ادمین و روت catch-all آن) require می‌شود.
| آدرس عمداً admin/smart-instagram است تا با منوی قدیمی admin/instagram* تداخل نداشته باشد.
*/

use App\Http\Controllers\Admin\SmartInstagram\AutomationController;
use App\Http\Controllers\Admin\SmartInstagram\ConnectionController;
use App\Http\Controllers\Admin\SmartInstagram\ContactController;
use App\Http\Controllers\Admin\SmartInstagram\ContentController;
use App\Http\Controllers\Admin\SmartInstagram\DashboardController;
use App\Http\Controllers\Admin\SmartInstagram\HealthController;
use App\Http\Controllers\Admin\SmartInstagram\InboxController;
use App\Http\Controllers\Admin\SmartInstagram\KnowledgeController;
use App\Http\Controllers\Admin\SmartInstagram\PipelineController;
use App\Http\Controllers\Admin\SmartInstagram\ReportController;
use App\Http\Controllers\SmartInstagram\IngestWebhookController;
use Illuminate\Support\Facades\Route;

// ورودی امضاشده‌ی n8n / Composio (CSRF در bootstrap/app.php مستثنی شده است)
Route::post('/webhooks/smart-instagram/ingest', IngestWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.smart-instagram.ingest');

Route::prefix('admin/smart-instagram')
    ->name('admin.smart-instagram.')
    ->middleware('auth:admin')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        // صندوق گفتگو
        Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
        Route::get('/inbox/poll', [InboxController::class, 'poll'])->name('inbox.poll');
        Route::get('/inbox/{conversation}', [InboxController::class, 'show'])->whereNumber('conversation')->name('inbox.show');
        Route::post('/inbox/{conversation}/reply', [InboxController::class, 'reply'])->middleware('throttle:60,1')->name('inbox.reply');
        Route::post('/inbox/{conversation}/note', [InboxController::class, 'note'])->name('inbox.note');
        Route::patch('/inbox/{conversation}', [InboxController::class, 'update'])->name('inbox.update');
        Route::post('/inbox/{conversation}/analyze', [InboxController::class, 'analyze'])->middleware('throttle:20,1')->name('inbox.analyze');
        Route::post('/outbound/{outbound}/manual-sent', [InboxController::class, 'markManualSent'])->name('outbound.manual');
        Route::post('/suggestions/{suggestion}/review', [InboxController::class, 'reviewSuggestion'])->name('suggestions.review');
        Route::get('/attachments/{attachment}', [InboxController::class, 'attachment'])->name('attachments.show');
        Route::post('/attachments/{attachment}', [InboxController::class, 'attachmentAction'])->name('attachments.action');

        // مشتریان و لیدها
        Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/{contact}', [ContactController::class, 'show'])->name('contacts.show');
        Route::patch('/contacts/{contact}', [ContactController::class, 'update'])->name('contacts.update');
        Route::post('/contacts/{contact}/notes', [ContactController::class, 'note'])->name('contacts.notes');
        Route::post('/contacts/{contact}/tags', [ContactController::class, 'tags'])->name('contacts.tags');
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy'])->name('contacts.destroy');

        // قیف فروش و وظایف
        Route::get('/pipeline', [PipelineController::class, 'index'])->name('pipeline');
        Route::post('/deals', [PipelineController::class, 'storeDeal'])->name('deals.store');
        Route::patch('/deals/{deal}', [PipelineController::class, 'updateDeal'])->name('deals.update');
        Route::post('/tasks', [PipelineController::class, 'storeTask'])->name('tasks.store');
        Route::patch('/tasks/{task}', [PipelineController::class, 'updateTask'])->name('tasks.update');

        // اتومیشن‌ها
        Route::get('/automations', [AutomationController::class, 'index'])->name('automations.index');
        Route::get('/automations/create', [AutomationController::class, 'create'])->name('automations.create');
        Route::post('/automations', [AutomationController::class, 'store'])->name('automations.store');
        Route::get('/automations/{rule}', [AutomationController::class, 'show'])->name('automations.show');
        Route::get('/automations/{rule}/edit', [AutomationController::class, 'edit'])->name('automations.edit');
        Route::put('/automations/{rule}', [AutomationController::class, 'update'])->name('automations.update');
        Route::post('/automations/{rule}/status', [AutomationController::class, 'status'])->name('automations.status');
        Route::post('/automations/{rule}/simulate', [AutomationController::class, 'simulate'])->name('automations.simulate');
        Route::delete('/automations/{rule}', [AutomationController::class, 'destroy'])->name('automations.destroy');

        // دانش هوش مصنوعی
        Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
        Route::post('/knowledge/profile', [KnowledgeController::class, 'saveProfile'])->name('knowledge.profile.save');
        Route::post('/knowledge/profile/{profile}/activate', [KnowledgeController::class, 'activateProfile'])->name('knowledge.profile.activate');
        Route::post('/knowledge/playground', [KnowledgeController::class, 'playground'])->middleware('throttle:20,1')->name('knowledge.playground');
        Route::post('/knowledge/sources', [KnowledgeController::class, 'storeSource'])->name('knowledge.sources.store');
        Route::get('/knowledge/sources/{source}', [KnowledgeController::class, 'showSource'])->name('knowledge.sources.show');
        Route::put('/knowledge/sources/{source}', [KnowledgeController::class, 'updateSource'])->name('knowledge.sources.update');
        Route::post('/knowledge/sources/{source}/action', [KnowledgeController::class, 'sourceAction'])->name('knowledge.sources.action');
        Route::delete('/knowledge/sources/{source}', [KnowledgeController::class, 'destroySource'])->name('knowledge.sources.destroy');

        // محتوا، گزارش‌ها، اتصال‌ها، سلامت
        Route::get('/content', ContentController::class)->name('content');
        Route::get('/reports', ReportController::class)->name('reports');
        Route::get('/connections', [ConnectionController::class, 'index'])->name('connections');
        Route::post('/connections/sync', [ConnectionController::class, 'sync'])->name('connections.sync');
        Route::post('/connections/sync-composio', [ConnectionController::class, 'syncComposio'])->name('connections.sync-composio');
        Route::post('/connections/sandbox', [ConnectionController::class, 'storeSandbox'])->name('connections.sandbox');
        Route::post('/connections/settings', [ConnectionController::class, 'settings'])->name('connections.settings');
        Route::post('/connections/team', [ConnectionController::class, 'storeMember'])->name('connections.team.store');
        Route::delete('/connections/team/{member}', [ConnectionController::class, 'destroyMember'])->name('connections.team.destroy');
        Route::post('/connections/{channel}/test', [ConnectionController::class, 'test'])->name('connections.test');
        Route::patch('/connections/{channel}', [ConnectionController::class, 'update'])->name('connections.update');
        Route::get('/health', [HealthController::class, 'index'])->name('health');
        Route::post('/health/events/{event}/reprocess', [HealthController::class, 'reprocess'])->name('health.reprocess');
        Route::post('/health/outbound/{outbound}/retry', [HealthController::class, 'retryOutbound'])->name('health.retry');
    });
