{{-- تغییر نسبی: $now, $before, $lower (کمتر=بهتر), $abs (اختلاف مطلق به‌جای درصد) --}}
@php
  $seoD = null;
  if (($now ?? null) !== null && ($before ?? null) !== null && ($abs ?? false || $before != 0)) {
    $seoD = ($abs ?? false) ? $now - $before : ($now - $before) / abs($before);
  }
  $seoGood = $seoD === null ? null : (($lower ?? false) ? $seoD < 0 : $seoD > 0);
@endphp
@if($seoD === null)
  <span class="seo-delta is-flat">—</span>
@elseif(abs($seoD) < 0.005)
  <span class="seo-delta is-flat">بدون تغییر</span>
@else
  <span class="seo-delta {{ $seoGood ? 'is-up' : 'is-down' }}"><i class="fa-solid {{ $seoGood ? 'fa-caret-up' : 'fa-caret-down' }}"></i>{{ ($abs ?? false) ? \Vatan\Seo\Support\Fa::n(abs($seoD), 1) : \Vatan\Seo\Support\Fa::percent(abs($seoD), 0) }}</span>
@endif
