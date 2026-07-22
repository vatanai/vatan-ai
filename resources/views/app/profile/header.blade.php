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
