@php
  $isMobileCenter = request()->is('admin/dashboard') && request()->boolean('mobile_center');
  $isMobileUsers = request()->is('admin/users*');
  $isMobileProducts = request()->is('admin/products*') || request()->is('admin/categories*') || request()->is('admin/lab*');
  $isMobileSales = request()->is('admin/plans*')
    || request()->is('admin/orders*')
    || request()->is('admin/discounts*')
    || request()->is('admin/referrals*')
    || request()->is('admin/growth*');
@endphp

{{-- ناوبری موبایل پنل مدیریت؛ ظاهرش از اپ وطن گرفته شده، اما لینک‌ها مخصوص مدیریت هستند. --}}
<nav class="admin-mobile-nav" id="admin-mobile-nav" aria-label="منوی اصلی مدیریت">
  <div class="admin-mobile-nav-bar">
    <a
      href="{{ route('admin.dashboard', ['mobile_center' => 1]) }}"
      class="admin-mobile-nav-item {{ $isMobileCenter ? 'is-active' : '' }}"
      aria-label="مرکز فرماندهی"
    >
      <span class="admin-mobile-nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
      <span class="admin-mobile-nav-label">مرکز</span>
    </a>

    <button type="button" class="admin-mobile-nav-item {{ $isMobileUsers ? 'is-active' : '' }}" data-admin-mobile-sheet="users" aria-label="کاربران" aria-expanded="false">
      <span class="admin-mobile-nav-icon"><i class="fa-solid fa-users"></i></span>
      <span class="admin-mobile-nav-label">کاربران</span>
    </button>

    <button type="button" class="admin-mobile-nav-item {{ $isMobileProducts ? 'is-active' : '' }}" data-admin-mobile-sheet="products" aria-label="محصولات" aria-expanded="false">
      <span class="admin-mobile-nav-icon"><i class="fa-solid fa-box-open"></i></span>
      <span class="admin-mobile-nav-label">محصولات</span>
    </button>

    <button type="button" class="admin-mobile-nav-item {{ $isMobileSales ? 'is-active' : '' }}" data-admin-mobile-sheet="sales" aria-label="فروش و مارکتینگ" aria-expanded="false">
      <span class="admin-mobile-nav-icon"><i class="fa-solid fa-bullseye"></i></span>
      <span class="admin-mobile-nav-label">فروش</span>
    </button>

    <button type="button" class="admin-mobile-nav-item" data-admin-mobile-sidebar aria-label="بیشتر" aria-expanded="false">
      <span class="admin-mobile-nav-icon"><i class="fa-solid fa-ellipsis"></i></span>
      <span class="admin-mobile-nav-label">بیشتر</span>
    </button>
  </div>
</nav>

<div class="admin-mobile-sheet" id="admin-mobile-sheet" hidden>
  <div class="admin-mobile-sheet-backdrop" data-admin-mobile-close></div>
  <section class="admin-mobile-sheet-panel" role="dialog" aria-modal="true" aria-labelledby="admin-mobile-sheet-title">
    <header class="admin-mobile-sheet-header">
      <div>
        <span class="admin-mobile-sheet-kicker">وطن استودیو</span>
        <h2 id="admin-mobile-sheet-title">منوی مدیریت</h2>
      </div>
      <button type="button" class="admin-mobile-sheet-close" data-admin-mobile-close aria-label="بستن منو">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </header>

    <div class="admin-mobile-sheet-content" data-admin-mobile-panel="users" hidden>
      <div class="admin-mobile-sheet-section-title"><i class="fa-solid fa-users"></i><span>کاربران</span></div>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.users.index') }}"><i class="fa-solid fa-user-group"></i><span>لیست کاربران</span></a>
      <a class="admin-mobile-sheet-link" href="/admin/users/smart-lists"><i class="fa-solid fa-list-check"></i><span>لیست‌های هوشمند</span></a>
      <a class="admin-mobile-sheet-link" href="{{ url('/admin/users/gallery') }}"><i class="fa-solid fa-images"></i><span>گالری شخصی کاربران</span></a>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.users.face-profiles.index') }}"><i class="fa-solid fa-id-card"></i><span>کارکتر شیت کاربران</span></a>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.users.tokens') }}"><i class="fa-solid fa-coins"></i><span>مدیریت اعتبار</span></a>
    </div>

    <div class="admin-mobile-sheet-content" data-admin-mobile-panel="products" hidden>
      <div class="admin-mobile-sheet-section-title"><i class="fa-solid fa-box-open"></i><span>مدیریت محصولات</span></div>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.products') }}"><i class="fa-solid fa-image"></i><span>لیست محصولات عکس</span></a>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.products.videos') }}"><i class="fa-solid fa-film"></i><span>لیست محصولات ویدیو</span></a>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.products.create') }}"><i class="fa-solid fa-plus"></i><span>ثبت محصول عکس</span></a>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.products.video.v2.create') }}"><i class="fa-solid fa-wand-magic-sparkles"></i><span>ثبت محصول ویدیو</span></a>

      <details class="admin-mobile-sheet-group">
        <summary><span><i class="fa-solid fa-flask"></i> آزمایشگاه</span><i class="fa-solid fa-chevron-down"></i></summary>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.lab.create') }}">آزمایش جدید</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.lab.index') }}">لیست آزمایش‌ها</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.lab.reports') }}">گزارش آزمایشگاه</a>
      </details>

      <a class="admin-mobile-sheet-link" href="{{ route('admin.categories.index') }}"><i class="fa-solid fa-tags"></i><span>دسته‌بندی‌ها</span></a>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.categories.create') }}"><i class="fa-solid fa-tag"></i><span>افزودن دسته‌بندی</span></a>
      <div class="admin-mobile-sheet-link is-disabled"><i class="fa-solid fa-chart-column"></i><span>گزارش محصولات</span><small>بزودی</small></div>
      <div class="admin-mobile-sheet-link is-disabled"><i class="fa-solid fa-sliders"></i><span>تنظیمات نمایش</span><small>بزودی</small></div>
    </div>

    <div class="admin-mobile-sheet-content" data-admin-mobile-panel="sales" hidden>
      <div class="admin-mobile-sheet-section-title"><i class="fa-solid fa-bullseye"></i><span>فروش و مارکتینگ</span></div>
      <details class="admin-mobile-sheet-group" open>
        <summary><span><i class="fa-solid fa-layer-group"></i> پلن‌بیلدر فروش</span><i class="fa-solid fa-chevron-down"></i></summary>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.plans.index') }}">لیست پلن‌ها</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.plans.create') }}">ثبت پلن جدید</a>
      </details>
      <details class="admin-mobile-sheet-group">
        <summary><span><i class="fa-solid fa-cart-shopping"></i> سفارشات</span><i class="fa-solid fa-chevron-down"></i></summary>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.orders.index') }}">همه سفارشات</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.orders.processing') }}">در حال پردازش</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.orders.failed') }}">ناموفق و نیازمند بررسی</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.orders.plan-purchases') }}">خرید پلن‌ها و پرداخت‌ها</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.orders.refunds') }}">لغو و بازپرداخت</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.orders.analytics') }}">گزارش سفارش‌ها</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.discounts.index') }}">تخفیفات</a>
      </details>
      <details class="admin-mobile-sheet-group">
        <summary><span><i class="fa-solid fa-chart-line"></i> رشد</span><i class="fa-solid fa-chevron-down"></i></summary>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.growth.monitor') }}">پایش کامل</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.growth.overview') }}">نمای کلی</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.growth.users.index') }}">کاربران و سفر مشتری</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.growth.contents') }}">محتواها</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.growth.links.index') }}">مدیریت لینک‌ها</a>
        <a class="admin-mobile-sheet-link is-nested" href="{{ route('admin.growth.section', 'reports') }}">گزارش‌های رشد</a>
      </details>
      <a class="admin-mobile-sheet-link" href="{{ route('admin.referrals.overview') }}"><i class="fa-solid fa-share-nodes"></i><span>همکاری در فروش</span></a>
    </div>
  </section>
</div>

<script>
  (function () {
    var sheet = document.getElementById('admin-mobile-sheet');
    if (!sheet) return;
    var title = document.getElementById('admin-mobile-sheet-title');
    var labels = { users: 'کاربران', products: 'مدیریت محصولات', sales: 'فروش و مارکتینگ' };
    var triggers = Array.from(document.querySelectorAll('[data-admin-mobile-sheet]'));
    var sidebarTrigger = document.querySelector('[data-admin-mobile-sidebar]');

    function closeSheet() {
      sheet.hidden = true;
      document.body.classList.remove('admin-mobile-sheet-open');
      triggers.forEach(function (trigger) { trigger.setAttribute('aria-expanded', 'false'); });
    }

    function openSheet(key, trigger) {
      var wrap = document.getElementById('admin-wrap');
      if (wrap && wrap.classList.contains('sidebar-toggled') && window.adminToggleSidebar) {
        window.adminToggleSidebar();
      }
      sheet.hidden = false;
      document.body.classList.add('admin-mobile-sheet-open');
      title.textContent = labels[key] || 'منوی مدیریت';
      sheet.querySelectorAll('[data-admin-mobile-panel]').forEach(function (panel) {
        panel.hidden = panel.dataset.adminMobilePanel !== key;
      });
      triggers.forEach(function (item) { item.setAttribute('aria-expanded', item === trigger ? 'true' : 'false'); });
    }

    triggers.forEach(function (trigger) {
      trigger.addEventListener('click', function () { openSheet(trigger.dataset.adminMobileSheet, trigger); });
    });
    if (sidebarTrigger) {
      sidebarTrigger.addEventListener('click', function () {
        closeSheet();
        var wrap = document.getElementById('admin-wrap');
        if (wrap && !wrap.classList.contains('sidebar-toggled') && window.adminToggleSidebar) {
          window.adminToggleSidebar();
        }
      });
    }
    sheet.querySelectorAll('[data-admin-mobile-close]').forEach(function (el) { el.addEventListener('click', closeSheet); });
    sheet.addEventListener('click', function (event) {
      if (event.target.closest('a')) closeSheet();
    });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeSheet(); });
    window.AdminMobileNav = { closeSheet: closeSheet };
  }());
</script>
