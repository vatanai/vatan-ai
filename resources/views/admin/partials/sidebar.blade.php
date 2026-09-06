<aside class="sidebar" id="admin-sidebar" dir="rtl">

  {{-- لوگو --}}
  <div class="sb-logo">
    <div class="sb-logo-mark">و</div>
    <div>
      <div class="sb-logo-name">وطن استودیو</div>
      <div class="sb-logo-sub">Admin Panel</div>
    </div>
  </div>

  {{-- کاربر — کلیک روی پروفایل خروج امن از داشبورد و انتقال به لاگین را انجام می‌دهد. --}}
  @php $currentAdmin = auth('admin')->user(); @endphp
  <form id="admin-logout-form" method="POST" action="{{ route('admin.logout') }}" class="hidden">
    @csrf
  </form>
  <button type="submit" form="admin-logout-form" class="sb-user-top" style="width:100%;border:0;background:transparent;color:inherit;font:inherit;text-align:right;cursor:pointer;" title="خروج از داشبورد" aria-label="خروج از داشبورد">
    <div class="sb-av">{{ mb_substr($currentAdmin?->name ?? 'م', 0, 1) }}</div>
    <div class="flex-1">
      <div class="sb-uname">{{ $currentAdmin?->name ?? 'مدیر پنل' }}</div>
      <div class="sb-urole">{{ $currentAdmin?->isLeader() ? 'رهبر' : 'مدیر' }}</div>
    </div>
    <div class="sb-status-dot"></div>
  </button>

  <nav class="flex-1 py-2">

    @include('admin.partials.sidebar-menu')

  </nav>
</aside>

<script>
/* ── حفظ موقعیت و منوی باز سایدبار هنگام جابه‌جایی بین صفحات ── */
(function () {
  const scrollKey = 'admin-sidebar-scroll-top';
  const openMenuKey = 'admin-sidebar-open-menu';
  const openSubMenuKey = 'admin-sidebar-open-submenu';

  function saveSidebarState(sidebar) {
    try {
      sessionStorage.setItem(scrollKey, String(sidebar.scrollTop));
      sessionStorage.setItem(openMenuKey, sidebar.querySelector('.submenu.open')?.id || '');
      sessionStorage.setItem(openSubMenuKey, sidebar.querySelector('.sub-sub-wrap.open')?.id || '');
    } catch (e) {}
  }

  function restoreOpenMenu(sidebar) {
    /* اگر روت فعلی منوی فعال مشخصی دارد، وضعیت سرور اولویت دارد. */
    const routeActiveMenu = Array.from(sidebar.querySelectorAll('.submenu')).find(
      menu => menu.querySelector('.sub-item.active, .sub-sub-item.active')
    );
    if (routeActiveMenu) return;

    try {
      const openMenu = document.getElementById(sessionStorage.getItem(openMenuKey) || '');
      if (openMenu?.classList.contains('submenu')) {
        openMenu.classList.add('open');
        openMenu.previousElementSibling?.querySelector('.nav-chev')?.classList.add('open');
      }

      const openSubMenu = document.getElementById(sessionStorage.getItem(openSubMenuKey) || '');
      if (openSubMenu?.classList.contains('sub-sub-wrap')) {
        openSubMenu.classList.add('open');
        openSubMenu.previousElementSibling?.querySelector('.sub-chev')?.classList.add('open');
      }
    } catch (e) {}
  }

  function restoreSidebarState() {
    const sidebar = document.getElementById('admin-sidebar');
    if (!sidebar) return;

    restoreOpenMenu(sidebar);

    try {
      const savedScrollTop = Number(sessionStorage.getItem(scrollKey));
      if (Number.isFinite(savedScrollTop) && savedScrollTop >= 0) {
        sidebar.scrollTop = savedScrollTop;
      }
    } catch (e) {}

    let scrollFrame = null;
    sidebar.addEventListener('scroll', function () {
      if (scrollFrame) cancelAnimationFrame(scrollFrame);
      scrollFrame = requestAnimationFrame(function () {
        saveSidebarState(sidebar);
        scrollFrame = null;
      });
    }, { passive: true });

    /* قبل از شروع ناوبری ذخیره می‌شود تا حتی در لودهای سریع هم از دست نرود. */
    sidebar.addEventListener('click', function (event) {
      if (event.target.closest('a, .nav-link, .sub-item-parent')) {
        saveSidebarState(sidebar);
      }
    });
    window.addEventListener('pagehide', () => saveSidebarState(sidebar));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', restoreSidebarState);
  } else {
    restoreSidebarState();
  }
})();

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
  const track = headerEl.closest('.sub-sub-track') || headerEl.closest('.sub-track');

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
    updateLines('.sub-sub-track', ':scope > .sub-sub-item');
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
