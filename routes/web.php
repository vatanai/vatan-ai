<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FrontPromptController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\ProductGenerateController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\AiTestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\PlanController;  
use App\Http\Controllers\PlanSubscriptionController;


// ─── Root & Landing ──────────────────────────────────────
Route::get('/', fn() => view('site.home'))->name('site.home.root');
Route::get('/site', fn() => redirect('/'));

Route::prefix('site')->group(function () {
Route::get('/pricing', [App\Http\Controllers\PlanSubscriptionController::class, 'index'])->name('pricing.index');
    Route::get('/about',   fn() => view('site.about'))->name('site.about');
});

// ─── User Authentication ──────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    
    // ۱. چک کردن وضعیت شماره تلفن قبل از رفتن به مرحله OTP
    Route::post('/auth/check-phone', [AuthController::class, 'checkPhone'])->name('auth.checkPhone');
    
    // ۲. ارسال نهایی فرم ورود
    Route::post('/auth/login-submit', [AuthController::class, 'loginSubmit'])->name('auth.login.submit');
    
    // ۳. ارسال نهایی فرم ثبت نام
    Route::post('/auth/register-submit', [AuthController::class, 'registerSubmit'])->name('auth.register.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
   Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/auth/forgot-send-otp', [AuthController::class, 'sendResetOtp']);
Route::post('/auth/forgot-verify-otp', [AuthController::class, 'verifyResetOtp']); // مسیر جدید تایید کد
Route::post('/auth/forgot-verify-reset', [AuthController::class, 'verifyAndResetPassword']);
});

// مسیرهای مخصوص کاربرانی که لاگین کرده‌اند
Route::middleware('auth')->group(function () {
    // تکمیل اطلاعات پروفایل (نام و فامیل) بعد از تایید OTP ثبت‌نام
    Route::post('/auth/complete-profile', [AuthController::class, 'completeProfile'])->name('auth.completeProfile');
// کد اشتباه شما
Route::get('/profile', [ProfileController::class, 'index'])->middleware('auth')->name('profile');
Route::get('/my-gallery', [ProfileController::class, 'gallery'])->name('profile.gallery');
  Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
  Route::post('/pricing/fake-payment/{plan}', [App\Http\Controllers\PlanSubscriptionController::class, 'fakePayment'])->name('pricing.fakePayment');
});
Route::post('/auth/complete-profile', [AuthController::class, 'completeProfile'])->name('auth.completeProfile');

Route::middleware('auth')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('logout');
    
    // اکشن پرداخت الکی و افزایش توکن
    Route::post('/pricing/fake-payment/{plan}', [PlanSubscriptionController::class, 'fakePayment'])->name('pricing.fakePayment');
});

// ─── App Pages & Generation ──────────────────────────────
Route::prefix('app')->group(function () {
    Route::get('/',             fn() => redirect('/app/home'));
Route::get('/home', [HomeController::class, 'index'])->name('app.home');    Route::get('/explore',      fn() => view('app.ideas'))->name('app.explore');
    Route::get('/trends',       fn() => view('app.explore'))->name('app.trends');
    Route::get('/create',       [ProductGenerateController::class, 'create'])->name('app.create');
    Route::get('/profile',      fn() => view('app.profile'))->name('app.profile');
    Route::get('/product/{product:slug}', [ProductGenerateController::class, 'show'])->name('app.product');
    Route::post('/product/{product:slug}/generate', [ProductGenerateController::class, 'generate'])->name('app.product.generate');
});

Route::get('/prompts/{id}',          [FrontPromptController::class, 'show'])->name('prompts.show');
Route::post('/prompts/{id}/generate',[FrontPromptController::class, 'generateImage'])->name('prompts.generate');
Route::get('/generation/{id}/status', [GenerationController::class, 'checkStatus'])->name('generation.status');

// ─── Admin Authentication (Guest) ────────────────────────
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login',  [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});

// ─── Admin Panel Area (Protected) ────────────────────────
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {

    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
Route::resource('plans',PlanController::class);
Route::post('ai-models/{aiModel}/test-image', [AiTestController::class, 'testImage'])
    ->name('ai-models.test-image');
Route::post('ai-models/test-prompt', [AiTestController::class, 'testPrompt'])
    ->name('ai-models.test-prompt');

    // داشبورد مرکزی
    Route::get('/dashboard/{section?}', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->where('section', '[a-z0-9]+');

    // مدیریت پرامپت‌ها
    Route::resource('prompts', AdminPromptController::class)->except(['show']);

    // مدیریت کاربران
    Route::get('/users',                [UserController::class, 'index'])->name('users.index');
    Route::get('/users/all-logs',       [UserController::class, 'allLogs'])->name('users.all_logs');
    Route::get('/users/all-activities', [UserController::class, 'allActivities'])->name('users.all_activities');
    Route::get('/users/{id}/logs',      [UserController::class, 'logs'])->name('users.logs');

    // صفحات UI ادمین
    Route::get('/crm',                 fn() => view('admin.crm'))->name('crm');
    Route::get('/products',            fn() => view('admin.products.index'))->name('products');
    Route::get('/products/create',     fn() => view('admin.products.create'))->name('products.create');
    Route::get('/products/dashboard',  fn() => view('admin.products.products-dashboard'))->name('products.dashboard');
    Route::get('/products/categories', fn() => view('admin.products.products-categories'))->name('products.categories');
    Route::get('/products/pricing',    fn() => view('admin.products.products-pricing'))->name('products.pricing');
    Route::get('/products/{id}',       fn($id) => view('admin.products.products-show', ['productId' => $id]))->name('products.show');
    Route::get('/products/{id}/edit',  fn($id) => view('admin.products.products-edit',  ['productId' => $id]))->name('products.edit');
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
=======
    // مدیریت محصولات (بدون تداخل با روت‌های کلوزر قدیمی)
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/dashboard',  fn() => view('admin.products-dashboard'))->name('products.dashboard');
    Route::get('/products/categories', fn() => view('admin.products-categories'))->name('products.categories');
    Route::get('/products/pricing',    fn() => view('admin.products-pricing'))->name('products.pricing');
>>>>>>> bc98e98 (...)
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // مدیریت مدل‌های هوش مصنوعی (OpenRouter)
   Route::resource('ai-models', AiModelController::class)->names('ai-models');

    // بقیه بخش‌های فرانت پنل ادمین
    Route::get('/crm',              fn() => view('admin.crm'))->name('crm');
    Route::get('/crm/attendance',   fn() => view('admin.attendance'))->name('crm.attendance');
    Route::get('/orders',           fn() => view('admin.orders'))->name('orders');
    Route::get('/orders/analytics', fn() => view('admin.orders.analytics'))->name('orders.analytics');
    Route::get('/analytics',        fn() => view('admin.analytics'))->name('analytics');
    Route::get('/jobs',             fn() => view('admin.jobs'))->name('jobs');
    Route::get('/payments',         fn() => view('admin.payments'))->name('payments');
    Route::get('/bloggers',         fn() => view('admin.bloggers'))->name('bloggers');

    // تنظیمات زیرسیستم‌ها
    Route::get('/settings/admins',           fn() => view('admin.settings.admins'))->name('settings.admins');
    Route::get('/settings/system',           fn() => view('admin.settings.system'))->name('settings.system');
});