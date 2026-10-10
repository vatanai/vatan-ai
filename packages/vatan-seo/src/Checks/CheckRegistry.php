<?php

namespace Vatan\Seo\Checks;

use Vatan\Seo\Data\Google\SearchConsole;
use Vatan\Seo\Data\Google\ServiceAccount;
use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\Cluster;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\Fetcher;
use Vatan\Seo\Services\PageParser;
use Vatan\Seo\Support\Fa;

/**
 * همه‌ی بررسی‌های خودکار پلی‌بوک. هر کلید check در پلی‌بوک به یک متد اینجا نگاشت می‌شود:
 *   "robots" → robots()   |   "crawl:titles" → crawl('titles')   |   "kw:onpage" → kw('onpage', $task)
 * هیچ بررسی‌ای هزینه‌ی هوش مصنوعی ندارد؛ همه با HTTP، داده‌ی سرچ کنسول و ممیزی خزش انجام می‌شوند.
 */
class CheckRegistry
{
    private array $cache = [];

    public function __construct(private Fetcher $fetcher, private PageParser $parser, private SearchConsole $gsc) {}

    public function run(Site $site, Task $task): CheckResult
    {
        $check = (string) $task->check;
        if ($check === '') {
            return CheckResult::wait('این تسک بررسی خودکار ندارد؛ بعد از انجام، دستی تیک بزنید.');
        }
        [$name, $arg] = array_pad(explode(':', $check, 2), 2, null);
        try {
            return match ($name) {
                'crawl' => $this->crawl($site, (string) $arg),
                'schema' => $this->schema($site, (string) $arg),
                'pagespeed' => $this->pagespeed($site, (string) $arg),
                'headers' => $arg === 'security' ? $this->securityHeaders($site) : $this->compression($site),
                'agent' => $this->agent($site, (string) $arg),
                'kw' => $this->kw($site, (string) $arg, $task),
                'daily' => $this->daily($site, (string) $arg, $task),
                'weekly' => $this->weekly($site, (string) $arg, $task),
                'checkpoint' => app(\Vatan\Seo\Services\Checkpoints::class)->checkpoint($site, (int) $arg, $task),
                'baseline' => app(\Vatan\Seo\Services\Checkpoints::class)->baseline($site),
                default => method_exists($this, $m = lcfirst(str_replace('_', '', ucwords($name, '_'))))
                    ? $this->{$m}($site)
                    : CheckResult::wait("بررسی «{$check}» تعریف نشده است."),
            };
        } catch (\Throwable $e) {
            return CheckResult::wait('بررسی انجام نشد: '.mb_substr($e->getMessage(), 0, 300));
        }
    }

    protected function fetch(string $url): array
    {
        return $this->cache[$url] ??= $this->fetcher->get($url);
    }

    // ───────────────────────── اتصال داده ─────────────────────────

    protected function gscConnected(Site $site): CheckResult
    {
        if (! ServiceAccount::configured()) {
            return CheckResult::fail('سرویس‌اکانت گوگل هنوز آپلود نشده است (تنظیمات ← اتصال‌ها).');
        }
        $sites = collect($this->gsc->sites())->pluck('url');
        return $sites->contains($site->gscProperty())
            ? CheckResult::ok('سرچ کنسول متصل است: '.$site->gscProperty())
            : CheckResult::fail('سرویس‌اکانت به property «'.$site->gscProperty().'» دسترسی ندارد. ایمیل '.ServiceAccount::email().' را در سرچ کنسول با سطح Full اضافه کنید.', ['available' => $sites->all()]);
    }

    protected function ga4Connected(Site $site): CheckResult
    {
        return ServiceAccount::configured() && filled($site->ga4_property)
            ? CheckResult::ok('شناسه‌ی GA4 ثبت شده است: '.$site->ga4_property)
            : CheckResult::fail('شناسه‌ی property گوگل آنالیتیکس ۴ را در تنظیمات وارد کنید و ایمیل سرویس‌اکانت را در GA4 با نقش Viewer اضافه کنید.');
    }

    // ───────────────────────── خزش و ایندکس ─────────────────────────

    public function robotsTxt(Site $site): array
    {
        $res = $this->fetch($site->url('/robots.txt'));
        return [$res['status'], (string) $res['body']];
    }

    /** @return array<string, string[]> user-agent => disallow rules */
    protected function robotsGroups(string $body): array
    {
        $groups = [];
        $agents = [];
        $lastWasAgent = false;
        foreach (preg_split('/\r\n|\r|\n/', $body) as $line) {
            $line = trim(preg_replace('/#.*/', '', $line));
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode(':', $line, 2));
            $k = strtolower($k);
            if ($k === 'user-agent') {
                if (! $lastWasAgent) {
                    $agents = [];
                }
                $agents[] = strtolower($v);
                $groups[strtolower($v)] ??= [];
                $lastWasAgent = true;
                continue;
            }
            $lastWasAgent = false;
            if ($k === 'disallow') {
                foreach ($agents as $a) {
                    $groups[$a][] = $v;
                }
            }
        }
        return $groups;
    }

    protected function robots(Site $site): CheckResult
    {
        [$status, $body] = $this->robotsTxt($site);
        if ($status !== 200) {
            return CheckResult::fail("robots.txt در دسترس نیست (HTTP {$status}).");
        }
        $groups = $this->robotsGroups($body);
        $all = $groups['*'] ?? [];
        if (in_array('/', $all, true)) {
            return CheckResult::fail('robots.txt کل سایت را برای همه‌ی ربات‌ها مسدود کرده است (Disallow: /).');
        }
        $blocked = array_values(array_filter($all, fn ($r) => preg_match('#^/(articles|products?|blog|app/product)/?$#i', $r)));
        if ($blocked) {
            return CheckResult::fail('مسیرهای مهم مسدود شده‌اند: '.implode('، ', $blocked));
        }
        $hasSitemap = (bool) preg_match('/^\s*sitemap\s*:/im', $body);
        return CheckResult::ok('robots.txt سالم است'.($hasSitemap ? ' و آدرس نقشه‌ی سایت در آن ثبت شده.' : '؛ پیشنهاد: خط Sitemap را اضافه کنید.'), ['disallow' => $all, 'has_sitemap' => $hasSitemap]);
    }

    protected function aiCrawlers(Site $site): CheckResult
    {
        [$status, $body] = $this->robotsTxt($site);
        if ($status !== 200) {
            return CheckResult::wait('robots.txt در دسترس نیست.');
        }
        $groups = $this->robotsGroups($body);
        $search = ['oai-searchbot' => 'ChatGPT Search', 'perplexitybot' => 'Perplexity', 'googlebot' => 'Google', 'bingbot' => 'Bing'];
        $training = ['gptbot' => 'GPTBot', 'google-extended' => 'Google-Extended', 'claudebot' => 'ClaudeBot', 'ccbot' => 'CommonCrawl'];
        $blocked = fn ($ua) => in_array('/', $groups[$ua] ?? ($groups['*'] ?? []), true);
        $searchBlocked = array_values(array_map(fn ($ua) => $search[$ua], array_filter(array_keys($search), $blocked)));
        $policy = [];
        foreach ($training as $ua => $label) {
            $policy[$label] = $blocked($ua) ? 'مسدود' : 'مجاز';
        }
        if ($searchBlocked) {
            return CheckResult::fail('ربات‌های جستجوی هوش مصنوعی مسدود هستند: '.implode('، ', $searchBlocked).' — سایت در پاسخ‌های AI دیده نمی‌شود.', ['training' => $policy]);
        }
        return CheckResult::ok('ربات‌های جستجوی AI اجازه‌ی خزش دارند. سیاست آموزش مدل‌ها: '.collect($policy)->map(fn ($v, $k) => "$k: $v")->implode('، '), ['training' => $policy]);
    }

    protected function sitemap(Site $site): CheckResult
    {
        $url = $site->url((string) config('seo-engine.host.sitemap_url', '/sitemap.xml'));
        $crawler = app(\Vatan\Seo\Services\Crawler::class);
        $urls = $crawler->sitemapUrls($url, 5000);
        if (! $urls) {
            return CheckResult::fail("نقشه‌ی سایت در {$url} پیدا نشد یا XML معتبر نیست.");
        }
        $http = array_filter($urls, fn ($u) => str_starts_with($u, 'http://'));
        $sample = array_slice($urls, 0, 15);
        $bad = [];
        foreach ($sample as $u) {
            $r = $this->fetch($u);
            if ($r['status'] !== 200) {
                $bad[] = $u.' ('.$r['status'].')';
            }
        }
        if ($http || count($bad) > 1) {
            return CheckResult::fail(sprintf('نقشه‌ی سایت %s URL دارد؛ %s نشانی HTTP و %s نمونه‌ی خراب.', Fa::n(count($urls)), Fa::n(count($http)), Fa::n(count($bad))), ['bad' => $bad]);
        }
        return CheckResult::ok('نقشه‌ی سایت معتبر است: '.Fa::n(count($urls)).' نشانی.', ['count' => count($urls)]);
    }

    protected function sitemapSubmitted(Site $site): CheckResult
    {
        if (! ServiceAccount::configured()) {
            return CheckResult::wait('ابتدا سرچ کنسول را متصل کنید.');
        }
        $url = $site->url((string) config('seo-engine.host.sitemap_url', '/sitemap.xml'));
        $list = collect($this->gsc->sitemaps($site->gscProperty()));
        if (! $list->pluck('path')->contains($url)) {
            $this->gsc->submitSitemap($site->gscProperty(), $url); // اقدام خودکار
            return CheckResult::ok('نقشه‌ی سایت به‌صورت خودکار در سرچ کنسول ثبت شد.');
        }
        $item = $list->firstWhere('path', $url);
        $errors = (int) ($item['errors'] ?? 0);
        return $errors > 0
            ? CheckResult::fail('سرچ کنسول برای نقشه‌ی سایت '.Fa::n($errors).' خطا گزارش کرده است.')
            : CheckResult::ok('نقشه‌ی سایت در سرچ کنسول ثبت و بدون خطاست.');
    }

    protected function https(Site $site): CheckResult
    {
        $res = $this->fetch('http://'.$site->domain.'/');
        $final = (string) $res['final_url'];
        $first = $res['chain'][0]['status'] ?? null;
        if (str_starts_with($final, 'https://') && in_array($first, [301, 308], true)) {
            return CheckResult::ok('نسخه‌ی HTTP با ریدایرکت دائمی به HTTPS منتقل می‌شود.');
        }
        if (str_starts_with($final, 'https://')) {
            return CheckResult::fail('HTTP به HTTPS می‌رود ولی با ریدایرکت موقت (HTTP '.$first.')؛ باید ۳۰۱ باشد.');
        }
        return CheckResult::fail('نسخه‌ی HTTP به HTTPS ریدایرکت نمی‌شود.');
    }

    protected function canonicalHost(Site $site): CheckResult
    {
        $bare = preg_replace('/^www\./', '', $site->domain);
        $a = $this->fetch('https://'.$bare.'/');
        $b = $this->fetch('https://www.'.$bare.'/');
        $ha = parse_url((string) $a['final_url'], PHP_URL_HOST);
        $hb = parse_url((string) $b['final_url'], PHP_URL_HOST);
        if ($b['status'] === 0) {
            return CheckResult::ok('فقط یک نسخه‌ی دامنه پاسخ می‌دهد ('.$ha.').');
        }
        return $ha === $hb
            ? CheckResult::ok('هر دو نسخه‌ی دامنه به '.$ha.' می‌رسند.')
            : CheckResult::fail("دو نسخه‌ی جدا پاسخ می‌دهند: {$ha} و {$hb}. یکی را با ۳۰۱ به دیگری ریدایرکت کنید.");
    }

    protected function gscInspect(Site $site): CheckResult
    {
        if (! ServiceAccount::configured()) {
            return CheckResult::wait('ابتدا سرچ کنسول را متصل کنید.');
        }
        $urls = array_unique(array_filter(array_merge([$site->url('/')], $site->keywords()->targets()->whereNotNull('target_url')->limit(4)->pluck('target_url')->all())));
        $bad = [];
        $results = [];
        foreach ($urls as $u) {
            $r = $this->gsc->inspect($site->gscProperty(), $u);
            $results[$u] = $r;
            if ($r['verdict'] !== 'PASS') {
                $bad[] = $u.' — '.($r['coverage'] ?? $r['verdict']);
            }
        }
        return $bad
            ? CheckResult::fail('صفحاتی که ایندکس نیستند: '.implode(' | ', $bad), ['results' => $results])
            : CheckResult::ok(Fa::n(count($urls)).' صفحه‌ی کلیدی در ایندکس گوگل هستند.', ['results' => $results]);
    }

    protected function indexnow(Site $site): CheckResult
    {
        $key = (string) config('seo-engine.indexnow.key');
        if ($key === '') {
            return CheckResult::fail('کلید IndexNow (SEO_INDEXNOW_KEY) تنظیم نشده است.');
        }
        $res = $this->fetch($site->url('/'.$key.'.txt'));
        return $res['status'] === 200 && str_contains((string) $res['body'], $key)
            ? CheckResult::ok('فایل کلید IndexNow در دسترس است.')
            : CheckResult::fail('فایل '.$key.'.txt در ریشه‌ی سایت پیدا نشد (موتور آن را از مسیر /{key}.txt سرو می‌کند؛ بعد از دیپلوی دوباره بررسی کنید).');
    }

    protected function notFound(Site $site): CheckResult
    {
        $res = $this->fetch($site->url('/seo-engine-check-'.substr(md5((string) now()->timestamp), 0, 6)));
        return $res['status'] === 404
            ? CheckResult::ok('صفحه‌ی ناموجود کد ۴۰۴ درست برمی‌گرداند.')
            : CheckResult::fail('صفحه‌ی ناموجود کد '.$res['status'].' برمی‌گرداند (soft-404).');
    }

    protected function llmsTxt(Site $site): CheckResult
    {
        $res = $this->fetch($site->url('/llms.txt'));
        return $res['status'] === 200 && mb_strlen(trim((string) $res['body'])) > 50
            ? CheckResult::ok('فایل llms.txt موجود است.')
            : CheckResult::fail('فایل llms.txt وجود ندارد. می‌توانید از بخش «زیرساخت» پیش‌نویس آن را با هوش مصنوعی بسازید.');
    }

    protected function compression(Site $site): CheckResult
    {
        $res = $this->fetch($site->url('/'));
        $enc = (string) ($res['headers']['content-encoding'] ?? '');
        return preg_match('/br|gzip|zstd/i', $enc)
            ? CheckResult::ok('فشرده‌سازی فعال است ('.$enc.').')
            : CheckResult::fail('پاسخ صفحه‌ی اصلی فشرده نیست (Brotli/Gzip را در سرور یا CDN فعال کنید).');
    }

    protected function securityHeaders(Site $site): CheckResult
    {
        $h = $this->fetch($site->url('/'))['headers'] ?? [];
        $missing = array_values(array_filter(['strict-transport-security', 'x-content-type-options', 'referrer-policy'], fn ($k) => empty($h[$k])));
        return $missing
            ? CheckResult::fail('هدرهای امنیتی موجود نیست: '.implode('، ', $missing))
            : CheckResult::ok('هدرهای امنیتی اصلی تنظیم شده‌اند.');
    }

    // ───────────────────────── ممیزی خزش ─────────────────────────

    protected function latestAudit(Site $site, string $type, int $days = 14): ?Audit
    {
        return $this->cache["audit:{$type}"] ??= Audit::where('site_id', $site->id)->where('type', $type)
            ->where('created_at', '>=', now()->subDays($days))->latest()->first();
    }

    protected function crawl(Site $site, string $what): CheckResult
    {
        $audit = $this->latestAudit($site, 'crawl');
        if (! $audit) {
            return CheckResult::wait('منتظر اولین خزش سایت (سناریوی «خزش کامل سایت»).');
        }
        if ($what === 'trust_pages') {
            $paths = collect($audit->pages)->pluck('url')->merge((array) data_get($audit->summary, 'home_links', []))
                ->map(fn ($u) => urldecode((string) parse_url($u, PHP_URL_PATH)))->implode(' ');
            $need = ['درباره' => '/(about|درباره)/u', 'تماس' => '/(contact|تماس)/u', 'حریم خصوصی/قوانین' => '/(privacy|terms|rules|قوانین|حریم)/u'];
            $missing = array_keys(array_filter($need, fn ($re) => ! preg_match($re, $paths)));
            return $missing ? CheckResult::fail('این صفحات پیدا نشدند: '.implode('، ', $missing)) : CheckResult::ok('صفحات اعتماد (درباره، تماس، قوانین) موجودند.');
        }
        $map = [
            'titles' => ['titles_missing', 'titles_length'], 'descriptions' => ['descriptions'], 'h1' => ['h1'], 'canonical' => ['canonical'],
            'noindex' => ['noindex_in_sitemap'], 'status' => ['status'], 'redirects' => ['redirects'], 'alt' => ['alt'],
            'duplicates' => ['duplicates'], 'thin' => ['thin'], 'urls' => ['urls'], 'orphans' => ['orphans'], 'depth' => ['depth'],
            'lang' => ['lang'], 'og' => ['og'],
        ];
        $found = [];
        foreach ($map[$what] ?? [$what] as $key) {
            if (! empty($audit->issues[$key])) {
                $found[] = $audit->issues[$key];
            }
        }
        $pages = (int) data_get($audit->summary, 'pages', 0);
        if (! $found) {
            return CheckResult::ok('در خزش '.Fa::n($pages).' صفحه مشکلی پیدا نشد.', ['audit_id' => $audit->id]);
        }
        $count = array_sum(array_column($found, 'count'));
        $urls = array_slice(array_merge(...array_column($found, 'urls')), 0, 10);
        return CheckResult::fail($found[0]['title'].': '.Fa::n($count).' مورد از '.Fa::n($pages).' صفحه.', ['urls' => $urls, 'audit_id' => $audit->id]);
    }

    protected function schema(Site $site, string $type): CheckResult
    {
        $audit = $this->latestAudit($site, 'crawl');
        if (! $audit) {
            return CheckResult::wait('منتظر اولین خزش سایت.');
        }
        $want = match ($type) {
            'organization' => ['Organization', 'WebSite', 'Corporation', 'LocalBusiness'],
            'product' => ['Product', 'SoftwareApplication', 'Service', 'Offer'],
            'article' => ['Article', 'BlogPosting', 'NewsArticle', 'TechArticle'],
            'breadcrumb' => ['BreadcrumbList'],
            default => [$type],
        };
        $pages = collect($audit->pages)->filter(fn ($p) => array_intersect($want, (array) $p['schema']));
        if ($type === 'organization') {
            $home = collect($audit->pages)->first();
            return $home && array_intersect($want, (array) $home['schema'])
                ? CheckResult::ok('اسکیمای '.implode('/', array_intersect($want, (array) $home['schema'])).' در صفحه‌ی اصلی موجود است.')
                : CheckResult::fail('صفحه‌ی اصلی اسکیمای Organization/WebSite ندارد.');
        }
        return $pages->isNotEmpty()
            ? CheckResult::ok('اسکیمای '.$want[0].' در '.Fa::n($pages->count()).' صفحه پیدا شد.')
            : CheckResult::fail('هیچ صفحه‌ای با اسکیمای '.implode(' / ', $want).' پیدا نشد.');
    }

    protected function pagespeed(Site $site, string $what): CheckResult
    {
        $audit = $this->latestAudit($site, 'pagespeed', 21);
        if (! $audit) {
            return CheckResult::wait('منتظر اولین سنجش سرعت (سناریوی PageSpeed).');
        }
        $mobile = collect($audit->pages)->firstWhere('strategy', 'mobile');
        if (! $mobile) {
            return CheckResult::wait('نتیجه‌ی موبایل موجود نیست.');
        }
        $fails = array_keys((array) ($mobile['failing_audits'] ?? []));
        $has = fn (array $ids) => array_values(array_intersect($ids, $fails));
        return match ($what) {
            'cwv' => $this->cwvResult($mobile),
            'images' => ($x = $has(['modern-image-formats', 'uses-optimized-images', 'offscreen-images', 'uses-responsive-images', 'unsized-images'])) ? CheckResult::fail('مشکلات تصویر: '.implode('، ', $x)) : CheckResult::ok('تصاویر بهینه هستند.'),
            'render' => ($x = $has(['render-blocking-resources', 'font-display', 'unused-css-rules', 'unused-javascript'])) ? CheckResult::fail('منابع مسدودکننده/اضافه: '.implode('، ', $x)) : CheckResult::ok('منبع مسدودکننده‌ی مهمی وجود ندارد.'),
            'weight' => ($kb = data_get($mobile, 'lab.weight_kb')) && $kb > 1500 ? CheckResult::fail('حجم صفحه '.Fa::n($kb).' کیلوبایت است (هدف: زیر ۱۵۰۰).') : CheckResult::ok('حجم صفحه مناسب است ('.Fa::n((int) $kb).' کیلوبایت).'),
            'mobile' => ($x = $has(['tap-targets', 'font-size', 'viewport', 'content-width'])) ? CheckResult::fail('مشکلات موبایل: '.implode('، ', $x)) : CheckResult::ok('تجربه‌ی موبایل از نظر Lighthouse قبول است.'),
            default => CheckResult::wait('بررسی ناشناخته.'),
        };
    }

    protected function cwvResult(array $m): CheckResult
    {
        $lcp = data_get($m, 'field.lcp_ms') ?? data_get($m, 'lab.lcp_ms');
        $cls = data_get($m, 'field.cls') ?? data_get($m, 'lab.cls');
        $inp = data_get($m, 'field.inp_ms');
        $src = data_get($m, 'field.lcp_ms') ? 'داده‌ی واقعی کاربران' : 'آزمایشگاهی';
        $problems = [];
        if ($lcp !== null && $lcp > 2500) $problems[] = 'LCP '.Fa::n(round($lcp / 1000, 1)).' ثانیه';
        if ($cls !== null && $cls > 0.1) $problems[] = 'CLS '.Fa::n(round($cls, 2));
        if ($inp !== null && $inp > 200) $problems[] = 'INP '.Fa::n((int) $inp).'ms';
        return $problems
            ? CheckResult::fail('Core Web Vitals موبایل رد شد ('.$src.'): '.implode('، ', $problems))
            : CheckResult::ok('Core Web Vitals موبایل قبول است ('.$src.').');
    }

    // ───────────────────────── ایجنت‌ها و کلمات ─────────────────────────

    protected function targetsSelected(Site $site): CheckResult
    {
        $n = $site->keywords()->targets()->count();
        return $n >= 5 ? CheckResult::ok(Fa::n($n).' کلمه‌ی هدف انتخاب شده است.') : CheckResult::fail('هنوز '.Fa::n($n).' کلمه‌ی هدف دارید؛ حداقل ۵ کلمه از بخش «کلمات کلیدی ← پیشنهادها» انتخاب کنید.');
    }

    protected function agent(Site $site, string $name): CheckResult
    {
        return match ($name) {
            'discovery' => ($n = $site->keywords()->count()) > 0 ? CheckResult::ok(Fa::n($n).' کلمه کشف و ثبت شده است.') : CheckResult::wait('کشف کلمات هنوز اجرا نشده است.'),
            'clustering' => ($n = Cluster::where('site_id', $site->id)->count()) > 0 ? CheckResult::ok(Fa::n($n).' خوشه ساخته شده است.') : CheckResult::wait('خوشه‌بندی بعد از انتخاب کلمات هدف انجام می‌شود.'),
            'competitors' => ($c = (array) $site->setting('competitors', [])) ? CheckResult::ok('رقبا: '.implode('، ', $c)) : CheckResult::fail('رقیبی ثبت نشده است (تنظیمات ← پروفایل سایت).'),
            'calendar' => ($n = Task::where('site_id', $site->id)->where('playbook_key', 'kw.article')->whereNotNull('due_on')->count()) > 0
                ? CheckResult::ok('تقویم محتوا آماده است: '.Fa::n($n).' مقاله در موج‌های هفتگی برنامه‌ی ۹۰ روزه زمان‌بندی شده.')
                : CheckResult::wait('تقویم بعد از انتخاب کلمات هدف خودکار ساخته می‌شود (موج‌بندی هفتگی).'),
            'geo_probe' => ($p = (array) $site->setting('geo_probe.last', [])) && ! empty($p['at']) && \Illuminate\Support\Carbon::parse($p['at'])->diffInDays(now()) < 10
                ? CheckResult::ok('پایش AI انجام شد: برند در '.Fa::n($p['mentioned'] ?? 0).' از '.Fa::n($p['asked'] ?? 0).' پاسخ دیده شد.', $p)
                : CheckResult::wait('پایش دیده‌شدن در AI هنوز در این دوره اجرا نشده است (سناریوی «برنامه‌ی هفته» آن را اجرا می‌کند).'),
            'cannibalization' => Run::where('site_id', $site->id)->where('action', 'cannibalization')->where('status', '!=', 'failed')->exists() ? CheckResult::ok('بررسی همنوع‌خواری انجام شده است.') : CheckResult::wait('منتظر داده‌ی سرچ کنسول.'),
            default => CheckResult::wait('ناشناخته'),
        };
    }

    /** بازه‌ی هفته‌ی یک تسک (شنبه تا جمعه) */
    protected function weekRange(Task $task): array
    {
        $tz = config('seo-engine.host.timezone', 'Asia/Tehran');
        $d = \Illuminate\Support\Carbon::parse($task->due_on ?? now(), $tz)->startOfDay();
        while ($d->dayOfWeek !== \Illuminate\Support\Carbon::SATURDAY) {
            $d->subDay();
        }
        return [$d->copy(), $d->copy()->addDays(7)];
    }

    protected function daily(Site $site, string $what, Task $task): CheckResult
    {
        $day = $task->due_on?->toDateString() ?? now()->toDateString();
        if ($day > now(config('seo-engine.host.timezone', 'Asia/Tehran'))->toDateString()) {
            return CheckResult::wait('هنوز روزش نرسیده است.');
        }
        return match ($what) {
            'brief' => in_array($day, (array) $site->setting('seen_days', []), true)
                ? CheckResult::ok('خلاصه‌ی امروز مرور شد.')
                : CheckResult::wait('با باز کردن «نمای کلی» یا دستور /today در تلگرام تیک می‌خورد.'),
            'queue' => ($n = ContentItem::where('site_id', $site->id)->where('status', 'review')->count()) === 0
                ? CheckResult::ok('محتوایی منتظر تأیید نیست.')
                : CheckResult::wait(Fa::n($n).' محتوا منتظر تأیید شماست.'),
            default => CheckResult::wait('ناشناخته'),
        };
    }

    protected function weekly(Site $site, string $what, Task $task): CheckResult
    {
        [$from, $to] = $this->weekRange($task);
        return match ($what) {
            'strategy' => ($plan = (array) $site->setting('weekly_plan', [])) && ! empty($plan['at']) && \Illuminate\Support\Carbon::parse($plan['at'])->between($from->copy()->subDay(), $to)
                ? CheckResult::ok('برنامه‌ی هفته آماده است: '.($plan['focus'] ?? ''), ['actions' => $plan['actions'] ?? []])
                : CheckResult::wait('استراتژیست شنبه صبح برنامه‌ی هفته را می‌سازد.'),
            'publish' => (function () use ($site, $from, $to) {
                $target = app(\Vatan\Seo\Services\RoadmapPlanner::class)->articlesPerWeek($site);
                $n = ContentItem::where('site_id', $site->id)->where('status', 'published')->whereBetween('published_at', [$from, $to])->count();
                $review = ContentItem::where('site_id', $site->id)->where('status', 'review')->count();
                return $n >= $target
                    ? CheckResult::ok(Fa::n($n).' مقاله در این هفته منتشر شد.')
                    : ($review ? CheckResult::fail(Fa::n($n).' از '.Fa::n($target).' مقاله منتشر شده؛ '.Fa::n($review).' پیش‌نویس منتظر تأیید شماست.') : CheckResult::wait(Fa::n($n).' از '.Fa::n($target).' مقاله منتشر شده؛ خط تولید در حال آماده‌سازی است.'));
            })(),
            'tech' => (function () use ($site) {
                $audit = $this->latestAudit($site, 'crawl', 8);
                if (! $audit) {
                    return CheckResult::wait('منتظر خزش هفتگی.');
                }
                $danger = collect($audit->issues)->where('level', 'danger');
                return $danger->isEmpty()
                    ? CheckResult::ok('خطای قرمزی در خزش این هفته نیست (امتیاز فنی '.Fa::n($audit->score).').')
                    : CheckResult::fail('خطاهای فوری: '.$danger->map(fn ($i) => $i['title'].' ('.Fa::n($i['count']).')')->implode('، '), ['urls' => $danger->pluck('urls')->flatten()->take(8)->all()]);
            })(),
            default => CheckResult::wait('ناشناخته'),
        };
    }

    protected function kw(Site $site, string $what, Task $task): CheckResult
    {
        $kw = $task->keyword;
        if (! $kw) {
            return CheckResult::wait('کلمه‌ی این تسک حذف شده است.');
        }
        switch ($what) {
            case 'map_page':
                if (! $kw->target_url) {
                    $url = $kw->ranking_url ?: data_get($kw->meta, 'product_url');
                    if ($url) {
                        $kw->update(['target_url' => $url]);
                    }
                }
                return $kw->target_url ? CheckResult::ok('صفحه‌ی هدف: '.urldecode($kw->target_url)) : CheckResult::fail('صفحه‌ی هدف مشخص نیست؛ از جزئیات کلمه آن را تعیین کنید یا یک مقاله‌ی ستون برایش بسازید.');
            case 'onpage':
                if (! $kw->target_url) {
                    return CheckResult::wait('اول صفحه‌ی هدف تعیین شود.');
                }
                $res = $this->fetch($kw->target_url);
                if ($res['status'] !== 200) {
                    return CheckResult::fail('صفحه‌ی هدف در دسترس نیست (HTTP '.$res['status'].').');
                }
                $p = $this->parser->parse($res['body'], $res['final_url']);
                $norm = Fa::normalizeKeyword($kw->keyword);
                $has = fn (?string $t) => $t && str_contains(Fa::normalizeKeyword($t), $norm);
                $missing = [];
                if (! $has($p['title'])) $missing[] = 'عنوان';
                if (! $has(implode(' ', $p['h1']))) $missing[] = 'H1';
                if (! $has($p['description'])) $missing[] = 'توضیحات متا';
                return $missing
                    ? CheckResult::fail('کلمه در این بخش‌ها نیست: '.implode('، ', $missing).'. از دکمه‌ی «پیشنهاد بهینه‌سازی» متن پیشنهادی بگیرید.', ['title' => $p['title'], 'h1' => $p['h1'], 'description' => $p['description']])
                    : CheckResult::ok('کلمه در عنوان، H1 و توضیحات صفحه‌ی هدف حضور دارد.');
            case 'brief':
                return ContentItem::where('keyword_id', $kw->id)->whereNotNull('brief')->exists() ? CheckResult::ok('بریف آماده است.') : CheckResult::wait('خط تولید محتوا بریف را می‌سازد.');
            case 'article':
                $item = ContentItem::where('keyword_id', $kw->id)->latest()->first();
                return match ($item?->status) {
                    'published' => CheckResult::ok('مقاله منتشر شد: '.urldecode((string) $item->published_url)),
                    'review' => CheckResult::wait('پیش‌نویس منتظر تأیید شماست.'),
                    default => CheckResult::wait('مقاله هنوز آماده نیست.'),
                };
            case 'internal_links':
                $audit = $this->latestAudit($site, 'crawl');
                if (! $audit || ! $kw->target_url) {
                    return CheckResult::wait('منتظر خزش و تعیین صفحه‌ی هدف.');
                }
                $page = collect($audit->pages)->first(fn ($p) => rtrim($p['url'], '/') === rtrim($kw->target_url, '/'));
                $in = (int) ($page['inbound'] ?? 0);
                return $in >= 3 ? CheckResult::ok(Fa::n($in).' لینک داخلی به صفحه‌ی هدف وجود دارد.') : CheckResult::fail('فقط '.Fa::n($in).' لینک داخلی به صفحه‌ی هدف هست (هدف: ۳+).');
            case 'schema':
                if (! $kw->target_url) {
                    return CheckResult::wait('اول صفحه‌ی هدف تعیین شود.');
                }
                $res = $this->fetch($kw->target_url);
                $p = $this->parser->parse((string) $res['body'], (string) $res['final_url']);
                return $p['schema_types'] ? CheckResult::ok('اسکیما: '.implode('، ', $p['schema_types'])) : CheckResult::fail('صفحه‌ی هدف هیچ اسکیمایی ندارد.');
        }
        return CheckResult::wait('ناشناخته');
    }
}
