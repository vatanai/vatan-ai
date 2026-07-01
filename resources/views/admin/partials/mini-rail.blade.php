<div class="mini-rail" id="miniRail">

  {{-- Logo mark --}}
  <div class="mini-rail-logo" onclick="window.location='/admin/dashboard'" title="وطن استودیو">
    <img src="/assets/img/icon_vatan.svg" alt="وطن">
  </div>

  {{-- Primary shortcuts --}}
  <div class="mini-btn {{ request()->is('admin/dashboard*') ? 'active' : '' }}"
       onclick="miniBtnGo(this); window.location='/admin/dashboard'">
    <i class="fa-solid fa-border-all"></i>
    <span class="mini-btn-tooltip">داشبورد</span>
  </div>

  <div class="mini-btn {{ request()->is('admin/users*') ? 'active' : '' }}"
       onclick="miniBtnGo(this); window.location='/admin/users'">
    <i class="fa-solid fa-users"></i>
    <span class="mini-btn-tooltip">کاربران</span>
  </div>

  <div class="mini-btn {{ request()->is('admin/products*') ? 'active' : '' }}"
       onclick="miniBtnGo(this); window.location='/admin/products'">
    <i class="fa-solid fa-box-open"></i>
    <span class="mini-btn-tooltip">محصولات</span>
  </div>

  <div class="mini-btn {{ request()->is('admin/jobs*') ? 'active' : '' }}"
       onclick="miniBtnGo(this); window.location='/admin/jobs'">
    <i class="fa-solid fa-chart-line"></i>
    <span class="mini-btn-tooltip">لاگ‌ها</span>
  </div>

  <div class="mini-btn {{ request()->is('admin/payments*') ? 'active' : '' }}"
       onclick="miniBtnGo(this); window.location='/admin/payments'">
    <i class="fa-solid fa-receipt"></i>
    <span class="mini-btn-tooltip">پرداخت‌ها</span>
  </div>

  <div class="mini-rail-divider"></div>

  <div class="mini-btn" onclick="miniBtnGo(this); window.location='/admin/reports'">
    <i class="fa-solid fa-file-arrow-down"></i>
    <span class="mini-btn-tooltip">گزارش‌ها</span>
  </div>

  <div class="mini-btn" onclick="miniBtnGo(this); window.location='/admin/analytics'">
    <i class="fa-solid fa-star"></i>
    <span class="mini-btn-tooltip">آنالیز</span>
  </div>

  <div class="mini-btn" onclick="miniBtnGo(this); window.location='/admin/prompts'">
    <i class="fa-solid fa-wand-magic-sparkles"></i>
    <span class="mini-btn-tooltip">پرامپت‌ها</span>
  </div>

  <div class="mini-rail-spacer"></div>
  <div class="mini-rail-divider"></div>

  <div class="mini-btn" onclick="miniBtnGo(this); window.location='/admin/settings/system'">
    <i class="fa-solid fa-circle-question"></i>
    <span class="mini-btn-tooltip">تنظیمات سیستم</span>
  </div>

  <div class="mini-btn"
       onclick="if(confirm('از پنل خارج می‌شوید؟')) { document.getElementById('admin-logout-form').submit(); }">
    <i class="fa-solid fa-right-from-bracket"></i>
    <span class="mini-btn-tooltip">خروج</span>
  </div>

</div>

<form id="admin-logout-form" action="{{ route('admin.logout') }}" method="POST" style="display:none;">
  @csrf
</form>
