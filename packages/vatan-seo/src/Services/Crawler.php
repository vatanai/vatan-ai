<?php

namespace Vatan\Seo\Services;

use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\Site;

/**
 * خزنده‌ی سبک: از صفحه‌ی اصلی و نقشه‌ی سایت شروع می‌کند (BFS) تا سقف صفحات پروفایل.
 * خروجی یک ممیزی (seo_audits type=crawl) با امتیاز، خلاصه و فهرست مشکلات است.
 */
class Crawler
{
    public function __construct(private Fetcher $fetcher, private PageParser $parser) {}

    public function crawl(Site $site, int $limit): Audit
    {
        $home = $site->url('/');
        $sitemapUrls = $this->sitemapUrls($site->url((string) config('seo-engine.host.sitemap_url', '/sitemap.xml')), $limit * 2);
        $queue = [[$home, 0]];          // صف لینک‌ها (عمق کلیک معلوم)
        $sitemapQueue = $sitemapUrls;    // بعد از تمام شدن لینک‌ها، صفحات نقشه‌ی سایت
        $seen = [];
        $pages = [];
        $inbound = [];
        $delay = (int) config('seo-engine.crawler.concurrency_delay_ms', 250) * 1000;

        while (($queue || $sitemapQueue) && count($pages) < $limit) {
            [$url, $depth] = $queue ? array_shift($queue) : [array_shift($sitemapQueue), null];
            $key = rtrim($url, '/');
            if (isset($seen[$key]) || ! $this->sameHost($url, $site) || $this->excluded($url)) {
                continue;
            }
            $seen[$key] = true;
            $res = $this->fetcher->get($url);
            $info = $res['status'] === 200 && $res['body'] !== '' ? $this->parser->parse($res['body'], $res['final_url']) : [];
            $pages[$key] = [
                'url' => $url,
                'status' => $res['status'],
                'ms' => $res['ms'],
                'redirects' => count($res['chain']),
                'final_url' => $res['final_url'],
                'depth' => $depth,
                'in_sitemap' => in_array($url, $sitemapUrls, true),
                'title' => $info['title'] ?? null,
                'description' => $info['description'] ?? null,
                'h1' => $info['h1'] ?? [],
                'canonical' => $info['canonical'] ?? null,
                'robots' => $info['robots'] ?? null,
                'lang' => $info['lang'] ?? null,
                'dir' => $info['dir'] ?? null,
                'viewport' => $info['viewport'] ?? null,
                'og' => ($info['og_title'] ?? false) && ($info['og_image'] ?? false),
                'schema' => $info['schema_types'] ?? [],
                'images' => $info['images'] ?? 0,
                'images_no_alt' => $info['images_no_alt'] ?? 0,
                'words' => $info['words'] ?? 0,
                'links_out' => count($info['links'] ?? []),
            ];
            if ($depth === 0) {
                $homeLinks = array_values(array_unique(array_column($info['links'] ?? [], 'url')));
            }
            foreach ($info['links'] ?? [] as $link) {
                $lk = rtrim($link['url'], '/');
                $inbound[$lk] = ($inbound[$lk] ?? 0) + 1;
                if (! isset($seen[$lk]) && ! $link['nofollow'] && ! preg_match('/\.(jpg|jpeg|png|webp|gif|svg|pdf|zip|mp4|css|js)(\?|$)/i', $link['url'])) {
                    $queue[] = [$link['url'], $depth === null ? null : $depth + 1];
                }
            }
            usleep($delay);
        }

        foreach ($pages as $k => &$p) {
            $p['inbound'] = $inbound[$k] ?? 0;
        }
        unset($p);

        [$issues, $summary, $score] = $this->analyze($pages);
        $summary['home_links'] = array_slice($homeLinks ?? [], 0, 300);
        $summary['sitemap_urls'] = count($sitemapUrls);

        return Audit::create([
            'site_id' => $site->id,
            'type' => 'crawl',
            'score' => $score,
            'summary' => $summary,
            'issues' => $issues,
            'pages' => array_values($pages),
        ]);
    }

    protected function excluded(string $url): bool
    {
        if (preg_match((string) config('seo-engine.crawler.exclude', '#^$#'), $url)) {
            return true;
        }
        $query = (string) parse_url($url, PHP_URL_QUERY);
        if ($query !== '') {
            parse_str($query, $params);
            return (bool) array_diff(array_keys($params), (array) config('seo-engine.crawler.allowed_query', ['page']));
        }
        return false;
    }

    protected function sameHost(string $url, Site $site): bool
    {
        $h = preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST));
        return $h === preg_replace('/^www\./', '', $site->domain);
    }

    public function sitemapUrls(string $sitemap, int $max = 2000, int $depth = 0): array
    {
        $res = $this->fetcher->get($sitemap);
        if ($res['status'] !== 200 || $res['body'] === '') {
            return [];
        }
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($res['body']);
        libxml_use_internal_errors($prev);
        if (! $xml) {
            return [];
        }
        $urls = [];
        if ($xml->getName() === 'sitemapindex' && $depth < 2) {
            foreach ($xml->sitemap as $sm) {
                $urls = array_merge($urls, $this->sitemapUrls(trim((string) $sm->loc), $max - count($urls), $depth + 1));
                if (count($urls) >= $max) break;
            }
        } else {
            foreach ($xml->url as $u) {
                $urls[] = trim((string) $u->loc);
                if (count($urls) >= $max) break;
            }
        }
        return array_values(array_unique(array_filter($urls)));
    }

    /** @return array{0:array,1:array,2:int} */
    protected function analyze(array $pages): array
    {
        $ok = array_filter($pages, fn ($p) => $p['status'] === 200);
        $indexable = array_filter($ok, fn ($p) => ! str_contains((string) $p['robots'], 'noindex'));
        $issues = [];
        $add = function (string $key, string $level, string $title, array $urls) use (&$issues) {
            if ($urls) {
                $issues[$key] = ['level' => $level, 'title' => $title, 'count' => count($urls), 'urls' => array_slice(array_values($urls), 0, 50)];
            }
        };

        $add('status', 'danger', 'صفحات با خطای ۴xx/۵xx', array_keys(array_filter($pages, fn ($p) => $p['status'] >= 400 || $p['status'] === 0)));
        $add('redirects', 'warning', 'زنجیره‌ی ریدایرکت (بیش از یک پرش)', array_keys(array_filter($pages, fn ($p) => $p['redirects'] > 1)));
        $add('titles_missing', 'danger', 'صفحات بدون عنوان', array_keys(array_filter($indexable, fn ($p) => ! $p['title'])));
        $add('titles_length', 'warning', 'عنوان کوتاه‌تر از ۲۰ یا بلندتر از ۶۵ کاراکتر', array_keys(array_filter($indexable, fn ($p) => $p['title'] && (mb_strlen($p['title']) < 20 || mb_strlen($p['title']) > 65))));
        $titles = [];
        foreach ($indexable as $k => $p) {
            if ($p['title']) $titles[$p['title']][] = $k;
        }
        $add('duplicates', 'warning', 'عنوان تکراری بین چند صفحه', collect($titles)->filter(fn ($u) => count($u) > 1)->flatten()->all());
        $add('descriptions', 'warning', 'توضیحات متا ندارد یا طول نامناسب دارد', array_keys(array_filter($indexable, fn ($p) => ! $p['description'] || mb_strlen($p['description']) < 50 || mb_strlen($p['description']) > 170)));
        $add('h1', 'warning', 'صفحات بدون H1 یا با چند H1', array_keys(array_filter($indexable, fn ($p) => count($p['h1']) !== 1)));
        $add('canonical', 'warning', 'canonical ندارد', array_keys(array_filter($indexable, fn ($p) => ! $p['canonical'])));
        $add('noindex', 'info', 'صفحات noindex (بررسی کنید عمدی باشد)', array_keys(array_filter($ok, fn ($p) => str_contains((string) $p['robots'], 'noindex'))));
        $add('noindex_in_sitemap', 'danger', 'صفحه‌ی noindex داخل نقشه‌ی سایت', array_keys(array_filter($ok, fn ($p) => $p['in_sitemap'] && str_contains((string) $p['robots'], 'noindex'))));
        $add('alt', 'info', 'تصاویر بدون alt', array_keys(array_filter($indexable, fn ($p) => $p['images_no_alt'] > 0)));
        $add('thin', 'warning', 'محتوای کم (کمتر از ۲۵۰ کلمه)', array_keys(array_filter($indexable, fn ($p) => $p['words'] > 0 && $p['words'] < 250)));
        $add('orphans', 'warning', 'صفحات یتیم (در نقشه هست ولی لینک داخلی ندارد)', array_keys(array_filter($indexable, fn ($p) => $p['in_sitemap'] && $p['inbound'] === 0)));
        $add('depth', 'info', 'صفحات با عمق کلیک بیشتر از ۳', array_keys(array_filter($indexable, fn ($p) => $p['depth'] !== null && $p['depth'] > 3)));
        $add('lang', 'warning', 'زبان/جهت/viewport تنظیم نشده', array_keys(array_filter($indexable, fn ($p) => ! $p['lang'] || ! $p['viewport'])));
        $add('og', 'info', 'Open Graph ناقص', array_keys(array_filter($indexable, fn ($p) => ! $p['og'])));
        $add('urls', 'info', 'URL با حروف بزرگ لاتین یا زیرخط', array_keys(array_filter($indexable, fn ($p) => preg_match('/[A-Z_]/', rawurldecode((string) parse_url($p['url'], PHP_URL_PATH))))));
        $add('slow', 'warning', 'پاسخ سرور کندتر از ۱٫۵ ثانیه', array_keys(array_filter($ok, fn ($p) => $p['ms'] > 1500)));

        $weights = ['danger' => 6, 'warning' => 2, 'info' => 0.5];
        $total = max(1, count($pages));
        $penalty = 0;
        foreach ($issues as $i) {
            $penalty += $weights[$i['level']] * min(1, $i['count'] / $total) * 10;
        }
        $score = (int) max(0, min(100, round(100 - $penalty)));

        $summary = [
            'pages' => count($pages),
            'ok' => count($ok),
            'indexable' => count($indexable),
            'errors' => count(array_filter($pages, fn ($p) => $p['status'] >= 400 || $p['status'] === 0)),
            'avg_ms' => $ok ? (int) round(array_sum(array_column($ok, 'ms')) / count($ok)) : null,
            'avg_words' => $indexable ? (int) round(array_sum(array_column($indexable, 'words')) / count($indexable)) : null,
            'schema_types' => array_values(array_unique(array_merge(...array_values(array_map(fn ($p) => $p['schema'], $ok)) ?: [[]]))),
            'issue_counts' => array_map(fn ($i) => $i['count'], $issues),
        ];

        return [$issues, $summary, $score];
    }
}
