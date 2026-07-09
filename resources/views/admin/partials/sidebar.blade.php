<aside class="sidebar fixed top-0 right-16 bottom-0 w-[230px] flex flex-col overflow-y-auto z-[100] direction-rtl" dir="rtl">

  {{-- لوگو --}}
  <div class="sb-logo">
    <div class="sb-logo-mark">و</div>
    <div>
      <div class="sb-logo-name">وطن استودیو</div>
      <div class="sb-logo-sub">Admin Panel</div>
    </div>
  </div>

  {{-- کاربر --}}
  <div class="sb-user-top">
    <div class="sb-av">م</div>
    <div class="flex-1">
      <div class="sb-uname">محسن رضایی</div>
      <div class="sb-urole">مدیر کل</div>
    </div>
    <div class="sb-status-dot"></div>
  </div>

  <nav class="flex-1 py-2">

    {{-- مرکز فرماندهی --}}
    <div class="nav-item">
      <a href="/admin/dashboard" class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
        <div class="nav-icon"><i class="fa-solid fa-bolt-lightning"></i></div>
        <div class="nav-label">مرکز فرماندهی</div>
      </a>
    </div>

    <div class="sb-section">مدیریت محصولات</div>

    {{-- محصولات --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/products*') || request()->is('admin/categories*') ? 'active' : '' }}" onclick="toggleSub('products-submenu', this)">
        <div class="nav-icon"><i class="fa-solid fa-box-open"></i></div>
        <div class="nav-label">محصولات</div>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/products*') || request()->is('admin/categories*') ? 'open' : '' }}"></i>
      </div>
      <div class="submenu {{ request()->is('admin/products*') || request()->is('admin/categories*') ? 'open' : '' }}" id="products-submenu">
        <div class="sub-track">
          <a href="/admin/products" class="sub-item {{ request()->is('admin/products') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">لیست محصولات</div>
          </a>
          <a href="/admin/products/create" class="sub-item {{ request()->is('admin/products/create') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">ثبت محصول جدید</div>
          </a>
          <a href="{{ route('admin.categories.index') }}" class="sub-item {{ request()->is('admin/categories') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">دسته‌بندی‌ها</div>
          </a>
          <a href="{{ route('admin.categories.create') }}" class="sub-item {{ request()->is('admin/categories/create') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">افزودن دسته‌بندی جدید</div>
          </a>
          <div class="sub-item">
            <div class="sub-dot"></div><div class="sub-label">گزارش محصولات</div>
            <span class="nav-status-badge warn">بزودی</span>
          </div>
          <div class="sub-item">
            <div class="sub-dot"></div><div class="sub-label">تنظیمات نمایش</div>
            <span class="nav-status-badge warn">بزودی</span>
          </div>
        </div>
      </div>
    </div>

    {{-- اکسپلور --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/explore*') ? 'active' : '' }}" onclick="toggleSub('explore-submenu', this)">
        <div class="nav-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
        <div class="nav-label">اکسپلور</div>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/explore*') ? 'open' : '' }}"></i>
      </div>
      <div class="submenu {{ request()->is('admin/explore*') ? 'open' : '' }}" id="explore-submenu">
        <div class="sub-track">
          <a href="{{ route('admin.explore.index') }}" class="sub-item {{ request()->is('admin/explore') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">مدیریت اکسپلور</div>
          </a>
        </div>
      </div>
    </div>

    {{-- سفارشات --}}
    <div class="nav-item">
      <a href="/admin/orders" class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}">
        <div class="nav-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="nav-label">سفارشات</div>
        <span class="nav-status-badge warn">بزودی</span>
      </a>
    </div>

    <div class="sb-section">هوش مصنوعی</div>

    {{-- مدل‌های هوشمند --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/ai-models*') ? 'active' : '' }}" onclick="toggleSub('ai-models-submenu', this)">
        <div class="nav-icon"><i class="fa-solid fa-microchip"></i></div>
        <div class="nav-label">مدل‌های هوشمند</div>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/ai-models*') ? 'open' : '' }}"></i>
      </div>
      <div class="submenu {{ request()->is('admin/ai-models*') ? 'open' : '' }}" id="ai-models-submenu">
        <div class="sub-track">
          <a href="{{ route('admin.ai-models.index') }}" class="sub-item {{ request()->is('admin/ai-models') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">لیست مدل‌ها</div>
          </a>
          <a href="{{ route('admin.ai-models.create') }}" class="sub-item {{ request()->is('admin/ai-models/create') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">افزودن مدل جدید</div>
          </a>
        </div>
      </div>
    </div>

    <div class="sb-divider"></div>

    <div class="sb-section">کاربران</div>

    {{-- کاربران --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}" onclick="toggleSub('users-submenu', this)">
        <div class="nav-icon"><i class="fa-solid fa-users"></i></div>
        <div class="nav-label">کاربران</div>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/users*') ? 'open' : '' }}"></i>
      </div>
      <div class="submenu {{ request()->is('admin/users*') ? 'open' : '' }}" id="users-submenu">
        <div class="sub-track">
          <a href="/admin/users" class="sub-item {{ request()->is('admin/users') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">لیست کاربران</div>
          </a>
          <a href="/admin/users/smart-lists" class="sub-item {{ request()->is('admin/users/smart-lists') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">لیست‌های هوشمند</div>
          </a>
          <a href="/admin/users/tokens" class="sub-item {{ request()->is('admin/users/tokens') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">مدیریت توکن</div>
          </a>
        </div>
      </div>
    </div>

    <div class="sb-divider"></div>

    {{-- تنظیمات --}}
    <div class="nav-item">
      <div class="nav-link {{ request()->is('admin/settings*') || request()->is('admin/crm*') ? 'active' : '' }}" onclick="toggleSub('settings-submenu', this)">
        <div class="nav-icon"><i class="fa-solid fa-gear"></i></div>
        <div class="nav-label">تنظیمات</div>
        <i class="fa-solid fa-chevron-down nav-chev {{ request()->is('admin/settings*') || request()->is('admin/crm*') ? 'open' : '' }}"></i>
      </div>
      <div class="submenu {{ request()->is('admin/settings*') || request()->is('admin/crm*') ? 'open' : '' }}" id="settings-submenu">
        <div class="sub-track">
          <a href="/admin/crm" class="sub-item {{ request()->is('admin/crm') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">CRM</div>
          </a>
          <a href="/admin/settings/admins" class="sub-item {{ request()->is('admin/settings/admins') ? 'active' : '' }}">
            <div class="sub-dot"></div><div class="sub-label">مدیریت ادمین‌ها</div>
            <span class="nav-status-badge info">آینده</span>
          </a>
          <a href="/admin/settings/access" class="sub-item">
            <div class="sub-dot"></div><div class="sub-label">سطوح دسترسی</div>
            <span class="nav-status-badge info">آینده</span>
          </a>
          <a href="/admin/settings/system" class="sub-item">
            <div class="sub-dot"></div><div class="sub-label">تنظیمات سیستم</div>
            <span class="nav-status-badge info">آینده</span>
          </a>
          <a href="/admin/settings/payment-gateway" class="sub-item">
            <div class="sub-dot"></div><div class="sub-label">درگاه پرداخت</div>
            <span class="nav-status-badge info">آینده</span>
          </a>
          <a href="/admin/settings/backup" class="sub-item">
            <div class="sub-dot"></div><div class="sub-label">پشتیبان‌گیری</div>
            <span class="nav-status-badge info">آینده</span>
          </a>
          <a href="/admin/settings/logs" class="sub-item">
            <div class="sub-dot"></div><div class="sub-label">لاگ فعالیت ادمین‌ها</div>
            <span class="nav-status-badge info">آینده</span>
          </a>
        </div>
      </div>
    </div>

    <div class="sb-divider"></div>

    {{-- آپدیت در آینده (گروه سه‌سطحی) --}}
    <div class="nav-item">
      <div class="nav-link" onclick="toggleSub('future-all-submenu', this)">
        <div class="nav-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <div class="nav-label">آپدیت در آینده</div>
        <i class="fa-solid fa-chevron-down nav-chev"></i>
      </div>
      <div class="submenu" id="future-all-submenu">
        <div class="sub-track">

          <div class="sb-section">نظارت</div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-dashboard-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">داشبورد نظارتی</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-dashboard-submenu">
            <div class="sub-sub-track">
              <a href="/admin/dashboard/stats" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">آمار لحظه‌ای</div></a>
              <a href="/admin/dashboard/daily" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">آمار روزانه و ماهانه</div></a>
              <a href="/admin/dashboard/alerts" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">هشدارها</div></a>
            </div>
          </div>

          <div class="sb-section">مدیریت</div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-bloggers-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">بلاگرها</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-bloggers-submenu">
            <div class="sub-sub-track">
              <a href="/admin/bloggers" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">لیست بلاگرها</div></a>
              <a href="/admin/bloggers/commission" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">مدیریت کمیسیون</div></a>
              <a href="/admin/bloggers/traffic" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">گزارش ترافیک</div></a>
            </div>
          </div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-orders-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">سفارشات</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-orders-submenu">
            <div class="sub-sub-track">
              <a href="/admin/orders" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">لیست سفارشات</div></a>
              <a href="/admin/orders/analytics" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">آنالیتیکس سفارشات</div></a>
            </div>
          </div>

          <div class="sb-section">ارتباطات</div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-tickets-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">تیکت‌ها</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-tickets-submenu">
            <div class="sub-sub-track">
              <a href="/admin/tickets" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">تیکت‌های باز</div></a>
              <a href="/admin/tickets/processing" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">در حال بررسی</div></a>
              <a href="/admin/tickets/ai-response" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">پاسخ هوش مصنوعی</div></a>
              <a href="/admin/tickets/report" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">گزارش تیکت‌ها</div></a>
            </div>
          </div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-messages-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">پیام‌رسانی</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-messages-submenu">
            <div class="sub-sub-track">
              <a href="/admin/messages" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">ارسال به کاربر خاص</div></a>
              <a href="/admin/messages/bulk" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">ارسال گروهی</div></a>
              <a href="/admin/messages/scheduled" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">زمان‌بندی پیام</div></a>
              <a href="/admin/messages/history" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">تاریخچه پیام‌ها</div></a>
            </div>
          </div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-banners-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">بنر و نمایش</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-banners-submenu">
            <div class="sub-sub-track">
              <a href="/admin/banners" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">بنرهای صفحه اصلی</div></a>
              <a href="/admin/banners/popups" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">پاپ‌آپ عمومی</div></a>
              <a href="/admin/banners/discounts" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">کدهای تخفیف</div></a>
            </div>
          </div>

          <div class="sb-section">مالی</div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-finance-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">مالی</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-finance-submenu">
            <div class="sub-sub-track">
              <a href="/admin/payments" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">تراکنش‌ها</div></a>
              <a href="/admin/payments/manual" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">پرداخت دستی</div></a>
              <a href="/admin/payments/commission" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">کمیسیون بلاگرها</div></a>
              <a href="/admin/payments/revenue-report" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">گزارش درآمد و هزینه</div></a>
              <a href="/admin/payments/forecast" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">پیش‌بینی درآمد</div></a>
            </div>
          </div>

          <div class="sb-section">آنالیز و مارکتینگ</div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-analytics-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">آنالیز</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-analytics-submenu">
            <div class="sub-sub-track">
              <a href="/admin/analytics" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">قیف فروش</div></a>
              <a href="/admin/analytics/behavior" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">رفتار کاربر</div></a>
              <a href="/admin/analytics/bloggers" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">آنالیز بلاگرها</div></a>
              <a href="/admin/analytics/campaigns" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">کمپین‌ها</div></a>
              <a href="/admin/analytics/retarget" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">ریتارگت</div></a>
              <a href="/admin/analytics/viral" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">گزارش وایرال</div></a>
            </div>
          </div>

          <a href="/admin/reports" class="sub-item"><div class="sub-dot"></div><div class="sub-label">گزارش‌ساز</div></a>

          <div class="sb-section">سیستم</div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-infra-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">زیرساخت</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-infra-submenu">
            <div class="sub-sub-track">
              <a href="/admin/infrastructure" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">وضعیت سرور</div></a>
              <a href="/admin/infrastructure/queue" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">صف پردازش</div></a>
              <a href="/admin/infrastructure/ai-cost" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">هزینه هوش مصنوعی</div></a>
              <a href="/admin/infrastructure/logs" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">لاگ خطاها</div></a>
            </div>
          </div>

          <div class="sub-item sub-item-parent" onclick="toggleSubSub('future-content-submenu', this)">
            <div class="sub-dot"></div><div class="sub-label">محتوا</div>
            <i class="fa-solid fa-chevron-down sub-chev"></i>
          </div>
          <div class="sub-sub-wrap" id="future-content-submenu">
            <div class="sub-sub-track">
              <a href="/admin/content" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">مقالات</div></a>
              <a href="/admin/content/pages" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">صفحات سایت</div></a>
              <a href="/admin/content/media" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">مدیریت رسانه‌ها</div></a>
              <a href="/admin/content/notifications" class="sub-sub-item"><div class="sub-sub-dot"></div><div class="sub-sub-label">اعلان‌های سیستمی</div></a>
            </div>
          </div>

          <a href="/admin/crm/attendance" class="sub-item"><div class="sub-dot"></div><div class="sub-label">حضور و غیاب</div></a>
          <a href="/admin/jobs" class="sub-item"><div class="sub-dot"></div><div class="sub-label">لاگ جاب‌ها</div></a>

        </div>
      </div>{{-- /future-all-submenu --}}
    </div>

    <div class="h-4"></div>

  </nav>
</aside>

<script>
/* ── باز/بسته کردن زیرمنو سطح ۲ (دقیقا مثل یوآی داشبورد محسن) ── */
function toggleSub(subId, headerEl) {
  const sub = document.getElementById(subId);
  const chev = headerEl.querySelector('.nav-chev');
  const wasOpen = sub.classList.contains('open');

  document.querySelectorAll('.submenu').forEach(s => s.classList.remove('open'));
  document.querySelectorAll('.nav-chev').forEach(c => c.classList.remove('open'));

  if (!wasOpen) {
    sub.classList.add('open');
    if (chev) chev.classList.add('open');
  }

  document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
  headerEl.classList.add('active');
}

/* ── باز/بسته کردن زیرمنوی سطح ۳ ── */
function toggleSubSub(subId, headerEl) {
  const wrap = document.getElementById(subId);
  const chev = headerEl.querySelector('.sub-chev');
  const wasOpen = wrap.classList.contains('open');
  const track = headerEl.closest('.sub-track');

  if (track) {
    track.querySelectorAll('.sub-sub-wrap').forEach(w => w.classList.remove('open'));
    track.querySelectorAll('.sub-chev').forEach(c => c.classList.remove('open'));
  }

  if (!wasOpen) {
    wrap.classList.add('open');
    if (chev) chev.classList.add('open');
  }
}

/* ── محاسبه خط سبز پیشرونده تا آیتم فعال، هنگام بارگذاری صفحه ── */
(function () {
  function updateLines(selectorTrack, selectorItem) {
    document.querySelectorAll(selectorTrack).forEach(track => {
      const items = Array.from(track.querySelectorAll(selectorItem));
      const idx = items.findIndex(i => i.classList.contains('active'));
      if (idx === -1) return;
      const trackRect = track.getBoundingClientRect();
      const itemRect = items[idx].getBoundingClientRect();
      if (trackRect.height === 0) return;
      const pct = Math.round(((itemRect.top - trackRect.top + itemRect.height * 0.5) / trackRect.height) * 100);
      track.style.setProperty('--line-pct', Math.min(Math.max(pct, 0), 96) + '%');
    });
  }
  function runUpdateLines() {
    updateLines('.sub-track', ':scope > .sub-item');
    updateLines('.sub-sub-track', '.sub-sub-item');
  }

  document.addEventListener('DOMContentLoaded', function () {
    /* اجرای اولیه (برای جلوگیری از پرش ناگهانی خط) */
    runUpdateLines();

    /* بعد از لود کامل فونت یکان‌بخ دوباره محاسبه می‌شود چون قبل از لود فونت
       عرض/ارتفاع متن‌ها هنوز نهایی نیست و باعث می‌شد خط سبز بد رندر شود */
    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(runUpdateLines);
    }

    /* یک بار هم بعد از رندر کامل صفحه (fallback برای مرورگرهای قدیمی) */
    window.addEventListener('load', runUpdateLines);

    /* هنگام تغییر سایز صفحه هم دوباره محاسبه شود */
    window.addEventListener('resize', runUpdateLines);
  });
})();
</script>
