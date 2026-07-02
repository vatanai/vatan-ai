{{-- ══════════════════════════════════════════════════════
     HERO — آواتار + اطلاعات + آمار + بنر
     mobile: ستون وسط‌چین | tablet/desktop: دو ستون RTL
══════════════════════════════════════════════════════ --}}
<section class="profile-hero">

  {{-- ── ستون راست: آواتار + اطلاعات ── --}}
  <div class="hero-right-group">

    <div class="avatar-wrap">
      <div class="avatar-ring">
        <div class="avatar-inner">
          <img src="https://i.pravatar.cc/150?img=12" alt="avatar" class="avatar-img">
        </div>
      </div>
    </div>

    <div class="profile-info">

      <div class="name-row">
        <h1 class="profile-name">محسن آقاجانی</h1>
      </div>

      <p class="profile-phone" dir="ltr">۰۹۱۲۰۰۰۰۰۰۰</p>

      {{-- بج پلن — tablet/desktop (پنهان در mobile) --}}
      <div class="plan-badge show-desktop">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="#0BBF53">
          <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
        </svg>
        <span>پلن رایگان</span>
      </div>

      {{-- آمار ۴تایی — فقط mobile --}}
      <div class="stats-row hide-desktop">
        <div class="stat-col">
          <span class="stat-number">۳۵</span>
          <span class="stat-label">پست</span>
        </div>
        <div class="stat-sep"></div>
        <div class="stat-col">
          <span class="stat-number">۱۸۱۰</span>
          <span class="stat-label">ساخته‌شده</span>
        </div>
        <div class="stat-sep"></div>
        <div class="stat-col">
          <span class="stat-number">۱۴</span>
          <span class="stat-label">روز عضویت</span>
        </div>
        <div class="stat-sep"></div>
        <div class="stat-col">
          <span class="stat-number stat-number--plan">رایگان</span>
          <span class="stat-label">پلن</span>
        </div>
      </div>

      {{-- دکمه‌های اکشن --}}
      <div class="action-row">

        {{-- تنظیمات + dropdown — فقط mobile --}}
        <div class="settings-wrap">
          <button type="button" class="btn-card btn-icon" id="settingsBtn"
                  aria-label="تنظیمات" aria-expanded="false">
            <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}"
                 width="17" height="17" class="icon-filter" alt="">
          </button>
          <div id="settingsMenu" class="settings-menu" style="display:none;">
            <div class="sm-header">
              <div class="sm-user">
                <div class="sm-avatar-wrap">
                  <img src="https://i.pravatar.cc/150?img=12" alt="" class="sm-avatar-img">
                </div>
                <div>
                  <p class="sm-name">محسن آقاجانی</p>
                  <p class="sm-phone" dir="ltr">۰۹۱۲۰۰۰۰۰۰۰</p>
                </div>
              </div>
            </div>
            <button type="button" class="sm-item">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
              </svg>
              <span>عکس پروفایل</span>
            </button>
            <button type="button" class="sm-item">
              <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}"
                   width="15" height="15" class="icon-filter" alt="">
              <span>تنظیمات</span>
            </button>
            <button type="button" class="sm-item sm-item--danger">
              <i class="fa-solid fa-right-from-bracket"
                 style="font-size:13px;width:15px;text-align:center;"></i>
              <span>خروج</span>
            </button>
          </div>
        </div>

        <button type="button" class="btn-card btn-support">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          <span>پشتیبانی</span>
        </button>

        <button type="button" class="btn-subscribe">
          <span>خرید اشتراک ویژه</span>
          <span class="subscribe-badge">۱۵٪ تخفیف</span>
        </button>

      </div>
    </div>

  </div>{{-- /hero-right-group --}}

  {{-- ── ستون چپ: آمار + بنر — فقط tablet/desktop ── --}}
  <div class="hero-left-group">

    <div class="stats-desktop">
      <div class="stat-col">
        <span class="stat-number">۳۵</span>
        <span class="stat-label">پست</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number">۱۸۱۰</span>
        <span class="stat-label">ساخته‌شده</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number">۱۴</span>
        <span class="stat-label">روز عضویت</span>
      </div>
      <div class="stat-sep"></div>
      <div class="stat-col">
        <span class="stat-number stat-number--plan">رایگان</span>
        <span class="stat-label">پلن</span>
      </div>
    </div>

    <div class="promo-banner">
      <p class="promo-text">برنامه ویژه کسب درآمد مستمر</p>
      <button type="button" class="promo-btn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
          <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm0 2h14v1H5v-1z"/>
        </svg>
        همکاری در فروش
      </button>
    </div>

  </div>{{-- /hero-left-group --}}

</section>

{{-- بنر همکاری — فقط mobile (tablet/desktop داخل hero-left-group) --}}
<section class="promo-section">
  <div class="promo-banner">
    <p class="promo-text">برنامه ویژه کسب درآمد مستمر</p>
    <button type="button" class="promo-btn">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
        <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm0 2h14v1H5v-1z"/>
      </svg>
      همکاری در فروش
    </button>
  </div>
</section>
