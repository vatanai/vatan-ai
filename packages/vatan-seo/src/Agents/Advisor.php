<?php

namespace Vatan\Seo\Agents;

use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Connectors\ConnectorFactory;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\Fetcher;
use Vatan\Seo\Services\PageParser;
use Vatan\Seo\Support\Prompt;

/** مشاور سئوی داخلی (پیشنهاد آماده‌ی کپی) و سازنده‌ی llms.txt — روی درخواست، نه زمان‌بندی */
class Advisor
{
    public function __construct(private Ai $ai, private Fetcher $fetcher, private PageParser $parser) {}

    public function onpage(Site $site, Keyword $kw): array
    {
        $url = $kw->target_url ?: $kw->ranking_url;
        if (! $url) {
            throw new \RuntimeException('اول صفحه‌ی هدف این کلمه را تعیین کنید.');
        }
        $res = $this->fetcher->get($url);
        $p = $this->parser->parse((string) $res['body'], (string) $res['final_url']);
        $advice = $this->ai->json($site, 'strategist', 'onpage-advice', Prompt::get('onpage-advice', [
            'brand' => Prompt::brand($site), 'keyword' => $kw->keyword, 'intent' => $kw->intent ?: 'commercial',
            'title' => $p['title'] ?? '—', 'h1' => implode(' | ', $p['h1']) ?: '—', 'description' => $p['description'] ?? '—', 'words' => $p['words'],
        ]), 'پیشنهاد بده.', ['max_tokens' => 1500, 'temperature' => 0.4]);
        $kw->update(['meta' => array_merge((array) $kw->meta, ['onpage_advice' => $advice, 'onpage_advice_at' => now()->toDateTimeString()])]);

        return ['current' => ['title' => $p['title'], 'h1' => $p['h1'], 'description' => $p['description'], 'words' => $p['words']], 'advice' => $advice];
    }

    /** کشف رقبای واقعی از نتایج زنده‌ی گوگل (Grok + جستجوی وب) */
    public function competitors(Site $site): array
    {
        $queries = $site->keywords()->targets()->orderByDesc('priority')->orderByDesc('ai_score')->limit(6)->pluck('keyword');
        if ($queries->isEmpty()) {
            $queries = $site->keywords()->candidates()->orderByDesc('ai_score')->limit(6)->pluck('keyword');
        }
        if ($queries->isEmpty()) {
            throw new \RuntimeException('اول کلمات کلیدی را کشف یا انتخاب کنید.');
        }
        $json = $this->ai->json($site, 'research', 'competitors', Prompt::get('competitors', ['domain' => $site->domain, 'queries' => $queries->map(fn ($q) => '- '.$q)->implode("\n")]), 'Find the competitors.', ['web' => true, 'max_tokens' => 1500, 'temperature' => 0.1]);
        $list = collect((array) ($json['competitors'] ?? []))
            ->filter(fn ($c) => ! empty($c['domain']) && ! str_contains((string) $c['domain'], $site->domain))
            ->map(fn ($c) => ['domain' => strtolower(preg_replace('#^https?://(www\.)?|/.*$#', '', (string) $c['domain'])), 'appearances' => (int) ($c['appearances'] ?? 1), 'note' => (string) ($c['note'] ?? '')])
            ->unique('domain')->take(8)->values()->all();
        $site->putSetting('competitors_detail', ['at' => now()->toDateTimeString(), 'items' => $list]);
        if (! $site->setting('competitors')) {
            $site->putSetting('competitors', array_column(array_slice($list, 0, 5), 'domain'));
        }
        $site->save();
        return $list;
    }

    /** پایش دیده‌شدن برند در پاسخ‌های هوش مصنوعی (GEO) */
    public function geoProbe(Site $site, int $max = 3): array
    {
        $keywords = $site->keywords()->targets()->orderByDesc('priority')->orderByDesc('impressions_28d')->limit($max)->pluck('keyword');
        $brandTokens = array_filter([mb_strtolower($site->domain), mb_strtolower(preg_replace('/\..*$/', '', $site->domain)), mb_strtolower(trim((string) $site->name)), 'وطن']);
        $results = [];
        foreach ($keywords as $kw) {
            try {
                $json = $this->ai->json($site, 'research', 'geo-probe', Prompt::get('geo-probe'), 'سؤال کاربر: بهترین سرویس یا سایت برای «'.$kw.'» چیست؟', ['web' => true, 'max_tokens' => 700, 'temperature' => 0.3]);
            } catch (\Throwable $e) {
                $results[] = ['keyword' => $kw, 'error' => mb_substr($e->getMessage(), 0, 120)];
                continue;
            }
            $hay = mb_strtolower(json_encode($json, JSON_UNESCAPED_UNICODE));
            $mentioned = (bool) collect($brandTokens)->first(fn ($t) => $t !== '' && mb_strlen($t) > 2 && str_contains($hay, $t));
            $results[] = ['keyword' => $kw, 'mentioned' => $mentioned, 'recommended' => array_slice(array_map(fn ($r) => ($r['name'] ?? '').' ('.($r['domain'] ?? '').')', (array) ($json['recommended'] ?? [])), 0, 5)];
        }
        $summary = ['at' => now()->toDateTimeString(), 'asked' => count(array_filter($results, fn ($r) => ! isset($r['error']))), 'mentioned' => count(array_filter($results, fn ($r) => ! empty($r['mentioned']))), 'results' => $results];
        $history = (array) $site->setting('geo_probe.history', []);
        $history[] = ['at' => $summary['at'], 'asked' => $summary['asked'], 'mentioned' => $summary['mentioned']];
        $site->putSetting('geo_probe.last', $summary);
        $site->putSetting('geo_probe.history', array_slice($history, -26));
        $site->save();
        return $summary;
    }

    /**
     * پیشنهاد لینک داخلی بدون هزینه‌ی AI: صفحاتی از آخرین خزش و مقالات منتشرشده که موضوعشان به کلمه
     * نزدیک است ولی هنوز به صفحه‌ی هدف لینک نمی‌دهند.
     */
    public function internalLinks(Site $site, Keyword $kw, int $limit = 6): array
    {
        $target = rtrim((string) ($kw->target_url ?: $kw->ranking_url), '/');
        if ($target === '') {
            return [];
        }
        $tokens = array_values(array_filter(explode(' ', $kw->normalized), fn ($t) => mb_strlen($t) > 2 && ! in_array($t, ['برای', 'با', 'از', 'در', 'به', 'که', 'این', 'چگونه', 'بهترین'], true)));
        $audit = \Vatan\Seo\Models\Audit::where('site_id', $site->id)->where('type', 'crawl')->latest()->first();
        $pages = collect($audit?->pages ?? [])->filter(fn ($p) => ($p['status'] ?? 0) === 200 && ! empty($p['title']));
        $out = [];
        foreach ($pages as $p) {
            $url = rtrim((string) $p['url'], '/');
            if ($url === $target) {
                continue;
            }
            $title = \Vatan\Seo\Support\Fa::normalizeKeyword((string) $p['title'].' '.implode(' ', (array) ($p['h1'] ?? [])));
            $hits = count(array_filter($tokens, fn ($t) => str_contains($title, $t)));
            if ($tokens && $hits / count($tokens) >= 0.5) {
                $out[] = ['source' => $p['url'], 'title' => $p['title'], 'score' => round($hits / count($tokens), 2), 'anchor' => $kw->keyword, 'words' => $p['words'] ?? 0];
            }
        }
        usort($out, fn ($a, $b) => [$b['score'], $b['words']] <=> [$a['score'], $a['words']]);
        return array_slice($out, 0, $limit);
    }

    public function llmsTxt(Site $site): string
    {
        $c = ConnectorFactory::for($site);
        $pages = collect([['title' => 'صفحه‌ی اصلی', 'url' => $site->url('/')]])
            ->merge(collect($c->products(30))->map(fn ($p) => ['title' => $p['title'], 'url' => $p['url']]))
            ->merge(collect($c->articles(20))->map(fn ($a) => ['title' => $a['title'], 'url' => $a['url']]))
            ->filter(fn ($p) => $p['url'])->map(fn ($p) => '- '.$p['title'].': '.$p['url'])->implode("\n");
        $text = $this->ai->text($site, 'fast', 'llms-txt', Prompt::get('llms-txt', ['brand' => Prompt::brand($site), 'pages' => $pages]), 'فایل را بساز.', ['max_tokens' => 2500, 'temperature' => 0.3]);
        $site->putSetting('llms_txt', trim(preg_replace('/^```(?:markdown|md)?\s*|\s*```$/i', '', trim($text))));
        $site->save();

        return (string) $site->setting('llms_txt');
    }
}
