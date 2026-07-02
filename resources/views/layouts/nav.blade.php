{{-- ══════════════════════════════════════════
     TOP NAV — تبلت و دسکتاپ (+640px)
══════════════════════════════════════════ --}}
<nav id="vatan-topnav" aria-label="منوی بالای صفحه" dir="rtl" class="hidden sm:block fixed top-0 left-0 right-0 z-[300] bg-[#0c0c10]/88 [.light_&]:bg-white/92 backdrop-blur-[20px] backdrop-saturate-[180%] border-b border-white/10 [.light_&]:border-black/10 shadow-lg [.light_&]:shadow-sm">
  <div id="vatan-topnav-inner" class="max-w-[1280px] mx-auto px-8 h-16 flex items-center justify-between gap-6">

    {{-- لوگو — سمت راست --}}
    <a href="{{ route('app.home') }}" class="flex items-center gap-2 no-underline shrink-0" aria-label="خانه">
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="28" height="28" class="shrink-0">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" style="height:22px; width:auto;" class="shrink-0">
    </a>

    {{-- لینک‌های ناوبری — وسط --}}
    <div class="topnav-links flex items-center gap-2">
      @php
        $navItems = [
          ['route' => 'app.home', 'key' => 'home', 'label' => 'خانه'],
          ['route' => 'app.explore', 'key' => 'explore', 'label' => 'اکسپلور'],
          ['route' => 'app.trends', 'key' => 'trends', 'label' => 'ترندز'],
          ['route' => 'app.profile', 'key' => 'profile', 'label' => 'پروفایل'],
        ];
      @endphp

      @foreach($navItems as $item)
        <a href="{{ route($item['route']) }}" 
           class="topnav-link text-[14px] font-medium text-white/55 [.light_&]:text-black/50 no-underline px-3.5 py-1.5 rounded-lg transition-all duration-200 whitespace-nowrap hover:text-white [.light_&]:hover:text-black hover:bg-white/10 [.light_&]:hover:bg-black/5 [&.is-active]:text-white [.light_&][&.is-active]:text-black [&.is-active]:font-bold [&.is-active]:bg-white/15 [.light_&][&.is-active]:bg-black/10" 
           data-key="{{ $item['key'] }}">
          {{ $item['label'] }}
        </a>
      @endforeach
    </div>

    {{-- بخش اکشن‌ها و وضعیت احراز هویت — سمت چپ --}}
    <div class="topnav-left-side flex items-center gap-3 shrink-0">
      {{-- دکمه بساز --}}
      <a href="{{ route('app.home') }}" class="topnav-create flex items-center gap-1.5 px-5 py-2.5 bg-[#0BBF53] rounded-[10px] text-white text-[14px] font-bold no-underline transition-all duration-180 hover:opacity-90 [&.is-active]:shadow-[0_0_0_2px_rgba(11,191,83,0.4)] whitespace-nowrap" data-key="create">
        <i class="fa-solid fa-plus text-[14px]"></i>
        بساز
      </a>

      {{-- نمایش آواتار کاربری به همراه دراپ‌داون --}}
      @auth
        <div class="topnav-profile-wrapper relative">
          <button type="button" id="profile-menu-trigger" class="topnav-avatar block w-[38px] h-[38px] shrink-0 bg-none border-none p-0 cursor-pointer" aria-label="منوی کاربری">
            @if(auth()->user()->avatar)
              <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="پروفایل" class="w-full h-full object-cover rounded-full border-2 border-white [.light_&]:border-black/15 transition-all duration-200 hover:border-[#0BBF53] hover:scale-105">
            @else
              <div class="w-full h-full rounded-full border-2 border-white bg-white/10 flex items-center justify-center text-white transition-all duration-200 hover:border-[#0BBF53] hover:scale-105">
                <i class="fa-solid fa-user text-[16px]"></i>
              </div>
            @endif
          </button>
          
          {{-- صدا زدن مودال/دراپ‌داون مشخصات کاربر از فایل پارشیال --}}
          @include('partials.profile-dropdown')
          
        </div>
      @endauth

      @guest
        <a href="{{ route('login') }}" class="topnav-auth text-[13px] font-bold text-white/80 [.light_&]:text-black/70 no-underline px-4 py-2 rounded-[10px] bg-white/5 [.light_&]:bg-black/5 border border-white/10 [.light_&]:border-black/10 transition-all duration-200 whitespace-nowrap hover:text-white [.light_&]:hover:text-black hover:bg-white/10 [.light_&]:hover:bg-black/10 hover:border-white/20 [.light_&]:hover:border-black/20">
          ورود / ثبت‌نام
        </a>
      @endguest
    </div>

  </div>
</nav>

{{-- ══════════════════════════════════════════
     BOTTOM NAV — فقط موبایل (< 640px)
══════════════════════════════════════════ --}}
<nav id="vatan-nav" role="navigation" aria-label="منوی اصلی" class="sm:hidden fixed bottom-0 left-0 right-0 z-[200] pb-[calc(env(safe-area-inset-bottom,0px)+28px)] px-4 pointer-events-none max-w-[480px] mx-auto">
  <div id="vatan-nav-bar" class="flex items-center h-[70px] bg-[#111116]/82 [.light_&]:bg-white/88 rounded-full border border-white/15 [.light_&]:border-black/10 p-0 relative pointer-events-auto backdrop-blur-[20px] backdrop-saturate-[180%] w-full box-border shadow-2xl transition-all duration-300">

    <div id="vatan-nav-thumb" aria-hidden="true" class="absolute top-1.5 bottom-1.5 left-0 w-0 rounded-full bg-[#0BBF53] z-0 pointer-events-none invisible"></div>

    <a href="{{ route('app.home') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="home" aria-label="خانه">
      <i class="fa-solid fa-house vatan-nav-icon text-[20px] text-white [.light_&]:text-black transition-all duration-300 group-[.is-active]:scale-110 group-[.is-active]:text-white"></i>
    </a>

    <a href="{{ route('app.trends') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="trends" aria-label="ترندز">
      <i class="fa-solid fa-fire vatan-nav-icon text-[20px] text-white/60 [.light_&]:text-black/60 transition-all duration-300 group-[.is-active]:scale-110 group-[.is-active]:text-white"></i>
    </a>

    <a href="{{ route('app.create') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="create" aria-label="بساز">
      <i class="fa-solid fa-plus vatan-nav-icon text-[22px] text-white [.light_&]:text-black transition-all duration-300 group-[.is-active]:scale-110 group-[.is-active]:text-white"></i>
    </a>

    <a href="{{ route('app.explore') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="explore" aria-label="اکسپلور">
      <i class="fa-solid fa-compass vatan-nav-icon text-[20px] text-white/60 [.light_&]:text-black/60 transition-all duration-300 group-[.is-active]:scale-110 group-[.is-active]:text-white"></i>
    </a>

    @auth
      <a href="{{ route('app.profile') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="profile" aria-label="پروفایل">
        @if(auth()->user()->avatar)
          <img src="{{ asset('storage/' . auth()->user()->avatar) }}" class="w-6 h-6 rounded-full object-cover border-[1.5px] border-white [.light_&]:border-black/20 transition-all duration-200 group-[.is-active]:border-white group-[.is-active]:scale-110" alt="پروفایل">
        @else
          <i class="fa-solid fa-user text-[18px] text-white transition-all duration-200 group-[.is-active]:scale-110"></i>
        @endif
      </a>
    @endauth

    @guest
      <a href="{{ route('login') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="profile" aria-label="ورود به حساب">
        <i class="fa-solid fa-right-to-bracket vatan-nav-icon text-[19px] text-white [.light_&]:text-black transition-all duration-300 group-[.is-active]:scale-110 group-[.is-active]:text-white"></i>
      </a>
    @endguest

  </div>
</nav>

<style>
  /* انیمیشن ورود روان مودال */
  @keyframes dropFadeIn {
    from { opacity: 0; transform: translateY(-12px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }
  /* انیمیشن خروج روان مودال */
  @keyframes dropFadeOut {
    from { opacity: 1; transform: translateY(0) scale(1); }
    to { opacity: 0; transform: translateY(-12px) scale(0.96); }
  }

  .animate-in {
    animation: dropFadeIn 0.22s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  }
  .animate-out {
    animation: dropFadeOut 0.18s cubic-bezier(0.36, 0.07, 0.19, 0.97) forwards;
  }
</style>

<script>
(function () {
  function detectActiveKey() {
    var path = window.location.pathname;
    if (/\/profile/.test(path))   return 'profile';
    if (/\/trends/.test(path))    return 'trends';
    if (/\/create/.test(path))    return 'create';
    if (/\/explore/.test(path))   return 'explore';
    return 'home';
  }

  var activeKey = detectActiveKey();

  var topLinks = document.querySelectorAll('.topnav-link, .topnav-create');
  topLinks.forEach(function (link) {
    if (link.dataset.key === activeKey) link.classList.add('is-active');
  });

  // مدیریت دراپ‌داون آواتار با انیمیشن ورود و خروج کاملاً هماهنگ
  var trigger = document.getElementById('profile-menu-trigger');
  var dropdown = document.getElementById('vatan-profile-dropdown');

  function showDropdown() {
    dropdown.style.display = 'block';
    dropdown.classList.remove('animate-out');
    dropdown.classList.add('animate-in');
  }

  function hideDropdown() {
    if (dropdown && dropdown.style.display === 'block' && !dropdown.classList.contains('animate-out')) {
      dropdown.classList.remove('animate-in');
      dropdown.classList.add('animate-out');
      
      // تضمین اینکه استایل پنهان‌سازی حتماً پس از پایان کامل انیمیشن CSS رخ می‌دهد
      setTimeout(function() {
        dropdown.style.display = 'none';
        dropdown.classList.remove('animate-out');
      }, 180);
    }
  }

  if (trigger && dropdown) {
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (dropdown.style.display === 'block' && !dropdown.classList.contains('animate-out')) {
        hideDropdown();
      } else {
        showDropdown();
      }
    });

    document.addEventListener('click', function () {
      hideDropdown();
    });

    dropdown.addEventListener('click', function (e) {
      e.stopPropagation();
    });
  }

  // انیمیشن ردیاب چسبان منوی موبایل (Sliding Thumb)
  var bar   = document.getElementById('vatan-nav-bar');
  var thumb = document.getElementById('vatan-nav-thumb');
  var items = Array.from(document.querySelectorAll('.vatan-nav-item'));

  function getThumbProps(el) {
    if(!bar) return { left: 0, width: 0 };
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

  if (activeKey && bar) {
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
      var href = item.getAttribute('href');
      if(!href || href === '#') return;
      e.preventDefault();
      if (item.classList.contains('is-active')) return;
      setActive(item);
      slideThumb(item);
      setTimeout(function () { window.location.href = href; }, 320);
    });
  });

  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var el = bar ? bar.querySelector('.vatan-nav-item.is-active') : null;
      if (el) snapThumb(el);
    }, 100);
  });

  if (window.ResizeObserver && bar) {
    new ResizeObserver(function () {
      var el = bar.querySelector('.vatan-nav-item.is-active');
      if (el) snapThumb(el);
    }).observe(bar);
  }
}());
</script>