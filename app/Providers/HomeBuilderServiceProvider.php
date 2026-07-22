<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HomeBuilderServiceProvider extends ServiceProvider
{
    /**
     * فیچر Home Builder — سرویس‌پروایدر مستقل.
     * همه‌ی Migrationها و Routeهای این بخش از اینجا لود می‌شوند، دقیقاً مطابق الگوی
     * ExploreServiceProvider / CrmServiceProvider. هیچ تاثیری روی بقیه‌ی قسمت‌های پروژه ندارد.
     */
    public function boot(): void
    {
        // ── Migrations ────────────────────────────────────────
        $this->loadMigrationsFrom(database_path('migrations/home-builder'));

        // ── Routes ────────────────────────────────────────────
        if (! $this->app->routesAreCached()) {
            require base_path('routes/home-builder.php');
        }
    }
}
