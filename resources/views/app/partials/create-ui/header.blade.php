<header class="hoosha-topbar" aria-label="ناوبری استودیوی وطن">
  <a class="hoosha-brand" href="{{ route('app.home') }}" aria-label="وطن AI">
    <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="32" height="32">
    <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" class="hoosha-brand-wordmark">
  </a>

  <div class="hoosha-top-tools">
    <button type="button" class="hoosha-model-button"><span class="hoosha-model-mark"><i class="fa-solid fa-sparkles"></i></span><span>دستیار وطن</span><i class="fa-solid fa-chevron-down"></i></button>
    <button type="button" class="hoosha-tool-icon" data-mode="chat" aria-label="چت"><i class="fa-regular fa-comments"></i></button>
    <button type="button" class="hoosha-tool-icon" data-mode="image" aria-label="تصویر"><i class="fa-regular fa-image"></i></button>
    <button type="button" class="hoosha-tool-icon" data-mode="video" aria-label="ویدیو"><i class="fa-solid fa-film"></i></button>
    <button type="button" class="hoosha-tool-icon" aria-label="گالری"><i class="fa-regular fa-folder-open"></i></button>
  </div>

  <div class="hoosha-user-area"><button type="button" class="hoosha-theme-toggle" data-create-theme aria-label="تغییر تم"><i class="fa-solid fa-sun"></i></button><span>خوش آمدید</span><button type="button">ورود به حساب کاربری</button><span class="hoosha-user-avatar"><i class="fa-regular fa-user"></i></span></div>
</header>

<aside class="hoosha-sidebar" aria-label="منوی اصلی">
  <div class="hoosha-sidebar-top"><span class="hoosha-sidebar-greeting">خوش آمدید</span><button type="button" class="hoosha-theme-toggle hoosha-sidebar-theme" data-create-theme aria-label="تغییر تم"><i class="fa-solid fa-sun"></i></button><button type="button" class="hoosha-user-avatar"><i class="fa-regular fa-user"></i></button><small>ورود به حساب کاربری</small></div>
  <div class="hoosha-sidebar-group"><button type="button" class="hoosha-sidebar-row"><span><i class="fa-solid fa-wand-magic-sparkles"></i> مدل‌ها</span><strong>دستیار وطن</strong><i class="fa-solid fa-chevron-left"></i></button><button type="button" class="hoosha-sidebar-row"><span><i class="fa-regular fa-clock"></i> تاریخچه گفتگوها</span><i class="fa-solid fa-chevron-left"></i></button></div>
  <span class="hoosha-sidebar-label">سرویس‌های اصلی</span>
  <div class="hoosha-service-grid"><button type="button" data-mode="image"><i class="fa-regular fa-image"></i> تصویر</button><button type="button" data-mode="video"><i class="fa-solid fa-film"></i> ویدیو</button><button type="button"><i class="fa-regular fa-folder-open"></i> گالری</button><button type="button"><i class="fa-solid fa-music"></i> موزیک</button></div>
  <span class="hoosha-sidebar-label">سایر امکانات</span>
  <div class="hoosha-sidebar-group hoosha-sidebar-links"><button type="button" class="hoosha-sidebar-row" data-mode="chat"><span><i class="fa-solid fa-robot"></i> دستیارها</span><i class="fa-solid fa-chevron-left"></i></button><button type="button" class="hoosha-sidebar-row"><span><i class="fa-solid fa-toolbox"></i> ابزارها</span><i class="fa-solid fa-chevron-left"></i></button><button type="button" class="hoosha-sidebar-row"><span><i class="fa-solid fa-microphone-lines"></i> صداگذاری و دوبله <em>جدید</em></span><i class="fa-solid fa-chevron-left"></i></button><button type="button" class="hoosha-sidebar-row"><span><i class="fa-regular fa-paper-plane"></i> کانال پرامپت</span><i class="fa-solid fa-chevron-left"></i></button></div>
  <button type="button" class="hoosha-credit-button"><i class="fa-solid fa-bolt"></i> خرید اعتبار</button>
  <div class="hoosha-sidebar-bottom"><button type="button" class="hoosha-sidebar-row"><span><i class="fa-solid fa-headset"></i> پشتیبانی</span><i class="fa-solid fa-chevron-left"></i></button><button type="button" class="hoosha-sidebar-row"><span><i class="fa-solid fa-gear"></i> تنظیمات</span><i class="fa-solid fa-chevron-left"></i></button></div>
</aside>

<div class="hoosha-promo"><span class="hoosha-promo-art"><i class="fa-solid fa-film"></i></span><strong>با وطن، ایده‌هایت را سریع‌تر به تصویر و ویدیو تبدیل کن!</strong><span>محیطی فارسی و ساده برای شروع</span></div>

<div class="create-mode-tabs" role="tablist" aria-label="نوع فعالیت">
  <button type="button" class="create-mode-tab is-active" id="mode-image" data-mode="image" role="tab" aria-selected="true"><span class="create-mode-icon"><i class="fa-regular fa-image"></i></span><span><strong>تصویر</strong><small>ایده را تصویر کن</small></span></button>
  <button type="button" class="create-mode-tab" id="mode-video" data-mode="video" role="tab" aria-selected="false"><span class="create-mode-icon"><i class="fa-solid fa-film"></i></span><span><strong>ویدیو</strong><small>حرکت به ایده بده</small></span></button>
  <button type="button" class="create-mode-tab" id="mode-chat" data-mode="chat" role="tab" aria-selected="false"><span class="create-mode-icon"><i class="fa-regular fa-comments"></i></span><span><strong>چت</strong><small>با دستیار وطن حرف بزن</small></span></button>
</div>
