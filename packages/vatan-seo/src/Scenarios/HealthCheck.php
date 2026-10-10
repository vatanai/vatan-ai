<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Checks\CheckRegistry;
use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\Fetcher;
use Vatan\Seo\Services\Notifier;

class HealthCheck implements Handler
{
    public function __construct(private CheckRegistry $checks, private Fetcher $fetcher, private Notifier $notifier) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $home = $this->fetcher->get($site->url('/'));
        $results = ['home' => ['pass' => $home['status'] === 200, 'message' => 'صفحه‌ی اصلی: HTTP '.$home['status'].' در '.$home['ms'].'ms']];
        foreach (['robots' => 'robots', 'sitemap' => 'sitemap', 'https' => 'https'] as $key => $check) {
            $r = $this->checks->run($site, new Task(['check' => $check]));
            $results[$key] = ['pass' => $r->pass, 'message' => $r->message];
        }
        $previous = Audit::where('site_id', $site->id)->where('type', 'health')->latest()->first();
        $broken = [];
        foreach ($results as $key => $r) {
            $wasOk = data_get($previous?->summary, "{$key}.pass", true) !== false;
            if ($r['pass'] === false && $wasOk) {
                $broken[] = $r['message'];
            }
        }
        Audit::create(['site_id' => $site->id, 'type' => 'health', 'score' => (int) round(count(array_filter($results, fn ($r) => $r['pass'] !== false)) / count($results) * 100), 'summary' => $results]);
        if ($broken) {
            $this->notifier->alert($site, 'danger', 'health', 'مشکل جدید در سلامت سایت', implode("\n", $broken));
        }
        $fails = array_filter($results, fn ($r) => $r['pass'] === false);
        return [$fails ? 'warning' : 'success', $fails ? 'مشکل: '.implode(' | ', array_column($fails, 'message')) : 'همه‌ی بررسی‌های سلامت قبول شد.', $results];
    }
}
