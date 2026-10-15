<?php

namespace Vatan\Content\Console;

use Illuminate\Console\Command;
use Vatan\Content\Models\Production;
use Vatan\Content\Services\InstagramBridge;

/** برداشت آمار زمان‌مند پست‌های منتشرشده (۱۰ دقیقه تا ۲۸ روز) از اینستاگرام هوشمند + امتیازدهی */
class MetricsCommand extends Command
{
    protected $signature = 'content:metrics';
    protected $description = 'برداشت آمار زمان‌مند و امتیازدهی تولیدهای منتشرشده';

    public function handle(InstagramBridge $bridge): int
    {
        if (! $bridge->available()) {
            $this->warn('اینستاگرام هوشمند در دسترس نیست.');

            return self::SUCCESS;
        }
        $n = 0;
        Production::where('status', 'published')->whereNotNull('published_at')->where('published_at', '>=', now()->subDays(30))->each(function ($p) use ($bridge, &$n) {
            $n += $bridge->snapshot($p);
        });
        $this->info("{$n} برداشت ثبت شد.");

        return self::SUCCESS;
    }
}
