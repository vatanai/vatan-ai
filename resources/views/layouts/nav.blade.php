{{-- ══════════════════════════════════════════
     TOP NAV — تبلت و دسکتاپ (640px+)
══════════════════════════════════════════ --}}
<nav id="vatan-topnav" aria-label="منوی بالای صفحه" dir="rtl">
  <div id="vatan-topnav-inner">

    {{-- لوگو — سمت راست --}}
    <a href="{{ route('app.home') }}" class="topnav-logo" aria-label="خانه">
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="28" height="28">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" style="height:22px;width:auto;">
    </a>

    {{-- لینک‌های ناوبری — وسط --}}
    <div class="topnav-links">
      <a href="{{ route('app.home') }}"    class="topnav-link" data-key="home">خانه</a>
      <a href="{{ route('app.explore') }}" class="topnav-link" data-key="explore">اکسپلور</a>
      <a href="{{ route('app.trends') }}"  class="topnav-link" data-key="trends">ترندز</a>
      <a href="{{ route('app.profile') }}" class="topnav-link" data-key="profile">پروفایل</a>
    </div>

    {{-- دکمه بساز — سمت چپ --}}
    <a href="{{ route('app.create') }}" class="topnav-create" data-key="create">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2.8" stroke-linecap="round"/>
      </svg>
      بساز
    </a>

  </div>
</nav>

{{-- ══════════════════════════════════════════
     BOTTOM NAV — فقط موبایل (< 640px)
══════════════════════════════════════════ --}}
<nav id="vatan-nav" role="navigation" aria-label="منوی اصلی">
  <div id="vatan-nav-bar">

    <div id="vatan-nav-thumb" aria-hidden="true"></div>

    <a href="{{ route('app.home') }}" class="vatan-nav-item" data-key="home" aria-label="خانه">
      <svg class="vatan-nav-icon" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 9.5L12 3L21 9.5V20C21 20.5523 20.5523 21 20 21H15V15H9V21H4C3.44772 21 3 20.5523 3 20V9.5Z"/>
      </svg>
    </a>

    <a href="{{ route('app.trends') }}" class="vatan-nav-item" data-key="trends" aria-label="ترندز">
      <img src="{{ asset('assets/img/icons/nav-trend.svg') }}" class="vatan-nav-icon" alt="">
    </a>

    <a href="{{ route('app.create') }}" class="vatan-nav-item" data-key="create" aria-label="بساز">
      <svg class="vatan-nav-icon vatan-nav-icon--plus" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </a>

    <a href="{{ route('app.explore') }}" class="vatan-nav-item" data-key="explore" aria-label="اکسپلور">
      <img src="{{ asset('assets/img/icons/nav-explore.svg') }}" class="vatan-nav-icon" alt="">
    </a>

    <a href="{{ route('app.profile') }}" class="vatan-nav-item" data-key="profile" aria-label="پروفایل">
      <img src="{{ asset('assets/img/icons/nav-profile.svg') }}" class="vatan-nav-icon" alt="">
    </a>

  </div>
</nav>

<style>
/* ══════════════════════════════════════════
   TOP NAV — پنهان روی موبایل، نمایش روی 640px+
══════════════════════════════════════════ */
#vatan-topnav {
  display: none;
}

@media (min-width: 640px) {
  #vatan-topnav {
    display: block;
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 300;
    background: rgba(12, 12, 16, 0.88);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255,255,255,0.09);
    box-shadow: 0 2px 16px rgba(0,0,0,0.3);
  }

  html.light #vatan-topnav {
    background: rgba(255,255,255,0.92);
    border-bottom-color: rgba(0,0,0,0.08);
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
  }

  #vatan-topnav-inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    direction: rtl;
    gap: 24px;
  }

  /* لوگو */
  .topnav-logo {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    flex-shrink: 0;
  }

  /* لینک‌ها */
  .topnav-links {
    display: flex;
    align-items: center;
    gap: 8px;
    direction: rtl;
  }

  .topnav-link {
    font-size: 14px;
    font-weight: 500;
    color: rgba(255,255,255,0.55);
    text-decoration: none;
    padding: 6px 14px;
    border-radius: 8px;
    transition: color 0.2s, background 0.2s;
    white-space: nowrap;
  }
  .topnav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.07);
  }
  .topnav-link.is-active {
    color: #fff;
    font-weight: 700;
    background: rgba(255,255,255,0.09);
  }

  html.light .topnav-link { color: rgba(0,0,0,0.5); }
  html.light .topnav-link:hover { color: #000; background: rgba(0,0,0,0.05); }
  html.light .topnav-link.is-active { color: #000; background: rgba(0,0,0,0.07); }

  /* دکمه بساز */
  .topnav-create {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 9px 20px;
    background: #0BBF53;
    border-radius: 10px;
    color: #ffffff;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    flex-shrink: 0;
    transition: opacity 0.18s, transform 0.14s;
    white-space: nowrap;
  }
  .topnav-create:hover { opacity: 0.88; }
  .topnav-create.is-active {
    box-shadow: 0 0 0 2px rgba(11,191,83,0.4);
  }
}

/* ══════════════════════════════════════════
   BOTTOM NAV — فقط موبایل
══════════════════════════════════════════ */
#vatan-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 200;
  padding: 0 16px calc(env(safe-area-inset-bottom, 0px) + 28px) 16px;
  pointer-events: none;
  max-width: 480px;
  margin: 0 auto;
}

@media (min-width: 640px) {
  #vatan-nav { display: none; }
}

#vatan-nav-bar {
  display: flex;
  align-items: center;
  height: 70px;
  background: rgba(17, 17, 22, 0.82);
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,0.13);
  padding: 0;
  position: relative;
  pointer-events: all;
  direction: ltr;
  backdrop-filter: blur(20px) saturate(180%);
  -webkit-backdrop-filter: blur(20px) saturate(180%);
  width: 100%;
  box-sizing: border-box;
  transition: background 0.3s ease, border-color 0.3s ease;
  box-shadow: 0 4px 24px rgba(0,0,0,0.35);
}

#vatan-nav-thumb {
  position: absolute;
  top: 6px;
  bottom: 6px;
  left: 0;
  width: 0;
  border-radius: 999px;
  background: #0BBF53;
  z-index: 0;
  pointer-events: none;
  transition: none;
  visibility: hidden;
}

.vatan-nav-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-decoration: none;
  cursor: pointer;
  position: relative;
  z-index: 1;
  -webkit-tap-highlight-color: transparent;
  user-select: none;
}

.vatan-nav-icon {
  width: 24px;
  height: 24px;
  display: block;
  pointer-events: none;
  filter: brightness(0) invert(1);
  transition: filter 0.3s ease, transform 200ms ease;
}

html.light .vatan-nav-icon {
  filter: brightness(0) invert(0);
}

.vatan-nav-item svg.vatan-nav-icon {
  color: #ffffff;
  filter: none;
  transition: color 0.3s ease, transform 200ms ease;
}

.vatan-nav-item svg.vatan-nav-icon--plus {
  width: 29px;
  height: 29px;
}

html.light .vatan-nav-item svg.vatan-nav-icon {
  color: #000000;
}

.vatan-nav-item.is-active .vatan-nav-icon {
  filter: brightness(0) invert(1) !important;
  transform: scale(1.08);
}

.vatan-nav-item.is-active svg.vatan-nav-icon {
  color: #ffffff !important;
  filter: none;
  transform: scale(1.08);
}

html.light #vatan-nav-bar {
  background: rgba(255, 255, 255, 0.88);
  border-color: rgba(0,0,0,0.1);
  box-shadow: 0 4px 24px rgba(0,0,0,0.1);
}
</style>

<script>
(function () {
  /* ── تشخیص صفحه فعال ── */
  function detectActiveKey() {
    var path = window.location.pathname;
    if (/\/profile/.test(path))  return 'profile';
    if (/\/trends/.test(path))   return 'trends';
    if (/\/create/.test(path))   return 'create';
    if (/\/explore/.test(path))  return 'explore';
    if (/\/product/.test(path))  return '';
    return 'home';
  }

  var activeKey = detectActiveKey();

  /* ── top nav active ── */
  var topLinks = document.querySelectorAll('.topnav-link, .topnav-create');
  topLinks.forEach(function (link) {
    if (link.dataset.key === activeKey) link.classList.add('is-active');
  });

  /* ── bottom nav ── */
  var bar   = document.getElementById('vatan-nav-bar');
  var thumb = document.getElementById('vatan-nav-thumb');
  var items = Array.from(document.querySelectorAll('.vatan-nav-item'));

  function getThumbProps(el) {
    var barRect   = bar.getBoundingClientRect();
    var itemWidth = barRect.width / items.length;
    var index     = items.indexOf(el);
    return { left: index * itemWidth + 6, width: itemWidth - 12 };
  }

  function snapThumb(el) {
    var p = getThumbProps(el);
    thumb.style.transition  = 'none';
    thumb.style.left        = p.left + 'px';
    thumb.style.width       = p.width + 'px';
    thumb.style.visibility  = 'visible';
  }

  function slideThumb(el) {
    var p = getThumbProps(el);
    thumb.style.transition = 'left 360ms cubic-bezier(0.22,1,0.36,1), width 360ms cubic-bezier(0.22,1,0.36,1)';
    thumb.style.left       = p.left + 'px';
    thumb.style.width      = p.width + 'px';
    thumb.style.visibility = 'visible';
  }

  function setActive(el) {
    items.forEach(function (i) { i.classList.remove('is-active'); });
    el.classList.add('is-active');
  }

  if (activeKey) {
    var activeEl = bar.querySelector('[data-key="' + activeKey + '"]');
    if (activeEl) {
      setActive(activeEl);
      requestAnimationFrame(function () {
        requestAnimationFrame(function () { snapThumb(activeEl); });
      });
      window.addEventListener('load', function () { snapThumb(activeEl); });
      setTimeout(function () { snapThumb(activeEl); }, 300);
    }
  }

  items.forEach(function (item) {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      if (item.classList.contains('is-active')) return;
      var href = item.getAttribute('href');
      setActive(item);
      slideThumb(item);
      setTimeout(function () { window.location.href = href; }, 370);
    });
  });

  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var el = bar.querySelector('.vatan-nav-item.is-active');
      if (el) snapThumb(el);
    }, 100);
  });

  new ResizeObserver(function () {
    var el = bar.querySelector('.vatan-nav-item.is-active');
    if (el) snapThumb(el);
  }).observe(bar);

}());
</script>
