<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Agents\Insights;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;

class ContentDecay implements Handler
{
    public function __construct(private Insights $insights) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $items = $this->insights->decay($site);
        return [$items ? 'warning' : 'success', $items ? Fa::n(count($items)).' صفحه افت کلیک بیش از ۳۰٪ داشته و در صف به‌روزرسانی قرار گرفت.' : 'افت محتوای معناداری دیده نشد.', ['items' => $items]];
    }
}
