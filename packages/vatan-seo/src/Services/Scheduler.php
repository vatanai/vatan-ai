<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Carbon;
use Vatan\Seo\Models\Scenario;

/** محاسبه‌ی زمان اجرای بعدی سناریو (به وقت تهران، ذخیره به UTC/زمان اپ) */
class Scheduler
{
    public function effectiveFrequency(Scenario $s): string
    {
        $profileKey = $s->config['profile_key'] ?? null;
        if ($profileKey && $s->follow_profile && $s->site) {
            return (string) data_get($s->site->profile(), "schedules.{$profileKey}", $s->frequency);
        }
        return $s->frequency;
    }

    public function nextRun(Scenario $s, ?Carbon $from = null): Carbon
    {
        $tz = config('seo-engine.host.timezone', 'Asia/Tehran');
        $from = ($from ?? now())->copy()->setTimezone($tz);
        [$h, $m] = array_map('intval', explode(':', $s->at ?: '06:00') + [1 => 0]);
        $freq = $this->effectiveFrequency($s);

        $candidate = $from->copy()->setTime($h, $m);
        switch ($freq) {
            case 'weekly':
                $wd = $s->weekday ?? 6;
                while ($candidate->dayOfWeek !== (int) $wd || $candidate->lte($from)) {
                    $candidate->addDay()->setTime($h, $m);
                }
                break;
            case 'twice_weekly':
                $wd = $s->weekday ?? 6;
                $days = [(int) $wd, ((int) $wd + 3) % 7];
                while (! in_array($candidate->dayOfWeek, $days, true) || $candidate->lte($from)) {
                    $candidate->addDay()->setTime($h, $m);
                }
                break;
            case 'monthly':
                $day = max(1, min(28, (int) ($s->day ?? 1)));
                $candidate = $from->copy()->setDay($day)->setTime($h, $m);
                if ($candidate->lte($from)) {
                    $candidate = $from->copy()->addMonthNoOverflow()->setDay($day)->setTime($h, $m);
                }
                break;
            default: // daily
                if ($candidate->lte($from)) {
                    $candidate->addDay();
                }
        }

        return $candidate->setTimezone(config('app.timezone', 'UTC'));
    }
}
