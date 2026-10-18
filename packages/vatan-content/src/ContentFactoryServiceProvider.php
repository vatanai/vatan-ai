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

        // روی مک (محیط local) بعد از به‌روزرسانی پکیج، مایگریشن‌های کارخانه خودکار یک بار اجرا می‌شوند تا لازم نباشد دستی بزنید.
        if (! $this->app->runningInConsole() && $this->app->environment('local')) {
            $this->app->booted(function () use ($base) {
                try {
                    $ver = trim((string) @file_get_contents($base.'/VERSION'));
                    $flag = storage_path('framework/cf-migrated-'.$ver);
                    if ($ver && ! is_file($flag)) {
                        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true, '--path' => 'packages/vatan-content/database/migrations']);
                        @touch($flag);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('ContentFactory auto-migrate: '.$e->getMessage());
                }
            });
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('content:metrics')->everyTenMinutes()->withoutOverlapping(20)->runInBackground();
        });
    }
}
