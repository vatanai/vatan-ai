<?php

namespace Vatan\Seo\Data\Rank;

use Illuminate\Support\Facades\Http;
use Vatan\Seo\Models\Site;

/**
 * رتبه‌ی دقیق روزانه از نتایج زنده‌ی گوگل ایران (DataForSEO — ارتقای پولی).
 * هزینه تقریباً ۰٫۰۰۲ دلار برای هر کلمه در هر بار بررسی.
 */
class DataForSeoRankProvider implements RankProvider
{
    public function key(): string
    {
        return 'dataforseo';
    }

    public static function configured(): bool
    {
        return filled(config('seo-engine.dataforseo.login')) && filled(config('seo-engine.dataforseo.password'));
    }

    public function fetch(Site $site, $keywords): array
    {
        $out = [];
        $domain = preg_replace('/^www\./', '', $site->domain);
        foreach ($keywords->chunk(20) as $chunk) {
            $tasks = $chunk->map(fn ($k) => [
                'keyword' => $k->keyword,
                'location_code' => (int) config('seo-engine.dataforseo.location_code', 2364),
                'language_code' => config('seo-engine.dataforseo.language_code', 'fa'),
                'depth' => 100,
                'tag' => (string) $k->id,
            ])->values()->all();
            $res = Http::withBasicAuth(config('seo-engine.dataforseo.login'), config('seo-engine.dataforseo.password'))
                ->timeout(120)->post('https://api.dataforseo.com/v3/serp/google/organic/live/regular', $tasks);
            if (! $res->successful()) {
                continue;
            }
            foreach ((array) $res->json('tasks', []) as $task) {
                $id = (int) data_get($task, 'data.tag');
                $items = (array) data_get($task, 'result.0.items', []);
                $hit = collect($items)->first(fn ($i) => ($i['type'] ?? '') === 'organic' && str_ends_with(preg_replace('/^www\./', '', (string) ($i['domain'] ?? '')), $domain));
                $out[$id] = [
                    'position' => $hit ? (float) $hit['rank_group'] : null,
                    'url' => $hit['url'] ?? null,
                    'clicks' => 0,
                    'impressions' => 0,
                    'date' => now()->toDateString(),
                    'estimated' => false,
                    'competitors' => collect($items)->where('type', 'organic')->take(10)->map(fn ($i) => ['rank' => $i['rank_group'], 'domain' => $i['domain'], 'url' => $i['url'], 'title' => $i['title'] ?? null])->values()->all(),
                ];
            }
        }
        return $out;
    }
}
