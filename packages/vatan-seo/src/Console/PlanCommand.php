<?php

namespace Vatan\Seo\Console;

use Illuminate\Console\Command;
use Vatan\Seo\Services\RoadmapPlanner;
use Vatan\Seo\Services\SiteManager;
use Vatan\Seo\Support\Fa;

class PlanCommand extends Command
{
    protected $signature = 'seo:plan {--reschedule : تاریخ تسک‌های باز را دوباره از برنامه‌ی ۹۰ روزه تنظیم کن} {--start= : تاریخ شروع پروژه (Y-m-d)}';
    protected $description = 'ساخت/تکمیل برنامه‌ی ۹۰ روزه‌ی سئو (روزانه، هفتگی، نقطه‌های عطف و موج کلمات) تا ۱۳ هفته جلوتر';

    public function handle(SiteManager $sites, RoadmapPlanner $planner): int
    {
        $site = $sites->current();
        if ($start = $this->option('start')) {
            $site->update(['started_on' => $start]);
        }
        $r = $planner->ensure($site, (bool) $this->option('reschedule') || (bool) $this->option('start'));
        $w = $planner->currentWeek($site);
        $this->info('هفته‌ی جاری: '.$w.' — '.$planner->theme($site, $w)['theme']);
        $this->info("تسک تازه: {$r['created']} · زمان‌بندی دوباره: {$r['rescheduled']} · منقضی: {$r['expired']}");
        $this->line('شروع برنامه: '.Fa::date($planner->startSaturday($site)).' · افق تا هفته‌ی '.$site->setting('roadmap.horizon_week'));
        return self::SUCCESS;
    }
}
