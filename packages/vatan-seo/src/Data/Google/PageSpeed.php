<?php

namespace Vatan\Seo\Data\Google;

use RuntimeException;

/** PageSpeed Insights (Lighthouse + داده‌ی واقعی کاربران CrUX) — رایگان */
class PageSpeed
{
    public function run(string $url, string $strategy = 'mobile'): array
    {
        $params = ['url' => $url, 'strategy' => $strategy, 'category' => ['performance', 'seo', 'best-practices', 'accessibility'], 'locale' => 'fa'];
        if ($key = config('seo-engine.google.pagespeed_key')) {
            $params['key'] = $key;
        }
        $query = http_build_query(array_diff_key($params, ['category' => 1]));
        foreach ($params['category'] as $c) {
            $query .= '&category='.$c;
        }
        $res = GoogleHttp::request(90)->get(GoogleHttp::url('https://www.googleapis.com/pagespeedonline/v5/runPagespeed?'.$query));
        if (! $res->successful()) {
            throw new RuntimeException('PageSpeed: '.mb_substr((string) data_get($res->json(), 'error.message', $res->body()), 0, 300));
        }
        $j = (array) $res->json();
        $audits = (array) data_get($j, 'lighthouseResult.audits', []);
        $field = (array) data_get($j, 'loadingExperience.metrics', []);

        $fails = [];
        foreach ($audits as $id => $a) {
            if (isset($a['score']) && $a['score'] !== null && $a['score'] < 0.9 && ($a['scoreDisplayMode'] ?? '') !== 'informative' && ($a['scoreDisplayMode'] ?? '') !== 'notApplicable') {
                $fails[$id] = ['title' => $a['title'] ?? $id, 'score' => $a['score'], 'display' => $a['displayValue'] ?? null];
            }
        }

        return [
            'url' => $url,
            'strategy' => $strategy,
            'scores' => [
                'performance' => $this->score($j, 'performance'),
                'seo' => $this->score($j, 'seo'),
                'best_practices' => $this->score($j, 'best-practices'),
                'accessibility' => $this->score($j, 'accessibility'),
            ],
            'lab' => [
                'lcp_ms' => data_get($audits, 'largest-contentful-paint.numericValue'),
                'cls' => data_get($audits, 'cumulative-layout-shift.numericValue'),
                'tbt_ms' => data_get($audits, 'total-blocking-time.numericValue'),
                'fcp_ms' => data_get($audits, 'first-contentful-paint.numericValue'),
                'weight_kb' => ($w = data_get($audits, 'total-byte-weight.numericValue')) ? round($w / 1024) : null,
            ],
            'field' => [
                'lcp_ms' => data_get($field, 'LARGEST_CONTENTFUL_PAINT_MS.percentile'),
                'inp_ms' => data_get($field, 'INTERACTION_TO_NEXT_PAINT.percentile'),
                'cls' => ($c = data_get($field, 'CUMULATIVE_LAYOUT_SHIFT_SCORE.percentile')) !== null ? $c / 100 : null,
                'category' => data_get($j, 'loadingExperience.overall_category'),
            ],
            'failing_audits' => $fails,
        ];
    }

    private function score(array $j, string $cat): ?int
    {
        $s = data_get($j, "lighthouseResult.categories.{$cat}.score");
        return $s === null ? null : (int) round($s * 100);
    }
}
