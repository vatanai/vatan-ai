<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Data\Rank\DataForSeoRankProvider;
use Vatan\Seo\Data\Rank\GscRankProvider;
use Vatan\Seo\Models\KeywordRank;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\Notifier;
use Vatan\Seo\Support\Fa;

class RankUpdate implements Handler
{
    public function __construct(private Notifier $notifier) {}

    public function provider(Site $site)
    {
        $source = data_get($site->profile(), 'rank_source', 'gsc');
        return ($source === 'dataforseo_if_connected' || $source === 'dataforseo') && DataForSeoRankProvider::configured()
            ? new DataForSeoRankProvider()
            : new GscRankProvider();
    }

    public function handle(Site $site, Scenario $scenario): array
    {
        $keywords = $site->keywords()->targets()->get();
        if ($keywords->isEmpty()) {
            return ['skipped', 'هنوز کلمه‌ی هدفی انتخاب نشده است.'];
        }
        $provider = $this->provider($site);
        $data = $provider->fetch($site, $keywords);
        $threshold = (float) ($scenario->config['drop_alert'] ?? 3);
        $drops = [];
        $gains = [];
        $updated = 0;

        foreach ($keywords as $kw) {
            $r = $data[$kw->id] ?? null;
            if (! $r) {
                continue;
            }
            KeywordRank::updateOrCreate(
                ['keyword_id' => $kw->id, 'date' => $r['date'], 'source' => $provider->key()],
                ['position' => $r['position'], 'clicks' => $r['clicks'], 'impressions' => $r['impressions'], 'url' => $r['url']]
            );
            $isNewDay = ! $kw->rank_checked_at || $r['date'] > optional($kw->rank_checked_at)->toDateString();
            $old = $kw->current_position;
            $new = $r['position'];
            $changes = [
                'current_position' => $new,
                'ranking_url' => $r['url'] ?? $kw->ranking_url,
                'clicks_28d' => $r['clicks'] ?: $kw->clicks_28d,
                'impressions_28d' => $r['impressions'] ?: $kw->impressions_28d,
                'rank_checked_at' => $r['date'],
            ];
            if ($isNewDay && $old !== null) {
                $changes['previous_position'] = $old;
            }
            if ($new !== null && ($kw->best_position === null || $new < $kw->best_position)) {
                $changes['best_position'] = $new;
            }
            if ($kw->start_position === null && $new !== null) {
                $changes['start_position'] = $new;
            }
            if (! empty($r['competitors'])) {
                $changes['meta'] = array_merge((array) $kw->meta, ['serp' => $r['competitors']]);
            }
            $kw->update($changes);
            $updated++;

            if ($isNewDay && $old !== null && $new !== null) {
                if ($new - $old >= $threshold) {
                    $drops[] = $kw->keyword.' ('.Fa::n($old, 1).' ← '.Fa::n($new, 1).')';
                } elseif ($old > 10 && $new <= 10) {
                    $gains[] = $kw->keyword.' ← '.Fa::n($new, 1);
                }
            }
        }

        if ($drops) {
            $this->notifier->alert($site, 'danger', 'rank_drop', 'افت رتبه در '.Fa::n(count($drops)).' کلمه‌ی هدف', implode("\n", $drops), ['keywords' => $drops]);
        }
        if ($gains) {
            $this->notifier->alert($site, 'success', 'rank_top10', '🎉 ورود به صفحه‌ی اول گوگل', implode("\n", $gains), ['keywords' => $gains]);
        }
        return ['success', sprintf('رتبه‌ی %s کلمه از منبع %s به‌روز شد؛ %s افت و %s ورود به صفحه‌ی اول.', Fa::n($updated), $provider->key() === 'gsc' ? 'سرچ کنسول' : 'DataForSEO', Fa::n(count($drops)), Fa::n(count($gains))), compact('drops', 'gains')];
    }
}
