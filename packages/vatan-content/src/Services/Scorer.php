<?php

namespace Vatan\Content\Services;

use Vatan\Content\Models\Production;

/**
 * امتیازدهی نسبی هر محتوا (۰ تا ۱۰۰) در برابر پست‌های هم‌قالب و هم‌بازه‌ی خود پیج.
 * مقایسه‌ی برداشت‌های ۱۰ دقیقه‌ای با ۲۸ روزه ممنوع است؛ داده‌ی ناقص هم صفر فرض نمی‌شود.
 */
class Scorer
{
    public function score(Production $p): ?array
    {
        if ($p->status !== 'published' || ! $p->published_at) {
            return null;
        }

        $windows = config('content-factory.metric_windows', []);
        $rank = array_flip(array_keys($windows));
        $snapshots = $p->snapshots()->get()->filter(fn ($s) => isset($rank[$s->window]))
            ->sortByDesc(fn ($s) => $rank[$s->window]);
        $snap = $snapshots->first();
        if (! $snap) {
            return null;
        }

        // فقط پست‌هایی وارد گروه مقایسه می‌شوند که همان قالب و همان پنجره‌ی برداشت را دارند.
        $peers = Production::where('workspace_id', $p->workspace_id)
            ->where('status', 'published')
            ->where('id', '!=', $p->id)
            ->where('format', $p->format)
            ->where('published_at', '>=', now()->subDays(90))
            ->with(['snapshots' => fn ($q) => $q->where('window', $snap->window)])
            ->get()
            ->map(function ($peer) {
                return ['production' => $peer, 'metrics' => $peer->snapshots->first()?->metrics ?? []];
            })
            ->filter(fn ($peer) => ! empty($peer['metrics']))
            ->values();

        $m = (array) ($snap->metrics ?? []);
        $reach = $this->positive($m['reach'] ?? null);
        $weightedEngagement = $this->weightedEngagement($m);
        $perThousand = fn (?float $value) => $value !== null && $reach ? $value * 1000 / $reach : null;

        $dimensions = [
            'attention' => ['value' => $this->positive($m['reach'] ?? null), 'peer' => fn ($x) => $this->positive($x['reach'] ?? null)],
            'engagement' => ['value' => $perThousand($weightedEngagement), 'peer' => function ($x) {
                $r = $this->positive($x['reach'] ?? null);
                $e = $this->weightedEngagement($x);

                return $r && $e !== null ? $e * 1000 / $r : null;
            }],
            'business' => ['value' => $perThousand($this->positive($m['dm_flows'] ?? null)), 'peer' => function ($x) {
                $r = $this->positive($x['reach'] ?? null);
                $d = $this->positive($x['dm_flows'] ?? null);

                return $r && $d !== null ? $d * 1000 / $r : null;
            }],
            'economic' => ['value' => $this->economicEfficiency($weightedEngagement, (float) $p->cost_usd), 'peer' => function ($x, $peer) {
                return $this->economicEfficiency($this->weightedEngagement($x), (float) $peer->production->cost_usd);
            }],
        ];

        $scores = [];
        foreach ($dimensions as $key => $dimension) {
            $value = $dimension['value'];
            $benchmarks = $peers->map(fn ($peer) => $dimension['peer']($peer['metrics'], $peer))
                ->filter(fn ($v) => $v !== null && $v > 0)->values()->all();
            $baseline = $this->median($benchmarks);
            $scores[$key] = $value !== null && $baseline !== null
                ? max(0, min(100, round(50 * $value / $baseline)))
                : null;
        }

        $weights = ['attention' => 0.30, 'engagement' => 0.35, 'business' => 0.25, 'economic' => 0.10];
        $available = array_filter($scores, fn ($value) => $value !== null);
        $weight = array_sum(array_intersect_key($weights, $available));
        $total = $weight > 0
            ? round(array_sum(array_map(fn ($key) => $available[$key] * $weights[$key], array_keys($available))) / $weight, 1)
            : null;

        $ageHours = $p->published_at->diffInHours(now());
        $confidence = $ageHours < 24 ? 'نامشخص' : ($peers->count() < 5 ? 'کم' : ($peers->count() < 15 ? 'متوسط' : 'زیاد'));
        $scores += [
            'window' => $snap->window,
            'format' => $p->format,
            'peers' => $peers->count(),
            'basis' => 'قالب و بازه‌ی یکسان؛ تعامل و دایرکت به‌ازای هر ۱۰۰۰ دسترسی',
        ];

        $p->update([
            'scores' => $scores,
            'score_total' => $total,
            'score_confidence' => $confidence,
            'scored_at' => now(),
        ]);

        return $scores;
    }

    private function positive(mixed $value): ?float
    {
        return is_numeric($value) && (float) $value >= 0 ? (float) $value : null;
    }

    private function weightedEngagement(array $metrics): ?float
    {
        $weights = ['saves' => 3, 'shares' => 4, 'comments' => 2, 'likes' => 1];
        $hasValue = false;
        $total = 0.0;
        foreach ($weights as $key => $weight) {
            if (array_key_exists($key, $metrics) && $metrics[$key] !== null && is_numeric($metrics[$key])) {
                $total += max(0, (float) $metrics[$key]) * $weight;
                $hasValue = true;
            }
        }

        return $hasValue ? $total : null;
    }

    private function economicEfficiency(?float $engagement, float $cost): ?float
    {
        return $engagement !== null && $cost > 0 ? $engagement / $cost : null;
    }

    private function median(array $values): ?float
    {
        if (! $values) {
            return null;
        }
        sort($values, SORT_NUMERIC);
        $middle = intdiv(count($values), 2);

        return count($values) % 2
            ? (float) $values[$middle]
            : ((float) $values[$middle - 1] + (float) $values[$middle]) / 2;
    }
}
