<style>
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

  #vatan-nav-bar {
    display: flex;
    align-items: center;
    height: 70px;
    background: var(--nav-bg);
    border-radius: 999px;
    border: 1px solid var(--nav-border);
    padding: 0;
    position: relative;
    pointer-events: all;
    direction: ltr;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    width: 100%;
    box-sizing: border-box;
    transition: background 0.3s ease, border-color 0.3s ease;
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

  /* dark: سفید — light: مشکی */
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

  /* SVG اینلاین (دکمه +) */
  .vatan-nav-item svg.vatan-nav-icon {
    width: 29px;
    height: 29px;
    color: #ffffff;
    filter: none;
    transition: color 0.3s ease, transform 200ms ease;
  }

  html.light .vatan-nav-item svg.vatan-nav-icon {
    color: #000000;
  }

  /* active روی thumb سبز — همیشه سفید */
  .vatan-nav-item.is-active .vatan-nav-icon {
    filter: brightness(0) invert(1) !important;
    transform: scale(1.08);
  }

  .vatan-nav-item.is-active svg.vatan-nav-icon {
    color: #ffffff !important;
    filter: none;
    transform: scale(1.08);
  }

  /* nav bar در light mode */
  html.light #vatan-nav-bar {
    background: rgba(255, 255, 255, 0.97);
    border-color: #dddddd;
  }
</style>

<nav id="vatan-nav" role="navigation" aria-label="منوی اصلی">
  <div id="vatan-nav-bar">

    <div id="vatan-nav-thumb" aria-hidden="true"></div>

    <a href="{{ route('app.home') }}" class="vatan-nav-item" data-key="home" aria-label="خانه">
      <img src="{{ asset('assets/img/icons/nav-home.svg') }}" class="vatan-nav-icon" alt="">
    </a>

    <a href="{{ route('app.explore') }}" class="vatan-nav-item" data-key="explore" aria-label="ترندها">
      <img src="{{ asset('assets/img/icons/nav-trend.svg') }}" class="vatan-nav-icon" alt="">
    </a>

    <a href="{{ route('app.create') }}" class="vatan-nav-item" data-key="create" aria-label="بساز">
      <svg class="vatan-nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </a>

    <a href="{{ route('app.ideas') }}" class="vatan-nav-item" data-key="ideas" aria-label="ایده‌ها">
      <img src="{{ asset('assets/img/icons/nav-explore.svg') }}" class="vatan-nav-icon" alt="">
    </a>

    <a href="{{ route('app.profile') }}" class="vatan-nav-item" data-key="profile" aria-label="پروفایل">
      <img src="{{ asset('assets/img/icons/nav-profile.svg') }}" class="vatan-nav-icon" alt="">
    </a>

  </div>
</nav>

<script>
(function () {
  var bar   = document.getElementById('vatan-nav-bar');
  var thumb = document.getElementById('vatan-nav-thumb');
  var items = Array.from(document.querySelectorAll('.vatan-nav-item'));

  function detectActiveKey() {
    var path = window.location.pathname;
    if (/\/profile/.test(path))  return 'profile';
    if (/\/ideas/.test(path))    return 'ideas';
    if (/\/create/.test(path))   return 'create';
    if (/\/explore/.test(path))  return 'explore';
    return 'home';
  }

  function getThumbProps(el) {
    var barRect  = bar.getBoundingClientRect();
    var itemRect = el.getBoundingClientRect();
    var itemWidth = barRect.width / items.length;
    var index = items.indexOf(el);
    return {
      left:  index * itemWidth + 6,
      width: itemWidth - 12,
    };
  }

  function snapThumb(el) {
    var p = getThumbProps(el);
    console.log('snapThumb', p.left, p.width, bar.getBoundingClientRect().width);
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

  var activeKey = detectActiveKey();
  var activeEl  = bar.querySelector('[data-key="' + activeKey + '"]');

  if (activeEl) {
    setActive(activeEl);
    /* دو rAF برای render اولیه */
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        snapThumb(activeEl);
      });
    });
    /* snap دوباره بعد از load کامل صفحه — fix برای صفحاتی که max-width دارن */
    window.addEventListener('load', function () {
      snapThumb(activeEl);
    });
    /* snap بعد از 300ms — برای اطمینان از اینکه همه المان‌ها رندر شدن */
    setTimeout(function () {
      snapThumb(activeEl);
    }, 300);
  }

  items.forEach(function (item) {
    item.addEventListener('click', function (e) {
      e.preventDefault();
      if (item.classList.contains('is-active')) return;
      var href = item.getAttribute('href');
      setActive(item);
      slideThumb(item);
      setTimeout(function () {
        window.location.href = href;
      }, 370);
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

  new ResizeObserver(function() {
    var el = bar.querySelector('.vatan-nav-item.is-active');
    if (el) snapThumb(el);
  }).observe(bar);

}());
</script>
