<?php

use App\Http\Controllers\Admin\HomeBuilder\HomeBuilderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Home Builder Routes
|--------------------------------------------------------------------------
| همه‌ی روت‌های بخش «مدیریت صفحه هوم» اینجا هستند — کاملاً جدا از routes/web.php.
| این فایل توسط App\Providers\HomeBuilderServiceProvider لود می‌شود.
| prefix: /admin/home-builder | middleware: web + auth:admin
*/

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/home-builder')
    ->name('admin.home-builder.')
    ->group(function () {

        Route::get('/', [HomeBuilderController::class, 'index'])->name('index');

        Route::post('/', [HomeBuilderController::class, 'store'])->name('store');
        Route::put('/{homeSection}', [HomeBuilderController::class, 'update'])->name('update');
        Route::delete('/{homeSection}', [HomeBuilderController::class, 'destroy'])->name('destroy');

        Route::post('/{homeSection}/duplicate', [HomeBuilderController::class, 'duplicate'])->name('duplicate');
        Route::patch('/{homeSection}/status', [HomeBuilderController::class, 'updateStatus'])->name('status.update');

        Route::post('/reorder', [HomeBuilderController::class, 'reorder'])->name('reorder');

        // جستجوی محصولات برای فیلد «انتخاب دستی» در تنظیمات Section
        Route::get('/products/search', [HomeBuilderController::class, 'searchProducts'])->name('products.search');
    });
