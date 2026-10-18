<?php

namespace Vatan\Content\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Vatan\Content\Models\Production;

/** فهرست بخش‌های کارخانه (یک منبع برای تب‌ها و سایدبار) */
class Nav
{
    public static function links(): array
    {
        return [
            ['cf.overview', 'fa-gauge-high', 'فرماندهی', 'cf.overview'],
            ['cf.calendar', 'fa-calendar-days', 'تقویم محتوایی', 'cf.calendar*|cf.item*'],
            ['cf.productions', 'fa-clapperboard', 'صف ساخت و انتشار', 'cf.productions*'],
            ['cf.styles', 'fa-swatchbook', 'بانک سبک‌ها', 'cf.styles*'],
            ['cf.music', 'fa-music', 'کتابخانه‌ی موسیقی', 'cf.music*'],
            ['cf.prompts', 'fa-flask', 'آزمایشگاه پرامپت', 'cf.prompts*'],
            ['cf.insights', 'fa-chart-line', 'تحلیل و یادگیری', 'cf.insights'],
            ['cf.settings', 'fa-sliders', 'تنظیمات و اتصال‌ها', 'cf.settings*'],
        ];
    }

    public static function reviewCount(): int
    {
        try {
            return (int) Cache::remember('cf:review-badge', 60, fn () => Schema::hasTable('cf_productions')
                ? Production::where('status', 'review')->count() : 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}
