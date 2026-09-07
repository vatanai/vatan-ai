<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title>پیش‌نمایش پلان لودر | وطن AI</title>
  <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
  <link rel="stylesheet" href="{{ asset('css/create-loader-ad.css') }}?v={{ filemtime(public_path('css/create-loader-ad.css')) }}">
</head>
<body>
  <div class="ad-preview" data-ad-preview>
    <div class="ad-ambient ad-ambient--one"></div>
    <div class="ad-ambient ad-ambient--two"></div>

    <header class="ad-toolbar">
      <div class="ad-toolbar-title"><span class="ad-toolbar-dot"></span><span>پیش‌نمایش پلان تبلیغاتی</span></div>
      <div class="ad-toolbar-meta"><span>صفحه‌ی بساز محصول</span><b>۰۷ ثانیه</b></div>
      <button class="ad-replay" type="button" data-replay><span>↻</span> پخش دوباره</button>
    </header>

    <main class="ad-composition">
      <section class="ad-phone" aria-label="پیش‌نمایش صفحه‌ی ساخت محصول">
        <div class="ad-phone-speaker"></div>
        <div class="ad-phone-camera"></div>
        <div class="ad-screen">
          <div class="ad-status-bar"><span>۹:۴۱</span><span class="ad-status-icons">● ◒ ▰</span></div>

          <header class="ad-app-header">
            <div class="ad-brand"><span class="ad-brand-mark"><img src="{{ asset('assets/img/icon_vatan.svg') }}" alt=""></span><img class="ad-brand-wordmark" src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI"></div>
          </header>

          <div class="ad-screen-scroll">
            <div class="ad-page-kicker"><span>استودیوی ساخت</span><i></i></div>
            <h1>پرتره‌ی سینمایی</h1>
            <p class="ad-intro">عکس را آپلود کن و چند لحظه بعد خروجی آماده‌ات را ببین.</p>

            <section class="ad-upload-card">
              <div class="ad-upload-thumb ad-upload-thumb--file"><span>▧</span><b>✓</b></div>
              <div class="ad-upload-copy"><strong>portrait-final.jpg</strong><span>آماده برای ساخت · ۲.۴ مگابایت</span></div>
              <button type="button" class="ad-upload-more" aria-label="گزینه‌های عکس">•••</button>
            </section>

            <div class="ad-options">
              <div><span>نسبت خروجی</span><b>۳:۴</b></div>
              <div><span>کیفیت</span><b>استاندارد</b></div>
            </div>

            <div class="ad-build-area">
              <div class="ad-build-summary"><span>هزینه‌ی این ساخت</span><b>۱۲ <em>اعتبار</em></b></div>
              <button type="button" class="ad-build-button" data-build><span data-build-label>بساز</span><span class="ad-build-arrow">←</span></button>
            </div>

            <span class="ad-tap" data-tap aria-hidden="true"></span>

            <section class="ad-loader" data-loader hidden>
              <div class="ad-loader-top"><div class="ad-loader-brand"><span class="ad-loader-logo"><img src="{{ asset('assets/img/icon_vatan.svg') }}" alt=""></span><img class="ad-loader-wordmark" src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI"></div><strong data-progress>۰٪</strong></div>
              <h2>در حال ساخت تصویر شما</h2>
              <p data-stage-text>در حال بررسی تصویر و ورودی‌ها</p>
              <div class="ad-timeline" role="progressbar" aria-label="پیشرفت ساخت" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-progress-bar></span><i data-progress-dot></i></div>
              <div class="ad-timeline-labels"><span>آپلود</span><span>ساخت تصویر</span><span>پرداخت نهایی</span></div>
              <div class="ad-loader-meta"><span>زمان سپری‌شده <b data-time>۰۰:۰۰</b></span><span><i></i> خروجی امن و خصوصی</span></div>
            </section>

            <section class="ad-success" data-success hidden>
              <span class="ad-success-icon" aria-hidden="true"></span><strong>عکس شما آماد‌ه‌ست</strong><span>تصویر انتخابی با موفقیت ساخته شد</span>
            </section>
          </div>

          <nav class="ad-bottom-nav" aria-label="ناوبری برنامه">
            <span data-key="profile">
              @include('partials.nav-svg',['key'=>'profile','state'=>'off','size'=>18,'class'=>'ad-nav-icon ad-nav-icon-off'])
              @include('partials.nav-svg',['key'=>'profile','state'=>'on','size'=>18,'class'=>'ad-nav-icon ad-nav-icon-on'])
            </span>
            <span data-key="explore">
              @include('partials.nav-svg',['key'=>'explore','state'=>'off','size'=>21,'class'=>'ad-nav-icon ad-nav-icon-off'])
              @include('partials.nav-svg',['key'=>'explore','state'=>'on','size'=>21,'class'=>'ad-nav-icon ad-nav-icon-on'])
            </span>
            <span class="is-active" data-key="create">
              @include('partials.nav-svg',['key'=>'create','state'=>'off','size'=>25,'class'=>'ad-nav-icon ad-nav-icon-off'])
              @include('partials.nav-svg',['key'=>'create','state'=>'on','size'=>25,'class'=>'ad-nav-icon ad-nav-icon-on'])
            </span>
            <span data-key="trends">
              @include('partials.nav-svg',['key'=>'trends','state'=>'off','size'=>22,'class'=>'ad-nav-icon ad-nav-icon-off'])
              @include('partials.nav-svg',['key'=>'trends','state'=>'on','size'=>22,'class'=>'ad-nav-icon ad-nav-icon-on'])
            </span>
            <span data-key="home">
              @include('partials.nav-svg',['key'=>'home','state'=>'off','size'=>21,'class'=>'ad-nav-icon ad-nav-icon-off'])
              @include('partials.nav-svg',['key'=>'home','state'=>'on','size'=>21,'class'=>'ad-nav-icon ad-nav-icon-on'])
            </span>
          </nav>
        </div>
      </section>
    </main>

    <footer class="ad-caption"><span class="ad-caption-line"></span><span>سناریو: تصویر آماده ← کلیک روی بساز ← پرشدن تایم‌لاین</span><span class="ad-caption-line"></span></footer>
  </div>
  <script src="{{ asset('js/create-loader-ad.js') }}?v={{ filemtime(public_path('js/create-loader-ad.js')) }}"></script>
</body>
</html>
