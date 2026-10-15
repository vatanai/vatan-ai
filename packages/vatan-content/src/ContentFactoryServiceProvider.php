<?php

namespace Vatan\Content;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * کارخانه‌ی محتوای وطن — نقطه‌ی ورود پکیج (الگوی packages/vatan-seo).
 * میزبان فقط این Provider را ثبت و یک خط منو include می‌کند.
 */
class ContentFactoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/config/content-factory.php', 'content-factory');
        $this->mergeConfigFrom(dirname(__DIR__).'/config/help.php', 'content-factory-help');
    }

    public function boot(): void
    {
        $base = dirname(__DIR__);
        $this->loadMigrationsFrom($base.'/database/migrations');
        $this->loadViewsFrom($base.'/resources/views', 'cf');
        $this->loadRoutesFrom($base.'/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\SyncLabCommand::class,
                Console\MetricsCommand::class,
                Console\SeedCommand::class,
            ]);
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('content:metrics')->everyTenMinutes()->withoutOverlapping(20)->runInBackground();
        });
    }
}
