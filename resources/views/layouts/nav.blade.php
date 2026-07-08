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

      {{-- دکمه تغییر تم (روز / شب / سیستم) --}}
      <div class="topnav-theme-wrap relative">
        <button type="button" id="nav-theme-toggle" class="topnav-theme-btn w-9 h-9 flex items-center justify-center shrink-0 rounded-[10px] border border-white/15 [.light_&]:border-black/10 bg-white/5 [.light_&]:bg-black/5 text-white [.light_&]:text-black transition-all duration-200 hover:bg-white/10 [.light_&]:hover:bg-black/10 cursor-pointer" aria-label="تغییر تم" aria-expanded="false">
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

    @auth
      <a href="{{ route('app.profile') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="profile" aria-label="پروفایل">
        @if(auth()->user()->avatar)
          <img src="{{ asset('storage/' . auth()->user()->avatar) }}" class="vatan-nav-avatar w-6 h-6 rounded-full object-cover border-[1.5px] border-white [.light_&]:border-black/20 transition-all duration-200 group-[.is-active]:scale-110" alt="پروفایل">
        @else
          <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-18">
            <svg class="vatan-nav-icon-off text-white [.light_&]:text-black" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M9.75 2C7.12665 2 5 4.12665 5 6.75C5 9.37335 7.12665 11.5 9.75 11.5C12.3734 11.5 14.5 9.37335 14.5 6.75C14.5 4.12665 12.3734 2 9.75 2ZM6.5 6.75C6.5 4.95507 7.95507 3.5 9.75 3.5C11.5449 3.5 13 4.95507 13 6.75C13 8.54493 11.5449 10 9.75 10C7.95507 10 6.5 8.54493 6.5 6.75Z" fill="currentColor"/>
              <path fill-rule="evenodd" clip-rule="evenodd" d="M14.2135 0.123728C13.0714 -1.45286e-05 11.6318 -8.0335e-06 9.79525 1.32337e-07H9.70475C7.86821 -8.0335e-06 6.42861 -1.45286e-05 5.28648 0.123728C4.12094 0.250006 3.17656 0.512324 2.37024 1.09815C1.88209 1.45281 1.45281 1.88209 1.09815 2.37024C0.512324 3.17656 0.250006 4.12094 0.123728 5.28648C-1.45286e-05 6.42861 -8.0335e-06 7.86821 1.32337e-07 9.70475V9.79525C-8.0335e-06 11.6318 -1.45286e-05 13.0714 0.123728 14.2135C0.250006 15.3791 0.512324 16.3234 1.09815 17.1298C1.43667 17.5957 1.84317 18.008 2.30396 18.353C2.32592 18.3694 2.34801 18.3857 2.37024 18.4018C3.17656 18.9877 4.12094 19.25 5.28648 19.3763C6.42859 19.5 7.86817 19.5 9.70465 19.5H9.79527C11.6318 19.5 13.0714 19.5 14.2135 19.3763C15.3791 19.25 16.3234 18.9877 17.1298 18.4018C17.1327 18.3997 17.1357 18.3976 17.1386 18.3954C17.1592 18.3804 17.1796 18.3653 17.2 18.35C17.6592 18.0056 18.0643 17.5944 18.4018 17.1298C18.9877 16.3234 19.25 15.3791 19.3763 14.2135C19.5 13.0714 19.5 11.6318 19.5 9.79535V9.70473C19.5 7.86824 19.5 6.42859 19.3763 5.28648C19.25 4.12094 18.9877 3.17656 18.4018 2.37024C18.0472 1.88209 17.6179 1.45281 17.1298 1.09815C16.3234 0.512324 15.3791 0.250006 14.2135 0.123728ZM3.25191 2.31168C3.75992 1.94259 4.41013 1.72745 5.44804 1.615C6.49999 1.50103 7.85843 1.5 9.75 1.5C11.6416 1.5 13 1.50103 14.052 1.615C15.0899 1.72745 15.7401 1.94259 16.2481 2.31168C16.6089 2.57382 16.9262 2.89111 17.1883 3.25191C17.5574 3.75992 17.7725 4.41013 17.885 5.44804C17.999 6.49999 18 7.85843 18 9.75C18 11.6416 17.999 13 17.885 14.052C17.7774 15.0454 17.5757 15.6836 17.235 16.1819C16.5871 14.3289 14.824 13 12.75 13H6.75C4.67609 13 2.91282 14.3289 2.26502 16.1819C1.92432 15.6836 1.72263 15.0453 1.615 14.052C1.50103 13 1.5 11.6416 1.5 9.75C1.5 7.85843 1.50103 6.49999 1.615 5.44804C1.72745 4.41013 1.94259 3.75992 2.31168 3.25191C2.57382 2.89111 2.89111 2.57382 3.25191 2.31168ZM12.75 14.5C14.4137 14.5 15.786 15.7506 15.9772 17.3625C15.5093 17.6272 14.9145 17.7915 14.052 17.885C13 17.999 11.6416 18 9.75 18C7.85843 18 6.49999 17.999 5.44804 17.885C4.58553 17.7916 3.99074 17.6272 3.52282 17.3625C3.71416 15.7505 5.08622 14.5 6.75 14.5H12.75Z" fill="currentColor"/>
            </svg>
            <svg class="vatan-nav-icon-on text-white [.light_&]:text-black" width="18" height="18" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M9.79525 1.32337e-07C11.6318 -8.0335e-06 13.0714 -1.45286e-05 14.2135 0.123728C15.3791 0.250006 16.3234 0.512324 17.1298 1.09815C17.6179 1.45281 18.0472 1.88209 18.4018 2.37024C18.9877 3.17656 19.25 4.12094 19.3763 5.28648C19.5 6.42859 19.5 7.86816 19.5 9.70465V9.79527C19.5 11.6318 19.5 13.0714 19.3763 14.2135C19.25 15.3791 18.9877 16.3234 18.4018 17.1298C18.0643 17.5944 17.6592 18.0056 17.2 18.35C17.1902 18.3574 17.1803 18.3647 17.1704 18.3721C17.1598 18.3799 17.1492 18.3877 17.1386 18.3954L17.1298 18.4018C16.3234 18.9877 15.3791 19.25 14.2135 19.3763C13.0714 19.5 11.6318 19.5 9.79535 19.5H9.70473C7.86824 19.5 6.42859 19.5 5.28648 19.3763C4.12094 19.25 3.17656 18.9877 2.37024 18.4018C2.34801 18.3857 2.32592 18.3694 2.30396 18.353C1.84317 18.008 1.43667 17.5957 1.09815 17.1298C0.512324 16.3234 0.250006 15.3791 0.123728 14.2135C-1.45286e-05 13.0714 -8.0335e-06 11.6318 1.32337e-07 9.79525V9.70475C-8.0335e-06 7.86821 -1.45286e-05 6.42861 0.123728 5.28648C0.250006 4.12094 0.512324 3.17656 1.09815 2.37024C1.45281 1.88209 1.88209 1.45281 2.37024 1.09815C3.17656 0.512324 4.12094 0.250006 5.28648 0.123728C6.42861 -1.45286e-05 7.86821 -8.0335e-06 9.70475 1.32337e-07H9.79525ZM16.6499 16.8569C16.2442 15.0777 14.6521 13.75 12.75 13.75H6.75C4.84786 13.75 3.25583 15.0777 2.85008 16.8569C2.96214 16.9615 3.07992 17.0601 3.20293 17.1522C3.21918 17.1644 3.2355 17.1764 3.25191 17.1883C3.75992 17.5574 4.41013 17.7725 5.44804 17.885C6.49999 17.999 7.85843 18 9.75 18C11.6416 18 13 17.999 14.052 17.885C15.0899 17.7725 15.7401 17.5574 16.2481 17.1883C16.2655 17.1757 16.2828 17.1629 16.3 17.15C16.422 17.0585 16.5387 16.9607 16.6499 16.8569ZM9.75 2C7.12665 2 5 4.12665 5 6.75C5 9.37335 7.12665 11.5 9.75 11.5C12.3734 11.5 14.5 9.37335 14.5 6.75C14.5 4.12665 12.3734 2 9.75 2Z" fill="currentColor"/>
            </svg>
          </span>
        @endif
      </a>
    @endauth

    @guest
      <a href="{{ route('login') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="profile" aria-label="ورود به حساب">
        <i class="fa-solid fa-right-to-bracket vatan-nav-icon text-[19px] text-white [.light_&]:text-black transition-all duration-300 group-[.is-active]:scale-110 group-[.is-active]:text-white"></i>
      </a>
    @endguest

    <a href="{{ route('app.explore') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="explore" aria-label="اکسپلور">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-21">
        <svg class="vatan-nav-icon-off text-white/60 [.light_&]:text-black/60" width="21" height="21" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M21 21L16.65 16.65M11 6C13.7614 6 16 8.23858 16 11M19 11C19 15.4183 15.4183 19 11 19C6.58172 19 3 15.4183 3 11C3 6.58172 6.58172 3 11 3C15.4183 3 19 6.58172 19 11Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg class="vatan-nav-icon-on text-white [.light_&]:text-black" width="21" height="21" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M8.75 0C13.5825 0 17.5 3.91751 17.5 8.75C17.5 10.8962 16.7257 12.8606 15.4434 14.3828L19.2803 18.2197C19.5732 18.5126 19.5732 18.9874 19.2803 19.2803C18.9874 19.5732 18.5126 19.5732 18.2197 19.2803L14.3828 15.4434C12.8606 16.7257 10.8962 17.5 8.75 17.5C3.91751 17.5 0 13.5825 0 8.75C0 3.91751 3.91751 0 8.75 0ZM9.82422 3.00391C9.62642 2.98423 9.42924 3.04405 9.27539 3.16992C9.12144 3.29588 9.0237 3.47786 9.00391 3.67578C8.98423 3.87358 9.04405 4.07076 9.16992 4.22461C9.29588 4.37856 9.47786 4.4763 9.67578 4.49609C9.79543 4.50808 9.91029 4.52285 10.0244 4.54004C12.1247 4.78105 13.7911 6.40949 13.9834 8.4375C13.9939 8.54233 14.0001 8.65145 14 8.75C14 8.94891 14.0791 9.13962 14.2197 9.28027C14.3604 9.42093 14.5511 9.5 14.75 9.5C14.9489 9.5 15.1396 9.42093 15.2803 9.28027C15.4209 9.13962 15.5 8.94891 15.5 8.75C15.5001 8.595 15.4914 8.43626 15.4766 8.28809C15.2699 5.58156 12.828 3.31027 10.248 3.05664C10.1084 3.0356 9.96839 3.01834 9.82422 3.00391Z" fill="currentColor"/>
        </svg>
      </span>
    </a>

    <a href="{{ route('app.create') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="create" aria-label="بساز">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-25">
        <svg class="vatan-nav-icon-off text-white [.light_&]:text-black" width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6 12H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M12 18V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg class="vatan-nav-icon-on text-white [.light_&]:text-black" width="25" height="25" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81V16.18C2 19.83 4.17 22 7.81 22H16.18C19.82 22 21.99 19.83 21.99 16.19V7.81C22 4.17 19.83 2 16.19 2ZM18 12.75H12.75V18C12.75 18.41 12.41 18.75 12 18.75C11.59 18.75 11.25 18.41 11.25 18V12.75H6C5.59 12.75 5.25 12.41 5.25 12C5.25 11.59 5.59 11.25 6 11.25H11.25V6C11.25 5.59 11.59 5.25 12 5.25C12.41 5.25 12.75 5.59 12.75 6V11.25H18C18.41 11.25 18.75 11.59 18.75 12C18.75 12.41 18.41 12.75 18 12.75Z" fill="currentColor"/>
        </svg>
      </span>
    </a>

    <a href="{{ route('app.trends') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="trends" aria-label="ترندز">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-22">
        <svg class="vatan-nav-icon-off text-white [.light_&]:text-black" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M16.5 9.5L12.3 13.7L10.7 11.3L7.5 14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M14.5 9.5H16.5V11.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg class="vatan-nav-icon-on text-white [.light_&]:text-black" width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M16.19 2H7.81C4.17 2 2 4.17 2 7.81V16.18C2 19.83 4.17 22 7.81 22H16.18C19.82 22 21.99 19.83 21.99 16.19V7.81C22 4.17 19.83 2 16.19 2ZM16.88 11.53C16.88 11.92 16.57 12.23 16.18 12.23C15.79 12.23 15.48 11.92 15.48 11.53V11.35L12.76 14.07C12.61 14.22 12.41 14.29 12.2 14.27C11.99 14.25 11.8 14.14 11.69 13.96L10.67 12.44L8.29 14.82C8.15 14.96 7.98 15.02 7.8 15.02C7.62 15.02 7.44 14.95 7.31 14.82C7.04 14.55 7.04 14.11 7.31 13.83L10.29 10.85C10.44 10.7 10.64 10.63 10.85 10.65C11.06 10.67 11.25 10.78 11.36 10.96L12.38 12.48L14.49 10.37H14.31C13.92 10.37 13.61 10.06 13.61 9.67C13.61 9.28 13.92 8.97 14.31 8.97H16.17C16.26 8.97 16.35 8.99 16.44 9.02C16.61 9.09 16.75 9.23 16.82 9.4C16.86 9.49 16.87 9.58 16.87 9.67V11.53H16.88Z" fill="currentColor"/>
        </svg>
      </span>
    </a>

    <a href="{{ route('app.home') }}" class="vatan-nav-item group flex-1 flex items-center justify-center h-full no-underline relative z-1 select-none [-webkit-tap-highlight-color:transparent]" data-key="home" aria-label="خانه">
      <span class="vatan-nav-icon-wrap vatan-nav-icon-wrap-21">
        <svg class="vatan-nav-icon-off text-white [.light_&]:text-black" width="21" height="21" viewBox="0 0 20 21" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M6.49278 2.67493C7.64139 1.61377 8.21569 1.08319 8.86823 0.882521C9.44281 0.705826 10.0572 0.705826 10.6318 0.882521C11.2844 1.08319 11.8587 1.61377 13.0073 2.67493L16.693 6.08007C17.4509 6.78024 17.8298 7.13033 18.1014 7.54717C18.3421 7.91664 18.5201 8.32337 18.6281 8.75091C18.75 9.23326 18.75 9.74916 18.75 10.781V14.6804C18.75 16.3606 18.75 17.2006 18.423 17.8424C18.1354 18.4069 17.6765 18.8658 17.112 19.1534C16.4703 19.4804 15.6302 19.4804 13.95 19.4804H5.55002C3.86987 19.4804 3.02979 19.4804 2.38805 19.1534C1.82357 18.8658 1.36462 18.4069 1.077 17.8424C0.750023 17.2006 0.750023 16.3606 0.750023 14.6804L0.750023 10.781C0.750023 9.74916 0.750023 9.23326 0.871897 8.75091C0.979921 8.32337 1.1579 7.91664 1.39863 7.54717C1.67022 7.13033 2.04916 6.78024 2.80703 6.08007L6.49278 2.67493Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M6.75002 14.4804C6.75002 13.5485 6.75002 13.0826 6.90226 12.715C7.10525 12.225 7.4946 11.8356 7.98466 11.6326C8.3522 11.4804 8.81814 11.4804 9.75002 11.4804C10.6819 11.4804 11.1478 11.4804 11.5154 11.6326C12.0054 11.8356 12.3948 12.225 12.5978 12.715C12.75 13.0826 12.75 13.5485 12.75 14.4804V19.4804H6.75002V14.4804Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <svg class="vatan-nav-icon-on text-white [.light_&]:text-black" width="21" height="21" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M15.943 5.33007L12.2572 1.92493C11.1086 0.863767 10.5343 0.333186 9.8818 0.132521C9.30721 -0.0441742 8.69279 -0.0441742 8.1182 0.132521C7.46567 0.333186 6.89136 0.863767 5.74275 1.92493L2.057 5.33007C1.29913 6.03024 0.920198 6.38033 0.648604 6.79717C0.407875 7.16664 0.229898 7.57337 0.121874 8.00091C0 8.48326 0 8.99916 0 10.031V16.3304C0 17.1705 0 17.5905 0.16349 17.9114C0.3073 18.1936 0.536771 18.4231 0.819014 18.5669C1.13988 18.7304 1.55992 18.7304 2.4 18.7304H3.6C4.44008 18.7304 4.86012 18.7304 5.18099 18.5669C5.46323 18.4231 5.6927 18.1936 5.83651 17.9114C6 17.5905 6 17.1705 6 16.3304V13.1304C6 12.2903 6 11.8703 6.16349 11.5494C6.3073 11.2672 6.53677 11.0377 6.81901 10.8939C7.13988 10.7304 7.55992 10.7304 8.4 10.7304H9.6C10.4401 10.7304 10.8601 10.7304 11.181 10.8939C11.4632 11.0377 11.6927 11.2672 11.8365 11.5494C12 11.8703 12 12.2903 12 13.1304V16.3304C12 17.1705 12 17.5905 12.1635 17.9114C12.3073 18.1936 12.5368 18.4231 12.819 18.5669C13.1399 18.7304 13.5599 18.7304 14.4 18.7304H15.6C16.4401 18.7304 16.8601 18.7304 17.181 18.5669C17.4632 18.4231 17.6927 18.1936 17.8365 17.9114C18 17.5905 18 17.1705 18 16.3304V10.031C18 8.99916 18 8.48326 17.8781 8.00091C17.7701 7.57337 17.5921 7.16664 17.3514 6.79717C17.0798 6.38033 16.7009 6.03024 15.943 5.33007Z" fill="currentColor"/>
        </svg>
      </span>
    </a>

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

  /* آیکون‌های دو حالته منوی پایین موبایل (خاموش/روشن) */
  .vatan-nav-icon-wrap {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
  }
  .vatan-nav-icon-wrap-21 {
    width: 21px;
    height: 21px;
  }
  .vatan-nav-icon-wrap-22 {
    width: 22px;
    height: 22px;
  }
  .vatan-nav-icon-wrap-25 {
    width: 25px;
    height: 25px;
  }
  .vatan-nav-icon-wrap-18 {
    width: 18px;
    height: 18px;
  }
  .vatan-nav-icon-off,
  .vatan-nav-icon-on {
    position: absolute;
    inset: 0;
    margin: auto;
    transition: opacity 0.3s ease, transform 0.3s ease;
  }
  .vatan-nav-icon-off {
    opacity: 1;
    transform: scale(1);
  }
  .vatan-nav-icon-on {
    opacity: 0;
    transform: scale(1);
  }
  .vatan-nav-item.is-active .vatan-nav-icon-off {
    opacity: 0;
    transform: scale(0.85);
  }
  .vatan-nav-item.is-active .vatan-nav-icon-on {
    opacity: 1;
    transform: scale(1.1);
  }
  /* پیش‌نمایش حالت کلفت (trend-2) روی هاور آیکون ترندز */
  .vatan-nav-item[data-key="trends"]:hover .vatan-nav-icon-off {
    opacity: 0;
    transform: scale(0.85);
  }
  .vatan-nav-item[data-key="trends"]:hover .vatan-nav-icon-on {
    opacity: 1;
    transform: scale(1.1);
  }
  .vatan-nav-avatar {
    border-width: 1.5px;
    border-style: solid;
    transition: transform 0.2s ease;
  }

  /* ── دکمه/منوی تغییر تم (روز/شب/سیستم) ── */
  .theme-trigger-icon {
    display: none;
    position: absolute;
    inset: 0;
    margin: auto;
  }
  .theme-trigger-icon.is-shown {
    display: block;
  }

  .theme-menu {
    display: none;
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    min-width: 148px;
    padding: 6px;
    border-radius: 12px;
    background: #16161c;
    border: 1px solid rgba(255,255,255,.12);
    box-shadow: 0 10px 30px rgba(0,0,0,.35);
    z-index: 320;
  }
  html.light .theme-menu {
    background: #ffffff;
    border-color: rgba(0,0,0,.1);
    box-shadow: 0 10px 30px rgba(0,0,0,.12);
  }

  .theme-menu-item {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 8px 10px;
    border: none;
    background: transparent;
    border-radius: 8px;
    color: rgba(255,255,255,.75);
    font-size: 13px;
    font-family: inherit;
    cursor: pointer;
    transition: background-color .15s ease, color .15s ease;
    text-align: right;
  }
  html.light .theme-menu-item { color: rgba(0,0,0,.65); }

  .theme-menu-item:hover {
    background: rgba(255,255,255,.08);
    color: #fff;
  }
  html.light .theme-menu-item:hover {
    background: rgba(0,0,0,.05);
    color: #000;
  }

  .theme-menu-item.is-active {
    background: rgba(11,191,83,.15);
    color: #0BBF53;
    font-weight: 700;
  }
  html.light .theme-menu-item.is-active {
    background: rgba(11,191,83,.12);
    color: #0a9c44;
  }

  .theme-menu-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 15px;
    height: 15px;
    flex-shrink: 0;
  }

  .theme-menu-item span:nth-child(2) {
    flex: 1;
  }

  .theme-menu-check {
    flex-shrink: 0;
    opacity: 0;
    transform: scale(0.7);
    transition: opacity .15s ease, transform .15s ease;
  }
  .theme-menu-item.is-active .theme-menu-check {
    opacity: 1;
    transform: scale(1);
  }
</style>

<script>
(function () {
  /* ───── دکمه/منوی تغییر تم در هدر (روز/شب/سیستم) ───── */
  var navThemeBtn  = document.getElementById('nav-theme-toggle');
  var themeMenu    = document.getElementById('theme-menu');
  var themeMenuOpen = false;

  function syncThemeUI() {
    var mode = window.vatanGetThemeMode ? window.vatanGetThemeMode() : 'dark';
    if (navThemeBtn) {
      navThemeBtn.querySelectorAll('.theme-trigger-icon').forEach(function (icon) {
        icon.classList.toggle('is-shown', icon.dataset.icon === mode);
      });
    }
    if (themeMenu) {
      themeMenu.querySelectorAll('.theme-menu-item').forEach(function (item) {
        item.classList.toggle('is-active', item.dataset.themeChoice === mode);
      });
    }
  }

  function openThemeMenu() {
    if (!themeMenu) return;
    themeMenu.style.display = 'block';
    navThemeBtn.setAttribute('aria-expanded', 'true');
    themeMenuOpen = true;
  }

  function closeThemeMenu() {
    if (!themeMenu) return;
    themeMenu.style.display = 'none';
    navThemeBtn.setAttribute('aria-expanded', 'false');
    themeMenuOpen = false;
  }

  if (navThemeBtn && themeMenu) {
    syncThemeUI();

    navThemeBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      themeMenuOpen ? closeThemeMenu() : openThemeMenu();
    });

    themeMenu.querySelectorAll('.theme-menu-item').forEach(function (item) {
      item.addEventListener('click', function (e) {
        e.stopPropagation();
        window.vatanSetTheme && window.vatanSetTheme(item.dataset.themeChoice);
        closeThemeMenu();
      });
    });

    document.addEventListener('click', function () {
      if (themeMenuOpen) closeThemeMenu();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && themeMenuOpen) closeThemeMenu();
    });

    document.addEventListener('vatan-theme-changed', syncThemeUI);
  }

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
    if (!bar || !el) return { left: 0, width: 0 };
    // با getBoundingClientRect موقعیت واقعی رندرشده رو می‌خونیم؛ چون صفحه RTL هست
    // محاسبه‌ی دستی بر اساس index (چپ‌به‌راست) اشتباه می‌افتاد و پیل زیر آیتم غلط می‌نشست.
    var barRect = bar.getBoundingClientRect();
    var elRect  = el.getBoundingClientRect();
    return { left: elRect.left - barRect.left + 6, width: elRect.width - 12 };
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