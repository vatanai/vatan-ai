<?php

namespace Vatan\Content\Services;

use Vatan\Content\Models\Production;

/**
 * امتیاز چندلایه‌ی هر محتوا (۰ تا ۱۰۰) نسبت به میانگین پست‌های خود پیج:
 * توجه (دسترسی) · تعامل (ذخیره، اشتراک، کامنت) · کسب‌وکار (دایرکت/جریان کامنت) · اقتصادی (هزینه به نتیجه).
 * با کمتر از ۵ پست مقایسه، اطمینان «کم» و با کمتر از ۲۴ ساعت «نامشخص» است.
 */
class Scorer
{
    public function score(Production $p): ?array
    {
        $snap = $p->snapshots()->get()->sortByDesc(fn ($s) => array_search($s->window, array_keys(config('content-factory.metric_windows')), true))->first();
        if (! $snap) {
            return null;
        }
        $m = $snap->metrics ?? [];
        $peers = Production::where('workspace_id', $p->workspace_id)->where('status', 'published')->where('id', '!=', $p->id)->with('snapshots')->get()
            ->map(fn ($x) => optional($x->snapshots->sortByDesc('captured_at')->first())->metrics)->filter();
        $base = fn (string $k, float $d) => max($d, (float) ($peers->avg($k) ?: $d));
        $rel = fn (?float $v, float $b) => $v === null ? null : max(0, min(100, round(50 * $v / $b)));
        $eng = ($m['saves'] ?? 0) * 3 + ($m['shares'] ?? 0) * 4 + ($m['comments'] ?? 0) * 2 + ($m['likes'] ?? 0);
        $peerEng = $peers->avg(fn ($x) => ($x['saves'] ?? 0) * 3 + ($x['shares'] ?? 0) * 4 + ($x['comments'] ?? 0) * 2 + ($x['likes'] ?? 0)) ?: 20;
        $scores = [
            'attention' => $rel(isset($m['reach']) ? (float) $m['reach'] : null, $base('reach', 300)),
            'engagement' => $rel((float) $eng, (float) $peerEng),
            'business' => array_key_exists('dm_flows', $m) && $m['dm_flows'] !== null ? $rel((float) $m['dm_flows'], $base('dm_flows', 3)) : null,
            'economic' => null,
        ];
        if ($p->cost_usd > 0 && $scores['engagement'] !== null) {
            $scores['economic'] = max(0, min(100, round($scores['engagement'] / max(0.2, (float) $p->cost_usd * 4))));
        } elseif ((float) $p->cost_usd == 0.0) {
            $scores['economic'] = $scores['engagement'];
        }
        $vals = array_filter($scores, fn ($v) => $v !== null);
        $w = ['attention' => 0.3, 'engagement' => 0.35, 'business' => 0.25, 'economic' => 0.1];
        $tw = array_sum(array_intersect_key($w, $vals));
        $total = $tw ? round(array_sum(array_map(fn ($k) => $vals[$k] * $w[$k], array_keys($vals))) / $tw, 1) : null;
        $age = $p->published_at ? $p->published_at->diffInHours(now()) : 0;
        $conf = $age < 24 ? 'نامشخص' : ($peers->count() < 5 ? 'کم' : ($peers->count() < 15 ? 'متوسط' : 'زیاد'));
        $p->update(['scores' => $scores + ['window' => $snap->window, 'peers' => $peers->count()], 'score_total' => $total, 'score_confidence' => $conf, 'scored_at' => now()]);

        return $scores;
    }
}
