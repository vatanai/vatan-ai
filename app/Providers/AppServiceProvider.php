<?php

namespace App\Providers;

use App\Services\PlanCatalogService;
use App\Models\ReferralSetting;
use App\Models\Generation;
use App\Models\AiProviderRequest;
use App\Models\Order;
use App\Models\PlanPurchase;
use App\Models\User;
use App\Observers\FinanceSourceObserver;
use App\Observers\GrowthConversionObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // شنودگر رشد فقط در جدول مستقل خود می‌نویسد و خطای آن هرگز عملیات
        // اصلی ساخت، سفارش یا خرید پلن را متوقف نمی‌کند.
        Generation::observe(GrowthConversionObserver::class);
        Order::observe(GrowthConversionObserver::class);
        PlanPurchase::observe(GrowthConversionObserver::class);

        // اسنپ‌شات‌های مالی کاملاً جدا از جداول هسته نوشته می‌شوند و خطای احتمالی
        // آن‌ها به‌وسیله شنودگر مهار می‌شود تا خرید و اجرای محصول آسیب نبینند.
        PlanPurchase::observe(FinanceSourceObserver::class);
        Order::observe(FinanceSourceObserver::class);
        AiProviderRequest::observe(FinanceSourceObserver::class);

        View::composer('site.preview.partials.header', function ($view) {
            $user = auth()->user();

            try {
                $giftSettings = ReferralSetting::current();
                $newUserGiftTokens = $giftSettings->registration_gift_enabled
                    ? (int) $giftSettings->registration_gift_tokens
                    : 0;
            } catch (\Throwable $exception) {
                report($exception);
                $newUserGiftTokens = 0;
            }

            $isGuest = ! ($user instanceof User);
            $view->with([
                'headerTokenCount' => $isGuest
                    ? $newUserGiftTokens
                    : (int) ($user->effective_token_balance ?? $user->token_balance ?? $user->tokens ?? 0),
                'headerTokenLabel' => $isGuest ? 'هدیه' : '',
                'headerTokenTitle' => $isGuest
                    ? 'هدیه شروع کاربران جدید'
                    : 'موجودی اعتبار شما',
            ]);
        });

        View::composer('site.home', function ($view) {
            $service = app(PlanCatalogService::class);
            $user = auth()->user();
            // اگر نشست متعلق به نسخه‌ی قدیمی یا گارد دیگری باشد، صفحه‌ی عمومی
            // نباید به‌خاطر نوع متفاوت کاربر از رندر خارج شود.
            $catalog = $service->catalog($user instanceof User ? $user : null);
            $view->with('homePlans', $catalog['plans']->take((int) ($catalog['planDisplay']['home_limit'] ?? 3)));
            $view->with('planDisplay', $catalog['planDisplay']);
        });
    }
}
