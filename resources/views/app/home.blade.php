@extends('layouts.app')

@section('content')
<div class="home-page" dir="rtl">

  {{-- ===== SECTION 1: لوگو ===== --}}
  <section class="home-logo">
    <div class="home-logo-wrap" style="gap:12px;">
      <button id="menuOpenBtn" type="button" style="width:36px;height:36px;flex-shrink:0;display:flex;align-items:center;justify-content:center;background:transparent;border:none;cursor:pointer;">
        <img src="{{ asset('assets/img/icons/hamburger.svg') }}" width="26" height="26" class="floating-icon">
      </button>
      <div style="display:flex;align-items:center;gap:8px;">
        <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" style="width:31px;height:31px;display:block;">
        <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" style="width:77px;height:auto;display:block;">
      </div>
    </div>
    <div style="position:relative; display:inline-block; margin-top:10px;">

      <div style="
        background: #1e1e1e;
        border-radius: 9px;
        padding: 6.84px 13.86px 6.84px 13.86px;
        font-size: 11.7px;
        font-weight: 400;
        color: #ffffff;
        white-space: nowrap;
      ">خرید ویژه</div>

      <div style="
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: #E8326A;
        border-radius: 6px;
        padding: 1.9px 8px;
        font-size: 10px;
        font-weight: 800;
        color: #ffffff;
        white-space: nowrap;
        width: fit-content;
      ">۱۵٪ تخفیف</div>

    </div>
  </section>

  {{-- ===== HAMBURGER DROPDOWN MENU ===== --}}
  <div id="menuOverlay" style="display:none;position:fixed;inset:0;z-index:160;" onclick="if(event.target===this){closeMenu();}">
    <div id="menuSheet" style="position:absolute;top:calc(env(safe-area-inset-top) + 136px);right:12px;width:296px;background:#111116;border:1px solid #222230;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,0.5);transform:scale(0.9) translateY(-10px);opacity:0;transition:transform 0.2s ease,opacity 0.2s ease;transform-origin:top right;">

      {{-- عکس + اسم + تغییر تم --}}
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
              {{-- ماه --}}
              <svg class="theme-icon-moon" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
              </svg>
              {{-- خورشید --}}
              <svg class="theme-icon-sun" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="4.5"/>
                <line x1="12" y1="2" x2="12" y2="5"/>
                <line x1="12" y1="19" x2="12" y2="22"/>
                <line x1="4.22" y1="4.22" x2="6.34" y2="6.34"/>
                <line x1="17.66" y1="17.66" x2="19.78" y2="19.78"/>
                <line x1="2" y1="12" x2="5" y2="12"/>
                <line x1="19" y1="12" x2="22" y2="12"/>
                <line x1="4.22" y1="19.78" x2="6.34" y2="17.66"/>
                <line x1="17.66" y1="6.34" x2="19.78" y2="4.22"/>
              </svg>
            </span>
          </span>
        </button>
      </div>

      {{-- تنظیمات --}}
      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;border-bottom:1px solid #222230;" onmouseover="this.style.background='#16161c'" onmouseout="this.style.background='transparent'">
        <img src="{{ asset('assets/img/icons/fi-sr-settings.svg') }}" width="16" height="16" class="floating-icon">
        <span style="font-size:13px;color:#ffffff;">تنظیمات</span>
      </div>

      {{-- خروج --}}
      <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;" onmouseover="this.style.background='#16161c'" onmouseout="this.style.background='transparent'">
        <i class="fa-solid fa-right-from-bracket" style="font-size:14px;color:#f05c5c;width:16px;text-align:center;"></i>
        <span style="font-size:13px;color:#f05c5c;">خروج</span>
      </div>

    </div>
  </div>

  {{-- ===== SECTION 2: خوش‌آمدگویی هوشمند ===== --}}
  <section class="home-greeting">
    <p class="home-greeting-title">سلام، خوش اومدی</p>
    <p class="home-greeting-sub">می‌خوای چی خلق کنی؟</p>
  </section>

  {{-- ===== SECTION 3: نوار جستجوی هوشمند ===== --}}
  <section class="home-search">
    <div class="home-search-card">

      <div class="home-search-inner">
        <input type="text" dir="rtl" class="home-search-input search-input" placeholder="فقط بنویس دنبال چی هستی ، همین">
        <div class="home-search-send-row">
          <p class="home-search-hint">بیش از ۱۲۰۰ طرح آماده و ۷۰ مدل هوش مصنوعی در اختیار توست</p>
          <button type="button" class="home-search-send" aria-label="ارسال">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="7" stroke="white" stroke-width="2.5"/>
              <line x1="16.5" y1="16.5" x2="22" y2="22" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            </svg>
          </button>
        </div>
      </div>

      <div class="home-chips">
        <span class="home-chip">
          <span class="home-chip-line1">ساخت عکس</span>
          <span class="home-chip-line2">پرتره • فشن • تولد</span>
        </span>
        <span class="home-chip">
          <span class="home-chip-line1">ساخت ویدیو</span>
          <span class="home-chip-line2">ریلز • تبلیغاتی</span>
        </span>
        <span class="home-chip">
          <span class="home-chip-line1">کسب و کار</span>
          <span class="home-chip-line2">عکاس • زیبایی • برند</span>
        </span>
        <span class="home-chip">
          <span class="home-chip-line1">ترندهای امروز</span>
          <span class="home-chip-line2">محبوب‌ترین‌ها</span>
        </span>
      </div>

    </div>
  </section>

  {{-- ===== SECTION 4: محصولات پرطرفدار (داینامیک از دیتابیس) ===== --}}
  <section class="home-products">

    <div class="home-section-title">
      <span class="home-section-title-right">محصولات پرطرفدار</span>
    </div>
    <p class="home-products-subtitle">عکس‌ها و ویدیوهای خیره‌کننده خلق کنید...</p>

    {{-- ----- ردیف ۱: ترندهای امروز ----- --}}
    @if ($trending->isNotEmpty())
    <div class="home-section-title home-section-title--sub">
      <div>
        <span class="home-section-title-right">ترندهای امروز</span>
        <p class="home-section-title-caption">پراستفاده ترین سبک ها</p>
      </div>
      <button type="button" class="home-section-viewall">مشاهده همه</button>
    </div>

    <div class="home-cards-scroll">
      @foreach ($trending as $product)
        <a class="home-card" href="{{ route('app.product', $product->slug) }}" style="background-image: url('{{ $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/img/placeholder.webp') }}');">
          <div class="home-card-overlay"></div>
          <i class="fa-solid {{ $product->media_type === 'video' ? 'fa-video' : 'fa-image' }} home-card-badge-type"></i>
          @if ($product->is_featured)
            <i class="fa-solid fa-crown home-card-badge-tier"></i>
          @elseif ($product->is_new)
            <i class="fa-solid fa-bolt home-card-badge-tier"></i>
          @endif
          <div class="home-card-info">
            <p class="home-card-name">{{ $product->name_fa }}</p>
            <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
          </div>
        </a>
      @endforeach
    </div>
    @endif

    {{-- ----- ردیف ۲: کسب و کار ----- --}}
    @if ($business->isNotEmpty())
    <div class="home-section-title home-section-title--sub home-section-title--biz">
      <div>
        <span class="home-section-title-right">کسب و کار</span>
        <p class="home-section-title-caption">محتوا برای برندها</p>
      </div>
      <button type="button" class="home-section-viewall">مشاهده همه</button>
    </div>

    <div class="home-cards-scroll">
      @foreach ($business as $product)
        <a class="home-card" href="{{ route('app.product', $product->slug) }}" style="background-image: url('{{ $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/img/placeholder.webp') }}');">
          <div class="home-card-overlay"></div>
          <i class="fa-solid {{ $product->media_type === 'video' ? 'fa-video' : 'fa-image' }} home-card-badge-type"></i>
          @if ($product->is_featured)
            <i class="fa-solid fa-crown home-card-badge-tier"></i>
          @elseif ($product->is_new)
            <i class="fa-solid fa-bolt home-card-badge-tier"></i>
          @endif
          <div class="home-card-info">
            <p class="home-card-name">{{ $product->name_fa }}</p>
            <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
          </div>
        </a>
      @endforeach
    </div>
    @endif

    {{-- ----- ردیف ۳: پرتره سینمایی ----- --}}
    @if ($portrait->isNotEmpty())
    <div class="home-section-title home-section-title--sub home-section-title--biz">
      <div>
        <span class="home-section-title-right">پرتره سینمایی</span>
        <p class="home-section-title-caption">نورپردازی درام و اتمسفر</p>
      </div>
      <button type="button" class="home-section-viewall">مشاهده همه</button>
    </div>

    <div class="home-cards-scroll">
      @foreach ($portrait as $product)
        <a class="home-card" href="{{ route('app.product', $product->slug) }}" style="background-image: url('{{ $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/img/placeholder.webp') }}');">
          <div class="home-card-overlay"></div>
          <i class="fa-solid {{ $product->media_type === 'video' ? 'fa-video' : 'fa-image' }} home-card-badge-type"></i>
          @if ($product->is_featured)
            <i class="fa-solid fa-crown home-card-badge-tier"></i>
          @elseif ($product->is_new)
            <i class="fa-solid fa-bolt home-card-badge-tier"></i>
          @endif
          <div class="home-card-info">
            <p class="home-card-name">{{ $product->name_fa }}</p>
            <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
          </div>
        </a>
      @endforeach
    </div>
    @endif

    {{-- ----- ردیف ۴: عکاسی فشن ----- --}}
    @if ($fashion->isNotEmpty())
    <div class="home-section-title home-section-title--sub home-section-title--biz">
      <div>
        <span class="home-section-title-right">عکاسی فشن</span>
        <p class="home-section-title-caption">استایل و مد روز</p>
      </div>
      <button type="button" class="home-section-viewall">مشاهده همه</button>
    </div>

    <div class="home-cards-scroll">
      @foreach ($fashion as $product)
        <a class="home-card" href="{{ route('app.product', $product->slug) }}" style="background-image: url('{{ $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/img/placeholder.webp') }}');">
          <div class="home-card-overlay"></div>
          <i class="fa-solid {{ $product->media_type === 'video' ? 'fa-video' : 'fa-image' }} home-card-badge-type"></i>
          @if ($product->is_featured)
            <i class="fa-solid fa-crown home-card-badge-tier"></i>
          @elseif ($product->is_new)
            <i class="fa-solid fa-bolt home-card-badge-tier"></i>
          @endif
          <div class="home-card-info">
            <p class="home-card-name">{{ $product->name_fa }}</p>
            <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
          </div>
        </a>
      @endforeach
    </div>
    @endif

    {{-- ----- ردیف ۵: ریلز و ویدیو ----- --}}
    @if ($videos->isNotEmpty())
    <div class="home-section-title home-section-title--sub home-section-title--biz">
      <div>
        <span class="home-section-title-right">ریلز و ویدیو</span>
        <p class="home-section-title-caption">محتوای ویدیویی هوشمند</p>
      </div>
      <button type="button" class="home-section-viewall">مشاهده همه</button>
    </div>

    <div class="home-cards-scroll">
      @foreach ($videos as $product)
        <a class="home-card" href="{{ route('app.product', $product->slug) }}" style="background-image: url('{{ $product->thumbnail ? asset('storage/' . $product->thumbnail) : asset('assets/img/placeholder.webp') }}');">
          <div class="home-card-overlay"></div>
          <i class="fa-solid {{ $product->media_type === 'video' ? 'fa-video' : 'fa-image' }} home-card-badge-type"></i>
          @if ($product->is_featured)
            <i class="fa-solid fa-crown home-card-badge-tier"></i>
          @elseif ($product->is_new)
            <i class="fa-solid fa-bolt home-card-badge-tier"></i>
          @endif
          <div class="home-card-info">
            <p class="home-card-name">{{ $product->name_fa }}</p>
            <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
          </div>
        </a>
      @endforeach
    </div>
    @endif

  </section>

</div>
@endsection

@push('styles')
<style>
  html, body { background: #000000; overflow-x: hidden; }
  html.light, html.light body { background: #ffffff; }
  :root { --bg: #000000; }

  .floating-icon {
    transition: filter 0.2s ease;
    filter: brightness(0) invert(1);
  }

  html.light .floating-icon {
    filter: brightness(0) invert(0);
  }

  /* ===== دکمه روز/شب ===== */
  .theme-toggle-btn {
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .theme-toggle-track {
    display: flex;
    align-items: center;
    width: 46px;
    height: 26px;
    border-radius: 99px;
    background: #1a1a2e;
    border: 1px solid rgba(255,255,255,0.12);
    padding: 3px;
    transition: background 0.3s ease, border-color 0.3s ease;
    position: relative;
  }

  html.light .theme-toggle-track {
    background: #e8f0fe;
    border-color: rgba(0,0,0,0.1);
  }

  .theme-toggle-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), background 0.3s ease;
    transform: translateX(0);
    position: absolute;
    left: 3px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.3);
  }

  html.light .theme-toggle-thumb {
    transform: translateX(20px);
    background: #f59e0b;
  }

  .theme-icon-moon { color: #1a1a2e; display: block; }
  .theme-icon-sun  { color: #ffffff; display: none; }
  html.light .theme-icon-moon { display: none; }
  html.light .theme-icon-sun  { display: block; }

  .home-page {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
    min-height: 100vh;
    padding: calc(env(safe-area-inset-top) + 180px) 16px 120px 16px;
    direction: rtl;
    font-family: inherit;
  }

  .home-logo {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    max-width: 480px;
    margin: 0 auto;
    background: #000000;
    z-index: 150;
    padding: calc(env(safe-area-inset-top) + 100px) 16px 18px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
  }

  html.light .home-logo {
    background: #ffffff;
  }

  .home-logo-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
  }


  .home-greeting {
    margin-top: 36px;
    margin-bottom: 12px;
    text-align: center;
    direction: rtl;
  }

  .home-greeting-title {
    margin: 0;
    font-family: inherit;
    font-weight: 800;
    font-size: 20px;
    color: #ffffff;
  }

  .home-greeting-sub {
    margin: 2px 0 0 0;
    font-family: inherit;
    font-weight: 400;
    font-size: 13px;
    color: #ffffff;
    opacity: 0.6;
  }

  .home-search {
    margin-top: 0;
  }

  .home-search-card {
    width: 100%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .home-search-inner {
    width: 100%;
    min-height: 72px;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 10px 12px;
  }

  .home-search-input {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    padding: 0;
    font-size: 16px;
    color: #ffffff;
    font-family: inherit;
    direction: rtl;
  }

  .home-search-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
  }

  .search-input::placeholder {
    color: #ffffff;
    font-size: 14px;
  }

  .home-search-hint {
    margin: 0 0 6px 0;
    font-size: 11px;
    color: rgba(255, 255, 255, 0.4);
    direction: rtl;
  }

  .home-search-send-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    direction: rtl;
    margin-top: 6px;
  }

  .home-search-send {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    background: #0BBF53;
    border-radius: 10px;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .home-search-send i {
    color: #ffffff;
    font-size: 14px;
  }

  .home-chips {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    direction: rtl;
    gap: 8px;
  }

  .home-chip {
    width: 100%;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 9px;
    padding: 7.2px 4px;
    text-align: center;
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
  }

  .home-chip-line1 {
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;
  }

  .home-chip-line2 {
    margin-top: 2px;
    font-size: 10px;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.5);
  }

  /* ===== LIGHT MODE — باکس سرچ ===== */
  html.light .home-search-card {
    background: rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.1);
  }
  html.light .home-search-inner {
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.1);
  }
  html.light .home-search-input {
    color: #000000;
  }
  html.light .home-search-input::placeholder,
  html.light .search-input::placeholder {
    color: rgba(0, 0, 0, 0.45);
    font-size: 14px;
  }
  html.light .home-search-hint {
    color: rgba(0, 0, 0, 0.4);
  }
  html.light .home-chip {
    background: rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.1);
  }
  html.light .home-chip-line1 {
    color: #000000;
  }
  html.light .home-chip-line2 {
    color: rgba(0, 0, 0, 0.5);
  }
  html.light .home-section-title-right {
    color: #000000;
  }
  html.light .home-section-title-caption {
    color: rgba(0, 0, 0, 0.5);
  }
  html.light .home-greeting-title {
    color: #000000;
  }
  html.light .home-greeting-sub {
    color: #000000;
  }
  html.light .home-section-viewall {
    background: rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.1);
    color: #000000;
  }
  html.light .home-products-subtitle {
    color: #555555;
  }

  .home-products {
    padding-bottom: 120px;
  }

  .home-products > .home-section-title:first-child {
    margin-top: 47px;
  }

  .home-section-title {
    margin-top: 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    direction: rtl;
  }

  .home-section-viewall {
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 6.48px;
    padding: 4px 10px;
    font-size: 10.45px;
    font-weight: 300;
    line-height: 1.2;
    color: #ffffff;
    font-family: inherit;
    cursor: pointer;
    white-space: nowrap;
  }

  .home-section-title--biz {
    margin-top: 28px;
  }

  .home-section-title-right {
    font-size: 15px;
    font-weight: 700;
    color: #ffffff;
  }

  .home-section-title-caption {
    margin: 2px 0 0 0;
    font-size: 10px;
    font-weight: 400;
    color: rgba(255, 255, 255, 0.5);
  }

  .home-section-title-left {
    font-size: 13px;
    color: #0BBF53;
  }

  .home-products-subtitle {
    margin: 4px 0 0 0;
    font-size: 12px;
    color: #8a8a8a;
    direction: rtl;
    text-align: right;
  }

  .home-section-title:not(.home-section-title--sub) .home-section-title-right {
    font-family: inherit;
    font-weight: 700;
  }

  .home-section-title--sub:not(.home-section-title--biz) .home-section-title-right {
    font-family: inherit;
    font-weight: 700;
  }

  .home-cards-scroll {
    display: flex;
    flex-direction: row;
    gap: 10px;
    overflow-x: auto;
    overflow-y: visible;
    scrollbar-width: none;
    padding: 10px 0 14px 0;
    direction: rtl;
    margin: 2px -16px 0 -16px;
    width: calc(100% + 32px);
    isolation: isolate;
  }

  .home-cards-scroll::-webkit-scrollbar {
    display: none;
  }

  .home-cards-stack {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 12px;
  }

  .home-card {
    aspect-ratio: 4 / 5;
    border-radius: 4px;
    overflow: hidden;
    position: relative;
    background-size: cover;
    background-position: center;
    cursor: pointer;
    /* ===== افکت هاور: انیمیشن نرم بزرگ‌نمایی + سایه ===== */
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s ease;
    will-change: transform;
    transform-origin: center center;
    z-index: 0;
  }

  .home-card:hover {
    transform: scale(1.035) translateY(-2px);
    box-shadow: 0 14px 30px rgba(0, 0, 0, 0.45);
    z-index: 20;
  }

  .home-card:hover .home-card-overlay {
    background: linear-gradient(to top, rgba(0, 0, 0, 0.78) 0%, transparent 65%);
  }

  .home-card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 60%);
    transition: background 0.35s ease;
  }

  .home-card-info {
    position: absolute;
    bottom: 8px;
    right: 8px;
    text-align: right;
  }

  .home-card-badge-type,
  .home-card-badge-tier {
    position: absolute;
    top: 7px;
    color: #ffffff;
    font-size: 11px;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.65);
    z-index: 2;
  }

  .home-card-badge-type {
    right: 7px;
  }

  .home-card-badge-tier {
    left: 7px;
  }

  .home-card-name {
    margin: 0;
    font-size: 12px;
    font-weight: 700;
    color: #ffffff;
  }

  .home-card-tag {
    margin: 0;
    font-size: 10px;
    color: rgba(255, 255, 255, 0.6);
  }

  /* ══════════════════════════════════
     TABLET — 640px+
  ══════════════════════════════════ */
  @media (min-width: 640px) {
    /* هدر موبایل حذف میشه — top nav جایگزینه */
    .home-logo { display: none !important; }

    .home-page {
      max-width: 680px;
      padding: 24px 28px 80px;
    }

    .home-greeting-title { font-size: 24px; }

    .home-search-card { border-radius: 18px; }

    /* اسلایدر بدون negative margin */
    .home-cards-scroll {
      margin-left: 0;
      margin-right: 0;
      width: 100%;
    }
    .home-cards-scroll .home-card {
      width: 180px;
      max-width: 180px;
    }

    .home-section-title-right { font-size: 16px; }
  }

  /* ══════════════════════════════════
     DESKTOP — 1024px+
  ══════════════════════════════════ */
  @media (min-width: 1024px) {
    .home-page {
      max-width: 1080px;
      padding: 32px 40px 60px;
    }

    /* هدینگ و سرچ — سانتر و محدود */
    .home-greeting { margin-top: 16px; }
    .home-greeting-title { font-size: clamp(22px, 2vw, 28px); }

    .home-search { max-width: 680px; margin-left: auto; margin-right: auto; }

    /* کارت‌های اسلایدر */
    .home-cards-scroll {
      gap: 14px;
      padding: 10px 0 14px 0;
    }
    .home-cards-scroll .home-card {
      width: 200px;
      max-width: 210px;
      border-radius: 10px;
    }

    .home-section-title-right { font-size: 17px; }
    .home-products { padding-bottom: 40px; }

    /* کارت فول‌ویدث */
    .home-card--full { max-height: 420px; }
  }

  /* ══════════════════════════════════
     LARGE DESKTOP — 1280px+
  ══════════════════════════════════ */
  @media (min-width: 1280px) {
    .home-page { max-width: 1200px; padding: 36px 56px 60px; }
    .home-cards-scroll .home-card { width: 220px; max-width: 230px; }
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

})();
</script>
@endpush