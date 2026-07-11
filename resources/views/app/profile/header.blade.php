{{-- ===== هدر موبایل — دقیقاً مطابق هدر نسخه موبایل صفحه هوم (فقط زیر ۶۴۰px نمایش داده می‌شود) ===== --}}
<section class="home-logo">
  <div class="home-logo-wrap" style="gap:12px;">
    <button id="profileMenuOpenBtn" type="button" style="width:36px;height:36px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:transparent;border:none;cursor:pointer;">
      <img src="{{ asset('assets/img/icons/hamburger.svg') }}" width="26" height="26" class="icon-filter">
    </button>
    <div style="display:flex;align-items:center;gap:8px;">
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" style="width:31px;height:31px;display:block;">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" style="width:77px;height:auto;display:block;">
    </div>
  </div>
  <div style="position:relative; display:inline-block; margin-top:10px;">
    <div style="background:#1e1e1e;border-radius:9px;padding:6.84px 13.86px 6.84px 13.86px;font-size:11.7px;font-weight:400;color:#ffffff;white-space:nowrap;">خرید ویژه</div>
    <div style="position:absolute;bottom:-10px;left:50%;transform:translateX(-50%);background:#E8326A;border-radius:6px;padding:1.9px 8px;font-size:10px;font-weight:800;color:#ffffff;white-space:nowrap;width:fit-content;">۱۵٪ تخفیف</div>
  </div>
</section>

{{-- ===== دراپ‌داون منوی همبرگری هدر موبایل ===== --}}
<div id="profileMenuOverlay" style="display:none;position:fixed;inset:0;z-index:160;" onclick="if(event.target===this){closeProfileMenu();}">
  <div id="profileMenuSheet" style="position:absolute;top:calc(env(safe-area-inset-top) + 136px);right:12px;width:296px;background:var(--bg-card);border:1px solid var(--border-subtle);border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:scale(0.9) translateY(-10px);opacity:0;transition:transform 0.2s ease,opacity 0.2s ease;transform-origin:top right;">

    <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border-subtle);">
      <div style="display:flex;align-items:center;gap:10px;">
        <div style="width:38px;height:38px;border-radius:50%;overflow:hidden;flex-shrink:0;background:var(--bg-page);">
          @if(!($isGuest ?? false) && auth()->user()->avatar)
            <img src="{{ asset('storage/' . auth()->user()->avatar) }}" style="width:100%;height:100%;object-fit:cover;">
          @else
            <img src="{{ asset('assets/img/icons/nav-profile.svg') }}" class="icon-filter" style="width:100%;height:100%;object-fit:contain;padding:22%;box-sizing:border-box;opacity:.55;">
          @endif
        </div>
        <div>
          @if($isGuest ?? false)
            <p style="margin:0;font-size:13px;font-weight:700;color:var(--text-primary);">کاربر مهمان</p>
          @else
            <p style="margin:0;font-size:13px;font-weight:700;color:var(--text-primary);">{{ auth()->user()->name }} {{ auth()->user()->last_name }}</p>
            <p style="margin:2px 0 0 0;font-size:11px;color:var(--text-secondary);" dir="ltr">{{ auth()->user()->phone }}</p>
          @endif
        </div>
      </div>
      <button id="profileHeaderThemeToggle" type="button" class="theme-toggle-btn" aria-label="تغییر تم">
        <span class="theme-toggle-track">
          <span class="theme-toggle-thumb">
            <svg class="theme-icon-moon" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            <svg class="theme-icon-sun" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.5"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/><line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/></svg>
          </span>
        </span>
      </button>
    </div>

    @if($isGuest ?? false)
      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;" onclick="window.location.href='{{ route('login') }}'">
        <i class="fa-solid fa-right-to-bracket" style="font-size:14px;color:var(--text-primary);width:16px;text-align:center;"></i>
        <span style="font-size:13px;color:var(--text-primary);">ورود و ثبت‌نام</span>
      </div>
    @else
      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border-subtle);">
        <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="16" height="16" class="icon-filter">
        <span style="font-size:13px;color:var(--text-primary);">تنظیمات</span>
      </div>
      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;" onclick="event.preventDefault(); document.getElementById('logout-form-mobile-header').submit();">
        <i class="fa-solid fa-right-from-bracket" style="font-size:14px;color:#f05c5c;width:16px;text-align:center;"></i>
        <span style="font-size:13px;color:#f05c5c;">خروج</span>
      </div>
      <form id="logout-form-mobile-header" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
    @endif

  </div>
</div>

{{-- ===== بخش هدر و نمایش بالا: آواتار + اطلاعات + آمار + اکشن‌ها ===== --}}
<section class="profile-hero">

  {{-- گروه راست: آواتار + اطلاعات (روی desktop کنار هم، روی mobile روی هم) --}}
  <div class="hero-right-group">

  {{-- آواتار --}}
  <div class="avatar-wrap"@if(!($isGuest ?? false)) id="avatarClickTrigger" style="cursor:pointer;" title="تغییر عکس پروفایل"@endif>
    <div class="avatar-ring">
      <div class="avatar-inner">
        @if(!($isGuest ?? false) && auth()->user()->avatar)
          <img src="{{ asset('storage/' . auth()->user()->avatar) }}" alt="عکس پروفایل" class="avatar-img" id="profileAvatarImg">
        @else
          <img src="{{ asset('assets/img/icons/nav-profile.svg') }}" alt="عکس پروفایل" class="avatar-img avatar-img--placeholder icon-filter" id="profileAvatarImg">
        @endif
      </div>
    </div>
  </div>

  {{-- فرم مخفی آپلود عکس پروفایل --}}
  <form id="avatarUploadForm" action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" class="hidden">
    @csrf
    <input type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg,image/webp">
  </form>

  {{-- اطلاعات --}}
  <div class="profile-info">

    {{-- نام --}}
    <div class="name-row">
      <h1 class="profile-name">
        @if($isGuest ?? false)
          کاربر مهمان
        @else
          {{ auth()->user()->name }} {{ auth()->user()->last_name }}
        @endif
      </h1>
    </div>

    {{-- شماره موبایل / دعوت به ورود برای مهمان --}}
    @if($isGuest ?? false)
      <p class="profile-phone">برای مشاهده کامل پروفایل وارد حساب خود شو</p>
    @else
      <p class="profile-phone" dir="ltr">{{ auth()->user()->phone }}</p>
    @endif

    {{-- بج پلن — فقط desktop --}}
    <div class="plan-badge show-desktop">
      <svg width="10" height="10" viewBox="0 0 24 24" fill="#cffe00"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
      <span>پلن {{ $planName }}</span>
    </div>

    {{-- آمار ۴ تایی — فقط mobile --}}
    <div class="stats-row hide-desktop">
      <div class="stat-col">
        <span class="stat-number">{{ number_format($tokenBalance) }}</span>
        <span class="stat-label">توکن</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number">{{ number_format($createdCount) }}</span>
        <span class="stat-label">ساخته‌شده</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number stat-number--plan">{{ $planName }}</span>
        <span class="stat-label">پلن</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number">{{ number_format($earnings) }}</span>
        <span class="stat-label">تومان درآمد</span>
      </div>
    </div>

    {{-- دکمه‌های اکشن — راست به چپ: پشتیبانی | خرید اشتراک --}}
    <div class="action-row">

      {{-- پشتیبانی --}}
      <button type="button" class="btn-card btn-support">
        <span>پشتیبانی</span>
      </button>

      {{-- خرید اشتراک --}}
      <button type="button" class="btn-subscribe">
        <span>خرید اشتراک ویژه</span>
        <span class="subscribe-badge">۱۵٪ تخفیف</span>
      </button>

    </div>
  </div>

  </div>{{-- /hero-right-group --}}

  {{-- ستون چپ — فقط desktop/tablet: آمار + بنر همکاری --}}
  <div class="hero-left-group">

    {{-- آمار ۴ تایی --}}
    <div class="stats-desktop">
      <div class="stat-col">
        <span class="stat-number">{{ number_format($tokenBalance) }}</span>
        <span class="stat-label">توکن</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number">{{ number_format($createdCount) }}</span>
        <span class="stat-label">ساخته‌شده</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number stat-number--plan">{{ $planName }}</span>
        <span class="stat-label">پلن</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number">{{ number_format($earnings) }}</span>
        <span class="stat-label">تومان درآمد</span>
      </div>
    </div>

    {{-- بنر همکاری در فروش (desktop) --}}
    <div class="promo-banner">
      <p class="promo-text">برنامه ویژه کسب درآمد مستمر</p>
      <button type="button" class="promo-btn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm0 2h14v1H5v-1z"/></svg>
        همکاری در فروش
      </button>
    </div>

  </div>

</section>

{{-- ===== بنر همکاری در فروش (mobile) ===== --}}
<section class="promo-section">
  <div class="promo-banner">
    <p class="promo-text">برنامه ویژه کسب درآمد مستمر</p>
    <button type="button" class="promo-btn">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm0 2h14v1H5v-1z"/></svg>
      همکاری در فروش
    </button>
  </div>
</section>
