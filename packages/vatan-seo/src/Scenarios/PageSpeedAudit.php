<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Data\Google\PageSpeed;
use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;

class PageSpeedAudit implements Handler
{
    public function __construct(private PageSpeed $psi, private AuditRunner $runner) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $urls = array_values(array_unique(array_filter([
            $site->url('/'),
            $site->keywords()->targets()->whereNotNull('target_url')->value('target_url'),
            ContentItem::where('site_id', $site->id)->where('status', 'published')->latest('published_at')->value('published_url'),
        ])));
        $pages = [];
        $errors = [];
        foreach ($urls as $i => $url) {
            foreach ($i === 0 ? ['mobile', 'desktop'] : ['mobile'] as $strategy) {
                try {
                    $pages[] = $this->psi->run($url, $strategy);
                } catch (\Throwable $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        if (! $pages) {
            return ['failed', 'PageSpeed پاسخ نداد: '.($errors[0] ?? 'نامشخص')];
        }
        $homeMobile = collect($pages)->first(fn ($p) => $p['strategy'] === 'mobile');
        $audit = Audit::create([
            'site_id' => $site->id, 'type' => 'pagespeed', 'score' => $homeMobile['scores']['performance'] ?? null,
            'summary' => ['urls' => $urls, 'errors' => $errors], 'pages' => $pages,
        ]);
        $this->runner->handle($site, $scenario, 'pagespeed', true);
        return ['success', sprintf('سرعت موبایل صفحه‌ی اصلی: %s از ۱۰۰ · سئو: %s · LCP: %s ثانیه', Fa::n($homeMobile['scores']['performance'] ?? 0), Fa::n($homeMobile['scores']['seo'] ?? 0), Fa::n(round(($homeMobile['field']['lcp_ms'] ?? $homeMobile['lab']['lcp_ms'] ?? 0) / 1000, 1))), ['audit_id' => $audit->id]];
    }
}
