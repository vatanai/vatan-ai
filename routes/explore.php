<?php

use App\Http\Controllers\Admin\Explore\ExploreManagementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Explore (موتور فید) Routes
|--------------------------------------------------------------------------
| همه‌ی روت‌های بخش «مدیریت اکسپلور» اینجا هستند — کاملاً جدا از routes/web.php.
| این فایل توسط App\Providers\ExploreServiceProvider لود می‌شود.
| prefix: /admin/explore | middleware: web + auth:admin
*/

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/explore')
    ->name('admin.explore.')
    ->group(function () {

        Route::get('/', [ExploreManagementController::class, 'index'])->name('index');

        // ── تنظیمات نمایش (سبک چیدمان + رندوم بودن + نسبت کمپین) ──
        Route::post('/settings', [ExploreManagementController::class, 'updateSettings'])->name('settings.update');

        // ── کمپین‌ها ──
        Route::post('/campaigns', [ExploreManagementController::class, 'storeCampaign'])->name('campaigns.store');
        Route::put('/campaigns/{campaign}', [ExploreManagementController::class, 'updateCampaign'])->name('campaigns.update');
        Route::patch('/campaigns/{campaign}/toggle', [ExploreManagementController::class, 'toggleCampaign'])->name('campaigns.toggle');
        Route::delete('/campaigns/{campaign}', [ExploreManagementController::class, 'destroyCampaign'])->name('campaigns.destroy');

        // ── آیتم‌های سنجاق‌شده (Pin) ──
        Route::post('/pins', [ExploreManagementController::class, 'storePin'])->name('pins.store');
        Route::delete('/pins/{pin}', [ExploreManagementController::class, 'destroyPin'])->name('pins.destroy');

        // ── بوست دستی محصول ──
        Route::post('/boost', [ExploreManagementController::class, 'updateBoost'])->name('boost.update');
    });
