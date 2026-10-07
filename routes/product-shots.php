<?php

/*
| «استودیو محصول» — محصول پروداکتی سبک جدید (پک شات).
| این فایل از routes/web.php (پیش از گروه ادمین و روت catch-all آن) require می‌شود.
| همه‌ی روت‌های کاربر پشت middleware فلگ هستند؛ با فلگ خاموش ۴۰۴ می‌دهند.
*/

use App\Http\Controllers\Admin\ProductShotAdminController;
use App\Http\Controllers\ProductShotController;
use App\Http\Middleware\EnsureProductShotsAvailable;
use Illuminate\Support\Facades\Route;

Route::prefix('app/product-shots')
    ->name('app.product-shots.')
    ->middleware(['site.page', 'auth', EnsureProductShotsAvailable::class])
    ->group(function () {
        Route::post('/{product:slug}/preflight', [ProductShotController::class, 'preflight'])
            ->middleware('throttle:20,1')->name('preflight');
        Route::post('/{product:slug}/preflight-set', [ProductShotController::class, 'preflightSet'])
            ->middleware('throttle:12,1')->name('preflight-set');
        Route::post('/{product:slug}/batches', [ProductShotController::class, 'storeBatch'])
            ->middleware('throttle:10,1')->name('batches.store');
        Route::get('/batches/{shotBatch}', [ProductShotController::class, 'showBatch'])->name('batches.show');
        Route::get('/batches/{shotBatch}/download', [ProductShotController::class, 'download'])->name('batches.download');
        Route::post('/batches/{shotBatch}/items/{item}/run', [ProductShotController::class, 'runItem'])
            ->middleware('throttle:30,1')->name('items.run');
    });

Route::prefix('admin/product-shots')
    ->name('admin.product-shots.')
    ->middleware('auth:admin')
    ->group(function () {
        // تنظیمات و کتابخانه همیشه برای ادمین در دسترس است تا بتواند فلگ را روشن کند.
        Route::get('/', [ProductShotAdminController::class, 'index'])->name('index');
        Route::put('/settings', [ProductShotAdminController::class, 'updateSettings'])->name('settings.update');
        Route::post('/library', [ProductShotAdminController::class, 'storeShot'])->name('library.store');
        Route::put('/library/{shot}', [ProductShotAdminController::class, 'updateShot'])->name('library.update');
        Route::patch('/library/{shot}/toggle', [ProductShotAdminController::class, 'toggleShot'])->name('library.toggle');

        // ثبت محصول پروداکتی — فقط وقتی ماژول روشن است
        Route::middleware(EnsureProductShotsAvailable::class . ':admin')->group(function () {
            Route::get('/products/preview/draft', [ProductShotAdminController::class, 'previewDraftProductPage'])->name('products.preview-draft');
            Route::get('/products/create/{product?}', [ProductShotAdminController::class, 'createProduct'])->name('products.create');
            Route::get('/products/{product}/preview-page', [ProductShotAdminController::class, 'previewProductPage'])->name('products.preview-page');
            Route::post('/products', [ProductShotAdminController::class, 'storeProduct'])->name('products.store');
            Route::put('/products/{product}', [ProductShotAdminController::class, 'updateProduct'])->name('products.update');
            Route::post('/preview', [ProductShotAdminController::class, 'preview'])
                ->middleware('throttle:20,1')->name('preview');
            Route::post('/products/{product}/shots/{shot}/sample', [ProductShotAdminController::class, 'saveSample'])->name('products.sample');
        });
    });
