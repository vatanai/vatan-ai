<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Facades\Schema;
use Vatan\Seo\Models\Site;

/** سایت فعال پنل. نسخه‌ی ۰٫۱ تک‌سایتی است ولی همه‌چیز بر اساس site_id ساخته شده (آماده‌ی چندسایتی). */
class SiteManager
{
    protected ?Site $current = null;

    public function ready(): bool
    {
        return Schema::hasTable('seo_sites');
    }

    public function current(): Site
    {
        if ($this->current) {
            return $this->current;
        }
        $id = session('seo_site_id');
        $site = $id ? Site::find($id) : null;
        $site ??= Site::query()->where('is_active', true)->orderBy('id')->first();
        $site ??= $this->createDefault();

        return $this->current = $site;
    }

    public function createDefault(): Site
    {
        $url = rtrim((string) config('seo-engine.host.url'), '/');
        $domain = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST)) ?: 'example.com';
        $site = Site::create([
            'name' => (string) config('seo-engine.host.name', $domain),
            'domain' => $domain,
            'base_url' => $url,
            'platform' => 'laravel_local',
            'gsc_property' => config('seo-engine.google.gsc_property') ?: 'sc-domain:'.$domain,
            'ga4_property' => config('seo-engine.google.ga4_property'),
            'monthly_budget_usd' => 20,
            'niche' => 'پلتفرم ساخت عکس و ویدیوی محصول با هوش مصنوعی برای کسب‌وکارها',
            'started_on' => now()->toDateString(),
            'settings' => [],
        ]);
        app(Installer::class)->install($site);

        return $site;
    }

    public function setCurrent(Site $site): void
    {
        $this->current = $site;
    }
}
