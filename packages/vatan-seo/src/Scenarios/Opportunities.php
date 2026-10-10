<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Agents\Insights;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;

class Opportunities implements Handler
{
    public function __construct(private Insights $insights) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $r = $this->insights->opportunities($site);
        if (! $r['striking'] && ! $r['low_ctr']) {
            return ['skipped', 'داده‌ی کافی از سرچ کنسول برای کشف فرصت نیست.'];
        }
        $site->putSetting('last_opportunities', ['at' => now()->toDateTimeString()] + $r);
        $site->save();
        return ['success', sprintf('%s کلمه نزدیک صفحه‌ی اول و %s کوئری با CTR پایین پیدا شد.', Fa::n(count($r['striking'])), Fa::n(count($r['low_ctr']))), $r];
    }
}
