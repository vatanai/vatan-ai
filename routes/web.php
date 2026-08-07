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
use App\Http\Controllers\AiWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedProductController;
use App\Http\Controllers\Admin\PlanController;  
use App\Http\Controllers\PlanSubscriptionController;
use App\Http\Controllers\Admin\AdminUserController; // استفاده از کنترلر ادمین در پوشه Admin
use App\Http\Controllers\Admin\AdminManagerController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\SmsController;
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\ServiceCreditController;
use App\Http\Controllers\Admin\ReferralSettingController;
use App\Http\Controllers\Admin\LabExperimentController;
use App\Http\Controllers\Admin\Explore\TrendController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ReferralController;

// ─── Root & Landing ──────────────────────────────────────
// صفحه‌ی اصلی عمومی باید برای کاربر واردشده هم قابل مشاهده باشد؛
// ورود به بخش کاربری از دکمه‌های داخل صفحه یا مسیرهای /app انجام می‌شود.
Route::get('/', fn() => view('site.home'))->name('site.home.root');
Route::get('/site', fn() => redirect('/'));
Route::get('/r/{code}', [ReferralController::class, 'visit'])
    ->where('code', '[A-Za-z0-9]{6,20}')
    ->name('referral.visit');
Route::get('/r/{code}/product/{product:route_slug}', [ReferralController::class, 'productVisit'])
    ->where('code', '[A-Za-z0-9]{6,20}')
    ->name('referral.product');

Route::prefix('site')->group(function () {
    Route::get('/pricing', [PlanSubscriptionController::class, 'index'])->name('pricing.index');
    Route::get('/about',   fn() => view('site.about'))->name('site.about');
});

Route::get('/privacy', fn() => view('site.privacy'))->name('privacy');

Route::get('/auth/csrf-token', fn () => response()->json(['token' => csrf_token()]))
    ->name('auth.csrf-token');

// صفحات پروفایل برای مشاهده عمومی هستند؛ عملیات شخصی همچنان احراز هویت می‌خواهد.
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::get('/my-gallery', [ProfileController::class, 'gallery'])->name('profile.gallery');

// ─── User Authentication ──────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    
    // ۱. چک کردن وضعیت شماره تلفن قبل از رفتن به مرحله OTP
    Route::post('/auth/check-phone', [AuthController::class, 'checkPhone'])->name('auth.checkPhone');
    Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->name('auth.otp.send');
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp'])->name('auth.otp.verify');
    
    // ورود کاربران فقط با رمز یک‌بارمصرف انجام می‌شود.
    // ارسال نهایی فرم ثبت نام
    Route::post('/auth/register-submit', [AuthController::class, 'registerSubmit'])->name('auth.register.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/auth/forgot-send-otp', [AuthController::class, 'sendResetOtp']);
    Route::post('/auth/forgot-verify-otp', [AuthController::class, 'verifyResetOtp']); 
    Route::post('/auth/forgot-verify-reset', [AuthController::class, 'verifyAndResetPassword']);
});

// مسیرهای مخصوص کاربرانی که لاگین کرده‌اند
Route::middleware('auth')->group(function () {
    // تکمیل اطلاعات پروفایل (نام و فامیل) بعد از تایید OTP ثبت‌نام
    Route::post('/auth/complete-profile', [AuthController::class, 'completeProfile'])->name('auth.completeProfile');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::post('/app/product/{product:slug}/save', [SavedProductController::class, 'toggle'])->name('app.product.save');
    Route::post('/app/product/{product:slug}/like', [App\Http\Controllers\LikedProductController::class, 'toggle'])->name('app.product.like');
    Route::post('/app/product/{product:slug}/download', [App\Http\Controllers\ProductDownloadController::class, 'store'])->name('app.product.download');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/pricing/fake-payment/{plan}', [PlanSubscriptionController::class, 'fakePayment'])->name('pricing.fakePayment');
});

// ─── App Pages & Generation ──────────────────────────────
// سازگاری با لینک‌های قدیمی سکشن‌ها و بوکمارک‌ها؛ پارامترهای فیلتر حفظ می‌شوند.
Route::get('/products', function () {
    $query = request()->getQueryString();

    return redirect('/app/products' . ($query ? '?' . $query : ''), 301);
})->name('products.legacy');

Route::prefix('app')->group(function () {
    Route::get('/',             fn() => redirect('/app/home'));
    Route::get('/home', [HomeController::class, 'index'])->name('app.home');
    Route::get('/home/search', [HomeController::class, 'search'])->name('app.home.search');
    Route::get('/explore',      [\App\Http\Controllers\Explore\ExploreController::class, 'index'])->name('app.explore');
    Route::get('/trends',       [\App\Http\Controllers\Explore\ExploreController::class, 'trending'])->name('app.trends');
    Route::get('/products',     [ProductCatalogController::class, 'index'])->name('products.index');
    Route::get('/create',       [ProductGenerateController::class, 'create'])->name('app.create');
    Route::get('/create-preview', [ProductGenerateController::class, 'createPreview'])->name('app.create.preview');
    Route::view('/create-architecture', 'app.create-architecture')->name('app.create.architecture');
    Route::get('/create/{product:route_slug}', [ProductGenerateController::class, 'build'])->name('app.create.product');
    Route::post('/create/{product:route_slug}/generate', [ProductGenerateController::class, 'generate'])->middleware('auth')->name('app.create.generate');
    Route::get('/profile',      [ProfileController::class, 'index'])->name('app.profile');
    // لینک تستی صفحه محصول — به جدیدترین محصول فعال ری‌دایرکت می‌شود
    Route::get('/product-details', function () {
        $p = \App\Models\Product::where('status', 'active')->latest()->first();
        return $p
            ? redirect()->route('app.product', $p->route_slug)
            : redirect()->route('app.home');
    })->name('app.product-details');
    Route::get('/product/{product:route_slug}', [ProductGenerateController::class, 'show'])->name('app.product');
    Route::post('/product/{product:slug}/generate', [ProductGenerateController::class, 'generate'])->middleware('auth')->name('app.product.generate');
});

Route::get('/category/{path?}', [ProductCatalogController::class, 'category'])
    ->where('path', '.*')
    ->name('categories.show');

Route::get('/prompts/{id}',          [FrontPromptController::class, 'show'])->name('prompts.show');
Route::post('/prompts/{id}/generate',[FrontPromptController::class, 'generateImage'])->name('prompts.generate');
Route::get('/generation/{id}/status', [GenerationController::class, 'checkStatus'])->name('generation.status');

// وب‌هوک‌های providerها عمومی هستند، اما قبل از پردازش با امضای رسمی provider بررسی می‌شوند.
Route::post('/webhooks/ai/fal', [AiWebhookController::class, 'fal'])->name('webhooks.ai.fal');
Route::post('/webhooks/ai/replicate', [AiWebhookController::class, 'replicate'])->name('webhooks.ai.replicate');

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
Route::post('plans/reorder', [PlanController::class, 'reorder'])->name('plans.reorder');
Route::put('plans/display-settings', [PlanController::class, 'updateDisplay'])->name('plans.display-settings');
Route::post('plans/{plan}/duplicate', [PlanController::class, 'duplicate'])->name('plans.duplicate');
Route::patch('plans/{plan}/archive', [PlanController::class, 'archive'])->name('plans.archive');
Route::resource('plans', PlanController::class)->except('show');

Route::post('ai-models/{aiModel}/test-image', [AiTestController::class, 'testImage'])->name('ai-models.test-image');
    Route::post('ai-models/test-prompt', [AiTestController::class, 'testPrompt'])->name('ai-models.test-prompt');
    Route::get('product-tests/history', [AiTestController::class, 'history'])->name('product-tests.history');
    Route::patch('product-tests/{run}', [AiTestController::class, 'updateRun'])->name('product-tests.update');

    // داشبورد مرکزی
    Route::get('/dashboard/{section?}', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->where('section', '[a-z0-9]+');

    Route::get('/service-credits', [ServiceCreditController::class, 'index'])->name('service-credits.index');
    Route::post('/service-credits/accounts', [ServiceCreditController::class, 'storeAccount'])->name('service-credits.accounts.store');
    Route::put('/service-credits/accounts/{account}', [ServiceCreditController::class, 'updateAccount'])->name('service-credits.accounts.update');
    Route::post('/service-credits/transactions', [ServiceCreditController::class, 'storeTransaction'])->name('service-credits.transactions.store');
    Route::post('/service-credits/refresh', [ServiceCreditController::class, 'refresh'])->name('service-credits.refresh');

    // مدیریت پرامپت‌ها
    Route::resource('prompts', AdminPromptController::class)->except(['show']);

    // مدیریت کاربران (متصل شده به کنترلر ادمین در پوشه Admin)
    Route::get('/users',                [AdminUserController::class, 'index'])->name('users.index');
Route::post('/users/{id}/status', [App\Http\Controllers\Admin\AdminUserController::class, 'changeStatus'])->name('admin.users.changeStatus');
    Route::post('/users/{id}/copy-password', [AdminUserController::class, 'copyPassword'])->name('users.copy-password');
    Route::patch('/users/{id}/customer-segment', [AdminUserController::class, 'changeCustomerSegment'])->name('users.customer-segment');
    Route::get('/users/all-logs',       [AdminUserController::class, 'allLogs'])->name('users.all_logs');
    Route::get('/users/all-activities', [AdminUserController::class, 'allActivities'])->name('users.all_activities');
    Route::get('/users/{id}/logs',      [AdminUserController::class, 'logs'])->name('users.logs');
    Route::get('/users/tokens',         [AdminUserController::class, 'tokens'])->name('users.tokens'); // پشتیبانی از دکمه مدیریت توکن قالب شما

    // مدیریت محصولات
    // توجه: مسیر ویرایش دیگر جدا نیست و کامل از پروژه حذف شده — ویرایش هم از همین صفحه «ثبت محصول»
    // با پارامتر اختیاری محصول انجام می‌شود (مثال: /admin/products/create/52)
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create/{product?}', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/translate-identity-prompt', [ProductController::class, 'translateIdentityPrompt'])->name('products.translate_identity_prompt');
    Route::post('/products/{product}/optimize-images', [ProductController::class, 'optimizeImages'])->name('products.optimize_images');
    Route::patch('/products/{product}/credit', [ProductController::class, 'updateCredit'])->name('products.update_credit');
    Route::patch('/products/bulk-ai-model', [ProductController::class, 'bulkUpdateAiModel'])->name('products.bulk_update_ai_model');
    Route::patch('/products/{product}/ai-model', [ProductController::class, 'updateAiModel'])->name('products.update_ai_model');
    Route::get('/products/dashboard',  fn() => view('admin.products-dashboard'))->name('products.dashboard');
    Route::get('/products/categories', fn() => view('admin.products-categories'))->name('products.categories');
    Route::get('/products/pricing',    fn() => view('admin.products-pricing'))->name('products.pricing');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // قابلیت‌های حرفه‌ای لیست محصولات
    Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle_status');
    Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulk_action');

    // مدیریت مدل‌های هوش مصنوعی (OpenRouter + Liara)
    // کلید مرکزی روشن/خاموش provider — ابتدا ثبت می‌شود تا resource آن را نپوشاند.
    Route::post('ai-models/toggle-provider', [AiModelController::class, 'toggleProvider'])->name('ai-models.toggle-provider');
    Route::post('ai-models/{aiModel}/toggle', [AiModelController::class, 'toggleModel'])->name('ai-models.toggle-model');
    Route::get('ai-models/providers', [AiModelController::class, 'providers'])->name('ai-models.providers');
    Route::get('ai-models/providers/create', [AiModelController::class, 'createProvider'])->name('ai-models.providers.create');
    Route::put('ai-models/provider-settings', [AiModelController::class, 'updateProviderSettings'])->name('ai-models.provider-settings');
    Route::post('ai-models/test-provider', [AiModelController::class, 'testProvider'])->name('ai-models.test-provider');
    Route::post('ai-models/sync-catalog', [AiModelController::class, 'syncCatalog'])->name('ai-models.sync-catalog');
    Route::resource('ai-models', AiModelController::class)->names('ai-models');

    // آزمایشگاه محصولات — مستقل از فرم ثبت محصول
    Route::get('/lab', [LabExperimentController::class, 'index'])->name('lab.index');
    Route::get('/lab/create', [LabExperimentController::class, 'create'])->name('lab.create');
    Route::post('/lab', [LabExperimentController::class, 'store'])->name('lab.store');
    Route::get('/lab/reports', [LabExperimentController::class, 'reports'])->name('lab.reports');
    Route::get('/lab/products/{product}/images', [LabExperimentController::class, 'productImages'])->name('lab.products.images');
    Route::get('/lab/products/{product}/summary', [LabExperimentController::class, 'productSummary'])->name('lab.products.summary');
    Route::post('/lab/products/{product}/quick-run', [LabExperimentController::class, 'quickRun'])->name('lab.products.quick-run');
    Route::get('/lab/{experiment}/status', [LabExperimentController::class, 'status'])->name('lab.status');
    Route::get('/lab/{experiment}', [LabExperimentController::class, 'show'])->name('lab.show');
    Route::post('/lab/{experiment}/cancel', [LabExperimentController::class, 'cancel'])->name('lab.cancel');
    Route::post('/lab/{experiment}/duplicate', [LabExperimentController::class, 'duplicate'])->name('lab.duplicate');
    Route::post('/lab/{experiment}/apply', [LabExperimentController::class, 'apply'])->name('lab.apply');
    Route::post('/lab/runs/{run}/retry', [LabExperimentController::class, 'retry'])->name('lab.runs.retry');
    Route::post('/lab/outputs/{output}/score', [LabExperimentController::class, 'score'])->name('lab.outputs.score');
    Route::post('/lab/outputs/{output}/manager-score', [LabExperimentController::class, 'managerScore'])->name('lab.outputs.manager-score');

    // مدیریت یکپارچه محصولات و بنرهای صفحه ترندز
    Route::get('/trends', [TrendController::class, 'index'])->name('trends.index');
    Route::post('/trends/products/{product}/add', [TrendController::class, 'addProduct'])->name('trends.products.add');
    Route::patch('/trends/products/{product}/toggle', [TrendController::class, 'toggleProduct'])->name('trends.products.toggle');
    Route::post('/trends/banners', [TrendController::class, 'storeBanner'])->name('trends.banners.store');
    Route::put('/trends/banners/{banner}', [TrendController::class, 'updateBanner'])->name('trends.banners.update');
    Route::patch('/trends/banners/{banner}/toggle', [TrendController::class, 'toggleBanner'])->name('trends.banners.toggle');
    Route::delete('/trends/banners/{banner}', [TrendController::class, 'destroyBanner'])->name('trends.banners.destroy');

    // مسیرهای قدیمی مناسبت‌ها برای سازگاری با لینک‌های قبلی
    Route::post('/trends/occasions', [App\Http\Controllers\Admin\Explore\TrendOccasionController::class, 'store'])->name('trends.occasions.store');
    Route::put('/trends/occasions/{occasion}', [App\Http\Controllers\Admin\Explore\TrendOccasionController::class, 'update'])->name('trends.occasions.update');
    Route::patch('/trends/occasions/{occasion}/toggle', [App\Http\Controllers\Admin\Explore\TrendOccasionController::class, 'toggle'])->name('trends.occasions.toggle');
    Route::delete('/trends/occasions/{occasion}', [App\Http\Controllers\Admin\Explore\TrendOccasionController::class, 'destroy'])->name('trends.occasions.destroy');

    // بقیه بخش‌های فرانت پنل ادمین
    Route::get('/crm',              fn() => redirect('/admin/dashboard/crm'))->name('crm');
    Route::get('/crm/attendance',   fn() => redirect('/admin/dashboard/attendance'))->name('crm.attendance');
    // مدیریت سفارشات و تخفیفات — روت‌های ثابت باید قبل از پارامتر {order} بمانند.
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/processing', [OrderController::class, 'processing'])->name('orders.processing');
    Route::get('/orders/failed', [OrderController::class, 'failed'])->name('orders.failed');
    Route::get('/orders/refunds', [OrderController::class, 'refunds'])->name('orders.refunds');
    Route::get('/orders/analytics', [OrderController::class, 'analytics'])->name('orders.analytics');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/retry', [OrderController::class, 'retry'])->name('orders.retry');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
    Route::patch('/orders/{order}/note', [OrderController::class, 'note'])->name('orders.note');

    Route::get('/discounts', [DiscountController::class, 'index'])->name('discounts.index');

    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
    Route::get('/sms/compose', [SmsController::class, 'compose'])->name('sms.compose');
    Route::get('/sms/history', [SmsController::class, 'history'])->name('sms.history');
    Route::get('/sms/campaigns', [SmsController::class, 'campaigns'])->name('sms.campaigns');
    Route::get('/sms/providers', [SmsController::class, 'providers'])->name('sms.providers');
    Route::post('/sms/providers', [SmsController::class, 'storeProvider'])->name('sms.providers.store');
    Route::put('/sms/providers/{provider}', [SmsController::class, 'updateProvider'])->name('sms.providers.update');
    Route::post('/sms/providers/{provider}/test', [SmsController::class, 'testProvider'])->name('sms.providers.test');
    Route::post('/sms/send', [SmsController::class, 'send'])->name('sms.send');
    Route::put('/sms/settings', [SmsController::class, 'settings'])->name('sms.settings');
    Route::get('/sms/templates', [SmsController::class, 'templates'])->name('sms.templates');
    Route::post('/sms/templates', [SmsController::class, 'storeTemplate'])->name('sms.templates.store');
    Route::put('/sms/templates/{template}', [SmsController::class, 'updateTemplate'])->name('sms.templates.update');
    Route::delete('/sms/templates/{template}', [SmsController::class, 'destroyTemplate'])->name('sms.templates.destroy');
    Route::patch('/sms/templates/{template}/toggle', [SmsController::class, 'toggleTemplate'])->name('sms.templates.toggle');
    Route::patch('/sms/templates/{template}/default', [SmsController::class, 'defaultTemplate'])->name('sms.templates.default');
    Route::post('/sms/templates/{template}/test', [SmsController::class, 'testTemplate'])->name('sms.templates.test');
    Route::post('/sms/templates/sync-statuses', [SmsController::class, 'syncTemplateStatuses'])->name('sms.templates.sync-statuses');
    Route::post('/discounts', [DiscountController::class, 'store'])->name('discounts.store');
    Route::put('/discounts/{discount}', [DiscountController::class, 'update'])->name('discounts.update');
    Route::patch('/discounts/{discount}/toggle', [DiscountController::class, 'toggle'])->name('discounts.toggle');
    Route::delete('/discounts/{discount}', [DiscountController::class, 'destroy'])->name('discounts.destroy');
    Route::get('/analytics',        fn() => view('admin.analytics'))->name('analytics');
    Route::get('/jobs',             fn() => view('admin.jobs'))->name('jobs');
    Route::get('/payments',         fn() => view('admin.payments'))->name('payments');
    Route::get('/bloggers',         fn() => view('admin.bloggers'))->name('bloggers');

    // تنظیمات زیرسیستم‌ها
    Route::get('/settings/admins', [AdminManagerController::class, 'index'])->name('settings.admins');
    Route::post('/settings/admins', [AdminManagerController::class, 'store'])->name('settings.admins.store');
    Route::put('/settings/admins/{admin}', [AdminManagerController::class, 'update'])->name('settings.admins.update');
    Route::delete('/settings/admins/{admin}', [AdminManagerController::class, 'destroy'])->name('settings.admins.destroy');
    Route::post('/settings/admins/{admin}/copy-password', [AdminManagerController::class, 'copyPassword'])->name('settings.admins.copy-password');
    Route::prefix('referrals')->name('referrals.')->group(function () {
        Route::get('/', [ReferralSettingController::class, 'overview'])->name('overview');
        Route::get('/settings', [ReferralSettingController::class, 'settings'])->name('settings');
        Route::put('/settings', [ReferralSettingController::class, 'update'])->name('settings.update');
        Route::get('/conversions', [ReferralSettingController::class, 'conversions'])->name('conversions');
        Route::get('/rewards', [ReferralSettingController::class, 'rewards'])->name('rewards');
        Route::get('/visits', [ReferralSettingController::class, 'visits'])->name('visits');
        Route::get('/reviews', [ReferralSettingController::class, 'reviews'])->name('reviews');
        Route::get('/export', [ReferralSettingController::class, 'export'])->name('export');
        Route::patch('/conversions/{conversion}/review', [ReferralSettingController::class, 'reviewConversion'])->name('conversions.review');
        Route::patch('/rewards/{reward}/review', [ReferralSettingController::class, 'reviewReward'])->name('rewards.review');
    });

    // سازگاری با نشانی قبلی؛ بخش همکاری در فروش اکنون منوی مستقل دارد.
    Route::get('/settings/referrals', [ReferralSettingController::class, 'index'])->name('settings.referrals');
    Route::put('/settings/referrals', [ReferralSettingController::class, 'update'])->name('settings.referrals.update');
    Route::get('/settings/referrals/export', [ReferralSettingController::class, 'export'])->name('settings.referrals.export');
    Route::patch('/settings/referrals/conversions/{conversion}/review', [ReferralSettingController::class, 'reviewConversion'])->name('settings.referrals.conversions.review');
    Route::patch('/settings/referrals/rewards/{reward}/review', [ReferralSettingController::class, 'reviewReward'])->name('settings.referrals.rewards.review');
    Route::get('/settings/system',           fn() => view('admin.settings.system'))->name('settings.system');

    // صفحه‌ی جایگزین برای بخش‌هایی که هنوز فایل بک‌اند/روت ندارند
    Route::get('/{any}', fn ($any = null) => view('admin.coming-soon'))
        ->where('any', '.*')
        ->name('coming-soon');
});

// ─── Admin API (Ajax — مدیریت توکن کاربران) ──────────────
// این روت‌ها فقط برای درخواست‌های Ajax صفحه‌ی «مدیریت توکن» ادمین هستند و
// کاملاً مستقل از بخش‌های دیگر پنل عمل می‌کنند تا هیچ آسیبی به آن‌ها نرسد.
Route::prefix('api/v1/admin')->name('admin.api.')->middleware('auth:admin')->group(function () {
    Route::get('/users/search',              [AdminUserController::class, 'search'])->name('users.search');
    Route::get('/users/{id}/token-history',  [AdminUserController::class, 'tokenHistory'])->name('users.token_history');
    Route::get('/users/{id}',                [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{id}/token',         [AdminUserController::class, 'updateToken'])->name('users.token.update');
    Route::get('/token-history',             [AdminUserController::class, 'globalTokenHistory'])->name('token_history');
});
