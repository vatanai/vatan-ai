{{-- زیرمنوی «کارخانه‌ی محتوای وطن» داخل «استودیو تولید» سایدبار میزبان (پارشیال مستقل) --}}
@php $cfLinks = \Vatan\Content\Support\Nav::links(); $cfBadge = \Vatan\Content\Support\Nav::reviewCount(); @endphp
@php $cfMenuActive = request()->routeIs('cf.*'); @endphp
<div class="sub-item sub-item-parent {{ $cfMenuActive ? 'active' : '' }}" onclick="toggleSubSub('content-factory-submenu', this)"><div class="sub-dot"></div><div class="sub-label">کارخانه‌ی محتوای وطن</div>@if($cfBadge > 0)<span class="nav-status-badge warn">{{ \Vatan\Content\Support\Fa::n(min(99, $cfBadge)) }}</span>@endif<i class="fa-solid fa-chevron-down sub-chev"></i></div>
<div class="sub-sub-wrap {{ $cfMenuActive ? 'open' : '' }}" id="content-factory-submenu"><div class="sub-sub-track">
  @foreach($cfLinks as [$r, $icon, $label, $pattern])
    <a href="{{ route($r) }}" class="sub-sub-item {{ request()->routeIs(...explode("|", $pattern)) ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">{{ $label }}</div></a>
  @endforeach
</div></div>
