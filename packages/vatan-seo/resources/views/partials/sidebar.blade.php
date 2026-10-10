{{-- آیتم منوی «سئوی هوشمند» برای سایدبار میزبان (پارشیال مستقل؛ از sidebar-menu میزبان include می‌شود) --}}
@php
  $seoMenuActive = request()->routeIs('seo.*');
  $seoMenuBadge = 0;
  try {
    $seoMenuBadge = \Illuminate\Support\Facades\Cache::remember('seo-engine:sidebar-badge', 120, function () {
      return \Illuminate\Support\Facades\Schema::hasTable('seo_content_items')
        ? \Vatan\Seo\Models\ContentItem::where('status', 'review')->count() + \Vatan\Seo\Models\Task::where('status', 'needs_action')->where('automation', '!=', 'auto')->count()
        : 0;
    });
  } catch (\Throwable $e) { $seoMenuBadge = 0; }
  $seoMenuLinks = [
    ['seo.overview', 'نمای کلی و مانیتورینگ', request()->routeIs('seo.overview')],
    ['seo.keywords.index', 'کلمات کلیدی و رتبه', request()->routeIs('seo.keywords.*')],
    ['seo.plan', 'برنامه‌ی کار', request()->routeIs('seo.plan')],
    ['seo.technical', 'سلامت فنی', request()->routeIs('seo.technical')],
    ['seo.content.index', 'تولید محتوا', request()->routeIs('seo.content.*')],
    ['seo.scenarios.index', 'سناریوهای خودکار', request()->routeIs('seo.scenarios.*')],
    ['seo.activity', 'فعالیت و هزینه', request()->routeIs('seo.activity')],
    ['seo.settings', 'تنظیمات و اتصال‌ها', request()->routeIs('seo.settings')],
  ];
@endphp
<div class="nav-item">
  <div class="nav-link {{ $seoMenuActive ? 'active' : '' }}" onclick="toggleSub('seo-engine-submenu', this)"><div class="nav-icon"><i class="fa-solid fa-magnifying-glass-chart"></i></div><div class="nav-label">سئوی هوشمند</div>@if($seoMenuBadge > 0)<span class="nav-status-badge warn">{{ \Vatan\Seo\Support\Fa::n(min(99, $seoMenuBadge)) }}</span>@endif<i class="fa-solid fa-chevron-down nav-chev {{ $seoMenuActive ? 'open' : '' }}"></i></div>
  <div class="submenu {{ $seoMenuActive ? 'open' : '' }}" id="seo-engine-submenu"><div class="sub-track">
    @foreach($seoMenuLinks as [$seoR, $seoL, $seoA])
      <a href="{{ route($seoR) }}" class="sub-item {{ $seoA ? 'active' : '' }}"><div class="sub-dot"></div><div class="sub-label">{{ $seoL }}</div></a>
    @endforeach
  </div></div>
</div>
