{{-- هدر مشترک موبایل اپ: هوم، اکسپلور، ترندز و پروفایل --}}
<header class="app-mobile-header" aria-label="هدر اپلیکیشن" dir="rtl">
  <a href="{{ url()->current() }}#top" class="app-mobile-brand" aria-label="رفتن به ابتدای همین صفحه">
    <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="28" height="28">
    <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" class="app-mobile-wordmark">
  </a>

  <div class="app-mobile-actions">
      <div class="topnav-token-box app-mobile-token" title="موجودی توکن شما">
        <svg class="topnav-token-icon" viewBox="0 0 24 24" fill="#cffe00" role="img" aria-label="توکن"><path d="M12,3 C12.7,8.3 13.7,9.3 19,10 C13.7,10.7 12.7,11.7 12,17 C11.3,11.7 10.3,10.7 5,10 C10.3,9.3 11.3,8.3 12,3 Z"/><path d="M18.5,2 C18.72,3.6 19.08,3.96 20.7,4.18 C19.08,4.4 18.72,4.76 18.5,6.36 C18.28,4.76 17.92,4.4 16.3,4.18 C17.92,3.96 18.28,3.6 18.5,2 Z"/></svg>
        <span class="topnav-token-number">{{ number_format(auth()->user()->token_balance ?? 0) }}</span>
      </div>

    <a href="{{ route('pricing.index') }}" class="sub-btn app-mobile-sub" aria-label="خرید اشتراک">
      <span><i class="fa-solid fa-crown"></i><span class="sub-label">خرید اشتراک</span></span>
    </a>

    <label class="topnav-popup app-mobile-profile">
      <input type="checkbox" aria-label="منوی کاربری">
      <div tabindex="0" class="topnav-burger" role="button" aria-label="منوی کاربری">
        @if(auth()->check() && auth()->user()->avatar)
          <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="پروفایل" class="topnav-burger-img">
        @else
          <span class="topnav-burger-icon" aria-hidden="true">@include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>19])</span>
        @endif
      </div>

      <nav class="topnav-popup-window app-mobile-profile-menu">
        @auth
          <div class="tp-userinfo">
            <span class="tp-user-name">{{ trim((auth()->user()->name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'کاربر وطن AI' }}</span>
            <span class="tp-user-phone" dir="ltr">{{ auth()->user()->phone ?: '—' }}</span>
          </div>
          <hr>
        @endauth

        <div class="app-mobile-theme" role="radiogroup" aria-label="انتخاب حالت نمایش">
          <button type="button" data-mobile-theme="light" aria-label="روز"><i class="fa-solid fa-sun"></i><span>روز</span></button>
          <button type="button" data-mobile-theme="dark" aria-label="شب"><i class="fa-solid fa-moon"></i><span>شب</span></button>
          <button type="button" data-mobile-theme="system" aria-label="سیستم"><i class="fa-solid fa-desktop"></i><span>سیستم</span></button>
        </div>
        <hr>

        @auth
          <ul>
            <li><button type="button" onclick="window.location.href='{{ route('pricing.index') }}'"><i class="fa-solid fa-gem"></i><span>ارتقای حساب و خرید توکن</span></button></li>
            <li><button type="button" onclick="window.location.href='#'"><i class="fa-solid fa-handshake-angle"></i><span>همکاری در فروش</span></button></li>
            <li><button type="button" onclick="window.location.href='{{ route('app.profile') }}'"><i class="fa-solid fa-image"></i><span>عکس پروفایل</span></button></li>
            <hr>
            <li><button type="button" class="is-danger" onclick="event.preventDefault(); document.getElementById('logout-form-app-mobile').submit();"><i class="fa-solid fa-right-from-bracket"></i><span>خروج</span></button></li>
          </ul>
          <form id="logout-form-app-mobile" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        @else
          <ul>
            <li><button type="button" onclick="window.location.href='{{ route('login') }}'"><i class="fa-solid fa-right-to-bracket"></i><span>ورود و ثبت نام</span></button></li>
            <li><button type="button" onclick="window.location.href='#'"><i class="fa-solid fa-handshake-angle"></i><span>همکاری در فروش</span></button></li>
            <li><button type="button" onclick="window.location.href='{{ route('pricing.index') }}'"><i class="fa-solid fa-coins"></i><span>خرید توکن</span></button></li>
          </ul>
        @endauth
      </nav>
    </label>
  </div>
</header>

<style>
  .app-mobile-header { display:none; }
  @media (max-width:639px) {
    body { padding-top:calc(env(safe-area-inset-top, 0px) + 61px); }
    .app-mobile-header {
      display:flex; position:fixed; inset:0 0 auto; z-index:300;
      min-height:calc(env(safe-area-inset-top, 0px) + 61px);
      padding:calc(env(safe-area-inset-top, 0px) + 8px) 12px 8px;
      align-items:center; justify-content:space-between;
      background:#000; border-bottom:1px solid rgba(255,255,255,.1);
      font-family:'YekanBakh',sans-serif;
    }
    html.light .app-mobile-header { background:#fff; border-bottom-color:rgba(0,0,0,.1); }
    .app-mobile-brand { display:flex; align-items:center; gap:6px; flex:0 0 auto; text-decoration:none; }
    .app-mobile-brand img { display:block; flex-shrink:0; }
    .app-mobile-wordmark { width:65px; height:auto; }
    .app-mobile-actions { display:flex; align-items:center; gap:8px; min-width:0; direction:rtl; }
    .app-mobile-header .app-mobile-token { order:1; min-width:58px; height:36px; padding:0 12px; gap:5px; }
    .app-mobile-header .app-mobile-token .topnav-token-icon { width:20.4px; height:20.4px; transform:translateX(-2px); }
    .app-mobile-header .app-mobile-token .topnav-token-number { font-size:14.4px; }
    .app-mobile-header .app-mobile-sub { order:2; min-width:103.4px; width:auto; height:39.6px; padding:0 9.9px; }
    .app-mobile-header .app-mobile-sub span { font-size:11.55px; gap:4px; }
    .app-mobile-header .app-mobile-sub span i { font-size:13.2px; }
    .app-mobile-profile { order:3; }
    .app-mobile-header .topnav-burger { width:36px; height:36px; }
    .app-mobile-profile-menu { top:46px; left:0; right:auto; min-width:min(270px, calc(100vw - 24px)); transform-origin:top left; }
    .app-mobile-theme { display:grid; grid-template-columns:repeat(3,1fr); gap:5px; padding:4px; }
    .app-mobile-theme button { display:flex; flex-direction:row; align-items:center; justify-content:center; gap:6px; min-height:34px; padding:5px 7px; border:1px solid var(--tp-nav-border); border-radius:9px; background:transparent; color:var(--tp-item-color); font:600 10px 'YekanBakh',sans-serif; cursor:pointer; }
    .app-mobile-theme button i { order:0; font-size:13px; }
    .app-mobile-theme button span { order:1; }
    .app-mobile-theme button.is-active { background:rgba(207,254,0,.14); border-color:#cffe00; color:#cffe00; }
    html.light .app-mobile-theme button.is-active { color:#cffe00; }
  }
  @media (max-width:370px) {
    .app-mobile-header { padding-left:8px; padding-right:8px; }
    .app-mobile-actions { gap:5px; }
    .app-mobile-wordmark { width:55px; }
    .app-mobile-brand { gap:4px; }
    .app-mobile-brand img:first-child { width:25px; height:25px; }
    .app-mobile-header .app-mobile-sub { min-width:83.6px; padding-inline:6.6px; }
    .app-mobile-header .app-mobile-sub .sub-label { font-size:9.9px; }
    .app-mobile-header .app-mobile-token { min-width:54px; padding-inline:7px; }
  }
</style>
