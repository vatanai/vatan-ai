@extends('layouts.app')

@section('content')
<div class="profile-page" dir="rtl">

  {{-- ===== HERO: آواتار + اطلاعات + آمار ===== --}}
  <section class="profile-hero">

    {{-- گروه راست: آواتار + اطلاعات (روی desktop کنار هم، روی mobile روی هم) --}}
    <div class="hero-right-group">

    {{-- آواتار --}}
    <div class="avatar-wrap">
      <div class="avatar-ring">
        <div class="avatar-inner">
          <img src="https://i.pravatar.cc/150?img=12" alt="avatar" class="avatar-img">
        </div>
      </div>
    </div>

    {{-- اطلاعات --}}
    <div class="profile-info">

      {{-- نام --}}
      <div class="name-row">
        <h1 class="profile-name">محسن آقاجانی</h1>
      </div>

      {{-- شماره موبایل --}}
      <p class="profile-phone" dir="ltr">۰۹۱۲۰۰۰۰۰۰۰</p>

      {{-- بج پلن — فقط desktop --}}
      <div class="plan-badge show-desktop">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="#0BBF53"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
        <span>پلن رایگان</span>
      </div>

      {{-- آمار ۴ تایی — فقط mobile --}}
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

      {{-- دکمه‌های اکشن — راست به چپ: تنظیمات | پشتیبانی | خرید اشتراک --}}
      <div class="action-row">

        {{-- تنظیمات + dropdown --}}
        <div class="settings-wrap">
          <button type="button" class="btn-card btn-icon" id="settingsBtn" aria-label="تنظیمات" aria-expanded="false">
            <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="17" height="17" class="icon-filter" alt="">
          </button>
          <div id="settingsMenu" class="settings-menu" style="display:none;">
            {{-- هدر: آواتار + نام (بدون theme toggle — رفت توی هدر) --}}
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
            {{-- گزینه‌ها --}}
            <button type="button" class="sm-item">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
              <span>عکس پروفایل</span>
            </button>
            <button type="button" class="sm-item">
              <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="15" height="15" class="icon-filter" alt="">
              <span>تنظیمات</span>
            </button>
            <button type="button" class="sm-item sm-item--danger">
              <i class="fa-solid fa-right-from-bracket" style="font-size:13px;width:15px;text-align:center;"></i>
              <span>خروج</span>
            </button>
          </div>
        </div>

        {{-- پشتیبانی --}}
        <button type="button" class="btn-card btn-support">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
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

  {{-- ===== بنر همکاری در فروش ===== --}}
  <section class="promo-section">
    <div class="promo-banner">
      <p class="promo-text">برنامه ویژه کسب درآمد مستمر</p>
      <button type="button" class="promo-btn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm0 2h14v1H5v-1z"/></svg>
        همکاری در فروش
      </button>
    </div>
  </section>

  {{-- ===== تب‌ها + پنل‌ها ===== --}}
  <section class="tabs-section">

    {{-- تب‌ها — RTL راست به چپ: ذخیره | همکاری در فروش | فایلها | محتوا --}}
    <div class="profile-tabs" dir="rtl">
      <button type="button" class="profile-tab" data-tab="saved">
        <img src="{{ asset('assets/img/icons/fi-sr-bookmark.svg') }}" class="tab-icon" width="19" height="19" alt="">
        <span class="tab-label">ذخیره شده‌ها</span>
      </button>
      <button type="button" class="profile-tab" data-tab="referral">
        <svg class="tab-icon tab-icon--svg" width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
          <path d="M16 11C17.66 11 18.99 9.66 18.99 8S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05C16.19 13.89 17 15.02 17 16.5V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
        </svg>
        <span class="tab-label">همکاری در فروش</span>
      </button>
      <button type="button" class="profile-tab" data-tab="files">
        <img src="{{ asset('assets/img/icons/fi-sr-file.svg') }}" class="tab-icon" width="19" height="19" alt="">
        <span class="tab-label">فایلهای تو</span>
      </button>
      <button type="button" class="profile-tab active" data-tab="grid">
        <img src="{{ asset('assets/img/icons/fi-sr-grid.svg') }}" class="tab-icon" width="19" height="19" alt="">
        <span class="tab-label">محتوا</span>
      </button>
    </div>

    {{-- ===== PANEL: گرید (محتوا) ===== --}}
    @php
      $gridImages = [
        ['url' => asset('assets/img/9cb93b50-d93f-462f-b6d4-113f63ffc603.avif'), 'video' => false],
        ['url' => asset('assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg'), 'video' => true],
        ['url' => asset('assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp'), 'video' => false],
        ['url' => asset('assets/img/ai-photo-editor-prompt.webp'), 'video' => false],
        ['url' => asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg'), 'video' => true],
        ['url' => asset('assets/img/best-friends-ai-prompt-2.webp'), 'video' => false],
        ['url' => asset('assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg'), 'video' => false],
        ['url' => asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg'), 'video' => false],
        ['url' => asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif'), 'video' => true],
        ['url' => asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp'), 'video' => false],
        ['url' => asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif'), 'video' => false],
        ['url' => asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp'), 'video' => false],
      ];
    @endphp
    <div class="profile-panel panel-grid" data-panel="grid">
      @foreach ($gridImages as $item)
        <div class="grid-cell">
          <img src="{{ $item['url'] }}" alt="" class="grid-img">
          @if($item['video'])
            <i class="fa-solid fa-video cell-badge"></i>
          @endif
        </div>
      @endforeach
    </div>

    {{-- ===== PANEL: فایل‌ها ===== --}}
    <div class="profile-panel" data-panel="files" style="display:none;padding:16px;">

      <div class="storage-card">
        <div class="storage-header">
          <span class="storage-title">فضای ذخیره‌سازی</span>
          <span class="storage-used">۴۰ از ۱۰۰ مگابایت</span>
        </div>
        <div class="storage-bar">
          <div class="storage-fill" style="width:40%;"></div>
        </div>
        <div class="storage-footer">
          <span>۴۰ مگ استفاده شده</span>
          <span class="storage-free">۶۰ مگ آزاد</span>
        </div>
      </div>

      <div class="files-sub-tabs" dir="rtl">
        <button type="button" class="files-sub-tab active" data-sub="created">خلق شده</button>
        <button type="button" class="files-sub-tab" data-sub="personal">عکس‌های شخصی</button>
      </div>

      <div id="files-created" class="files-grid">
        @foreach ([
          asset('assets/img/prompt-for-gemini-ai-girl.webp'),
          asset('assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg'),
          asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp'),
        ] as $img)
          <div class="files-cell"><img src="{{ $img }}" alt="" class="grid-img"></div>
        @endforeach
      </div>

      <div id="files-personal" class="files-grid" style="display:none;">
        @foreach ([
          asset('assets/img/lookaside.fbsbx.webp'),
          asset('assets/img/lookasidwee.fbsbx.webp'),
          asset('assets/img/lookasjide.fbsbx.webp'),
        ] as $img)
          <div class="files-cell"><img src="{{ $img }}" alt="" class="grid-img"></div>
        @endforeach
      </div>

    </div>

    {{-- ===== PANEL: همکاری در فروش ===== --}}
    <div class="profile-panel" data-panel="referral" style="display:none;padding:20px 16px;">

      <div class="referral-hero">
        <div class="referral-icon-wrap">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="#0BBF53">
            <path d="M16 11C17.66 11 18.99 9.66 18.99 8S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05C16.19 13.89 17 15.02 17 16.5V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
          </svg>
        </div>
        <h3 class="referral-title">همکاری در فروش وطن AI</h3>
        <p class="referral-sub">با معرفی وطن AI به دیگران، کمیسیون مستمر دریافت کن</p>
      </div>

      <div class="referral-desc">
        <p>برنامه رفرال وطن AI یه فرصت واقعیه برای کسب درآمد پایدار. هر بار که یکی از لینک‌های معرفی تو ثبت‌نام کنه و پلن بخره، تو کمیسیون مستقیم دریافت می‌کنی — بدون سقف، بدون انقضا.</p>
      </div>

      <div class="referral-stats">
        <div class="referral-stat"><p class="rs-number">۲۵٪</p><p class="rs-label">کمیسیون مستقیم</p></div>
        <div class="referral-stat"><p class="rs-number">∞</p><p class="rs-label">بدون سقف درآمد</p></div>
        <div class="referral-stat"><p class="rs-number">۲ سطح</p><p class="rs-label">درآمد مستمر</p></div>
      </div>

      <div class="referral-actions">
        <button type="button" class="btn-subscribe" style="margin:0;">شروع همکاری</button>
        <button type="button" class="btn-referral-outline">دریافت مشاوره رایگان</button>
      </div>

    </div>

    {{-- ===== PANEL: ذخیره شده‌ها ===== --}}
    <div class="profile-panel panel-saved" data-panel="saved" style="display:none;">
      @foreach ([
        asset('assets/img/Screenshot-2025-12-09-at-12.33.35-PM.avif'),
        asset('assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg'),
        asset('assets/img/promptbank176.webp'),
        asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp'),
      ] as $img)
        <div class="grid-cell">
          <img src="{{ $img }}" alt="" class="grid-img">
          <div class="saved-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="#ffffff"><path d="M17 3H7C5.9 3 5 3.9 5 5V21L12 18L19 21V5C19 3.9 18.1 3 17 3Z"/></svg>
          </div>
        </div>
      @endforeach
    </div>

  </section>

</div>
@endsection

@push('styles')
<style>

/* ═══════════════════════════════════════
   CSS VARIABLES — DESIGN SYSTEM
═══════════════════════════════════════ */
:root {
  --bg-page:           #000000;
  --text-primary:      #ffffff;
  --text-secondary:    #ffffff;
  --icon:              #ffffff;
  --bg-card:           #3F3F3F;
  --bg-affiliate:      #0d2818;
  --border-affiliate:  #1a5c32;
  --green:             #0BBF53;
  --accent:            #a07af5;
  --red:               #f05c5c;
  --border-subtle:     #222230;
  --bg-surface:        #111116;
}
html.light {
  --bg-page:           #ffffff;
  --text-primary:      #000000;
  --text-secondary:    #000000;
  --icon:              #000000;
  --bg-card:           #E5E5E5;
  --bg-affiliate:      #e8f8ee;
  --border-affiliate:  #a8e6be;
  --green:             #0BBF53;
  --border-subtle:     #e0e0e0;
  --bg-surface:        #f5f5f5;
}

/* ═══════════════════════════════════════
   FONT — YekanBakh کل صفحه
═══════════════════════════════════════ */
.profile-page,
.profile-page * {
  font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif !important;
}

/* ═══════════════════════════════════════
   BASE — بک‌گراند شب/روز کامل
═══════════════════════════════════════ */
html, body { overflow-x: hidden; background: var(--bg-page) !important; color: var(--text-primary); }

.profile-page {
  width: 100%;
  max-width: 480px;
  margin: 0 auto;
  background: var(--bg-page);
  min-height: 100vh;
  padding-bottom: 120px;
}

/* ═══════════════════════════════════════
   UTILITY: show/hide by breakpoint
═══════════════════════════════════════ */
.show-desktop { display: none !important; }
.hide-desktop { display: flex; }

/* hero-left-group — موبایل پنهان */
.hero-left-group { display: none; }

/* stats-desktop — داخل hero-left-group */
.stats-desktop {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 0;
  direction: rtl;
  width: 100%;
}

/* ═══════════════════════════════════════
   HERO SECTION — mobile: stack centered
═══════════════════════════════════════ */
.profile-hero {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: calc(env(safe-area-inset-top) + 36px) 16px 16px;
  gap: 12px;
}

/* ═══════════════════════════════════════
   AVATAR
═══════════════════════════════════════ */
.avatar-wrap { flex-shrink: 0; }

.avatar-ring {
  width: 100px; height: 100px;
  border-radius: 50%;
  padding: 3px;
  background: var(--green);
}
.avatar-inner {
  width: 100%; height: 100%;
  border-radius: 50%;
  padding: 2px;
  background: var(--bg-page);
}
.avatar-img {
  width: 100%; height: 100%;
  object-fit: cover;
  border-radius: 50%;
  display: block;
}

/* ═══════════════════════════════════════
   HERO RIGHT GROUP
═══════════════════════════════════════ */
/* موبایل: ستون (آواتار بالا، اطلاعات پایین) */
.hero-right-group {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  gap: 12px;
}

/* ═══════════════════════════════════════
   PROFILE INFO — موبایل: وسط‌چین
═══════════════════════════════════════ */
.profile-info {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
  text-align: center;
}

.name-row {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 8px;
}

.profile-name {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0;
}

.profile-phone {
  font-size: 14.3px;
  color: rgba(168,196,168,1);
  margin: 0;
  letter-spacing: 0.5px;
}
html.light .profile-phone { color: #5a7a5a; }

/* بج پلن */
.plan-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(11,191,83,0.12);
  border: 1px solid rgba(11,191,83,0.3);
  border-radius: 8px;
  padding: 4px 10px;
}
.plan-badge span {
  font-size: 11px;
  font-weight: 700;
  color: var(--green);
}

/* ═══════════════════════════════════════
   آمار ۴ تایی
═══════════════════════════════════════ */
.stats-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0;
  width: 100%;
  max-width: 360px;
}

.stat-col {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 0 4px;
}

.stat-sep {
  width: 1px;
  height: 28px;
  background: var(--border-subtle);
  flex-shrink: 0;
}

.stat-number {
  font-size: 18px;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.1;
}

.stat-number--plan {
  font-size: 13px;
  font-weight: 700;
  color: var(--green);
}

.stat-label {
  font-size: 11px;
  color: rgba(168,196,168,1);
  white-space: nowrap;
}
html.light .stat-label { color: #5a7a5a; }

/* ═══════════════════════════════════════
   دکمه‌های اکشن — راست به چپ: تنظیمات | پشتیبانی | خرید اشتراک
═══════════════════════════════════════ */
.action-row {
  display: flex;
  gap: 5px;
  direction: rtl;
  width: 100%;
  max-width: 400px;
  margin-top: 6px;
}

/* کارت‌ دکمه (پشتیبانی + تنظیمات) */
.btn-card {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border-radius: 10px;
  border: none;
  background: var(--bg-card);
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  padding: 11px 14px;
  transition: opacity 0.15s;
}
.btn-card:active { opacity: 0.8; }

/* دکمه آیکون مربع (تنظیمات) */
.btn-icon {
  width: 44px;
  height: 44px;
  padding: 0;
  flex-shrink: 0;
}

/* دکمه پشتیبانی */
.btn-support {
  flex: 1.2;
}

/* دکمه خرید اشتراک */
.btn-subscribe {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 11px 14px;
  border-radius: 10px;
  border: none;
  background: var(--green);
  color: #ffffff;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s;
}
.btn-subscribe:active { opacity: 0.85; }

.subscribe-badge {
  background: #e91e8c;
  color: #ffffff;
  font-size: 10px;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 6px;
  white-space: nowrap;
}

/* ═══════════════════════════════════════
   SETTINGS DROPDOWN
═══════════════════════════════════════ */
.settings-wrap {
  position: relative;
  flex-shrink: 0;
}

.settings-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 270px;
  background: #111116;
  border: 1px solid var(--border-subtle);
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.55);
  z-index: 300;
  overflow: hidden;
  transform-origin: top right;
  animation: menuIn 0.18s ease forwards;
}
html.light .settings-menu {
  background: #ffffff;
  border-color: #e0e0e0;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

@keyframes menuIn {
  from { transform: scale(0.9) translateY(-6px); opacity: 0; }
  to   { transform: scale(1) translateY(0);      opacity: 1; }
}

.sm-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 14px 16px;
  border-bottom: 1px solid var(--border-subtle);
}

.sm-user {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: 1;
  min-width: 0;
}

.sm-avatar-wrap {
  width: 38px; height: 38px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
}
.sm-avatar-img { width: 100%; height: 100%; object-fit: cover; }

.sm-name {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--text-primary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sm-phone {
  margin: 2px 0 0;
  font-size: 11px;
  color: rgba(168,196,168,1);
}
html.light .sm-phone { color: #5a7a5a; }

.sm-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: transparent;
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 400;
  cursor: pointer;
  direction: rtl;
  text-align: right;
  border-bottom: 1px solid var(--border-subtle);
  transition: background 0.15s;
}
.sm-item:last-child { border-bottom: none; }
.sm-item:hover { background: rgba(255,255,255,0.05); }
html.light .sm-item:hover { background: rgba(0,0,0,0.04); }

.sm-item--danger {
  color: var(--red);
}

/* theme toggle داخل منو */
.theme-toggle-btn {
  background: transparent; border: none; cursor: pointer;
  padding: 0; display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.theme-toggle-track {
  display: flex; align-items: center;
  width: 46px; height: 26px; border-radius: 99px;
  background: #1a1a2e; border: 1px solid rgba(255,255,255,0.12);
  position: relative; transition: background 0.3s, border-color 0.3s;
}
html.light .theme-toggle-track { background: #e8f0fe; border-color: rgba(0,0,0,0.1); }
.theme-toggle-thumb {
  width: 20px; height: 20px; border-radius: 50%; background: #ffffff;
  display: flex; align-items: center; justify-content: center;
  position: absolute; left: 3px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.3);
  transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), background 0.3s;
}
html.light .theme-toggle-thumb { transform: translateX(20px); background: #f59e0b; }
.theme-icon-moon { color: #1a1a2e; display: block; }
.theme-icon-sun  { color: #ffffff; display: none; }
html.light .theme-icon-moon { display: none !important; }
html.light .theme-icon-sun  { display: block !important; }

/* ═══════════════════════════════════════
   PROMO SECTION (همکاری در فروش بنر)
═══════════════════════════════════════ */
.promo-section { padding: 0 16px; margin-top: 14px; }

.promo-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  direction: rtl;
  background: var(--bg-affiliate);
  border: 1px solid var(--border-affiliate);
  border-radius: 12px;
  padding: 12px 14px;
}
.promo-text {
  font-size: 13px;
  font-weight: 700;
  color: var(--green);
  margin: 0;
}
.promo-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: var(--green);
  color: #ffffff;
  border: none;
  border-radius: 10px;
  padding: 9px 14px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ═══════════════════════════════════════
   TABS SECTION
═══════════════════════════════════════ */
.tabs-section { margin-top: 20px; }

.profile-tabs {
  display: flex;
  align-items: center;
  border-bottom: 1px solid var(--border-subtle);
}

.profile-tab {
  flex: 1;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  border: none;
  background: transparent;
  cursor: pointer;
  position: relative;
  color: var(--text-primary);
  transition: color 0.2s;
}
html.light .profile-tab { color: var(--text-primary); }

.profile-tab::after {
  content: '';
  position: absolute;
  bottom: -1px; left: 0; right: 0;
  height: 2px;
  background: var(--green);
  opacity: 0;
  transition: opacity 0.2s;
}
.profile-tab.active::after { opacity: 1; }

/* آیکون تصویری — همیشه سفید */
.tab-icon {
  filter: brightness(0) invert(1);
  transition: filter 0.2s, transform 0.15s;
}
.profile-tab.active .tab-icon {
  filter: brightness(0) invert(1);
  transform: scale(1.05);
}
html.light .tab-icon { filter: brightness(0) invert(0); }
html.light .profile-tab.active .tab-icon { filter: brightness(0) invert(0); }

/* آیکون SVG inline — همیشه سفید */
.tab-icon--svg {
  filter: none !important;
  color: var(--text-primary);
  transition: color 0.2s, transform 0.15s;
}
.profile-tab.active .tab-icon--svg {
  color: var(--text-primary);
  transform: scale(1.05);
}
html.light .tab-icon--svg { color: var(--text-primary); }

/* متن تب — موبایل پنهان، دسکتاپ نمایش */
.tab-label {
  display: none;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

/* ═══════════════════════════════════════
   PANELS
═══════════════════════════════════════ */
.profile-panel { width: 100%; }

/* گرید ۳ ستونه */
.panel-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
}

/* سیو ۲ ستونه */
.panel-saved {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2px;
}

.grid-cell {
  aspect-ratio: 4/5;
  overflow: hidden;
  position: relative;
  background: var(--bg-surface);
  border-radius: 3px;
}
.grid-img { width: 100%; height: 100%; object-fit: cover; display: block; }

.cell-badge {
  position: absolute; top: 6px; right: 6px;
  color: #ffffff; font-size: 11px;
  text-shadow: 0 1px 3px rgba(0,0,0,0.65);
}
.saved-badge {
  position: absolute; top: 7px; left: 7px;
  filter: drop-shadow(0 1px 2px rgba(0,0,0,0.5));
}

/* ═══════════════════════════════════════
   PANEL: فایل‌ها
═══════════════════════════════════════ */
.storage-card {
  background: var(--bg-surface);
  border: 1px solid var(--border-subtle);
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 16px;
}
.storage-header {
  display: flex; justify-content: space-between; margin-bottom: 8px; direction: rtl;
}
.storage-title { font-size: 13px; font-weight: 700; color: var(--text-primary); }
.storage-used  { font-size: 11px; color: rgba(168,196,168,1); }
html.light .storage-used { color: #5a7a5a; }
.storage-bar {
  width: 100%; height: 6px;
  background: var(--border-subtle);
  border-radius: 99px; overflow: hidden;
}
.storage-fill {
  height: 100%;
  background: linear-gradient(to left, var(--green), #08a045);
  border-radius: 99px;
}
.storage-footer {
  display: flex; justify-content: space-between;
  margin-top: 6px; direction: rtl;
  font-size: 10px; color: rgba(168,196,168,1);
}
html.light .storage-footer { color: #5a7a5a; }
.storage-free { color: rgba(255,255,255,0.3); }
html.light .storage-free { color: rgba(0,0,0,0.25); }

.files-sub-tabs {
  display: flex; gap: 6px; margin-bottom: 14px;
}
.files-sub-tab {
  flex: 1; padding: 9px 8px; border-radius: 7px;
  border: 1px solid var(--border-subtle);
  background: var(--bg-surface);
  color: rgba(255,255,255,0.4);
  font-size: 13px; font-weight: 400; cursor: pointer;
  transition: background 0.2s, border-color 0.2s, color 0.2s;
}
html.light .files-sub-tab { color: rgba(0,0,0,0.35); }
.files-sub-tab.active {
  border-color: var(--green);
  background: rgba(11,191,83,0.1);
  color: var(--green);
  font-weight: 700;
}

.files-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2px;
  margin: 0 -16px;
  width: calc(100% + 32px);
}
.files-cell {
  aspect-ratio: 4/5;
  border-radius: 3px;
  overflow: hidden;
  background: var(--bg-surface);
  position: relative;
}

/* ═══════════════════════════════════════
   PANEL: همکاری در فروش
═══════════════════════════════════════ */
.referral-hero { text-align: center; margin-bottom: 20px; }
.referral-icon-wrap {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba(11,191,83,0.12);
  border: 1px solid rgba(11,191,83,0.25);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 12px;
}
.referral-title { font-size: 17px; font-weight: 700; color: var(--text-primary); margin: 0 0 6px; }
.referral-sub   { font-size: 12px; color: rgba(255,255,255,0.5); margin: 0; line-height: 1.7; }
html.light .referral-sub { color: rgba(0,0,0,0.45); }

.referral-desc {
  background: var(--bg-affiliate);
  border: 1px solid var(--border-affiliate);
  border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; direction: rtl;
}
.referral-desc p { font-size: 13px; color: #a8e6be; line-height: 1.9; margin: 0; }
html.light .referral-desc p { color: #1a6e3a; }

.referral-stats { display: flex; gap: 8px; margin-bottom: 20px; direction: rtl; }
.referral-stat {
  flex: 1; background: var(--bg-surface); border: 1px solid var(--border-subtle);
  border-radius: 9px; padding: 12px; text-align: center;
}
.rs-number { font-size: 20px; font-weight: 700; color: var(--green); margin: 0; }
.rs-label  { font-size: 11px; color: rgba(168,196,168,1); margin: 4px 0 0; }
html.light .rs-label { color: #5a7a5a; }

.referral-actions { display: flex; flex-direction: column; gap: 10px; }
.btn-referral-outline {
  width: 100%; padding: 13px;
  border-radius: 12px; border: 1px solid rgba(11,191,83,0.35);
  background: transparent; color: var(--green);
  font-size: 14px; font-weight: 500; cursor: pointer;
}

/* ═══════════════════════════════════════
   ICON FILTER (شب/روز)
═══════════════════════════════════════ */
.icon-filter { filter: brightness(0) invert(1); transition: filter 0.2s; }
html.light .icon-filter { filter: brightness(0) invert(0); }

.profile-page i[class*="fa-"] {
  font-family: "Font Awesome 6 Free" !important;
  font-weight: 900 !important;
  display: inline-block;
}

/* ═══════════════════════════════════════
   TABLET / DESKTOP — 640px+
   راست: آواتار + اطلاعات | چپ: آمار
═══════════════════════════════════════ */
@media (min-width: 640px) {

  /* utility */
  .show-desktop { display: inline-flex !important; }
  .hide-desktop { display: none !important; }

  .profile-page {
    max-width: 1100px;
    padding-bottom: 40px;
  }

  /* hero: flex-row RTL
     - اول در DOM = راست = hero-right-group (آواتار + اطلاعات)
     - دوم در DOM = چپ = hero-left-group (آمار + بنر)
  */
  .profile-hero {
    flex-direction: row;
    align-items: flex-start;
    justify-content: flex-start;
    padding: 40px 48px 28px;
    margin-top: 0;
    position: static;
    z-index: auto;
    gap: 32px;
  }

  /* گروه راست: آواتار + اطلاعات کنار هم */
  .hero-right-group {
    flex-direction: row;       /* RTL: avatar=right, info=left */
    align-items: flex-start;
    gap: 20px;
    flex: 1;
  }

  /* آواتار ۱۰٪ بزرگتر: 110px */
  .avatar-ring { width: 110px; height: 110px; }

  /* اطلاعات: راست‌چین (RTL start) */
  .profile-info {
    flex: 0 0 auto;
    align-items: flex-start;
    text-align: right;
    padding-top: 0;
    min-width: 220px;
  }

  .name-row { justify-content: flex-start; }
  .profile-name { font-size: 20px; }

  /* دکمه‌ها: همون اندازه موبایل، راست‌چین */
  .action-row {
    max-width: 380px;
    width: 100%;
    margin-top: 4px;
  }

  /* تنظیمات — در desktop پنهان (رفت به هدر) */
  .settings-wrap { display: none !important; }

  /* بنر موبایل — در desktop پنهان (داخل hero-left-group هست) */
  .promo-section { display: none; }

  /* ستون چپ: آمار + بنر — در desktop نمایش */
  .hero-left-group {
    display: flex;
    flex-direction: column;
    flex: 1;
    gap: 14px;
    align-items: stretch;
  }

  /* ستون آمار موبایل — پنهان */
  .stats-row { display: none !important; }

  /* تب‌ها — متن نمایش */
  .tab-label { display: inline; }
  .profile-tab { height: 48px; gap: 7px; flex: none; padding: 0 18px; }

  .promo-section { padding: 0 48px; }
  .tabs-section  { margin-top: 24px; }
  .profile-tabs  { padding: 0 48px; }

  .panel-grid  { grid-template-columns: repeat(4, 1fr); gap: 6px; }
  .panel-saved { grid-template-columns: repeat(3, 1fr); gap: 6px; }
  .files-grid  { grid-template-columns: repeat(4, 1fr); gap: 6px; margin: 0; width: 100%; }
}

/* ═══════════════════════════════════════
   DESKTOP — 1024px+
═══════════════════════════════════════ */
@media (min-width: 1024px) {
  .profile-page { max-width: 1200px; padding: 0 0 60px; }
  .profile-hero { padding: 44px 56px 32px; gap: 40px; }

  .avatar-ring  { width: 124px; height: 124px; }
  .hero-right-group { gap: 24px; }
  .profile-info { min-width: 260px; }
  .profile-name { font-size: 24px; }

  .tabs-section  { margin-top: 28px; }
  .promo-section { padding: 0 56px; }
  .profile-tabs  { padding: 0 56px; }

  .panel-grid  { grid-template-columns: repeat(5, 1fr); gap: 8px; }
  .panel-saved { grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .files-grid  { grid-template-columns: repeat(5, 1fr); gap: 8px; }
}

/* ═══════════════════════════════════════
   LARGE DESKTOP — 1280px+
═══════════════════════════════════════ */
@media (min-width: 1280px) {
  .profile-page  { max-width: 1320px; padding: 0 0 60px; }
  .profile-hero  { padding: 48px 72px 36px; }
  .promo-section { padding: 0 64px; }
  .profile-tabs  { padding: 0 64px; }
  .panel-grid    { grid-template-columns: repeat(6, 1fr); }
  .files-grid    { grid-template-columns: repeat(6, 1fr); }
}

</style>
@endpush

@push('scripts')
<script>
(function () {

  /* ───── Theme Toggle ───── */
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      window.vatanToggleTheme && window.vatanToggleTheme();
    });
  }

  /* ───── Settings Dropdown ───── */
  var settingsBtn  = document.getElementById('settingsBtn');
  var settingsMenu = document.getElementById('settingsMenu');
  var menuOpen     = false;

  function openSettings() {
    settingsMenu.style.display = 'block';
    settingsBtn.setAttribute('aria-expanded', 'true');
    menuOpen = true;
  }

  function closeSettings() {
    settingsMenu.style.display = 'none';
    settingsBtn.setAttribute('aria-expanded', 'false');
    menuOpen = false;
  }

  settingsBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    menuOpen ? closeSettings() : openSettings();
  });

  document.addEventListener('click', function (e) {
    if (menuOpen && !settingsMenu.contains(e.target) && e.target !== settingsBtn) {
      closeSettings();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && menuOpen) closeSettings();
  });

  /* ───── Main Tabs ───── */
  var tabs   = document.querySelectorAll('.profile-tab');
  var panels = document.querySelectorAll('.profile-panel');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');

      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');

      panels.forEach(function (panel) {
        var key  = panel.getAttribute('data-panel');
        var show = key === target;

        if (key === 'grid') {
          panel.style.display = show ? 'grid' : 'none';
        } else if (key === 'saved') {
          panel.style.display = show ? 'grid' : 'none';
        } else {
          panel.style.display = show ? 'block' : 'none';
        }
      });
    });
  });

  /* ───── Files Sub-Tabs ───── */
  document.querySelectorAll('.files-sub-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.files-sub-tab').forEach(function (b) {
        b.classList.remove('active');
      });
      btn.classList.add('active');
      var sub = btn.getAttribute('data-sub');
      document.getElementById('files-created').style.display  = sub === 'created'  ? 'grid' : 'none';
      document.getElementById('files-personal').style.display = sub === 'personal' ? 'grid' : 'none';
    });
  });

}());
</script>
@endpush
