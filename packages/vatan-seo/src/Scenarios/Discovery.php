<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Agents\Clustering;
use Vatan\Seo\Agents\KeywordDiscovery;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

class Discovery implements Handler
{
    public function __construct(private KeywordDiscovery $discovery, private Clustering $clustering) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $limit = Budget::limit($site, 'discovery_runs_per_month', 2);
        $runs = Run::where('site_id', $site->id)->where('action', 'discovery')->where('status', 'success')->where('created_at', '>=', now()->startOfMonth())->count();
        if ($runs >= $limit) {
            return ['skipped', 'سقف کشف ماهانه‌ی پروفایل ('.Fa::n($limit).' بار) پر شده است.'];
        }
        $r = $this->discovery->run($site);
        if ($site->keywords()->targets()->count() >= 3) {
            $r['clustering'] = $this->clustering->run($site);
        }
        return ['success', sprintf('%s محصول بررسی شد؛ %s پیشنهاد تازه و %s به‌روزرسانی.%s', Fa::n($r['products']), Fa::n($r['created']), Fa::n($r['updated']), $r['ai'] ? '' : ' (بدون هوش مصنوعی)'), $r];
    }
}
