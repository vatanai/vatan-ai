@extends('layouts.app')

@section('content')
<div class="profile-page" dir="rtl">

  {{-- ===== HEADER (اسکرول می‌شه — نه fixed) ===== --}}
  <section class="profile-header">
    <div class="profile-header-wrap">
      <button id="menuOpenBtn" type="button" class="p-header-btn">
        <img src="{{ asset('assets/img/icons/hamburger.svg') }}" width="26" height="26" class="floating-icon">
      </button>
      <div style="display:flex;align-items:center;gap:8px;">
        <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" style="width:31px;height:31px;display:block;">
        <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" style="width:77px;height:auto;display:block;">
      </div>
    </div>
  </section>

  {{-- ===== SECTION: آواتار + آمار ===== --}}
  <section style="padding:20px 16px 8px 16px;">
    <div style="display:flex;flex-direction:row;align-items:center;gap:16px;direction:ltr;">

      {{-- Avatar + Name + Phone + Plan --}}
      <div style="display:flex;flex-direction:column;align-items:center;gap:8px;flex-shrink:0;">
        <div class="avatar-ring">
          <div class="avatar-inner">
            <img src="https://i.pravatar.cc/150?img=12" alt="avatar" class="avatar-img">
          </div>
        </div>
        <div style="text-align:center;">
          <p class="profile-name">محسن آقاجانی</p>
          <p class="profile-phone" dir="ltr">۰۹۱۲۰۰۰۰۰۰۰</p>
          <div class="plan-badge">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="#0BBF53"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
            <span>پلن رایگان</span>
          </div>
        </div>
      </div>

      {{-- Stats --}}
      <div style="display:flex;flex:1;justify-content:space-around;align-items:center;direction:rtl;">
        <div class="stat-col">
          <span class="profile-stat-number">۳۵</span>
          <span class="profile-stat-label">پست</span>
        </div>
        <div class="stat-col">
          <span class="profile-stat-number">۱۸۱۰</span>
          <span class="profile-stat-label">ساخته‌شده</span>
        </div>
        <div class="stat-col">
          <span class="profile-stat-number">۱۴</span>
          <span class="profile-stat-label">روز عضویت</span>
        </div>
      </div>

    </div>
  </section>

  {{-- ===== SECTION: دکمه‌های اکشن (طراحی جدید) ===== --}}
  <section style="padding:0 16px;margin-top:14px;">

    {{-- ردیف اول: خرید اشتراک (برجسته) --}}
    <button type="button" class="btn-subscribe">
      <span>خرید اشتراک ویژه</span>
      <span class="btn-subscribe-badge">۱۵٪ تخفیف</span>
    </button>

    {{-- ردیف دوم: پشتیبانی + تنظیمات --}}
    <div style="display:flex;gap:8px;margin-top:8px;direction:rtl;">
      <button type="button" class="btn-secondary" style="flex:1;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        پشتیبانی
      </button>
      <button type="button" class="btn-secondary btn-icon" aria-label="تنظیمات" data-card="settings">
        <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="17" height="17" class="floating-icon">
      </button>
    </div>

  </section>

  {{-- ===== SECTION: تب‌های ۴ گانه ===== --}}
  <section style="margin-top:20px;">

    <div class="profile-tabs" dir="ltr">
      <button type="button" class="profile-tab active" data-tab="grid" aria-label="گرید">
        <img src="{{ asset('assets/img/icons/fi-sr-grid.svg') }}" class="tab-icon" width="19" height="19" alt="">
      </button>
      <button type="button" class="profile-tab" data-tab="files" aria-label="فایل‌ها">
        <img src="{{ asset('assets/img/icons/fi-sr-file.svg') }}" class="tab-icon" width="19" height="19" alt="">
      </button>
      <button type="button" class="profile-tab" data-tab="referral" aria-label="همکاری در فروش">
        <svg class="tab-icon tab-icon--svg" width="19" height="19" viewBox="0 0 24 24" fill="currentColor">
          <path d="M16 11C17.66 11 18.99 9.66 18.99 8S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05C16.19 13.89 17 15.02 17 16.5V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
        </svg>
      </button>
      <button type="button" class="profile-tab" data-tab="saved" aria-label="ذخیره‌شده‌ها">
        <img src="{{ asset('assets/img/icons/fi-sr-bookmark.svg') }}" class="tab-icon" width="19" height="19" alt="">
      </button>
    </div>

    {{-- ===== PANEL: گرید ===== --}}
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

    <div class="profile-panel" data-panel="grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:2px;">
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

      <div id="filesSubTabs" style="display:flex;gap:6px;margin-bottom:14px;direction:rtl;">
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

      <div style="text-align:center;margin-bottom:20px;">
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

      <div style="display:flex;flex-direction:column;gap:10px;">
        <button type="button" class="btn-subscribe" style="margin:0;">شروع همکاری</button>
        <button type="button" class="btn-referral-outline">دریافت مشاوره رایگان</button>
      </div>

    </div>

    {{-- ===== PANEL: سیو ===== --}}
    <div class="profile-panel" data-panel="saved" style="display:none;gap:2px;grid-template-columns:repeat(2,1fr);">
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

  {{-- ===== HAMBURGER DROPDOWN ===== --}}
  <div id="menuOverlay" style="display:none;position:fixed;inset:0;z-index:160;" onclick="if(event.target===this){closeMenu();}">
    <div id="menuSheet" style="position:absolute;top:74px;right:12px;width:296px;background:#111116;border:1px solid #222230;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:scale(0.9) translateY(-10px);opacity:0;transition:transform 0.2s ease,opacity 0.2s ease;transform-origin:top right;">

      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid #222230;">
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:38px;height:38px;border-radius:50%;overflow:hidden;flex-shrink:0;">
            <img src="https://i.pravatar.cc/150?img=12" style="width:100%;height:100%;object-fit:cover;">
          </div>
          <div>
            <p style="margin:0;font-size:13px;font-weight:700;color:#ffffff;">محسن آقاجانی</p>
            <p style="margin:2px 0 0 0;font-size:11px;color:#a8c4a8;" dir="ltr">۰۹۱۲۰۰۰۰۰۰۰</p>
          </div>
        </div>
        <button id="theme-toggle" type="button" class="theme-toggle-btn" aria-label="تغییر تم">
          <span class="theme-toggle-track">
            <span class="theme-toggle-thumb">
              <svg class="theme-icon-moon" width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
              <svg class="theme-icon-sun" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4.5"/>
                <line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/>
                <line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/><line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                <line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/>
                <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/><line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/>
              </svg>
            </span>
          </span>
        </button>
      </div>

      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;border-bottom:1px solid #222230;" onmouseover="this.style.background='#16161c'" onmouseout="this.style.background='transparent'">
        <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="16" height="16" class="floating-icon">
        <span style="font-size:13px;color:#ffffff;">تنظیمات</span>
      </div>

      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;" onmouseover="this.style.background='#16161c'" onmouseout="this.style.background='transparent'">
        <i class="fa-solid fa-right-from-bracket" style="font-size:14px;color:#f05c5c;width:16px;text-align:center;"></i>
        <span style="font-size:13px;color:#f05c5c;">خروج</span>
      </div>

    </div>
  </div>

</div>
@endsection

@push('styles')
<style>

  /* ===== BASE ===== */
  html, body { overflow-x: hidden; background: #000000; }
  html.light, html.light body { background: #ffffff; }

  .profile-page {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    background: var(--bg, #000000);
    min-height: 100vh;
    padding-bottom: 120px;
  }

  /* ===== HEADER — اسکرول می‌شه ===== */
  .profile-header {
    background: var(--bg, #000000);
    padding: calc(env(safe-area-inset-top) + 12px) 16px 14px 16px;
    display: flex;
    align-items: center;
  }

  .profile-header-wrap { display: flex; align-items: center; gap: 12px; }

  .p-header-btn {
    width: 36px; height: 36px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: transparent; border: none; cursor: pointer;
  }

  /* ===== AVATAR ===== */
  .avatar-ring {
    width: 99px; height: 99px; border-radius: 50%;
    padding: 3px; background: #0BBF53;
  }
  .avatar-inner {
    width: 100%; height: 100%; border-radius: 50%;
    padding: 2px; background: var(--bg, #000000);
  }
  .avatar-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }

  /* ===== NAME / PHONE / PLAN ===== */
  .profile-name  { margin: 0; font-size: 16px; font-weight: 700; color: var(--text, #ffffff); }
  .profile-phone { margin: 3px 0 0; font-size: 14px; color: #a8c4a8; }

  .plan-badge {
    display: inline-flex; align-items: center; gap: 4px;
    margin-top: 6px;
    background: rgba(11,191,83,0.12);
    border: 1px solid rgba(11,191,83,0.3);
    border-radius: 8px;
    padding: 4px 10px;
  }
  .plan-badge span { font-size: 11px; font-weight: 700; color: #0BBF53; }

  /* ===== STATS ===== */
  .stat-col { display: flex; flex-direction: column; align-items: center; gap: 4px; }
  .profile-stat-number { font-size: 22px; font-weight: 700; color: var(--text, #ffffff); }
  .profile-stat-label  { font-size: 12px; color: #a8c4a8; }

  /* ===== BUTTONS ===== */
  .btn-subscribe {
    display: flex; align-items: center; justify-content: center; gap: 10px;
    width: 100%; padding: 13px 16px;
    border-radius: 12px; border: none;
    background: #0BBF53; color: #ffffff;
    font-size: 15px; font-weight: 700; cursor: pointer;
  }
  .btn-subscribe-badge {
    background: #e91e8c; color: #ffffff;
    font-size: 10px; font-weight: 700;
    padding: 2px 8px; border-radius: 6px; white-space: nowrap;
  }

  .btn-secondary {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    padding: 11px 16px; border-radius: 12px;
    border: 1px solid var(--b1, #222230);
    background: var(--s1, #111116);
    color: var(--text, #ffffff);
    font-size: 14px; font-weight: 500; cursor: pointer;
  }
  .btn-icon { width: 44px; height: 44px; padding: 0; flex-shrink: 0; }

  .btn-referral-outline {
    width: 100%; padding: 13px;
    border-radius: 12px; border: 1px solid rgba(11,191,83,0.35);
    background: transparent; color: #0BBF53;
    font-size: 14px; font-weight: 500; cursor: pointer;
  }

  /* ===== TABS ===== */
  .profile-tabs {
    display: flex; align-items: center;
    border-bottom: 1px solid var(--b1, #222230);
  }
  .profile-tab {
    flex: 1; height: 44px;
    display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; cursor: pointer; position: relative;
  }
  .profile-tab::after {
    content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
    height: 2px; background: #0BBF53; opacity: 0; transition: opacity 0.2s;
  }
  .profile-tab.active::after { opacity: 1; }

  .tab-icon { filter: brightness(0) invert(1); transition: filter 0.2s, transform 0.15s; }
  .profile-tab.active .tab-icon { filter: brightness(0) invert(1); transform: scale(1.08); }

  .tab-icon--svg { filter: none; color: #ffffff; }
  .profile-tab.active .tab-icon--svg { color: #ffffff; transform: scale(1.08); }

  html.light .tab-icon { filter: brightness(0) invert(0); }
  html.light .profile-tab.active .tab-icon { filter: brightness(0) invert(0); }
  html.light .tab-icon--svg { color: #000000; }

  /* ===== IMAGE CELLS — border-radius مثل home ===== */
  .profile-panel { width: 100%; }

  .grid-cell {
    aspect-ratio: 4/5; overflow: hidden; position: relative;
    background: #111116; border-radius: 4px;
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

  /* ===== FILES ===== */
  .storage-card {
    background: var(--s1, #111116);
    border: 1px solid var(--b1, #222230);
    border-radius: 12px; padding: 14px 16px; margin-bottom: 16px;
  }
  .storage-header { display: flex; justify-content: space-between; margin-bottom: 8px; direction: rtl; }
  .storage-title  { font-size: 13px; font-weight: 700; color: var(--text, #ffffff); }
  .storage-used   { font-size: 11px; color: #a8c4a8; }
  .storage-bar { width: 100%; height: 6px; background: var(--b1, #222230); border-radius: 99px; overflow: hidden; }
  .storage-fill { height: 100%; background: linear-gradient(to left, #0BBF53, #08a045); border-radius: 99px; }
  .storage-footer { display: flex; justify-content: space-between; margin-top: 6px; direction: rtl; font-size: 10px; color: #a8c4a8; }
  .storage-free { color: rgba(255,255,255,0.35); }

  .files-sub-tab {
    flex: 1; padding: 9px 8px; border-radius: 8px;
    border: 1px solid var(--b1, #222230);
    background: var(--s1, #111116);
    color: rgba(255,255,255,0.4);
    font-size: 13px; font-weight: 400; cursor: pointer;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
  }
  .files-sub-tab.active {
    border-color: #0BBF53;
    background: rgba(11,191,83,0.12);
    color: #0BBF53; font-weight: 700;
  }

  .files-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 6px; }
  .files-cell { aspect-ratio: 4/5; border-radius: 4px; overflow: hidden; background: #111116; }

  /* ===== REFERRAL ===== */
  .referral-icon-wrap {
    width: 64px; height: 64px; border-radius: 50%;
    background: rgba(11,191,83,0.12); border: 1px solid rgba(11,191,83,0.25);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 12px;
  }
  .referral-title { font-size: 17px; font-weight: 700; color: var(--text, #ffffff); margin: 0 0 6px; }
  .referral-sub   { font-size: 12px; color: rgba(255,255,255,0.5); margin: 0; line-height: 1.7; }
  .referral-desc  {
    background: var(--affiliate-bg, #0d2818); border: 1px solid var(--affiliate-border, #1a5c32);
    border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; direction: rtl;
  }
  .referral-desc p { font-size: 13px; color: #a8e6be; line-height: 1.9; margin: 0; }

  .referral-stats { display: flex; gap: 8px; margin-bottom: 20px; direction: rtl; }
  .referral-stat {
    flex: 1; background: var(--s1, #111116); border: 1px solid var(--b1, #222230);
    border-radius: 10px; padding: 12px; text-align: center;
  }
  .rs-number { font-size: 20px; font-weight: 700; color: #0BBF53; margin: 0; }
  .rs-label  { font-size: 11px; color: #a8c4a8; margin: 4px 0 0; }

  /* ===== THEME TOGGLE ===== */
  .theme-toggle-btn { background: transparent; border: none; cursor: pointer; padding: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
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

  /* ===== MISC ===== */
  .floating-icon { filter: brightness(0) invert(1); transition: filter 0.2s; }
  html.light .floating-icon { filter: brightness(0) invert(0); }
  html.light .btn-secondary { background: var(--s1, #eeeeee) !important; border-color: var(--b1, #dddddd) !important; }

  .profile-page i[class*="fa-"] {
    font-family: "Font Awesome 6 Free" !important;
    font-weight: 900 !important; display: inline-block;
  }

</style>
@endpush

@push('scripts')
<script>
(function () {

  document.getElementById('theme-toggle').addEventListener('click', function () { vatanToggleTheme(); });

  var menuOpenBtn = document.getElementById('menuOpenBtn');
  var menuOverlay = document.getElementById('menuOverlay');
  var menuSheet   = document.getElementById('menuSheet');
  var menuOpen    = false;

  function openMenu() {
    menuOverlay.style.display = 'block';
    setTimeout(function () {
      menuSheet.style.transform = 'scale(1) translateY(0)';
      menuSheet.style.opacity   = '1';
    }, 10);
    menuOpen = true;
  }

  function closeMenu() {
    menuSheet.style.transform = 'scale(0.9) translateY(-10px)';
    menuSheet.style.opacity   = '0';
    setTimeout(function () { menuOverlay.style.display = 'none'; }, 200);
    menuOpen = false;
  }

  menuOpenBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    menuOpen ? closeMenu() : openMenu();
  });

  document.addEventListener('click', function (e) {
    if (menuOpen && !menuSheet.contains(e.target)) closeMenu();
  });

  /* ---- Main Tabs ---- */
  var tabs   = document.querySelectorAll('.profile-tab');
  var panels = document.querySelectorAll('.profile-panel');

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var target = tab.getAttribute('data-tab');
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');
      panels.forEach(function (panel) {
        var key = panel.getAttribute('data-panel');
        var show = key === target;
        if (key === 'grid' || key === 'saved') {
          panel.style.display = show ? 'grid' : 'none';
        } else {
          panel.style.display = show ? 'block' : 'none';
        }
      });
    });
  });

  /* ---- Files Sub Tabs ---- */
  document.querySelectorAll('.files-sub-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.files-sub-tab').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      var sub = btn.getAttribute('data-sub');
      document.getElementById('files-created').style.display  = sub === 'created'  ? 'grid' : 'none';
      document.getElementById('files-personal').style.display = sub === 'personal' ? 'grid' : 'none';
    });
  });

}());
</script>
@endpush
