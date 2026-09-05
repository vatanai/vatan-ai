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
use App\Http\Controllers\Admin\QueueController;
use App\Http\Controllers\Admin\SitePageController;
use App\Http\Controllers\Admin\Explore\TrendController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\GrowthTrackingController;
use App\Http\Controllers\Admin\GrowthController;
use App\Http\Controllers\Admin\GrowthDataSourceController;
use App\Http\Controllers\Admin\GrowthUserController;
use App\Http\Controllers\Admin\VideoStudioController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleCommentController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\Admin\ArticleCategoryController as AdminArticleCategoryController;
use App\Http\Controllers\Admin\ArticleCommentController as AdminArticleCommentController;
use App\Http\Controllers\TelegramMiniAppController;
use App\Http\Controllers\TelegramWebhookController;

// ─── Root & Landing ──────────────────────────────────────
// صفحه‌ی اصلی عمومی باید برای کاربر واردشده هم قابل مشاهده باشد؛
// ورود به بخش کاربری از دکمه‌های داخل صفحه یا مسیرهای /app انجام می‌شود.
Route::get('/', [PublicHomeController::class, 'index'])->middleware('site.page')->name('site.home.root');
Route::get('/site', fn() => redirect('/'));
Route::get('/r/{code}', [ReferralController::class, 'visit'])
    ->where('code', '[A-Za-z0-9]{6,20}')
    ->name('referral.visit');
Route::get('/r/{code}/product/{product:route_slug}', [ReferralController::class, 'productVisit'])
    ->where('code', '[A-Za-z0-9]{6,20}')
    ->name('referral.product');

// رهگیری رشد مستقل از سیستم دعوت: کلیک و بازشدن مقصد دو رویداد جدا هستند.
Route::get('/g/{growthLink}', [GrowthTrackingController::class, 'redirect'])
    ->where('growthLink', '[A-Za-z0-9_-]+')
    ->middleware('throttle:300,1')
    ->name('growth.redirect');
Route::get('/growth/tracker.js', [GrowthTrackingController::class, 'trackerScript'])->name('growth.tracker');
Route::get('/growth/track/page-open', [GrowthTrackingController::class, 'pageOpen'])
    ->middleware('throttle:600,1')
    ->name('growth.page-open');

Route::prefix('site')->group(function () {
    Route::get('/pricing', [PlanSubscriptionController::class, 'index'])->name('pricing.index');
    Route::get('/about',   fn() => view('site.about'))->name('site.about');
});
Route::get('/site/payments/demo/result', [PlanSubscriptionController::class, 'demoResult'])->name('payments.demo');

Route::get('/privacy', fn() => view('site.privacy'))->name('privacy');

// ─── مرکز عمومی مقالات وطن ──────────────────────────────
// تمام مسیرهای محتوایی خارج از /app هستند تا ساختار عمومی، اشتراک‌پذیر و سئویی داشته باشند.
Route::get('/articles', [ArticleController::class, 'index'])->middleware('site.page')->name('articles.index');
Route::get('/articles/category/{slug}', [ArticleController::class, 'category'])->name('articles.category');
Route::get('/articles/author/{slug}', [ArticleController::class, 'author'])->name('articles.author');
Route::get('/articles/feed.xml', [ArticleController::class, 'feed'])->name('articles.feed');
Route::post('/articles/{article}/events', [ArticleController::class, 'track'])->middleware('throttle:120,1')->name('articles.events');
Route::post('/articles/{article}/comments', [ArticleCommentController::class, 'store'])
    ->middleware(['auth', 'throttle:5,1'])->name('articles.comments.store');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::get('/sitemap.xml', [ArticleController::class, 'sitemap'])->name('sitemap');

Route::get('/auth/csrf-token', fn () => response()->json(['token' => csrf_token()]))
    ->name('auth.csrf-token');

// ورودی بات در یک مسیر مستقل نگه داشته شده تا با احراز هویت و فرم‌های سایت تداخل نداشته باشد.
Route::post('/webhooks/telegram', [TelegramWebhookController::class, 'update'])
    ->name('telegram.webhook');
Route::get('/telegram/mini-app', [TelegramMiniAppController::class, 'show'])
    ->name('telegram.mini-app');
Route::post('/api/telegram/mini-app/session', [TelegramMiniAppController::class, 'session'])
    ->name('telegram.mini-app.session');

// APIهای CRM تلگرام فقط برای ادمین احراز هویت‌شده؛ ارسال کمپین در این فاز فعال نیست.
Route::prefix('api')->name('telegram.api.')->middleware('auth:admin')->group(function () {
    Route::get('/telegram-users', [\App\Http\Controllers\Admin\TelegramApiController::class, 'users'])->name('users.index');
    Route::post('/telegram-users', [\App\Http\Controllers\Admin\TelegramApiController::class, 'storeUser'])->name('users.store');
    Route::get('/campaigns', [\App\Http\Controllers\Admin\TelegramApiController::class, 'campaigns'])->name('campaigns.index');
    Route::post('/campaigns', [\App\Http\Controllers\Admin\TelegramApiController::class, 'storeCampaign'])->name('campaigns.store');
});

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
    Route::post('/auth/unified/send-otp', [AuthController::class, 'sendUnifiedOtp'])->name('auth.unified.send');
    Route::post('/auth/unified/verify-otp', [AuthController::class, 'verifyUnifiedOtp'])->name('auth.unified.verify');
    Route::post('/auth/unified/register', [AuthController::class, 'registerUnified'])->name('auth.unified.register');
    
    // ورود با رمز ثابت؛ ورود پیامکی نیز به‌عنوان مسیر جایگزین در دسترس است.
    Route::post('/auth/login-submit', [AuthController::class, 'loginSubmit'])->name('auth.login.submit');
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
    Route::post('/profile/face-profiles', [ProfileController::class, 'storeFaceProfile'])->name('profile.face-profiles.store');
    Route::delete('/profile/face-profiles/{faceProfile}', [ProfileController::class, 'destroyFaceProfile'])->name('profile.face-profiles.destroy');
    Route::post('/app/product/{product:slug}/save', [SavedProductController::class, 'toggle'])->name('app.product.save');
    Route::post('/app/product/{product:slug}/like', [App\Http\Controllers\LikedProductController::class, 'toggle'])->name('app.product.like');
    Route::post('/app/product/{product:slug}/download', [App\Http\Controllers\ProductDownloadController::class, 'store'])->name('app.product.download');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/site/pricing/{plan}/checkout', [PlanSubscriptionController::class, 'checkout'])->name('pricing.checkout');
    Route::post('/site/pricing/{plan}/checkout', [PlanSubscriptionController::class, 'startPayment'])->name('pricing.start-payment');
    Route::get('/site/payments/{purchase}/result', [PlanSubscriptionController::class, 'result'])->name('payments.result');
    Route::get('/site/payments/{purchase}/receipt', [PlanSubscriptionController::class, 'receipt'])->name('payments.receipt');
});

Route::get('/site/payments/{purchase}/callback', [PlanSubscriptionController::class, 'callback'])->name('payments.callback');

// ─── App Pages & Generation ──────────────────────────────
// سازگاری با لینک‌های قدیمی سکشن‌ها و بوکمارک‌ها؛ پارامترهای فیلتر حفظ می‌شوند.
Route::get('/products', function () {
    $query = request()->getQueryString();

    return redirect('/app/products' . ($query ? '?' . $query : ''), 301);
})->name('products.legacy');

Route::prefix('app')->middleware('site.page')->group(function () {
    Route::get('/',             fn() => redirect('/app/home'));
    Route::get('/home', [HomeController::class, 'index'])->name('app.home');
    Route::get('/home/search', [HomeController::class, 'search'])->name('app.home.search');
    Route::get('/explore',      [\App\Http\Controllers\Explore\ExploreController::class, 'index'])->name('app.explore');
    Route::get('/trends',       [\App\Http\Controllers\Explore\ExploreController::class, 'trending'])->name('app.trends');
    Route::get('/products',     [ProductCatalogController::class, 'index'])->name('products.index');
    // صفحه عمومی ساخت بدون محصول، و صفحه ساخت اختصاصی محصول از یک مسیر کنترل‌شده
    // عبور می‌کنند تا تنظیمات خروجی ذخیره‌شده‌ی همان محصول به کاربر برسد.
    Route::get('/create', [ProductGenerateController::class, 'create'])->name('app.create');
    // استودیوی عمومی ساخت از تنظیمات دو محصول واقعی استفاده می‌کند و ارسال
    // درخواست آن از همان مسیرهای ساخت تصویر و ویدیو عبور می‌کند.
    Route::get('/create-studio/quote', [ProductGenerateController::class, 'studioQuote'])->name('app.create.studio.quote');
    Route::get('/create-studio', [\App\Http\Controllers\StudioWorkflowController::class, 'show'])->name('app.create.studio');
    Route::get('/create-studio-workflows', [\App\Http\Controllers\StudioWorkflowController::class, 'show'])->name('app.create.studio.workflows');
    Route::get('/create-studio-workflows/quote', [\App\Http\Controllers\StudioWorkflowController::class, 'quote'])->name('app.create.studio.workflows.quote');
    Route::post('/create-studio-workflows/generate', [\App\Http\Controllers\StudioWorkflowController::class, 'generate'])
        ->middleware('auth')
        ->name('app.create.studio.workflows.generate');
    Route::get('/create-samples', function (\App\Services\ProductBuildSchema $schema) {
        $product = \App\Models\Product::where('status', 'active')->latest()->first();

        abort_unless($product, 404);

        return view('app.create-samples', [
            'product' => $product,
            'buildProduct' => $schema->pageData($product),
        ]);
    })->name('app.create.samples');
    Route::get('/create-product-preview', function (\Illuminate\Http\Request $request, \App\Services\ProductBuildSchema $schema) {
        $slug = $request->query('product');
        $product = $slug
            ? (new \App\Models\Product())->resolveRouteBinding($slug, 'route_slug')
            : \App\Models\Product::where('status', 'active')->latest()->first();

        abort_unless($product && $product->status === 'active', 404);

        return view('app.create-product-preview', [
            'product' => $product,
            'buildProduct' => $schema->pageData($product),
        ]);
    })->name('app.create.product.preview');
    Route::get('/create-versions-compare', function (\Illuminate\Http\Request $request) {
        $slug = $request->query('product', 'ai-fashion-portrait');
        $product = (new \App\Models\Product())->resolveRouteBinding($slug, 'route_slug');
        $product = $product && $product->status === 'active'
            ? $product
            : \App\Models\Product::where('status', 'active')->latest()->first();

        abort_unless($product, 404);

        return view('app.create-versions-compare', [
            'product' => $product,
            'productSlug' => $product->route_slug,
        ]);
    })->name('app.create.versions.compare');
    Route::get('/create-versions-compare/{source}/{page}', function (string $source, string $page, \App\Services\ProductBuildSchema $schema, \Illuminate\Http\Request $request) {
        abort_unless(in_array($source, ['backup-one', 'backup-two'], true), 404);
        abort_unless(in_array($page, ['create', 'product'], true), 404);

        $slug = $request->query('product', 'ai-fashion-portrait');
        $product = (new \App\Models\Product())->resolveRouteBinding($slug, 'route_slug');
        $product = $product && $product->status === 'active'
            ? $product
            : \App\Models\Product::where('status', 'active')->latest()->first();

        abort_unless($product, 404);

        return view('app.create-versions-compare-legacy', [
            'product' => $schema->pageData($product),
            'source' => $source,
            'page' => $page,
        ]);
    })->where(['source' => 'backup-one|backup-two', 'page' => 'create|product'])->name('app.create.versions.compare.legacy');
    Route::get('/create-preview', [ProductGenerateController::class, 'createPreview'])->name('app.create.preview');
    if (app()->environment('local')) {
        Route::get('/create-loader-demo', function () {
            $previewProduct = (object) [
                'name_fa' => 'پیش‌نمایش لودر وطن',
                'name_en' => 'Vatan Loader Preview',
            ];
            $buildProduct = [
                'name' => 'پرتره سینمایی وطن',
                'description' => 'پیش‌نمایش لوکال لودر صفحه ساخت؛ بدون ارسال درخواست و بدون مصرف اعتبار.',
                'cover' => asset('icons/vatan-512.png'),
                'cost' => 12,
                'estimated_time' => 'حدود ۳۰ ثانیه',
                'output_count' => 1,
                'fields' => [],
                'identity' => ['available' => false, 'extra_cost' => 0, 'max_images' => 3],
                'output_variants' => [],
                'output_aspect_ratios' => ['3:4', '1:1', '16:9'],
                'output_resolutions' => ['720', '1080', '2K'],
                'default_output_aspect_ratio' => '3:4',
                'default_output_resolution' => '1080',
                'main_quality_options' => [],
                'show_output_quality_selector' => false,
                'face_profiles' => [],
                'generate_url' => '',
                'download_track_url' => '',
                'login_url' => route('login'),
                'is_authenticated' => true,
            ];

            return view('app.create-product', compact('previewProduct', 'buildProduct'))
                ->with('product', $previewProduct);
        })->name('app.create.loader-demo');
        Route::get('/create-loader-ad', function () {
            return view('app.create-loader-ad');
        })->name('app.create.loader-ad');
    }
    Route::view('/create-architecture', 'app.create-architecture')->name('app.create.architecture');
    Route::get('/create/{product:route_slug}', [ProductGenerateController::class, 'build'])->name('app.create.product');
    Route::post('/create/{product:route_slug}/generate', [ProductGenerateController::class, 'generate'])
        ->middleware(['auth', \App\Http\Middleware\MarkTelegramBuildCompleted::class])
        ->name('app.create.generate');
    Route::post('/video-products/{product:slug}/quote', [\App\Http\Controllers\VideoProductController::class, 'quote'])->middleware('auth')->name('app.video-product.quote');
                Route::get('/video-generations/{generatedVideo}/status', [\App\Http\Controllers\VideoProductController::class, 'status'])
        ->middleware('auth')
        ->name('app.video-generation.status');
    Route::get('/profile',      [ProfileController::class, 'index'])->name('app.profile');
    // سازگاری با نشانی قدیمی مقالات؛ آدرس اصلی و کانونیکال خارج از /app است.
    Route::get('/articles', fn () => redirect()->route('articles.index', [], 301))->name('app.articles');
    // لینک تستی صفحه محصول — به جدیدترین محصول فعال ری‌دایرکت می‌شود
    Route::get('/product-details', function () {
        $p = \App\Models\Product::where('status', 'active')->latest()->first();
        return $p
            ? redirect()->route('app.product', $p->route_slug)
            : redirect()->route('app.home');
    })->name('app.product-details');
    Route::get('/product/{product:route_slug}', [ProductGenerateController::class, 'show'])->name('app.product');
    Route::post('/product/{product:slug}/generate', [ProductGenerateController::class, 'generate'])
        ->middleware(['auth', \App\Http\Middleware\MarkTelegramBuildCompleted::class])
        ->name('app.product.generate');
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
Route::post('/webhooks/video-studio/{job}/status', [VideoStudioController::class, 'n8nStatus'])
    ->name('webhooks.video-studio.status');
Route::get('/webhooks/meta', [\App\Http\Controllers\MarketingMetaWebhookController::class, 'verify'])
    ->name('webhooks.meta.verify');
Route::post('/webhooks/meta', [\App\Http\Controllers\MarketingMetaWebhookController::class, 'receive'])
    ->name('webhooks.meta.receive');

// ─── Admin Authentication (Guest) ────────────────────────
Route::middleware('guest:admin')->group(function () {
    Route::get('/admin/login',  [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
});

// ─── Admin Panel Area (Protected) ────────────────────────
Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::prefix('telegram')->name('telegram.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'index'])->name('index');
        Route::get('/users', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'users'])->name('users');
        Route::get('/users/{telegramUser}', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'show'])->name('users.show');
        Route::post('/segments', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'storeSegment'])->name('segments.store');
        Route::delete('/segments/{telegramSegment}', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'destroySegment'])->name('segments.destroy');
        Route::patch('/users/{telegramUser}', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'update'])->name('users.update');
        Route::patch('/users/{telegramUser}/archive', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'archive'])->name('users.archive');
        Route::post('/users/{telegramUser}/credit', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'adjustCredit'])->name('users.credit');
        Route::get('/content', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'content'])->name('content');
        Route::post('/content', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'storeContent'])->name('content.store');
        Route::put('/content/{telegramBotContent}', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'updateContent'])->name('content.update');
        Route::delete('/content/{telegramBotContent}', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'destroyContent'])->name('content.destroy');
        Route::get('/campaigns', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'campaigns'])->name('campaigns');
        Route::post('/campaigns', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'storeCampaign'])->name('campaigns.store');
        Route::post('/campaigns/{telegramCampaign}/prepare', [\App\Http\Controllers\Admin\TelegramAdminController::class, 'prepareCampaign'])->name('campaigns.prepare');
    });
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

    // نمای کلی مدیریت صفحات سایت در داشبورد
    Route::get('/pages', [SitePageController::class, 'index'])->name('pages.index');
    Route::get('/content/pages', [SitePageController::class, 'index'])->name('content.pages');
    Route::get('/pages/{sitePage}', [SitePageController::class, 'edit'])->name('pages.edit');
    Route::put('/pages/{sitePage}', [SitePageController::class, 'update'])->name('pages.update');
    Route::patch('/pages/{sitePage}/publish', [SitePageController::class, 'publish'])->name('pages.publish');
    Route::post('/pages/{sitePage}/revisions/{revision}/restore', [SitePageController::class, 'restore'])->name('pages.revisions.restore');

    // مدیریت کامل مرکز مقالات
    Route::get('/articles', [AdminArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/analytics', [AdminArticleController::class, 'overviewAnalytics'])->name('articles.analytics-overview');
    Route::get('/articles/create', [AdminArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [AdminArticleController::class, 'store'])->name('articles.store');
    Route::post('/articles/bulk', [AdminArticleController::class, 'bulk'])->name('articles.bulk');
    Route::get('/articles/{article}/edit', [AdminArticleController::class, 'edit'])->name('articles.edit');
    Route::put('/articles/{article}', [AdminArticleController::class, 'update'])->name('articles.update');
    Route::patch('/articles/{article}/archive', [AdminArticleController::class, 'archive'])->name('articles.archive');
    Route::delete('/articles/{article}', [AdminArticleController::class, 'destroy'])->name('articles.destroy');
    Route::patch('/articles/trash/{article}/restore', [AdminArticleController::class, 'restore'])->name('articles.restore');
    Route::delete('/articles/trash/{article}/force', [AdminArticleController::class, 'forceDelete'])->name('articles.force-delete');
    Route::get('/articles/{article}/analytics', [AdminArticleController::class, 'analytics'])->name('articles.analytics');
    Route::post('/articles/{article}/revisions/{revision}/restore', [AdminArticleController::class, 'restoreRevision'])->name('articles.revisions.restore');

    Route::get('/article-categories', [AdminArticleCategoryController::class, 'index'])->name('article-categories.index');
    Route::post('/article-categories', [AdminArticleCategoryController::class, 'store'])->name('article-categories.store');
    Route::put('/article-categories/{articleCategory}', [AdminArticleCategoryController::class, 'update'])->name('article-categories.update');
    Route::delete('/article-categories/{articleCategory}', [AdminArticleCategoryController::class, 'destroy'])->name('article-categories.destroy');

    Route::get('/article-comments', [AdminArticleCommentController::class, 'index'])->name('article-comments.index');
    Route::patch('/article-comments/{comment}', [AdminArticleCommentController::class, 'update'])->name('article-comments.update');
    Route::delete('/article-comments/{comment}', [AdminArticleCommentController::class, 'destroy'])->name('article-comments.destroy');

    // مدیریت پرامپت‌ها
    Route::resource('prompts', AdminPromptController::class)->except(['show']);

    // مدیریت کاربران (متصل شده به کنترلر ادمین در پوشه Admin)
    Route::get('/users',                [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/export',         [AdminUserController::class, 'export'])->name('users.export');
    Route::post('/users/{id}/status', [AdminUserController::class, 'changeStatus'])->name('users.status');
    Route::patch('/users/bulk-status', [AdminUserController::class, 'bulkChangeStatus'])->name('users.bulk-status');
    Route::post('/users/{id}/copy-password', [AdminUserController::class, 'copyPassword'])->name('users.copy-password');
    Route::patch('/users/{id}/customer-segment', [AdminUserController::class, 'changeCustomerSegment'])->name('users.customer-segment');
    Route::patch('/users/bulk-plan', [AdminUserController::class, 'bulkChangePlan'])->name('users.bulk-plan');
    Route::patch('/users/{id}/plan', [AdminUserController::class, 'changePlan'])->name('users.plan');
    Route::get('/users/all-logs',       [AdminUserController::class, 'allLogs'])->name('users.all_logs');
    Route::get('/users/all-activities', [AdminUserController::class, 'allActivities'])->name('users.all_activities');
    Route::get('/users/{id}/logs',      [AdminUserController::class, 'logs'])->name('users.logs');
    Route::get('/users/tokens',         [AdminUserController::class, 'tokens'])->name('users.tokens'); // پشتیبانی از دکمه مدیریت توکن قالب شما

    // مدیریت محصولات
    // توجه: مسیر ویرایش دیگر جدا نیست و کامل از پروژه حذف شده — ویرایش هم از همین صفحه «ثبت محصول»
    // با پارامتر اختیاری محصول انجام می‌شود (مثال: /admin/products/create/52)
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/videos/create/{product?}', [\App\Http\Controllers\Admin\VideoProductController::class, 'create'])->name('products.video.create');
    Route::post('/products/videos', [\App\Http\Controllers\Admin\VideoProductController::class, 'store'])->name('products.video.store');
    Route::put('/products/videos/{product}', [\App\Http\Controllers\Admin\VideoProductController::class, 'update'])->name('products.video.update');
    Route::get('/products/create/{product?}', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/translate-identity-prompt', [ProductController::class, 'translateIdentityPrompt'])->name('products.translate_identity_prompt');
    Route::post('/products/{product}/optimize-images', [ProductController::class, 'optimizeImages'])->name('products.optimize_images');
    Route::patch('/products/{product}/credit', [ProductController::class, 'updateCredit'])->name('products.update_credit');
    Route::patch('/products/bulk-ai-model', [ProductController::class, 'bulkUpdateAiModel'])->name('products.bulk_update_ai_model');
    Route::patch('/products/bulk-model-tier-preset', [ProductController::class, 'bulkApplyModelTierPreset'])->name('products.bulk_apply_model_tier_preset');
    Route::patch('/products/bulk-model-quality-configuration', [ProductController::class, 'bulkUpdateModelQualityConfiguration'])->name('products.bulk_update_model_quality_configuration');
    Route::patch('/products/bulk-quality-credit-preset', [ProductController::class, 'bulkApplyQualityCreditPreset'])->name('products.bulk_apply_quality_credit_preset');
    Route::patch('/products/{product}/model-tier-preset', [ProductController::class, 'applyModelTierPreset'])->name('products.apply_model_tier_preset');
    Route::patch('/products/{product}/quality-credit-preset', [ProductController::class, 'applyQualityCreditPreset'])->name('products.apply_quality_credit_preset');
    Route::patch('/products/{product}/model-tier-configuration', [ProductController::class, 'updateModelTierConfiguration'])->name('products.update_model_tier_configuration');
    Route::patch('/products/{product}/model-quality-configuration', [ProductController::class, 'updateModelQualityConfiguration'])->name('products.update_model_quality_configuration');
    Route::patch('/model-tier-defaults/{modelTierDefault}', [\App\Http\Controllers\Admin\ModelTierDefaultController::class, 'update'])->name('model-tier-defaults.update');
    Route::post('/model-quality-presets', [\App\Http\Controllers\Admin\ModelQualityPresetController::class, 'store'])->name('model-quality-presets.store');
    Route::patch('/model-quality-presets/{modelQualityPreset}', [\App\Http\Controllers\Admin\ModelQualityPresetController::class, 'update'])->name('model-quality-presets.update');
    Route::delete('/model-quality-presets/{modelQualityPreset}', [\App\Http\Controllers\Admin\ModelQualityPresetController::class, 'destroy'])->name('model-quality-presets.destroy');
    Route::post('/product-credit-presets', [\App\Http\Controllers\Admin\ProductCreditPresetController::class, 'store'])->name('product-credit-presets.store');
    Route::patch('/product-credit-presets/{productCreditPreset}', [\App\Http\Controllers\Admin\ProductCreditPresetController::class, 'update'])->name('product-credit-presets.update');
    Route::delete('/product-credit-presets/{productCreditPreset}', [\App\Http\Controllers\Admin\ProductCreditPresetController::class, 'destroy'])->name('product-credit-presets.destroy');
    Route::patch('/products/{product}/ai-model', [ProductController::class, 'updateAiModel'])->name('products.update_ai_model');
    Route::get('/products/dashboard', [VideoStudioController::class, 'index'])->name('products.dashboard');
    Route::get('/video-studio/experimental', [VideoStudioController::class, 'experimental'])->name('video-studio.experimental');
    Route::post('/video-studio/experimental/presets', [VideoStudioController::class, 'storePreset'])->name('video-studio.experimental.presets.store');
    Route::patch('/video-studio/experimental/presets/{preset}', [VideoStudioController::class, 'renamePreset'])->name('video-studio.experimental.presets.rename');
    Route::delete('/video-studio/experimental/presets/{preset}', [VideoStudioController::class, 'destroyPreset'])->name('video-studio.experimental.presets.destroy');
    Route::post('/video-studio/experimental/social-prompts', [VideoStudioController::class, 'storeSocialPrompts'])->name('video-studio.experimental.social-prompts.store');
    Route::post('/video-studio/experimental/hook-colors', [VideoStudioController::class, 'storeHookColor'])->name('video-studio.experimental.hook-colors.store');
    Route::delete('/video-studio/experimental/hook-colors/defaults/{target}/{colorKey}', [VideoStudioController::class, 'destroyDefaultHookColor'])->name('video-studio.experimental.hook-colors.defaults.destroy');
    Route::delete('/video-studio/experimental/hook-colors/{color}', [VideoStudioController::class, 'destroyHookColor'])->name('video-studio.experimental.hook-colors.destroy');
    Route::get('/create-studio', [\App\Http\Controllers\Admin\CreateStudioController::class, 'index'])->name('create-studio.index');
    Route::put('/create-studio/pricing-settings', [\App\Http\Controllers\Admin\CreateStudioController::class, 'updatePricingSettings'])->name('create-studio.pricing-settings.update');
    Route::post('/create-studio/cost-rules', [\App\Http\Controllers\Admin\CreateStudioController::class, 'storeCostRule'])->name('create-studio.cost-rules.store');
    Route::put('/create-studio/cost-rules/{studioCostRule}', [\App\Http\Controllers\Admin\CreateStudioController::class, 'updateCostRule'])->name('create-studio.cost-rules.update');
    Route::delete('/create-studio/cost-rules/{studioCostRule}', [\App\Http\Controllers\Admin\CreateStudioController::class, 'destroyCostRule'])->name('create-studio.cost-rules.destroy');
    Route::patch('/video-studio/settings', [VideoStudioController::class, 'updateSettings'])->name('video-studio.settings.update');
    Route::post('/video-studio/preview', [VideoStudioController::class, 'previewContent'])->name('video-studio.preview');
    Route::post('/video-studio/hooks', [VideoStudioController::class, 'storeHook'])->name('video-studio.hooks.store');
    Route::patch('/video-studio/hooks/{hook}', [VideoStudioController::class, 'updateHook'])->name('video-studio.hooks.update');
    Route::delete('/video-studio/hooks/{hook}', [VideoStudioController::class, 'destroyHook'])->name('video-studio.hooks.destroy');
    Route::post('/video-studio/sources', [VideoStudioController::class, 'storeSource'])->name('video-studio.sources.store');
    Route::delete('/video-studio/sources/{source}', [VideoStudioController::class, 'destroySource'])->name('video-studio.sources.destroy');
    Route::post('/video-studio/jobs', [VideoStudioController::class, 'createJob'])->name('video-studio.jobs.store');
    Route::patch('/video-studio/jobs/{job}/settings', [VideoStudioController::class, 'updateJobSettings'])->name('video-studio.jobs.settings.update');
    Route::post('/video-studio/jobs/{job}/revise', [VideoStudioController::class, 'reviseJob'])->name('video-studio.jobs.revise');
    Route::post('/video-studio/jobs/bulk', [VideoStudioController::class, 'bulkAction'])->name('video-studio.jobs.bulk');
    Route::post('/video-studio/jobs/{job}/retry', [VideoStudioController::class, 'retryJob'])->name('video-studio.jobs.retry');
    Route::get('/products/categories', fn() => view('admin.products-categories'))->name('products.categories');
    Route::get('/products/pricing',    fn() => view('admin.products-pricing'))->name('products.pricing');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    // قابلیت‌های حرفه‌ای لیست محصولات
    Route::post('/products/{product}/duplicate', [ProductController::class, 'duplicate'])->name('products.duplicate');
    Route::patch('/products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle_status');
    Route::post('/products/bulk-action', [ProductController::class, 'bulkAction'])->name('products.bulk_action');

    // مدیریت مدل‌های هوش مصنوعی و providerهای فعال پروژه
    // کلید مرکزی روشن/خاموش provider — ابتدا ثبت می‌شود تا resource آن را نپوشاند.
    Route::post('ai-models/toggle-provider', [AiModelController::class, 'toggleProvider'])->name('ai-models.toggle-provider');
    Route::post('ai-models/{aiModel}/toggle', [AiModelController::class, 'toggleModel'])->name('ai-models.toggle-model');
    Route::post('ai-models/{aiModel}/toggle-product-selection', [AiModelController::class, 'toggleProductSelection'])->name('ai-models.toggle-product-selection');
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
    Route::get('/orders/plan-purchases', [OrderController::class, 'planPurchases'])->name('orders.plan-purchases');
    Route::get('/orders/plan-purchases/export', [OrderController::class, 'exportPlanPurchases'])->name('orders.plan-purchases.export');
    Route::get('/orders/refunds', [OrderController::class, 'refunds'])->name('orders.refunds');
    Route::get('/orders/analytics', [OrderController::class, 'analytics'])->name('orders.analytics');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/retry', [OrderController::class, 'retry'])->name('orders.retry');
    Route::patch('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::patch('/orders/{order}/refund', [OrderController::class, 'refund'])->name('orders.refund');
    Route::patch('/orders/{order}/note', [OrderController::class, 'note'])->name('orders.note');

    // حسابداری مدیریتی فاز اول؛ جداول آن از سفارش و خرید اصلی جدا هستند.
    Route::get('/finance/export', [\App\Http\Controllers\Admin\FinanceController::class, 'export'])->name('finance.export');
    Route::get('/finance/purchase-cases', [\App\Http\Controllers\Admin\FinanceCaseController::class, 'index'])->name('finance.cases.index');
    Route::post('/finance/purchase-cases/sync', [\App\Http\Controllers\Admin\FinanceCaseController::class, 'sync'])->name('finance.cases.sync');
    Route::get('/finance/purchase-cases/{financeCase}', [\App\Http\Controllers\Admin\FinanceCaseController::class, 'show'])->name('finance.cases.show');
    Route::patch('/finance/purchase-cases/{financeCase}', [\App\Http\Controllers\Admin\FinanceCaseController::class, 'update'])->name('finance.cases.update');
    Route::get('/finance/plans/{plan}', [\App\Http\Controllers\Admin\FinanceController::class, 'plan'])->name('finance.plans.show');
    Route::post('/finance/transactions', [\App\Http\Controllers\Admin\FinanceTransactionController::class, 'store'])->name('finance.transactions.store');
    Route::put('/finance/transactions/{transaction}', [\App\Http\Controllers\Admin\FinanceTransactionController::class, 'update'])->name('finance.transactions.update');
    Route::patch('/finance/transactions/{transaction}/approve', [\App\Http\Controllers\Admin\FinanceTransactionController::class, 'approve'])->name('finance.transactions.approve');
    Route::delete('/finance/transactions/{transaction}', [\App\Http\Controllers\Admin\FinanceTransactionController::class, 'destroy'])->name('finance.transactions.destroy');
    Route::post('/finance/exchange-rates', [\App\Http\Controllers\Admin\FinanceReferenceController::class, 'storeRate'])->name('finance.exchange-rates.store');
    Route::post('/finance/cost-centers', [\App\Http\Controllers\Admin\FinanceReferenceController::class, 'storeCostCenter'])->name('finance.cost-centers.store');
    Route::post('/finance/vendors', [\App\Http\Controllers\Admin\FinanceReferenceController::class, 'storeVendor'])->name('finance.vendors.store');
    Route::post('/finance/payment-methods', [\App\Http\Controllers\Admin\FinanceReferenceController::class, 'storePaymentMethod'])->name('finance.payment-methods.store');
    Route::put('/finance/settings', [\App\Http\Controllers\Admin\FinanceReferenceController::class, 'updateSettings'])->name('finance.settings.update');
    Route::post('/finance/sync', [\App\Http\Controllers\Admin\FinanceReferenceController::class, 'sync'])->name('finance.sync');
    Route::get('/finance/{section?}', [\App\Http\Controllers\Admin\FinanceController::class, 'show'])
        ->where('section', 'overview|cases|transactions|expenses|income|plans|products|exchange-rates|cost-centers|reports|settings')
        ->name('finance.show');

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
    Route::get('/jobs', [QueueController::class, 'index'])->name('jobs');
    Route::get('/jobs/snapshot', [QueueController::class, 'snapshot'])->name('jobs.snapshot');
    Route::post('/jobs/clear', [QueueController::class, 'clear'])->name('jobs.clear');
    Route::post('/jobs/failed/{failedJob}/retry', [QueueController::class, 'retryFailed'])->name('jobs.failed.retry');
    Route::delete('/jobs/failed/{failedJob}', [QueueController::class, 'forgetFailed'])->name('jobs.failed.forget');
    Route::get('/payments',         fn() => redirect()->route('admin.orders.plan-purchases'))->name('payments');
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

    Route::get('/settings/new-user-gift', [ReferralSettingController::class, 'newUserGift'])->name('settings.new-user-gift');
    Route::put('/settings/new-user-gift', [ReferralSettingController::class, 'updateNewUserGift'])->name('settings.new-user-gift.update');

    // سازگاری با نشانی قبلی؛ بخش همکاری در فروش اکنون منوی مستقل دارد.
    Route::get('/settings/referrals', [ReferralSettingController::class, 'index'])->name('settings.referrals');
    Route::put('/settings/referrals', [ReferralSettingController::class, 'update'])->name('settings.referrals.update');
    Route::get('/settings/referrals/export', [ReferralSettingController::class, 'export'])->name('settings.referrals.export');
    Route::patch('/settings/referrals/conversions/{conversion}/review', [ReferralSettingController::class, 'reviewConversion'])->name('settings.referrals.conversions.review');
    Route::patch('/settings/referrals/rewards/{reward}/review', [ReferralSettingController::class, 'reviewReward'])->name('settings.referrals.rewards.review');
    Route::get('/settings/system',           fn() => view('admin.settings.system'))->name('settings.system');

    // ماژول مستقل رشد و جذب؛ تمام مسیرها و داده‌های آن از سایر بخش‌های داشبورد جدا هستند.
    Route::prefix('growth')->name('growth.')->group(function () {
        Route::get('/', [GrowthController::class, 'monitor'])->name('monitor');
        Route::get('/monitor', [GrowthController::class, 'monitor'])->name('monitor.explicit');
        Route::get('/overview', [GrowthController::class, 'overview'])->name('overview');
        Route::get('/users', [GrowthUserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [GrowthUserController::class, 'show'])->name('users.show');
        Route::get('/channels/{channel?}', [GrowthController::class, 'channels'])->name('channels');
        Route::get('/contents', [GrowthController::class, 'contents'])->name('contents');
        Route::post('/contents', [GrowthController::class, 'storeContent'])->name('contents.store');
        Route::patch('/contents/{growthContent}', [GrowthController::class, 'updateContent'])->name('contents.update');
        Route::get('/links', [GrowthController::class, 'links'])->name('links.index');
        Route::get('/links/create', [GrowthController::class, 'createLink'])->name('links.create');
        Route::post('/links', [GrowthController::class, 'storeLink'])->name('links.store');
        Route::get('/links/analytics/{growthLink?}', [GrowthController::class, 'linkAnalytics'])->name('links.analytics');
        Route::get('/settings/data-sources', [GrowthDataSourceController::class, 'index'])->name('data-sources.index');
        Route::post('/settings/data-sources', [GrowthDataSourceController::class, 'store'])->name('data-sources.store');
        Route::put('/settings/data-sources/{growthDataSource}', [GrowthDataSourceController::class, 'update'])->name('data-sources.update');
        Route::patch('/settings/data-sources/{growthDataSource}/toggle', [GrowthDataSourceController::class, 'toggle'])->name('data-sources.toggle');
        Route::patch('/settings/data-sources/{growthDataSource}/clear-error', [GrowthDataSourceController::class, 'clearError'])->name('data-sources.clear-error');
        Route::post('/settings/data-sources/{growthDataSource}/mappings', [GrowthDataSourceController::class, 'storeMapping'])->name('data-sources.mappings.store');
        Route::delete('/settings/data-sources/{growthDataSource}/mappings/{growthDataMapping}', [GrowthDataSourceController::class, 'destroyMapping'])->name('data-sources.mappings.destroy');
        Route::post('/settings/data-sources/import/csv', [GrowthDataSourceController::class, 'importCsv'])->name('data-sources.import.csv');
        Route::get('/{section}', [GrowthController::class, 'section'])
            ->where('section', 'attribution|products|sales|retention|reports|settings')
            ->name('section');
    });

    // ماژول مستقل تکنولوژی مارکتینگ؛ نسخه‌ی توسعه‌پذیر مرکز رشد و اتوماسیون وطن.
    // این مسیرها عمداً از ماژول فعلی رشد جدا هستند و در فاز اتصال، به همان داده‌ها و رویدادهای استاندارد متصل می‌شوند.
    Route::prefix('marketing-technology')->name('marketing-technology.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'index'])->name('index');
        Route::get('/content-calendar', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'contentCalendar'])->name('content-calendar');
        Route::post('/content-calendar', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'storeContent'])->name('content-calendar.store');
        Route::patch('/content-calendar/{marketingContent}', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'updateContent'])->name('content-calendar.update');
        Route::post('/content-calendar/{marketingContent}/queue', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'queueContent'])->name('content-calendar.queue');
        Route::get('/scenarios', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'scenarios'])->name('scenarios');
        Route::post('/scenarios', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'storeScenario'])->name('scenarios.store');
        Route::patch('/scenarios/{marketingScenario}', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'updateScenario'])->name('scenarios.update');
        Route::get('/inbox', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'inbox'])->name('inbox');
        Route::post('/logs/{marketingOperationRun}/retry', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'retryOperation'])->name('logs.retry');
        Route::get('/reports', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'reports'])->name('reports');
        Route::get('/costs', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'costs'])->name('costs');
        Route::post('/costs', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'storeCost'])->name('costs.store');
        Route::get('/integrations', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'integrations'])->name('integrations');
        Route::post('/integrations/meta', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'storeMetaIntegration'])->name('integrations.meta.store');
        Route::post('/integrations/meta/{marketingIntegration}/test', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'testMetaIntegration'])->name('integrations.meta.test');
        Route::get('/logs', [\App\Http\Controllers\Admin\MarketingTechnologyController::class, 'logs'])->name('logs');
    });

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
    Route::post('/users/bulk-token',         [AdminUserController::class, 'bulkUpdateToken'])->name('users.bulk_token.update');
    Route::post('/token-history/{id}/sms',   [AdminUserController::class, 'resendTokenSms'])->name('token_history.sms');
    Route::get('/token-history',             [AdminUserController::class, 'globalTokenHistory'])->name('token_history');
});
