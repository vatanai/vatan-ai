<?php

namespace Vatan\Seo\Agents;

use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Models\Cluster;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Support\Prompt;

/** خوشه‌بندی کلمات هدف و بهترین پیشنهادها بر اساس نیت مشترک */
class Clustering
{
    public function __construct(private Ai $ai) {}

    public function run(Site $site): array
    {
        $keywords = Keyword::where('site_id', $site->id)
            ->where(fn ($q) => $q->where('status', 'target')->orWhere(fn ($q2) => $q2->where('status', 'candidate')->where('ai_score', '>=', 55)))
            ->orderByDesc('status')->orderByDesc('ai_score')->limit(150)->get();
        if ($keywords->count() < 3) {
            return ['clusters' => 0, 'note' => 'کلمه‌ی کافی برای خوشه‌بندی نیست.'];
        }

        if ($this->ai->available($site, 'fast')) {
            $json = $this->ai->json($site, 'fast', 'clustering', Prompt::get('clustering', ['brand' => Prompt::brand($site)]), json_encode($keywords->pluck('keyword')->all(), JSON_UNESCAPED_UNICODE), ['max_tokens' => 5000, 'temperature' => 0.2]);
            $groups = (array) ($json['clusters'] ?? []);
        } else {
            // جایگزین بدون AI: گروه‌بندی بر اساس دو کلمه‌ی اول
            $groups = $keywords->groupBy(fn ($k) => implode(' ', array_slice(explode(' ', $k->normalized), 0, 2)))
                ->map(fn ($g, $name) => ['name' => $g->first()->keyword, 'intent' => $g->first()->intent, 'keywords' => $g->pluck('keyword')->all()])->values()->all();
        }

        $byNorm = $keywords->keyBy('normalized');
        $count = 0;
        foreach ($groups as $g) {
            if (empty($g['name']) || empty($g['keywords'])) {
                continue;
            }
            $cluster = Cluster::firstOrCreate(['site_id' => $site->id, 'name' => Fa::cleanKeyword($g['name'])], ['intent' => $g['intent'] ?? null, 'summary' => $g['summary'] ?? ($g['page_type'] ?? null)]);
            foreach ((array) $g['keywords'] as $kw) {
                $k = $byNorm[Fa::normalizeKeyword((string) $kw)] ?? null;
                $k?->update(['cluster_id' => $cluster->id]);
            }
            $count++;
        }
        return ['clusters' => $count, 'keywords' => $keywords->count()];
    }
}
