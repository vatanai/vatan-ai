<?php

namespace Vatan\Content\Console;

use Illuminate\Console\Command;
use Vatan\Content\Models\Production;
use Vatan\Content\Services\InstagramBridge;
use Vatan\Content\Services\LearningAnalyzer;
use Vatan\Content\Services\Scorer;

/** برداشت آمار زمان‌مند پست‌های منتشرشده (۱۰ دقیقه تا ۲۸ روز) از اینستاگرام هوشمند + امتیازدهی */
class MetricsCommand extends Command
{
    protected $signature = 'content:metrics';
    protected $description = 'برداشت آمار زمان‌مند و امتیازدهی تولیدهای منتشرشده';

    public function handle(InstagramBridge $bridge, Scorer $scorer, LearningAnalyzer $learning): int
    {
        $n = 0;
        $connected = $bridge->available();
        if (! $connected) {
            $this->warn('اتصال اینستاگرام هوشمند در دسترس نیست؛ داده‌های دستی و ثبت‌شده تحلیل می‌شوند.');
        }
        Production::where('status', 'published')->whereNotNull('published_at')->where('published_at', '>=', now()->subDays(90))->each(function ($p) use ($bridge, $scorer, &$n, $connected) {
            if ($connected) {
                $n += $bridge->snapshot($p);
            }
            // با اضافه‌شدن نمونه‌های جدید، امتیازهای قبلی هم با همان گروه مقایسه بازحساب می‌شوند.
            $scorer->score($p->fresh());
        });
        $insights = $learning->refresh((int) config('content-factory.workspace_id', 1));
        $this->info("{$n} برداشت ثبت شد.");
        $this->line("{$insights} فرضیه‌ی یادگیری تازه یا به‌روزشده.");

        return self::SUCCESS;
    }
}
