<nav class="topnav-popup-window">
  @auth
    <div class="tp-userinfo">
      <span class="tp-usercopy"><span class="tp-user-name">{{ trim((auth()->user()->name ?? '') . ' ' . (auth()->user()->last_name ?? '')) ?: 'کاربر وطن AI' }}</span><span class="tp-user-plan">{{ $profilePlanLabel }}</span></span>
      <span class="tp-user-phone" dir="ltr">{{ auth()->user()->phone ?: '—' }}</span>
    </div>
    <hr>
    @if($showMobileThemeSelector ?? false)
      <div class="vp-profile-theme" role="radiogroup" aria-label="انتخاب حالت نمایش">
        <button type="button" data-mobile-theme="light" role="radio" aria-label="روز"><i class="fa-solid fa-sun"></i><span>روز</span></button>
        <button type="button" data-mobile-theme="dark" role="radio" aria-label="شب"><i class="fa-solid fa-moon"></i><span>شب</span></button>
        <button type="button" data-mobile-theme="system" role="radio" aria-label="سیستم"><i class="fa-solid fa-desktop"></i><span>سیستم</span></button>
      </div>
      <hr class="vp-profile-theme-divider">
    @endif
    <ul>
      <li><button type="button" onclick="window.location.href='{{ route('pricing.index') }}'"><i class="fa-solid fa-gem"></i><span>ارتقای حساب و خرید اعتبار</span></button></li>
      <li><button type="button" onclick="window.location.href='{{ route('app.profile', ['tab' => 'files', 'file_tab' => 'account']) }}'"><i class="fa-solid fa-wallet"></i><span>حساب و پرداخت‌ها</span></button></li>
      @if($referralProfileMenuEnabled)<li><button type="button" onclick="window.location.href='{{ route('app.profile', ['tab' => 'referral']) }}#referral-program'"><i class="fa-solid fa-handshake-angle"></i><span>همکاری در فروش</span></button></li>@endif
      <li><button type="button" onclick="window.location.href='{{ route('app.profile') }}'"><i class="fa-solid fa-image"></i><span>عکس پروفایل</span></button></li>
      <li><button type="button" onclick="window.location.href='{{ route('support.index') }}'"><i class="fa-solid fa-headset"></i><span>پشتیبانی</span></button></li>
      <hr>
      <li><button type="button" class="is-danger" onclick="window.logoutFromCurrentPage(this)"><i class="fa-solid fa-right-from-bracket"></i><span>خروج</span></button></li>
    </ul>
  @else
    @if($showMobileThemeSelector ?? false)
      <div class="vp-profile-theme" role="radiogroup" aria-label="انتخاب حالت نمایش">
        <button type="button" data-mobile-theme="light" role="radio" aria-label="روز"><i class="fa-solid fa-sun"></i><span>روز</span></button>
        <button type="button" data-mobile-theme="dark" role="radio" aria-label="شب"><i class="fa-solid fa-moon"></i><span>شب</span></button>
        <button type="button" data-mobile-theme="system" role="radio" aria-label="سیستم"><i class="fa-solid fa-desktop"></i><span>سیستم</span></button>
      </div>
      <hr class="vp-profile-theme-divider">
    @endif
    <ul>
      <li><button type="button" onclick="window.location.href='{{ route('login', ['redirect' => request()->fullUrl()]) }}'"><i class="fa-solid fa-right-to-bracket"></i><span>ورود و ثبت نام</span></button></li>
      @if($referralProfileMenuEnabled)<li><button type="button" onclick="window.location.href='{{ route('app.profile', ['tab' => 'referral']) }}#referral-program'"><i class="fa-solid fa-handshake-angle"></i><span>همکاری در فروش</span></button></li>@endif
      <li><button type="button" onclick="window.location.href='{{ route('pricing.index') }}'"><i class="fa-solid fa-coins"></i><span>خرید اعتبار</span></button></li>
      <li><button type="button" onclick="window.location.href='{{ route('support.index') }}'"><i class="fa-solid fa-headset"></i><span>پشتیبانی</span></button></li>
    </ul>
  @endauth
</nav>
