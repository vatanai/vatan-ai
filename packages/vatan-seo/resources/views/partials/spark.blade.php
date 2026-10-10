{{-- اسپارک‌لاین SVG سمت سرور: $values (آرایه), $invert (برای رتبه: کمتر=بهتر) --}}
@php
  $seoVals = array_values(array_filter(array_map(fn ($v) => $v === null ? null : (float) $v, (array) ($values ?? [])), fn ($v) => $v !== null));
  $seoPath = ''; $seoArea = '';
  if (count($seoVals) >= 2) {
    $min = min($seoVals); $max = max($seoVals); $span = ($max - $min) ?: 1; $n = count($seoVals) - 1;
    $pts = [];
    foreach ($seoVals as $i => $v) {
      $x = round(100 - ($i / $n) * 100, 2); // راست‌به‌چپ: جدیدترین سمت چپ
      $norm = ($v - $min) / $span;
      $y = round(($invert ?? false) ? 4 + $norm * 28 : 32 - $norm * 28, 2);
      $pts[] = $x.','.$y;
    }
    $seoPath = 'M'.implode(' L', $pts);
    $seoArea = $seoPath.' L0,36 L100,36 Z';
  }
@endphp
@if($seoPath)
  <svg class="seo-spark {{ $class ?? '' }}" viewBox="0 0 100 36" preserveAspectRatio="none" aria-hidden="true"><path class="area" d="{{ $seoArea }}"/><path class="line" d="{{ $seoPath }}" vector-effect="non-scaling-stroke"/></svg>
@else
  <svg class="seo-spark {{ $class ?? '' }}" viewBox="0 0 100 36" preserveAspectRatio="none" aria-hidden="true"><path class="line" d="M0,30 L100,30" vector-effect="non-scaling-stroke" style="opacity:.25;stroke-dasharray:3 3"/></svg>
@endif
