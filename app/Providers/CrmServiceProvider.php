<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class CrmServiceProvider extends ServiceProvider
{
    /**
     * CRM — سرویس پروایدر مستقل
     * همه routes و migrations مربوط به CRM از اینجا لود میشن
     * هیچ تاثیری روی بقیه قسمت‌های پروژه ندارد
     */
    public function boot(): void
    {
        // ── Migrations ────────────────────────────────────────
        $this->loadMigrationsFrom(database_path('migrations/crm'));

        // ── Routes ────────────────────────────────────────────
        if (! $this->app->routesAreCached()) {
            require base_path('routes/crm.php');
        }
    }
}
