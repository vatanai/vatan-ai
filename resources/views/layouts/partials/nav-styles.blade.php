<style>
  /* ── فونت هدر: فقط YekanBakh در کل هدر دسکتاپ (بدون هیچ فونت دیگر) ──
     روی #vatan-topnav ست می‌شود تا از طریق ارث‌بری به همه‌ی متن‌ها برسد؛
     آیکون‌های FontAwesome چون فونت خودشان را از کلاس می‌گیرند دست‌نخورده می‌مانند. */
  #vatan-topnav { font-family: 'YekanBakh', sans-serif; }

  /* ── حالت روز: متن و آیکون منوی سلکت‌شده کاملاً سفید (آیکون‌ها currentColor هستند) ── */
  html.light .topnav-link.is-active,
  html.light .topnav-link.is-active .topnav-link-icon svg {
    color: #ffffff;
  }

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
  /* آیکون فعال همیشه روی نشانگر لیمویی مشکی است؛ در تم شب هم سفید نمی‌ماند. */
  .vatan-nav-item.is-active .vatan-nav-icon-off,
  .vatan-nav-item.is-active .vatan-nav-icon-on {
    color: #000000;
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

  /* ── دکمه «بساز» در هدر ──
     حالت عادی: باکس مربعی با یک + وسط‌چین.
     روی هاور: باکس از چپ و راست باز می‌شود و «بساز +» نمایان می‌شود.
     رنگ سبز لیمویی سایت (#cffe00)، متن مشکی، فونت YekanBakh، خمیدگی هم‌اندازه‌ی باکس‌های هدر (۱۵٫۶px).
     رنگ سبز و متن مشکی در هر دو حالت روز/شب یکسان است. */
  .topnav-create {
    display: flex;
    align-items: center;
    justify-content: center;      /* + وسط‌چین تا باز شدن از دو طرف حس شود */
    width: 44px;                  /* حالت جمع‌شده */
    height: 40px;                 /* هم‌ارتفاع با سایر باکس‌های هدر */
    padding: 0;
    background: #cffe00;          /* سبز لیمویی اصلی سایت */
    color: #000000;              /* متن مشکی */
    border: none;
    border-radius: 12px;          /* خمیدگی مشترک همه باکس‌های تعاملی هدر */
    font-family: 'YekanBakh', sans-serif;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.199);
    transition: width 0.3s ease, background 0.15s ease;
  }
  /* علامت + */
  .topnav-create-sign {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 31.2px;              /* + حالت عادی ۲۰٪ بزرگ‌تر (۲۶px → ۳۱٫۲px) */
    line-height: 1;
    font-weight: 400;
    color: #000000;
    flex-shrink: 0;
    transition: font-size 0.3s ease;
  }
  /* متن «بساز» */
  .topnav-create-text {
    max-width: 0;
    opacity: 0;
    overflow: hidden;
    white-space: nowrap;
    color: #000000;
    font-size: 15px;
    font-weight: 900;              /* YekanBakh کلفت (Fat) هنگام هاور */
    font-family: 'YekanBakh', sans-serif;
    transition: max-width 0.3s ease, opacity 0.3s ease, margin 0.3s ease;
  }
  /* افکت باز شدن روی هاور */
  .topnav-create:hover {
    width: 120px;
  }
  .topnav-create:hover .topnav-create-sign {
    font-size: 22px;
  }
  .topnav-create:hover .topnav-create-text {
    opacity: 1;
    max-width: 70px;
    margin-right: 8px;            /* فاصله‌ی متن از + در حالت RTL */
  }
  /* افکت کلیک */
  .topnav-create:active {
    transform: translate(2px, 2px);
  }

  /* ── باکس نمایش موجودی توکن در هدر (سمت چپ دکمه «بساز») ──
     رنگ‌بندی هماهنگ با تم روز/شب از طریق متغیرهای --bg-card / --border-subtle / --text-primary
     (همان توکن‌های رنگی که در resources/css/app.css و public/css/profile.css تعریف شده‌اند) */
  .topnav-token-box {
    display: flex;
    align-items: center;
    gap: 6px;
    height: 32.3px; /* ارتفاع ۱۵٪ کمتر (۳۸px → ۳۲٫۳px) */
    padding: 0 13px;
    border-radius: 12px; /* خمیدگی باکس: ۱۲px */
    background: #1a1a1a; /* حالت شب */
    border: 1px solid var(--border-subtle);
    white-space: nowrap;
    flex-shrink: 0;
    transition: opacity 0.15s, border-color 0.15s;
  }
  html.light .topnav-token-box {
    background: #1a1a1a;
  }

  .topnav-token-box:hover {
    opacity: 0.85;
  }
  .topnav-token-icon {
    width: 20.98px;
    height: 20.98px;
    flex-shrink: 0;
    display: block;
    order: 1; /* آیکون سمت چپ */
    background: #cffe00;
    -webkit-mask: url('{{ asset('assets/icons/token-mark.png') }}') center / contain no-repeat;
    mask: url('{{ asset('assets/icons/token-mark.png') }}') center / contain no-repeat;
  }
  .topnav-token-number {
    font-size: 15.6px; /* ۲۰٪ بزرگتر نسبت به سایز پایه ۱۳px */
    font-weight: 800;
    color: #ffffff; /* سفید در هر دو حالت (باکس تیره #1a1a1a) */
    font-family: 'YekanBakh', sans-serif; /* فونت عدد: یکان بخ */
    font-feature-settings: "tnum";
    order: 0; /* عدد سمت راست */
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
    border-radius: 14.4px; /* خمیدگی ۲۰٪ بیشتر (۱۲px → ۱۴٫۴px) */
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
    border-radius: 9.6px; /* خمیدگی ۲۰٪ بیشتر (۸px → ۹٫۶px) */
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
    background: rgba(207,254,0,.15);
    color: #cffe00;
    font-weight: 700;
  }
  html.light .theme-menu-item.is-active {
    background: rgba(207,254,0,.12);
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

  /* ── آیکون منوهای دسکتاپ (مشترک با موبایل از partials/nav-svg) ── */
  .topnav-link { display: inline-flex; align-items: center; gap: 6px; }
  .topnav-link-icon {
    position: relative; display: inline-flex; flex-shrink: 0;
    width: 17px; height: 17px;
  }
  .topnav-link-icon .ni-off, .topnav-link-icon .ni-on {
    position: absolute; inset: 0; margin: auto;
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
  .topnav-link-icon .ni-on { opacity: 0; transform: scale(0.9); }
  .topnav-link.is-active .topnav-link-icon .ni-off,
  .topnav-link:hover .topnav-link-icon .ni-off { opacity: 0; transform: scale(0.9); }
  .topnav-link.is-active .topnav-link-icon .ni-on,
  .topnav-link:hover .topnav-link-icon .ni-on { opacity: 1; transform: scale(1); }

  /* ── باکس خرید اشتراک: حالت عادی خط دور تک‌رنگ سبز اصلی، حالت هاور گرادیانت متحرک ── */
  .sub-btn {
    min-width: 145.2px; height: 44px; padding: 0 17.6px;
    border: none; border-radius: 12px; /* خمیدگی باکس: ۱۲px */
    background: #cffe00; /* حالت عادی: خط دور یک‌رنگ، بدون گرادینت */
    position: relative; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; text-decoration: none;
    transition: background-position 1s, transform 0.15s ease;
  }
  .sub-btn::before {
    position: absolute; content: "";
    width: 96.4%; height: 89.2%; border-radius: 9.6px; /* خط دور ۱۰٪ نازک‌تر + خمیدگی داخلی هماهنگ */
    background-color: #1d2209;
    z-index: 0;
  }
  html.light .sub-btn::before { background-color: #1d2209; }
  .sub-btn span {
    position: relative; z-index: 1;
    display: flex; align-items: center; gap: 7px;
    font-size: 14.3px; font-weight: 800; color: #cffe00; white-space: nowrap;
  }
  .sub-btn span i { font-size: 15.84px; color: #cffe00; }
  .sub-btn:hover {
    /* حالت هاور: همون گرادینت متحرک قبلی روی خط دور */
    background: linear-gradient(to right,#5f7400,#7d9800,#5f7400,#5f7400,#cffe00,#7d9800);
    background-size: 250%;
    background-position: right;
  }
  .sub-btn:active { transform: scale(0.95); }

  /* ── آیکون پروفایل + منوی کشویی (Popup) ── */
  .topnav-popup {
    --tp-green: #cffe00; --tp-diameter: 40px; --tp-radius: 15.6px; /* خمیدگی ۲۰٪ بیشتر (۱۳px → ۱۵٫۶px) */
    --tp-nav-bg: #121218; --tp-nav-border: rgba(255,255,255,0.1);
    --tp-nav-shadow: rgba(0,0,0,0.45); --tp-item-color: rgba(255,255,255,0.72);
    --tp-hr-color: rgba(255,255,255,0.08); --tp-danger: #ff4a4a;
    display: inline-block; position: relative;
  }
  html.light .topnav-popup {
    --tp-nav-bg: #121218; --tp-nav-border: rgba(255,255,255,0.1);
    --tp-nav-shadow: rgba(0,0,0,0.45); --tp-item-color: rgba(255,255,255,0.72);
    --tp-hr-color: rgba(255,255,255,0.08);
  }
  .topnav-popup input { display: none; }
  .topnav-burger {
    display: flex; align-items: center; justify-content: center;
    width: var(--tp-diameter); height: var(--tp-diameter);
    border-radius: 50%; overflow: hidden; cursor: pointer;
    border: 2px solid var(--tp-green); background: var(--tp-green);
    transition: transform 0.15s ease, box-shadow 0.2s ease;
  }
  .topnav-burger-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
  /* آیکون پیش‌فرض پروفایل — وقتی کاربر عکسی انتخاب نکرده (یا مهمان است)، به‌جای عکس نمایش داده می‌شود.
     رنگ آیکون همیشه مشکی است چون پس‌زمینهٔ دایره سبز لیمویی ثابت است (در روز و شب یکسان). */
  .topnav-burger-icon { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: #000; }
  .topnav-burger:hover { transform: scale(1.08); }
  .topnav-burger:active { transform: scale(0.95); }
  .topnav-popup input:focus-visible + .topnav-burger { box-shadow: 0 0 0 3px rgba(207,254,0,0.35); }
  .topnav-popup-window {
    position: absolute; top: calc(var(--tp-diameter) + 12px); left: 0; right: unset;
    min-width: 260px; padding: 6px;
    background: var(--tp-nav-bg); border: 1px solid var(--tp-nav-border);
    border-radius: var(--tp-radius); box-shadow: 0 10px 30px var(--tp-nav-shadow);
    transform: scale(0.9); transform-origin: top left;
    visibility: hidden; opacity: 0; transition: all 0.16s ease-in-out; z-index: 400;
  }
  .topnav-popup input:checked ~ .topnav-popup-window { transform: scale(1); visibility: visible; opacity: 1; }
  .topnav-popup-window .tp-userinfo { display: flex; flex-direction: row; align-items: center; justify-content: space-between; gap: 12px; padding: 8px 12px 10px; }
  .topnav-popup-window .tp-user-name { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:14px; font-weight:800; color:var(--tp-item-color); }
  html:not(.light) .topnav-popup-window .tp-user-name { color: #fff; }
  html.light .topnav-popup-window .tp-user-name { color: #fff; }
  .topnav-popup-window .tp-user-phone { margin-right: auto; font-size: 12.65px; font-weight: 600; color: var(--tp-item-color); opacity: 0.6; text-align: left; white-space: nowrap; }
  .topnav-popup-window ul { margin: 0; padding: 0; list-style: none; }
  .topnav-popup-window ul button {
    width: 100%; border: none; background: none; cursor: pointer;
    display: flex; align-items: center; gap: 12px; color: var(--tp-item-color);
    font-family: inherit; font-size: 13.5px; font-weight: 600;
    padding: 10px 12px; border-radius: 10.8px; white-space: nowrap; transition: all 0.15s ease;
  }
  .topnav-popup-window ul button i { font-size: 15px; width: 18px; text-align: center; }
  .topnav-popup-window ul button:hover { background: var(--tp-green); color: #000; }
  .topnav-popup-window ul button:hover i,
  .topnav-popup-window ul button:hover span { color: #000; }
  .topnav-popup-window ul button:not(.is-danger):focus,
  .topnav-popup-window ul button:not(.is-danger):active,
  .topnav-popup-window ul button:not(.is-danger).is-selected { background: var(--tp-green); color: #000; outline: none; }
  .topnav-popup-window ul button:not(.is-danger):focus i,
  .topnav-popup-window ul button:not(.is-danger):focus span,
  .topnav-popup-window ul button:not(.is-danger):active i,
  .topnav-popup-window ul button:not(.is-danger):active span,
  .topnav-popup-window ul button:not(.is-danger).is-selected i,
  .topnav-popup-window ul button:not(.is-danger).is-selected span { color: #000; }
  .topnav-popup-window ul button.is-danger { color: var(--tp-danger); }
  .topnav-popup-window ul button.is-danger:hover { background: var(--tp-danger); color: #fff; }
  .topnav-popup-window ul button.is-danger:hover i,
  .topnav-popup-window ul button.is-danger:hover span { color: #fff; }
  .topnav-popup-window hr { margin: 6px 4px; border: none; border-bottom: 1px solid var(--tp-hr-color); }

  /* ── ریسپانسیو تبلت (۶۴۰–۹۶۰): جمع‌وجورتر کردن هدر ── */
  @media (min-width: 1280px) {
    #vatan-topnav-inner {
      max-width: none;
      padding-left: var(--home-desktop-grid-gutter);
      padding-right: var(--home-desktop-grid-gutter);
    }
    /* هم‌ترازی کل گروه اکشن‌های چپ هدر با لبه چپ محتوای صفحه هوم */
    .topnav-left-side {
      transform: translateX(18px);
    }
  }

  @media (min-width: 640px) and (max-width: 960px) {
    #vatan-topnav-inner { padding-left: 16px; padding-right: 16px; gap: 10px; }
    .topnav-left-side { gap: 8px; }
    .topnav-links { gap: 4px; }
    .topnav-link { padding-left: 10px; padding-right: 10px; font-size: 13px; }
    .sub-btn { min-width: 0; padding: 0 13.2px; }
    .sub-btn span { font-size: 13.2px; }
    .topnav-token-box { padding: 0 10px; }
    .topnav-create:hover { width: 88px; }
  }
  /* تبلت کوچک: متن خرید اشتراک مخفی، فقط آیکون */
  @media (min-width: 640px) and (max-width: 820px) {
    .sub-btn span { gap: 0; }
    .sub-btn span .sub-label { display: none; }
  }

  /* ── حالت نمایش در اپ (standalone / PWA): رعایت safe-area بالای هدر ── */
  @media (display-mode: standalone) {
    #vatan-topnav { padding-top: env(safe-area-inset-top, 0px); }
    #vatan-topnav-inner { padding-top: 0; }
  }
</style>
