<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Data\Google\ServiceAccount;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\GscSync as Sync;
use Vatan\Seo\Support\Fa;

class GscSync implements Handler
{
    public function __construct(private Sync $sync) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        if (! ServiceAccount::configured()) {
            return ['skipped', 'سرچ کنسول هنوز متصل نیست (تنظیمات ← اتصال‌ها).'];
        }
        $r = $this->sync->sync($site);
        return ['success', sprintf('%s روز همگام شد: %s ردیف روزانه و %s ردیف کوئری/صفحه.', Fa::n($r['days']), Fa::n($r['daily_rows']), Fa::n($r['detail_rows'])), $r];
    }
}
