<?php

namespace Vatan\Seo\Agents;

use Illuminate\Support\Facades\DB;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Connectors\ConnectorFactory;
use Vatan\Seo\Data\Google\Autocomplete;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Support\Prompt;

/**
 * پژوهشگر کلمات: محصولات ← بذر (AI) ← گسترش با پیشنهاد گوگل ← ادغام با کوئری‌های سرچ کنسول ← امتیازدهی.
 * فقط «پیشنهاد» (candidate) می‌سازد؛ کلمات هدف و آرشیو شده را تغییر نمی‌دهد.
 * بدون کلید AI هم کار می‌کند (بذر از نام و کلمات متای محصول).
 */
class KeywordDiscovery
{
    public function __construct(private Ai $ai, private Autocomplete $autocomplete) {}

    public function run(Site $site, int $maxProducts = 60, ?array $onlyProductIds = null): array
    {
        $connector = ConnectorFactory::for($site);
        $products = collect($connector->products(300));
        if ($onlyProductIds) {
            $products = $products->whereIn('id', $onlyProductIds);
        }
        $products = $products->take($maxProducts)->values();
        $useAi = $this->ai->available($site, 'fast');
        $pool = []; // normalized => data
        $add = function (string $kw, string $source, array $extra = []) use (&$pool) {
            $kw = Fa::cleanKeyword($kw);
            $n = Fa::normalizeKeyword($kw);
            if (mb_strlen($n) < 3 || mb_strlen($n) > 80 || substr_count($n, ' ') > 8) {
                return;
            }
            $pool[$n] = array_merge($pool[$n] ?? ['keyword' => $kw, 'source' => $source, 'meta' => []], array_filter($extra, fn ($v) => $v !== null));
        };

        // ۱) بذر از محصولات
        $seeds = [];
        if ($useAi && $products->isNotEmpty()) {
            foreach ($products->chunk(12) as $chunk) {
                $payload = $chunk->map(fn ($p) => ['id' => $p['id'], 'title' => $p['title'], 'category' => $p['category'], 'description' => mb_substr((string) $p['description'], 0, 300)])->values()->all();
                $json = $this->ai->json($site, 'fast', 'keyword-seeds', Prompt::get('keyword-seeds', ['brand' => Prompt::brand($site)]), json_encode($payload, JSON_UNESCAPED_UNICODE), ['max_tokens' => 2500, 'temperature' => 0.5]);
                foreach ((array) ($json['products'] ?? []) as $row) {
                    $product = $products->firstWhere('id', $row['id'] ?? null);
                    foreach ((array) ($row['seeds'] ?? []) as $seed) {
                        if (! empty($seed['keyword'])) {
                            $seeds[] = $seed['keyword'];
                            $add($seed['keyword'], 'products', ['intent' => $seed['intent'] ?? null, 'product_id' => $product['id'] ?? null, 'meta' => ['product_url' => $product['url'] ?? null, 'product' => $product['title'] ?? null]]);
                        }
                    }
                }
            }
        } else {
            foreach ($products as $p) {
                foreach (array_merge([$p['title']], array_slice($p['keywords'], 0, 4)) as $kw) {
                    $seeds[] = $kw;
                    $add($kw, 'products', ['product_id' => $p['id'], 'meta' => ['product_url' => $p['url'], 'product' => $p['title']]]);
                }
            }
        }

        // ۲) گسترش با پیشنهادهای گوگل (رایگان) — فقط بذرهای کوتاه‌تر که پتانسیل دنباله دارند
        $expandSeeds = collect($seeds)->unique()->sortBy(fn ($s) => mb_strlen($s))->take(25);
        foreach ($expandSeeds as $seed) {
            foreach ($this->autocomplete->expand($seed, 12) as $suggestion) {
                $add($suggestion, 'autocomplete', ['meta' => ['seed' => $seed]]);
            }
        }

        // ۳) کوئری‌های واقعی سرچ کنسول (۲۸ روز) با ایمپرشن معنادار
        $gsc = DB::table('seo_query_metrics')
            ->selectRaw('query, SUM(impressions) as imp, SUM(clicks) as clk, SUM(position*impressions)/NULLIF(SUM(impressions),0) as pos')
            ->where('site_id', $site->id)
            ->where('date', '>=', now()->subDays(28)->toDateString())
            ->groupBy('query')
            ->havingRaw('SUM(impressions) >= 5')
            ->orderByDesc('imp')
            ->limit(400)
            ->get();
        foreach ($gsc as $row) {
            $add($row->query, 'gsc', ['impressions' => (int) $row->imp, 'clicks' => (int) $row->clk, 'position' => $row->pos ? round((float) $row->pos, 1) : null]);
        }

        // حذف موارد موجود (هدف/آرشیو/پیشنهاد قبلی دست‌نخورده می‌ماند، فقط داده‌اش به‌روز می‌شود)
        $existing = Keyword::where('site_id', $site->id)->pluck('status', 'normalized');

        // ۴) امتیازدهی با AI (دسته‌های ۸۰تایی)
        $scores = [];
        if ($useAi && $pool) {
            $fresh = array_values(array_filter(array_keys($pool), fn ($n) => ! isset($existing[$n])));
            foreach (array_chunk($fresh, 80) as $chunk) {
                $list = array_map(fn ($n) => ['keyword' => $pool[$n]['keyword'], 'impressions' => $pool[$n]['impressions'] ?? null, 'position' => $pool[$n]['position'] ?? null], $chunk);
                try {
                    $json = $this->ai->json($site, 'fast', 'keyword-scoring', Prompt::get('keyword-scoring', ['brand' => Prompt::brand($site)]), json_encode($list, JSON_UNESCAPED_UNICODE), ['max_tokens' => 6000, 'temperature' => 0.2]);
                    foreach ((array) ($json['items'] ?? []) as $item) {
                        $scores[Fa::normalizeKeyword((string) ($item['keyword'] ?? ''))] = $item;
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        $created = 0;
        $updated = 0;
        foreach ($pool as $n => $data) {
            $score = $scores[$n] ?? null;
            $relevance = $score['relevance'] ?? null;
            if ($relevance !== null && $relevance < 25) {
                continue; // نامرتبط
            }
            $opportunity = $this->opportunity($data, $relevance);
            if (isset($existing[$n])) {
                Keyword::where('site_id', $site->id)->where('normalized', $n)->update(array_filter([
                    'impressions_28d' => $data['impressions'] ?? null,
                    'clicks_28d' => $data['clicks'] ?? null,
                    'current_position' => $existing[$n] === 'candidate' ? ($data['position'] ?? null) : null,
                ], fn ($v) => $v !== null));
                $updated++;
                continue;
            }
            Keyword::create([
                'site_id' => $site->id,
                'keyword' => $data['keyword'],
                'status' => 'candidate',
                'source' => $data['source'],
                'intent' => $score['intent'] ?? ($data['intent'] ?? null),
                'difficulty' => isset($score['difficulty']) ? max(1, min(100, (int) $score['difficulty'])) : null,
                'ai_score' => $opportunity,
                'product_id' => $data['product_id'] ?? null,
                'current_position' => $data['position'] ?? null,
                'impressions_28d' => (int) ($data['impressions'] ?? 0),
                'clicks_28d' => (int) ($data['clicks'] ?? 0),
                'meta' => array_filter(array_merge((array) ($data['meta'] ?? []), ['reason' => $score['reason'] ?? null, 'relevance' => $relevance])),
            ]);
            $created++;
        }

        return ['products' => $products->count(), 'pool' => count($pool), 'created' => $created, 'updated' => $updated, 'ai' => $useAi];
    }

    /** امتیاز فرصت ۰ تا ۱۰۰: ارتباط + تقاضای واقعی + نزدیکی به صفحه‌ی اول − سختی */
    protected function opportunity(array $d, ?int $relevance): int
    {
        $rel = $relevance ?? 55;
        $imp = (int) ($d['impressions'] ?? 0);
        $pos = $d['position'] ?? null;
        $demand = $imp > 0 ? min(30, (int) round(log10($imp + 1) * 12)) : ($d['source'] === 'autocomplete' ? 8 : 4);
        $proximity = $pos === null ? 0 : ($pos <= 3 ? 4 : ($pos <= 10 ? 18 : ($pos <= 20 ? 22 : ($pos <= 40 ? 10 : 3))));
        $intent = in_array($d['intent'] ?? null, ['transactional', 'commercial'], true) ? 6 : 0;

        return (int) max(0, min(100, round($rel * 0.5 + $demand + $proximity + $intent)));
    }
}
