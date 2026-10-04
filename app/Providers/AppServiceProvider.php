<?php

namespace App\Providers;

use App\Services\PlanCatalogService;
use App\Models\User;
use Illuminate\Support\Facades\Vite;
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
        // در production هر فایل hot باقی‌مانده از توسعه باید نادیده گرفته شود؛
        // وگرنه @vite به آدرس محلی Vite مثل [::1]:5173 اشاره می‌کند و CSS/فونت‌ها
        // از جمله آیکون‌ها بارگذاری نمی‌شوند.
        Vite::useHotFile($this->app->environment('local')
            ? storage_path('framework/vite.hot')
            : storage_path('framework/vite-production-disabled'));

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
