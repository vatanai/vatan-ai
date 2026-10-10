<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Agents\Insights;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\Installer;
use Vatan\Seo\Support\Fa;

class Cannibalization implements Handler
{
    public function __construct(private Insights $insights, private Installer $installer) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $items = $this->insights->cannibalization($site);
        $site->putSetting('last_cannibalization', ['at' => now()->toDateTimeString(), 'items' => $items]);
        $site->save();
        if ($items) {
            $this->installer->task($site, ['key' => 'opp.cannibalization', 'title' => 'رفع همنوع‌خواری در '.Fa::n(count($items)).' کوئری', 'pillar' => 'goals', 'kind' => 'opportunity', 'category' => 'strategy', 'impact' => 4, 'effort' => 3, 'automation' => 'manual',
                'why' => 'چند صفحه برای یک کوئری رقابت می‌کنند. یکی را صفحه‌ی اصلی کنید و بقیه را ادغام، canonical یا با لینک داخلی به آن وصل کنید.'], now()->addDays(10), null, now()->format('Y-m'));
        }
        return ['success', Fa::n(count($items)).' مورد همنوع‌خواری پیدا شد.', ['items' => $items]];
    }
}
