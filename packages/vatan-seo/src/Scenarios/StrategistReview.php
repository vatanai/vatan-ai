<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Agents\Advisor;
use Vatan\Seo\Agents\Strategist;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\RoadmapPlanner;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Telegram\SeoBot;

/** شنبه صبح: برنامه‌ی هفته + (هر دو هفته) پایش دیده‌شدن در AI + (اگر رقیبی ثبت نشده) کشف رقبا */
class StrategistReview implements Handler
{
    public function __construct(private Strategist $strategist, private Advisor $advisor, private Ai $ai, private SeoBot $bot, private RoadmapPlanner $planner) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $this->planner->ensure($site);
        $plan = $this->strategist->weekly($site);
        $extra = [];

        $research = $this->ai->available($site, 'research') && $site->keywords()->targets()->exists();
        if ($research && ! $site->setting('competitors_detail.at')) {
            try {
                $extra[] = Fa::n(count($this->advisor->competitors($site))).' رقیب کشف شد';
            } catch (\Throwable $e) {
                $extra[] = 'کشف رقبا ناموفق: '.mb_substr($e->getMessage(), 0, 80);
            }
        }
        $week = $plan['week'];
        if ($research && $week >= 7 && ($week - 7) % 2 === 0) {
            try {
                $g = $this->advisor->geoProbe($site);
                $extra[] = 'دیده‌شدن در AI: '.Fa::n($g['mentioned']).' از '.Fa::n($g['asked']);
            } catch (\Throwable $e) {
                $extra[] = 'پایش AI ناموفق';
            }
        }

        if ($this->bot->configured()) {
            $lines = collect($plan['actions'] ?? [])->take(5)->map(fn ($a, $i) => Fa::n($i + 1).'. '.e($a['title']))->implode("\n");
            $this->bot->broadcast("🧭 <b>برنامه‌ی هفته‌ی ".Fa::n($week).' — '.e($plan['theme']['theme'])."</b>\n\n<b>تمرکز:</b> ".e($plan['focus'] ?? '')."\n\n".$lines.($extra ? "\n\n".e(implode(' · ', $extra)) : ''));
        }
        return ['success', sprintf('برنامه‌ی هفته‌ی %s (%s): %s اقدام ثبت شد.%s', Fa::n($week), $plan['source'] === 'ai' ? 'هوش مصنوعی' : 'قاعده‌محور', Fa::n(count($plan['actions'] ?? [])), $extra ? ' '.implode(' · ', $extra) : ''), $plan];
    }
}
