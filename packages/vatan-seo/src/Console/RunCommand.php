<?php

namespace Vatan\Seo\Console;

use Illuminate\Console\Command;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Services\ScenarioRunner;
use Vatan\Seo\Services\SiteManager;

class RunCommand extends Command
{
    protected $signature = 'seo:run {scenario : کلید سناریو مثل gsc_sync یا crawl_audit}';
    protected $description = 'اجرای دستی یک سناریو';

    public function handle(SiteManager $sites, ScenarioRunner $runner): int
    {
        @set_time_limit(0);
        $scenario = Scenario::where('site_id', $sites->current()->id)->where('key', $this->argument('scenario'))->first();
        if (! $scenario) {
            $this->error('سناریو پیدا نشد. کلیدها: '.Scenario::pluck('key')->implode(', '));
            return self::FAILURE;
        }
        $run = $runner->run($scenario, 'manual');
        $this->line(($run?->status ?? 'locked').' — '.($run?->summary ?? 'در حال اجرا توسط فرایند دیگر'));
        return self::SUCCESS;
    }
}
