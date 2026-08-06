@php $referralProfileMenuEnabled = \App\Models\ReferralSetting::current()->profile_enabled; @endphp
@if(request()->routeIs('app.home', 'app.explore', 'app.trends', 'app.profile', 'profile'))
  @include('app.partials.mobile-header')
@endif

{{-- ══════════════════════════════════════════
     TOP NAV — تبلت و دسکتاپ (+640px)
══════════════════════════════════════════ --}}
<nav id="vatan-topnav" aria-label="منوی بالای صفحه" dir="rtl" class="hidden sm:block fixed top-0 left-0 right-0 z-[300] backdrop-blur-[20px] backdrop-saturate-[180%] border-b shadow-lg [.light_&]:shadow-sm" style="background:var(--vatan-header-bg);border-color:var(--vatan-header-border);">
  <div id="vatan-topnav-inner" class="max-w-[1280px] mx-auto px-8 h-16 flex items-center justify-between gap-6">

    {{-- لوگو — سمت راست --}}
    <a href="{{ route('app.home') }}" class="flex items-center gap-2 no-underline shrink-0" aria-label="رفتن به خانه اپ">
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="31" height="31" class="shrink-0">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" style="height:29px; width:auto;" class="shrink-0">
    </a>

    {{-- لینک‌های ناوبری — وسط (دکمه «بساز» بین اکسپلور و ترندز قرار دارد) --}}
    <div class="topnav-links flex items-center gap-2">
      @php
        $navItemsBefore = [
          ['route' => 'app.home', 'key' => 'home', 'label' => 'خانه'],
          ['route' => 'app.explore', 'key' => 'explore', 'label' => 'اکسپلور'],
        ];
        $navItemsAfter = [
          ['route' => 'app.trends', 'key' => 'trends', 'label' => 'ترندز'],
        ];
      @endphp

      @foreach($navItemsBefore as $item)
        <a href="{{ route($item['route']) }}" 
           class="topnav-link text-[14px] font-medium text-[#a2abb7] no-underline px-3.5 py-1.5 rounded-[12px] transition-all duration-200 whitespace-nowrap hover:text-white [.light_&]:hover:text-white hover:bg-[#161616] [.light_&]:hover:bg-[#161616] [&.is-active]:text-[#cffe00] [.light_&][&.is-active]:text-white [&.is-active]:font-bold [&.is-active]:bg-[#1d2209] [.light_&][&.is-active]:bg-[#1d2209]"
           data-key="{{ $item['key'] }}">

          <span class="topnav-link-icon">@include('partials.nav-svg',['key'=>$item['key'],'state'=>'off','size'=>17,'class'=>'ni-off'])@include('partials.nav-svg',['key'=>$item['key'],'state'=>'on','size'=>17,'class'=>'ni-on'])</span>
          <span>{{ $item['label'] }}</span>
        </a>
      @endforeach

      {{-- دکمه بساز — حالت عادی فقط + ، روی هاور از چپ و راست باز می‌شود و «بساز» نمایان می‌شود --}}
      <a href="{{ route('app.create') }}" class="topnav-create no-underline whitespace-nowrap" data-key="create" aria-label="بساز">
        <span class="topnav-create-sign" aria-hidden="true">+</span>
        <span class="topnav-create-text">بساز</span>
      </a>

      @foreach($navItemsAfter as $item)
        <a href="{{ route($item['route']) }}" 
           class="topnav-link text-[14px] font-medium text-[#a2abb7] no-underline px-3.5 py-1.5 rounded-[12px] transition-all duration-200 whitespace-nowrap hover:text-white [.light_&]:hover:text-white hover:bg-[#161616] [.light_&]:hover:bg-[#161616] [&.is-active]:text-[#cffe00] [.light_&][&.is-active]:text-white [&.is-active]:font-bold [&.is-active]:bg-[#1d2209] [.light_&][&.is-active]:bg-[#1d2209]"
           data-key="{{ $item['key'] }}">

          <span class="topnav-link-icon">@include('partials.nav-svg',['key'=>$item['key'],'state'=>'off','size'=>17,'class'=>'ni-off'])@include('partials.nav-svg',['key'=>$item['key'],'state'=>'on','size'=>17,'class'=>'ni-on'])</span>
          <span>{{ $item['label'] }}</span>
        </a>
      @endforeach

      {{-- پروفایل — همیشه نمایش داده می‌شود (مهمان و کاربر لاگین‌کرده) و همیشه به صفحه پروفایل می‌رود، نه لاگین --}}
      <a href="{{ route('app.profile') }}"
         class="topnav-link text-[14px] font-medium text-[#a2abb7] no-underline px-3.5 py-1.5 rounded-[12px] transition-all duration-200 whitespace-nowrap hover:text-white [.light_&]:hover:text-white hover:bg-[#161616] [.light_&]:hover:bg-[#161616] [&.is-active]:text-[#cffe00] [.light_&][&.is-active]:text-white [&.is-active]:font-bold [&.is-active]:bg-[#1d2209] [.light_&][&.is-active]:bg-[#1d2209]"
         data-key="profile">

        <span class="topnav-link-icon">@include('partials.nav-svg',['key'=>'profile','state'=>'off','size'=>17,'class'=>'ni-off'])@include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>17,'class'=>'ni-on'])</span>
        <span>پروفایل</span>
      </a>
    </div>

    {{-- بخش اکشن‌ها و وضعیت احراز هویت — سمت چپ --}}
    <div class="topnav-left-side flex items-center gap-3 shrink-0">
      {{-- باکس نمایش موجودی توکن — سمت چپ دکمه «بساز»، رنگ ست با تم روز/شب --}}
        <div class="topnav-token-box order-2" title="موجودی توکن شما">
          <span class="topnav-token-icon" role="img" aria-label="توکن"></span>
          <span class="topnav-token-number">{{ number_format(auth()->user()->token_balance ?? 0) }}</span>
        </div>

      {{-- دکمه تغییر تم (روز / شب / سیستم) --}}

      <div class="topnav-theme-wrap relative order-1">
        <button type="button" id="nav-theme-toggle" class="topnav-theme-btn w-[32.3px] h-[32.3px] flex items-center justify-center shrink-0 rounded-[15.6px] border border-white/15 bg-white/5 text-white transition-all duration-200 hover:bg-white/10 cursor-pointer" aria-label="تغییر تم" aria-expanded="false">
          <svg data-icon="moon" class="theme-trigger-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
          <svg data-icon="sun" class="theme-trigger-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.5"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/><line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/></svg>
          <svg data-icon="system" class="theme-trigger-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="13" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </button>

        <div id="theme-menu" class="theme-menu" role="menu" aria-label="انتخاب حالت نمایش">
          <button type="button" class="theme-menu-item" data-theme-choice="light" role="menuitemradio">
            <span class="theme-menu-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.5"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/><line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/></svg>
            </span>
            <span>روز</span>
            <svg class="theme-menu-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </button>
          <button type="button" class="theme-menu-item" data-theme-choice="dark" role="menuitemradio">
            <span class="theme-menu-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </span>
            <span>شب</span>
            <svg class="theme-menu-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </button>
          <button type="button" class="theme-menu-item" data-theme-choice="system" role="menuitemradio">
            <span class="theme-menu-icon">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="13" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </span>
            <span>سیستم</span>
            <svg class="theme-menu-check" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </button>
        </div>
      </div>

      {{-- باکس خرید اشتراک — قبل خرید «خرید اشتراک»، بعد خرید سطح اشتراک --}}
      @php
        $subLabel = auth()->check() ? (auth()->user()->plan_name ?? 'خرید اشتراک') : 'خرید اشتراک';
      @endphp
      <a href="{{ route('pricing.index') }}" class="sub-btn order-3 shrink-0" aria-label="خرید اشتراک">
        <span><i class="fa-solid fa-crown"></i><span class="sub-label">{{ $subLabel }}</span></span>
      </a>

      {{-- آیکون پروفایل + منوی کشویی (Popup) — سمت چپ --}}
      <label class="topnav-popup order-4" id="profile-popup">
        <input type="checkbox" aria-label="منوی کاربری" />
        <div tabindex="0" class="topnav-burger" role="button" aria-label="منوی کاربری">
          @auth
            @if(auth()->user()->avatar)
              <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="پروفایل" class="topnav-burger-img">
            @else
              {{-- کاربر هنوز عکس پروفایل انتخاب نکرده — به‌جای عکس، آیکون پیش‌فرض نمایش داده می‌شود --}}
              <span class="topnav-burger-icon" aria-hidden="true">@include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>19])</span>
            @endif
          @else
            {{-- مهمان (وارد نشده) — همان آیکون پیش‌فرض --}}
            <span class="topnav-burger-icon" aria-hidden="true">@include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>19])</span>
          @endauth
        </div>

        <nav class="topnav-popup-window">
          @auth
            {{-- نام و نام خانوادگی + شماره موبایل --}}
            <div class="tp-userinfo">
              <span class="tp-user-name">{{ trim((auth()->user()->name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'کاربر وطن AI' }}</span>
              <span class="tp-user-phone" dir="ltr">{{ auth()->user()->phone ?: '—' }}</span>
            </div>
            <hr>
            <ul>
              <li><button type="button" onclick="window.location.href='{{ route('pricing.index') }}'"><i class="fa-solid fa-gem"></i><span>ارتقای حساب و خرید توکن</span></button></li>
              @if($referralProfileMenuEnabled)<li><button type="button" onclick="window.location.href='{{ route('app.profile', ['tab' => 'referral']) }}#referral-program'"><i class="fa-solid fa-handshake-angle"></i><span>همکاری در فروش</span></button></li>@endif
              <li><button type="button" onclick="window.location.href='{{ route('app.profile') }}'"><i class="fa-solid fa-image"></i><span>عکس پروفایل</span></button></li>
              <hr>
              <li><button type="button" class="is-danger" onclick="window.logoutFromCurrentPage(this)"><i class="fa-solid fa-right-from-bracket"></i><span>خروج</span></button></li>
            </ul>
          @else
            <ul>
              <li><button type="button" onclick="window.location.href='{{ route('login', ['redirect' => request()->fullUrl()]) }}'"><i class="fa-solid fa-right-to-bracket"></i><span>ورود و ثبت نام</span></button></li>
              @if($referralProfileMenuEnabled)<li><button type="button" onclick="window.location.href='{{ route('app.profile', ['tab' => 'referral']) }}#referral-program'"><i class="fa-solid fa-handshake-angle"></i><span>همکاری در فروش</span></button></li>@endif
              <li><button type="button" onclick="window.location.href='{{ route('pricing.index') }}'"><i class="fa-solid fa-coins"></i><span>خرید توکن</span></button></li>
            </ul>
          @endauth
        </nav>
      </label>
    </div>

  </div>
</nav>

{{-- ══════════════════════════════════════════
     BOTTOM NAV — فقط موبایل (< 640px)
══════════════════════════════════════════ --}}
<nav id="vatan-nav" role="navigation" aria-label="منوی اصلی" class="sm:hidden fixed bottom-0 left-0 right-0 z-[200] pb-[calc(env(safe-area-inset-bottom,0px)+28px)] px-4 pointer-events-none max-w-[480px] mx-auto">
  <div id="vatan-nav-bar" class="flex items-center h-[70px] bg-[#111116]/82 [.light_&]:bg-white/88 rounded-full border border-white/15 [.light_&]:border-black/10 p-0 relative pointer-events-auto backdrop-blur-[20px] backdrop-saturate-[180%] w-full box-border shadow-2xl transition-all duration-300">

    <div id="vatan-nav-thumb" aria-hidden="true" class="absolute top-1.5 bottom-1.5 left-0 w-0 rounded-full bg-[#cffe00] z-0 pointer-events-none invisible"></div>

    {{-- پروفایل — همیشه نمایش داده می‌شود (مهمان و کاربر لاگین‌کرده) و همیشه به صفحه پروفایل می‌رود، نه لاگین.
         فقط با کلیک صریح روی «ورود و ثبت‌نام» (داخل خود صفحه پروفایل) کاربر به لاگین هدایت می‌شود. --}}
    <a href="{{ route('app.profile') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="profile" aria-label="پروفایل">
      @if(auth()->check() && auth()->user()->avatar)
        <img src="{{ asset('storage/' . auth()->user()->avatar) }}" class="vatan-nav-avatar w-6 h-6 rounded-full object-cover border-[1.5px] border-white [.light_&]:border-black/20 transition-all duration-200 group-[.is-active]:scale-110" alt="پروفایل">
      @else
        <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-18">
          @include('partials.nav-svg',['key'=>'profile','state'=>'off','size'=>18,'class'=>'vatan-nav-icon-off text-white [.light_&]:text-black'])
          @include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>18,'class'=>'vatan-nav-icon-on text-white [.light_&]:text-black'])
        </span>
      @endif
    </a>

    <a href="{{ route('app.explore') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="explore" aria-label="اکسپلور">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-21">
        @include('partials.nav-svg',['key'=>'explore','state'=>'off','size'=>21,'class'=>'vatan-nav-icon-off text-white/60 [.light_&]:text-black/60'])
        @include('partials.nav-svg',['key'=>'explore','state'=>'on','size'=>21,'class'=>'vatan-nav-icon-on text-white [.light_&]:text-black'])
      </span>
    </a>

    <a href="{{ route('app.create') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="create" aria-label="بساز">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-25">
        @include('partials.nav-svg',['key'=>'create','state'=>'off','size'=>25,'class'=>'vatan-nav-icon-off text-white [.light_&]:text-black'])
        @include('partials.nav-svg',['key'=>'create','state'=>'on','size'=>25,'class'=>'vatan-nav-icon-on text-white [.light_&]:text-black'])
      </span>
    </a>

    <a href="{{ route('app.trends') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="trends" aria-label="ترندز">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-22">
        @include('partials.nav-svg',['key'=>'trends','state'=>'off','size'=>22,'class'=>'vatan-nav-icon-off text-white [.light_&]:text-black'])
        @include('partials.nav-svg',['key'=>'trends','state'=>'on','size'=>22,'class'=>'vatan-nav-icon-on text-white [.light_&]:text-black'])
      </span>
    </a>

    <a href="{{ route('app.home') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="home" aria-label="خانه">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-21">
        @include('partials.nav-svg',['key'=>'home','state'=>'off','size'=>21,'class'=>'vatan-nav-icon-off text-white [.light_&]:text-black'])
        @include('partials.nav-svg',['key'=>'home','state'=>'on','size'=>21,'class'=>'vatan-nav-icon-on text-white [.light_&]:text-black'])
      </span>
    </a>

  </div>
</nav>

@include('layouts.partials.nav-styles')
@include('layouts.partials.nav-scripts')
