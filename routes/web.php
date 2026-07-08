<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FrontPromptController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\ProductGenerateController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\PromptController as AdminPromptController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\AiTestController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\PlanController;  
use App\Http\Controllers\PlanSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController; // استفاده از کنترلر ادمین در پوشه Admin
use App\Http\Controllers\Admin\CategoryController;

// ─── Root & Landing ──────────────────────────────────────
Route::get('/', fn() => view('site.home'))->name('site.home.root');
Route::get('/site', fn() => redirect('/'));

Route::prefix('site')->group(function () {
    Route::get('/pricing', [PlanSubscriptionController::class, 'index'])->name('pricing.index');
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
    Route::post('/auth/forgot-verify-otp', [AuthController::class, 'verifyResetOtp']); 
    Route::post('/auth/forgot-verify-reset', [AuthController::class, 'verifyAndResetPassword']);
});

// مسیرهای مخصوص کاربرانی که لاگین کرده‌اند
Route::middleware('auth')->group(function () {
    // تکمیل اطلاعات پروفایل (نام و فامیل) بعد از تایید OTP ثبت‌نام
    Route::post('/auth/complete-profile', [AuthController::class, 'completeProfile'])->name('auth.completeProfile');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::get('/my-gallery', [ProfileController::class, 'gallery'])->name('profile.gallery');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/pricing/fake-payment/{plan}', [PlanSubscriptionController::class, 'fakePayment'])->name('pricing.fakePayment');
});

// ─── App Pages & Generation ──────────────────────────────
Route::prefix('app')->group(function () {
    Route::get('/',             fn() => redirect('/app/home'));
    Route::get('/home', [HomeController::class, 'index'])->name('app.home');    
    Route::get('/explore',      fn() => view('app.ideas'))->name('app.explore');
    Route::get('/trends',       fn() => view('app.explore'))->name('app.trends');
    Route::get('/create',       [ProductGenerateController::class, 'create'])->name('app.create');
    Route::get('/profile',      [ProfileController::class, 'index'])->name('app.profile');
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
// مسیرهای کامل CRUD دسته‌بندی
    Route::resource('categories', CategoryController::class);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
Route::resource('plans', PlanController::class);

Route::post('ai-models/{aiModel}/test-image', [AiTestController::class, 'testImage'])->name('ai-models.test-image');
    Route::post('ai-models/test-prompt', [AiTestController::class, 'testPrompt'])->name('ai-models.test-prompt');

    // داشبورد مرکزی
    Route::get('/dashboard/{section?}', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->where('section', '[a-z0-9]+');

    // مدیریت پرامپت‌ها
    Route::resource('prompts', AdminPromptController::class)->except(['show']);

    // مدیریت کاربران (متصل شده به کنترلر ادمین در پوشه Admin)
    Route::get('/users',                [AdminUserController::class, 'index'])->name('users.index');
Route::post('/users/{id}/status', [App\Http\Controllers\Admin\AdminUserController::class, 'changeStatus'])->name('admin.users.changeStatus');
    Route::get('/users/all-logs',       [AdminUserController::class, 'allLogs'])->name('users.all_logs');
    Route::get('/users/all-activities', [AdminUserController::class, 'allActivities'])->name('users.all_activities');
    Route::get('/users/{id}/logs',      [AdminUserController::class, 'logs'])->name('users.logs');
    Route::get('/users/tokens',         [AdminUserController::class, 'tokens'])->name('users.tokens'); // پشتیبانی از دکمه مدیریت توکن قالب شما

    // مدیریت محصولات
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/dashboard',  fn() => view('admin.products-dashboard'))->name('products.dashboard');
    Route::get('/products/categories', fn() => view('admin.products-categories'))->name('products.categories');
    Route::get('/products/pricing',    fn() => view('admin.products-pricing'))->name('products.pricing');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // قابلیت‌های حرفه‌ای لیست محصولات
    Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle_status');
    Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulk_action');

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

    // صفحه‌ی جایگزین برای بخش‌هایی که هنوز فایل بک‌اند/روت ندارند
    Route::get('/{any}', fn ($any = null) => view('admin.coming-soon'))
        ->where('any', '.*')
        ->name('coming-soon');
});