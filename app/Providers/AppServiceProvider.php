<?php

namespace App\Providers;

use App\Services\PlanCatalogService;
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
            $catalog = $service->catalog(auth()->user());
            $view->with('homePlans', $catalog['plans']->take((int) ($catalog['planDisplay']['home_limit'] ?? 3)));
            $view->with('planDisplay', $catalog['planDisplay']);
        });
    }
}
