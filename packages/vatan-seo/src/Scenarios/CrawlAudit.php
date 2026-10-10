<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\Crawler;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

class CrawlAudit implements Handler
{
    public function __construct(private Crawler $crawler, private AuditRunner $runner) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $limit = max(20, Budget::limit($site, 'crawl_pages', 150));
        $audit = $this->crawler->crawl($site, $limit);
        $this->runner->handle($site, $scenario, 'crawl', true);
        $this->runner->handle($site, $scenario, 'schema', true);
        $this->runner->handle($site, $scenario, 'kw:internal_links', true);
        $issues = collect($audit->issues)->map(fn ($i) => $i['title'].': '.Fa::n($i['count']))->take(4)->implode(' | ');
        return ['success', sprintf('%s صفحه خزیده شد، امتیاز فنی %s از ۱۰۰. %s', Fa::n($audit->summary['pages'] ?? 0), Fa::n($audit->score), $issues), ['audit_id' => $audit->id]];
    }
}
