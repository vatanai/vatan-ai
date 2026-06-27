@extends('layouts.app')

@section('content')
<div class="profile-page" dir="rtl" style="position:relative;">

  <div style="position:absolute;top:calc(env(safe-area-inset-top) + 100px);right:16px;display:flex;align-items:center;gap:12px;z-index:50;">
    <button id="menuOpenBtn" type="button" style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:transparent;border:none;cursor:pointer;">
      <img src="{{ asset('assets/img/icons/hamburger.svg') }}" width="26" height="26" class="floating-icon">
    </button>
    <div class="home-logo-wrap">
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" style="width:31px;height:31px;display:block;">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" style="width:77px;height:auto;display:block;">
    </div>
  </div>

  {{-- ===== SECTION: پروفایل ===== --}}
  <section style="padding:calc(env(safe-area-inset-top) + 136px) 16px 8px 16px;">

    {{-- Avatar + Stats: direction:ltr so avatar is physically LEFT --}}
    <div style="display:flex;flex-direction:row;align-items:center;gap:16px;direction:ltr;">

      {{-- Avatar + Name + Phone --}}
      <div style="display:flex;flex-direction:column;align-items:center;gap:8px;flex-shrink:0;">
        <div style="width:99px;height:99px;border-radius:50%;padding:3px;background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);">
          <div style="width:100%;height:100%;border-radius:50%;padding:2px;background:#0c0c10;">
            <img src="https://i.pravatar.cc/150?img=12" alt="avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
          </div>
        </div>
        <div style="text-align:center;">
          <p class="profile-name" style="font-size:14px;font-weight:700;color:#ffffff;margin:0;font-family:'Yekan','Vazirmatn',sans-serif;">محسن آقاجانی</p>
          <p class="profile-phone" style="font-size:12px;color:#a8c4a8;margin:3px 0 0 0;font-family:'Yekan','Vazirmatn',sans-serif;" dir="ltr">۰۹۱۲۰۰۰۰۰۰۰</p>
        </div>
      </div>

      {{-- Stats --}}
      <div style="display:flex;flex:1;justify-content:space-around;align-items:center;direction:rtl;">
        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
          <span class="profile-stat-number" style="font-size:22px;font-weight:700;color:#ffffff;font-family:'Yekan','Vazirmatn',sans-serif;">۳۵</span>
          <span class="profile-stat-label" style="font-size:12px;color:#a8c4a8;font-family:'Yekan','Vazirmatn',sans-serif;">پست</span>
        </div>
        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
          <span class="profile-stat-number" style="font-size:22px;font-weight:700;color:#ffffff;font-family:'Yekan','Vazirmatn',sans-serif;">۱۸۱۰</span>
          <span class="profile-stat-label" style="font-size:12px;color:#a8c4a8;font-family:'Yekan','Vazirmatn',sans-serif;">ساخته‌شده</span>
        </div>
        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
          <span class="profile-stat-number" style="font-size:22px;font-weight:700;color:#ffffff;font-family:'Yekan','Vazirmatn',sans-serif;">۱۴</span>
          <span class="profile-stat-label" style="font-size:12px;color:#a8c4a8;font-family:'Yekan','Vazirmatn',sans-serif;">روز عضویت</span>
        </div>
      </div>

    </div>

  </section>

  {{-- ===== SECTION: باکس‌های اشتراک ===== --}}
  <section style="padding:0 16px;margin-top:12px;">
    <div style="display:flex;align-items:center;gap:8px;direction:rtl;">

      {{-- Settings icon button --}}
      <button type="button" aria-label="تنظیمات" data-card="settings"
        style="width:42px;height:42px;flex-shrink:0;display:flex;align-items:center;justify-content:center;border-radius:10px;border:1px solid #222230;background:#16161c;cursor:pointer;">
        <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="18" height="18" class="floating-icon">
      </button>

      {{-- پشتیبانی --}}
      <button type="button" data-card="support"
        style="flex:1;border-radius:10px;padding:10px 0;border:1px solid #222230;background:#16161c;color:#ffffff;font-size:14px;font-weight:700;font-family:'Yekan','Vazirmatn',sans-serif;cursor:pointer;">
        پشتیبانی
      </button>

      {{-- خرید اشتراک ویژه + badge --}}
      <button type="button" style="flex:1;border-radius:10px;padding:10px 12px;background:#0BBF53;color:#ffffff;font-size:14px;font-weight:700;font-family:'Yekan','Vazirmatn',sans-serif;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;position:relative;">
        <span>خرید اشتراک ویژه</span>
        <span style="background:#e91e8c;color:#ffffff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;font-family:'Yekan','Vazirmatn',sans-serif;white-space:nowrap;">
          ۱۵٪ تخفیف
        </span>
      </button>

    </div>
  </section>

  {{-- ===== SECTION: باکس توضیحات ===== --}}
  <section style="padding:0 16px;margin-top:16px;margin-bottom:8px;">
    <div data-card="affiliate" style="background:#0d2818;border:1px solid #1a5c32;border-radius:10px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;direction:rtl;gap:12px;">

      <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:0;">
        <p style="color:#0BBF53;font-size:14px;font-weight:700;margin:0;font-family:'Yekan','Vazirmatn',sans-serif;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">برنامه ویژه کسب درآمد مستمر</p>
      </div>

      <button style="background:#0BBF53;color:#ffffff;font-size:13px;font-weight:700;padding:8px 16px;border-radius:10px;border:none;white-space:nowrap;font-family:'Yekan','Vazirmatn',sans-serif;cursor:pointer;flex-shrink:0;display:flex;align-items:center;gap:6px;">
        <i class="fa-solid fa-crown" style="color:#ffffff;font-size:20px;"></i>
        همکاری در فروش
      </button>

    </div>
  </section>

  {{-- ===== SECTION: بخش فایل‌ها ===== --}}
  <section style="margin-top:24px;">

    {{-- Tabs --}}
    <div style="display:flex;align-items:center;border-bottom:1px solid #222230;background:transparent;" dir="ltr">
      <button type="button" class="profile-tab active" data-tab="files" aria-label="همه"
        style="flex:1;height:44px;display:flex;align-items:center;justify-content:center;border:none;background:transparent;cursor:pointer;position:relative;">
        <img src="{{ asset('assets/img/icons/fi-sr-grid.svg') }}" width="21" height="21" class="tab-icon">
      </button>
      <button type="button" class="profile-tab" data-tab="docs" aria-label="فایل‌ها"
        style="flex:1;height:44px;display:flex;align-items:center;justify-content:center;border:none;background:transparent;cursor:pointer;position:relative;">
        <img src="{{ asset('assets/img/icons/fi-sr-file.svg') }}" width="22" height="22" class="tab-icon">
      </button>
      <button type="button" class="profile-tab" data-tab="saved" aria-label="مارک‌شده‌ها"
        style="flex:1;height:44px;display:flex;align-items:center;justify-content:center;border:none;background:transparent;cursor:pointer;position:relative;">
        <img src="{{ asset('assets/img/icons/fi-sr-bookmark.svg') }}" width="22" height="22" class="tab-icon">
      </button>
    </div>

    {{-- Grid --}}
    @php
      $profileGridImages = [
        asset('assets/img/9cb93b50-d93f-462f-b6d4-113f63ffc603.avif'),
        asset('assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg'),
        asset('assets/img/ai-beach-girl-1.avif'),
        asset('assets/img/ai-photo-editor-prompt.webp'),
        asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg'),
        asset('assets/img/best-friends-ai-prompt-2.webp'),
        asset('assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg'),
        asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg'),
        asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif'),
        asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp'),
      ];
      $profileVideoCells = [2, 5, 8];
    @endphp

    @foreach (['files','docs','saved'] as $panelIndex => $panel)
      <div class="profile-tab-panel"
        data-panel="{{ $panel }}"
        style="display:{{ $panelIndex === 0 ? 'grid' : 'none' }};grid-template-columns:repeat(3,1fr);gap:2px;width:100%;">
        @foreach ($profileGridImages as $i => $imgUrl)
          <div style="aspect-ratio:4/5;overflow:hidden;position:relative;background:#16161c;">
            <img src="{{ $imgUrl }}" alt="" style="width:100%;height:100%;object-fit:cover;display:block;border-radius:4px;">
            @if(in_array($i + 1, $profileVideoCells))
              <i class="fa-solid fa-video" style="position:absolute;top:6px;right:6px;color:#ffffff;font-size:11px;"></i>
            @endif
          </div>
        @endforeach
      </div>
    @endforeach

  </section>

  {{-- ===== HAMBURGER DROPDOWN MENU ===== --}}
  <div id="menuOverlay" style="display:none;position:fixed;inset:0;z-index:100;" onclick="if(event.target===this){closeMenu();}">
    <div id="menuSheet" style="position:absolute;top:calc(env(safe-area-inset-top) + 136px);right:12px;width:296px;background:#111116;border:1px solid #222230;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:scale(0.9) translateY(-10px);opacity:0;transition:transform 0.2s ease,opacity 0.2s ease;transform-origin:top right;">

      {{-- عکس + اسم + تغییر تم (دقیقا نقطه مقابل پروفایل) --}}
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid #222230;">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:38px;height:38px;border-radius:50%;overflow:hidden;flex-shrink:0;">
            <img src="https://i.pravatar.cc/150?img=12" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div>
            <p style="margin:0;font-size:13px;font-weight:700;color:#ffffff;font-family:'Yekan','Vazirmatn',sans-serif;">محسن آقاجانی</p>
            <p style="margin:2px 0 0 0;font-size:11px;color:#a8c4a8;font-family:'Yekan','Vazirmatn',sans-serif;" dir="ltr">۰۹۱۲۰۰۰۰۰۰۰</p>
          </div>
        </div>
        <button id="theme-toggle" type="button" style="width:32px;height:32px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:transparent;border:none;cursor:pointer;">
          <img src="{{ asset('assets/img/icons/fi-sr-opacity.svg') }}" width="20" height="20" id="theme-icon" class="floating-icon">
        </button>
      </div>

      {{-- تنظیمات --}}
      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;border-bottom:1px solid #222230;" onmouseover="this.style.background='#16161c'" onmouseout="this.style.background='transparent'">
        <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="16" height="16" class="floating-icon">
        <span style="font-size:13px;color:#ffffff;font-family:'Yekan','Vazirmatn',sans-serif;">تنظیمات</span>
      </div>

      {{-- خروج --}}
      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;" onmouseover="this.style.background='#16161c'" onmouseout="this.style.background='transparent'">
        <i class="fa-solid fa-right-from-bracket" style="font-size:14px;color:#f05c5c;width:16px;text-align:center;"></i>
        <span style="font-size:13px;color:#f05c5c;font-family:'Yekan','Vazirmatn',sans-serif;">خروج</span>
      </div>

    </div>
  </div>

</div>
@endsection

@push('styles')
<style>
  @font-face {
    font-family: 'Yekan';
    src: url('/fonts/YekanBakh-Regular.ttf') format('truetype');
    font-weight: 400;
  }
  @font-face {
    font-family: 'Yekan';
    src: url('/fonts/YekanBakh-Bold.ttf') format('truetype');
    font-weight: 700;
  }

  .profile-page {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    background: #000000;
    min-height: 100vh;
    overflow-y: auto;
    overflow-x: hidden;
    font-family: 'Yekan', 'Vazirmatn', sans-serif;
  }

  .home-logo-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .profile-page i[class*="fa-"] {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important;
    -webkit-font-smoothing: antialiased;
    display: inline-block;
  }

  .profile-tab.active {
    position: relative;
  }
  .profile-tab.active::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 2px;
    background: #0BBF53;
  }
  .profile-tab:hover::after {
    display: none;
  }
  .profile-tab:not(.active)::after {
    display: none !important;
  }

  html.light .profile-page {
    background: #ffffff;
  }

  html.light {
    background: #ffffff;
  }

  html.light .profile-stat-number {
    color: #000000 !important;
  }

  html.light .profile-stat-label {
    color: #000000 !important;
  }

  html.light .profile-name {
    color: #000000 !important;
  }

  html.light .profile-phone {
    color: #000000 !important;
  }

  html.light #menuOpenBtn i,
  html.light #theme-toggle i {
    color: #000000 !important;
  }

  html.light .profile-tab i {
    color: #000000 !important;
  }

  html.light .profile-tab.active i {
    color: #000000 !important;
  }

  html.light .profile-tab:not(.active) i {
    color: rgba(0,0,0,0.4) !important;
  }

  html.light [data-card="support"] {
    background: #E5E5E5 !important;
    border-color: #E5E5E5 !important;
    color: #000000 !important;
  }

  html.light [data-card="settings"] {
    background: #E5E5E5 !important;
    border-color: #E5E5E5 !important;
  }

  html.light [data-card="settings"] i {
    color: #000000 !important;
  }

  html.light [data-card="affiliate"] {
    background: #e8f8ee !important;
    border-color: #a8e6be !important;
  }

  html.light [data-card="affiliate"] p {
    color: #0BBF53 !important;
  }

  html.light .profile-tab-panel {
    background: #ffffff;
  }

  html.light [style*="border-bottom:1px solid #222230"] {
    border-bottom-color: #e0e0e0 !important;
  }

  .tab-icon {
    filter: brightness(0) invert(1);
    transition: transform 0.15s ease, opacity 0.15s ease;
  }

  .profile-tab:not(.active) .tab-icon {
    opacity: 1;
  }

  .profile-tab.active .tab-icon {
    opacity: 1;
    transform: scale(1.12);
  }

  .profile-tab:hover .tab-icon {
    transform: scale(1.12);
    opacity: 1;
  }

  html.light .tab-icon {
    filter: brightness(0) invert(0);
  }

  .floating-icon {
    transition: filter 0.2s ease;
    filter: brightness(0) invert(1);
  }

  html.light .floating-icon {
    filter: brightness(0) invert(0);
  }

  html.light #theme-icon {
    filter: brightness(0);
  }

  html.light [data-card="settings"] img,
  html.light #menuOpenBtn img {
    filter: brightness(0);
  }

</style>
@endpush

@push('scripts')
<script>
(function () {

  // Theme toggle
  document.getElementById('theme-toggle').addEventListener('click', function () {
    vatanToggleTheme();
  });

  // Hamburger
  var menuOpenBtn = document.getElementById('menuOpenBtn');
  var menuOverlay = document.getElementById('menuOverlay');
  var menuSheet   = document.getElementById('menuSheet');
  var menuOpen    = false;

  function openMenu() {
    menuOverlay.style.display = 'block';
    setTimeout(function () {
      menuSheet.style.transform = 'scale(1) translateY(0)';
      menuSheet.style.opacity = '1';
    }, 10);
    menuOpen = true;
  }

  function closeMenu() {
    menuSheet.style.transform = 'scale(0.9) translateY(-10px)';
    menuSheet.style.opacity = '0';
    setTimeout(function () { menuOverlay.style.display = 'none'; }, 200);
    menuOpen = false;
  }

  menuOpenBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    menuOpen ? closeMenu() : openMenu();
  });

  document.addEventListener('click', function (e) {
    if (menuOpen && !menuSheet.contains(e.target)) {
      closeMenu();
    }
  });

  // Tabs
  var tabs   = document.querySelectorAll('.profile-tab');
  var panels = document.querySelectorAll('.profile-tab-panel');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');

      tabs.forEach(function (t) {
        t.classList.remove('active');
      });

      tab.classList.add('active');

      panels.forEach(function (panel) {
        panel.style.display = panel.getAttribute('data-panel') === target ? 'grid' : 'none';
      });
    });
  });

})();
</script>
@endpush
