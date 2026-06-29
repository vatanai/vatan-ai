<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FrontPromptController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DashboardController;



// ─── Root ────────────────────────────────────────────────
Route::get('/', fn() => view('site.home'))->name('site.home.root');
Route::get('/site', fn() => redirect('/'));

// ─── Site (Landing) ──────────────────────────────────────
Route::prefix('site')->group(function () {
    Route::get('/pricing', fn() => view('site.pricing'))->name('site.pricing');
    Route::get('/about',   fn() => view('site.about'))->name('site.about');
});

// ─── Auth (User) ─────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login',              [AuthController::class, 'showLogin'])->name('login');
    Route::post('/auth/login',        [AuthController::class, 'loginWithEmail'])->name('auth.login.email');
    Route::post('/auth/send-otp',     [AuthController::class, 'sendOtp'])->name('auth.sendOtp');
    Route::post('/auth/verify-otp',   [AuthController::class, 'verifyOtp'])->name('auth.verifyOtp');
    Route::post('/auth/register-email',[AuthController::class, 'registerWithEmail'])->name('auth.register.email');
});

Route::post('/auth/complete-profile', [AuthController::class, 'completeProfile'])->name('auth.completeProfile');

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
});

// ─── App (UI Pages) ──────────────────────────────────────
Route::prefix('app')->group(function () {
    Route::get('/',             fn() => redirect('/app/home'));
    Route::get('/home',         fn() => view('app.home'))->name('app.home');
    Route::get('/explore',      fn() => view('app.ideas'))->name('app.explore');
    Route::get('/trends',       fn() => view('app.explore'))->name('app.trends');
    Route::get('/create',       fn() => view('app.create'))->name('app.create');
    Route::get('/ideas',        fn() => redirect('/app/explore'));
    Route::get('/profile',      fn() => view('app.profile'))->name('app.profile');
    Route::get('/product/{id}', fn($id) => view('app.product', ['productId' => $id]))->name('app.product');
});

// ─── Prompts (AI Image Generation) ───────────────────────
Route::get('/prompts/{id}',          [FrontPromptController::class, 'show'])->name('prompts.show');
Route::post('/prompts/{id}/generate',[FrontPromptController::class, 'generateImage'])->name('prompts.generate');

// ─── Generation Status ────────────────────────────────────
Route::get('/generation/{id}/status', [GenerationController::class, 'checkStatus'])->name('generation.status');

// ─── Admin Auth ───────────────────────────────────────────
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login',  [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});

// ─── Admin Panel ──────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // داشبورد (SPA — section از URL تشخیص داده میشه توسط JS)
    Route::get('/dashboard/{section?}', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->where('section', '[a-z0-9]+');

    // مدیریت پرامپت‌ها (CRUD)
    Route::resource('prompts', AdminPromptController::class)->except(['show']);

    // مدیریت کاربران
    Route::get('/users',                [UserController::class, 'index'])->name('users.index');
    Route::get('/users/all-logs',       [UserController::class, 'allLogs'])->name('users.all_logs');
    Route::get('/users/all-activities', [UserController::class, 'allActivities'])->name('users.all_activities');
    Route::get('/users/{id}/logs',      [UserController::class, 'logs'])->name('users.logs');

    // صفحات UI ادمین (موحسن)
    Route::get('/crm',             fn() => view('admin.crm'))->name('crm');
    Route::get('/products',            fn() => view('admin.products'))->name('products');
    Route::get('/products/create',     fn() => view('admin.products-create'))->name('products.create');
    Route::get('/products/dashboard',  fn() => view('admin.products-dashboard'))->name('products.dashboard');
    Route::get('/products/categories', fn() => view('admin.products-categories'))->name('products.categories');
    Route::get('/products/pricing',    fn() => view('admin.products-pricing'))->name('products.pricing');
    Route::get('/products/{id}',       fn($id) => view('admin.products-show', ['productId' => $id]))->name('products.show');
    Route::get('/products/{id}/edit',  fn($id) => view('admin.products-edit',  ['productId' => $id]))->name('products.edit');
    Route::get('/orders',              fn() => view('admin.orders'))->name('orders');
    Route::get('/orders/analytics',    fn() => view('admin.orders.analytics'))->name('orders.analytics');
    Route::get('/analytics',           fn() => view('admin.analytics'))->name('analytics');
    Route::get('/jobs',            fn() => view('admin.jobs'))->name('jobs');
    Route::get('/payments',        fn() => view('admin.payments'))->name('payments');
    Route::get('/bloggers',        fn() => view('admin.bloggers'))->name('bloggers');
    Route::get('/bloggers/commission', fn() => view('admin.bloggers.commission'))->name('bloggers.commission');
    Route::get('/bloggers/traffic',    fn() => view('admin.bloggers.traffic'))->name('bloggers.traffic');
    Route::get('/bloggers/{id}',       fn($id) => view('admin.bloggers.show', ['bloggerId' => $id]))->name('bloggers.show');
    // کاربران - صفحات جدید
    Route::get('/users/smart-lists',   fn() => view('admin.users.smart-lists'))->name('users.smart_lists');
    Route::get('/users/tokens',        fn() => view('admin.users.tokens'))->name('users.tokens');

    // ─── آپدیت در آینده: داشبورد نظارتی ───
    Route::get('/dashboard/stats',  fn() => view('admin.dashboard.stats'))->name('dashboard.stats');
    Route::get('/dashboard/daily',  fn() => view('admin.dashboard.daily'))->name('dashboard.daily');
    Route::get('/dashboard/alerts', fn() => view('admin.dashboard.alerts'))->name('dashboard.alerts');

    // ─── آپدیت در آینده: تیکت‌ها ───
    Route::get('/tickets',             fn() => view('admin.tickets.index'))->name('tickets.index');
    Route::get('/tickets/processing',  fn() => view('admin.tickets.index'))->name('tickets.processing');
    Route::get('/tickets/ai-response', fn() => view('admin.tickets.index'))->name('tickets.ai_response');
    Route::get('/tickets/report',      fn() => view('admin.tickets.index'))->name('tickets.report');

    // ─── آپدیت در آینده: پیام‌رسانی ───
    Route::get('/messages',           fn() => view('admin.messages.index'))->name('messages.index');
    Route::get('/messages/bulk',      fn() => view('admin.messages.index'))->name('messages.bulk');
    Route::get('/messages/scheduled', fn() => view('admin.messages.index'))->name('messages.scheduled');
    Route::get('/messages/history',   fn() => view('admin.messages.index'))->name('messages.history');

    // ─── آپدیت در آینده: بنر و نمایش ───
    Route::get('/banners',           fn() => view('admin.banners.index'))->name('banners.index');
    Route::get('/banners/popups',    fn() => view('admin.banners.index'))->name('banners.popups');
    Route::get('/banners/discounts', fn() => view('admin.banners.index'))->name('banners.discounts');

    // ─── آپدیت در آینده: مالی (sub-items) ───
    Route::get('/payments/manual',         fn() => view('admin.payments'))->name('payments.manual');
    Route::get('/payments/commission',     fn() => view('admin.payments'))->name('payments.commission');
    Route::get('/payments/revenue-report', fn() => view('admin.payments'))->name('payments.revenue_report');
    Route::get('/payments/forecast',       fn() => view('admin.payments'))->name('payments.forecast');

    // ─── آپدیت در آینده: آنالیز (sub-items) ───
    Route::get('/analytics/behavior',  fn() => view('admin.analytics'))->name('analytics.behavior');
    Route::get('/analytics/bloggers',  fn() => view('admin.analytics'))->name('analytics.bloggers');
    Route::get('/analytics/campaigns', fn() => view('admin.analytics'))->name('analytics.campaigns');
    Route::get('/analytics/retarget',  fn() => view('admin.analytics'))->name('analytics.retarget');
    Route::get('/analytics/viral',     fn() => view('admin.analytics'))->name('analytics.viral');

    // ─── آپدیت در آینده: گزارش‌ساز ───
    Route::get('/reports', fn() => view('admin.reports'))->name('reports');

    // ─── آپدیت در آینده: زیرساخت ───
    Route::get('/infrastructure',          fn() => view('admin.infrastructure.index'))->name('infrastructure.index');
    Route::get('/infrastructure/queue',    fn() => view('admin.infrastructure.index'))->name('infrastructure.queue');
    Route::get('/infrastructure/ai-cost',  fn() => view('admin.infrastructure.index'))->name('infrastructure.ai_cost');
    Route::get('/infrastructure/logs',     fn() => view('admin.infrastructure.index'))->name('infrastructure.logs');

    // ─── آپدیت در آینده: محتوا ───
    Route::get('/content',                fn() => view('admin.content.index'))->name('content.index');
    Route::get('/content/pages',          fn() => view('admin.content.index'))->name('content.pages');
    Route::get('/content/media',          fn() => view('admin.content.index'))->name('content.media');
    Route::get('/content/notifications',  fn() => view('admin.content.index'))->name('content.notifications');

    // ─── آپدیت در آینده: حضور و غیاب ───
    Route::get('/crm/attendance', fn() => view('admin.attendance'))->name('crm.attendance');

    // ─── آپدیت در آینده: تنظیمات (sub-pages) ───
    Route::get('/settings/admins',           fn() => view('admin.settings.admins'))->name('settings.admins');
    Route::get('/settings/access',           fn() => view('admin.settings.access'))->name('settings.access');
    Route::get('/settings/system',           fn() => view('admin.settings.system'))->name('settings.system');
    Route::get('/settings/payment-gateway',  fn() => view('admin.settings.payment-gateway'))->name('settings.payment_gateway');
    Route::get('/settings/backup',           fn() => view('admin.settings.backup'))->name('settings.backup');
    Route::get('/settings/logs',             fn() => view('admin.settings.logs'))->name('settings.logs');

   Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
});
