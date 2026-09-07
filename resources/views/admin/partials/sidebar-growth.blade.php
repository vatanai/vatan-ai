@php($isGrowthActive = request()->is('admin/growth*'))

{{-- رشد داخل فروش و مارکتینگ؛ سطح‌بندی عمیق برای جلوگیری از به‌هم‌ریختگی --}}
<div class="sub-item sub-item-parent {{ $isGrowthActive ? 'active' : '' }}" onclick="toggleSubSub('sales-growth-submenu-new', this)">
  <div class="sub-dot"></div><div class="sub-label">رشد</div><i class="fa-solid fa-chevron-down sub-chev"></i>
</div>
<div class="sub-sub-wrap {{ $isGrowthActive ? 'open' : '' }}" id="sales-growth-submenu-new">
  <div class="sub-sub-track">
    <a href="{{ route('admin.growth.monitor') }}" class="sub-sub-item {{ request()->is('admin/growth') || request()->is('admin/growth/monitor') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">پایش کامل</div></a>
    <a href="{{ route('admin.growth.overview') }}" class="sub-sub-item {{ request()->is('admin/growth/overview') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">نمای کلی</div></a>
    <a href="{{ route('admin.growth.users.index') }}" class="sub-sub-item {{ request()->is('admin/growth/users*') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">کاربران و سفر مشتری</div></a>

    <div class="sub-sub-item sub-item-parent {{ request()->is('admin/growth/channels*') ? 'active' : '' }}" onclick="toggleSubSub('sales-growth-channels-submenu-new', this)"><div class="sub-sub-dot"></div><div class="sub-sub-label">کانال‌ها</div><i class="fa-solid fa-chevron-down sub-chev"></i></div>
    <div class="sub-sub-wrap sub-sub-wrap-deep {{ request()->is('admin/growth/channels*') ? 'open' : '' }}" id="sales-growth-channels-submenu-new"><div class="sub-sub-track sub-sub-track-deep">
      <a href="{{ route('admin.growth.channels', 'instagram') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/channels/instagram') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">اینستاگرام</div></a>
      <a href="{{ route('admin.growth.channels', 'telegram') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/channels/telegram') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">تلگرام</div></a>
      <a href="{{ route('admin.growth.channels', 'youtube') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/channels/youtube') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">یوتیوب</div></a>
      <a href="{{ route('admin.growth.channels', 'other') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/channels/other') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">سایر کانال‌ها</div></a>
    </div></div>

    <a href="{{ route('admin.growth.contents') }}" class="sub-sub-item {{ request()->is('admin/growth/contents*') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">محتواها</div></a>

    <div class="sub-sub-item sub-item-parent {{ request()->is('admin/growth/links*') ? 'active' : '' }}" onclick="toggleSubSub('sales-growth-links-submenu-new', this)"><div class="sub-sub-dot"></div><div class="sub-sub-label">لینک‌ها</div><i class="fa-solid fa-chevron-down sub-chev"></i></div>
    <div class="sub-sub-wrap sub-sub-wrap-deep {{ request()->is('admin/growth/links*') ? 'open' : '' }}" id="sales-growth-links-submenu-new"><div class="sub-sub-track sub-sub-track-deep">
      <a href="{{ route('admin.growth.links.index') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/links') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">مدیریت لینک‌ها</div></a>
      <a href="{{ route('admin.growth.links.create') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/links/create') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">ساخت و کوتاه‌سازی</div></a>
      <a href="{{ route('admin.growth.links.analytics') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/links/analytics*') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">آنالیز لینک‌ها</div></a>
    </div></div>

    <a href="{{ route('admin.growth.section', 'attribution') }}" class="sub-sub-item {{ request()->is('admin/growth/attribution') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">کاربران و اتریبیوشن</div></a>
    <a href="{{ route('admin.growth.section', 'products') }}" class="sub-sub-item {{ request()->is('admin/growth/products') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">محصولات</div></a>
    <a href="{{ route('admin.growth.section', 'sales') }}" class="sub-sub-item {{ request()->is('admin/growth/sales') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">فروش و تبدیل</div></a>
    <a href="{{ route('admin.growth.section', 'retention') }}" class="sub-sub-item {{ request()->is('admin/growth/retention') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">بازگشت و خرید مجدد</div></a>
    <a href="{{ route('admin.growth.section', 'reports') }}" class="sub-sub-item {{ request()->is('admin/growth/reports') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">گزارش‌ها</div></a>

    <div class="sub-sub-item sub-item-parent {{ request()->is('admin/growth/settings*') ? 'active' : '' }}" onclick="toggleSubSub('sales-growth-settings-submenu-new', this)"><div class="sub-sub-dot"></div><div class="sub-sub-label">تنظیمات</div><i class="fa-solid fa-chevron-down sub-chev"></i></div>
    <div class="sub-sub-wrap sub-sub-wrap-deep {{ request()->is('admin/growth/settings*') ? 'open' : '' }}" id="sales-growth-settings-submenu-new"><div class="sub-sub-track sub-sub-track-deep">
      <a href="{{ route('admin.growth.section', 'settings') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/settings') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">تنظیمات عمومی</div></a>
      <a href="{{ route('admin.growth.data-sources.index') }}" class="sub-sub-item sub-sub-item-deep {{ request()->is('admin/growth/settings/data-sources*') ? 'active' : '' }}"><div class="sub-sub-dot"></div><div class="sub-sub-label">منابع داده</div></a>
    </div></div>
  </div>
</div>
