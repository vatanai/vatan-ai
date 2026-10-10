@php
  $seoStats = app(\Vatan\Seo\Services\NavStats::class)->for($site);
  $seoReview = $seoStats['review'];
  $seoAction = $seoStats['action'];
  $seoNav = [
    ['seo.overview', 'fa-chart-line', 'نمای کلی', request()->routeIs('seo.overview'), null],
    ['seo.keywords.index', 'fa-key', 'کلمات کلیدی', request()->routeIs('seo.keywords.*'), null],
    ['seo.plan', 'fa-list-check', 'برنامه‌ی کار', request()->routeIs('seo.plan'), $seoAction],
    ['seo.technical', 'fa-screwdriver-wrench', 'سلامت فنی', request()->routeIs('seo.technical'), null],
    ['seo.content.index', 'fa-feather-pointed', 'تولید محتوا', request()->routeIs('seo.content.*'), $seoReview],
    ['seo.scenarios.index', 'fa-robot', 'سناریوها', request()->routeIs('seo.scenarios.*'), null],
    ['seo.activity', 'fa-wave-square', 'فعالیت و هزینه', request()->routeIs('seo.activity'), null],
    ['seo.settings', 'fa-sliders', 'تنظیمات', request()->routeIs('seo.settings'), null],
  ];
@endphp
<nav class="seo-tabs" aria-label="بخش‌های سئوی هوشمند">
  @foreach($seoNav as [$r, $icon, $label, $active, $badge])
    <a href="{{ route($r) }}" class="seo-tab {{ $active ? 'is-active' : '' }}"><i class="fa-solid {{ $icon }}"></i> {{ $label }}@if($badge)<span class="seo-tab-badge">{{ \Vatan\Seo\Support\Fa::n($badge) }}</span>@endif</a>
  @endforeach
  <span class="seo-tab-version" title="نسخه‌ی موتور سئو">موتور سئو {{ \Vatan\Seo\Support\Fa::digits('v'.config('seo-engine.version')) }}</span>
</nav>
