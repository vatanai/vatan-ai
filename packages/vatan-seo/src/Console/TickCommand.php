<?php

namespace Vatan\Seo\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Vatan\Seo\Services\ScenarioRunner;

class TickCommand extends Command
{
    protected $signature = 'seo:tick {--max=3 : حداکثر سناریو در هر تیک}';
    protected $description = 'موتور سئو: اجرای سناریوهای سررسیده (هر ۵ دقیقه از زمان‌بند لاراول)';

    public function handle(ScenarioRunner $runner): int
    {
        if (! Schema::hasTable('seo_scenarios')) {
            return self::SUCCESS;
        }
        @set_time_limit(1500);
        foreach ($runner->runDue((int) $this->option('max')) as $line) {
            $this->line($line);
        }
        return self::SUCCESS;
    }
}
