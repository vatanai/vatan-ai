<?php

namespace Vatan\Seo;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Ai\ModelRouter;
use Vatan\Seo\Ai\OpenRouterClient;
use Vatan\Seo\Services\SiteManager;
use Vatan\Seo\Telegram\SeoBot;

/**
 * Vatan SEO Engine — نقطه‌ی ورود پکیج.
 * همه‌چیز (کانفیگ، مایگریشن، ویو، روت، کامند و زمان‌بند) از داخل پکیج ثبت می‌شود؛
 * میزبان فقط این Provider را ثبت و یک خط منو اضافه می‌کند.
 */
class SeoEngineServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $base = dirname(__DIR__);
        $this->mergeConfigFrom($base.'/config/seo-engine.php', 'seo-engine');
        $this->mergeConfigFrom($base.'/config/budget-tiers.php', 'seo-budget-tiers');
        $this->mergeConfigFrom($base.'/config/help.php', 'seo-help');

        $this->app->singleton(OpenRouterClient::class);
        $this->app->singleton(ModelRouter::class);
        $this->app->singleton(Ai::class);
        $this->app->singleton(SeoBot::class);
        $this->app->scoped(SiteManager::class);
    }

    public function boot(): void
    {
        $base = dirname(__DIR__);
        $this->loadMigrationsFrom($base.'/database/migrations');
        $this->loadViewsFrom($base.'/resources/views', 'seo');
        $this->loadRoutesFrom($base.'/routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\TickCommand::class,
                Console\InstallCommand::class,
                Console\RunCommand::class,
                Console\TelegramCommand::class,
                Console\PlanCommand::class,
                Console\AiTestCommand::class,
            ]);
            $this->publishes([$base.'/config/seo-engine.php' => config_path('seo-engine.php')], 'seo-engine-config');
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $minutes = max(1, (int) config('seo-engine.tick_every_minutes', 5));
            $schedule->command('seo:tick')->cron("*/{$minutes} * * * *")->withoutOverlapping(30)->runInBackground();
        });
    }
}
