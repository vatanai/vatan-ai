@php($seoB = $p === null ? 'none' : ($p <= 3 ? 'top3' : ($p <= 10 ? 'top10' : ($p <= 20 ? 'top20' : 'rest'))))
<span class="seo-pos b-{{ $seoB }} seo-num">{{ $p === null ? '—' : \Vatan\Seo\Support\Fa::n($p, $p < 10 ? 1 : 0) }}</span>
