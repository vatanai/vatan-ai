<?php

namespace App\Providers;

use App\Services\PlanCatalogService;
use App\Models\User;
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
