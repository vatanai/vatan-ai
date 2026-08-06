{{--
  استایل مشترک تمام Sectionهای Home Builder (اسلایدر/گرید/هیرو/بنر/متن/فاصله‌گذار).
  از app/home.blade.php و هر partial رندر داخل sections/ استفاده می‌شود تا کلاس‌ها
  یک‌بار تعریف شوند، نه در هر partial جداگانه (جلوگیری از تکرار/ناهماهنگی رنگ).
  رنگ‌ها هم‌راستا با پالت فعلی صفحه Home (پس‌زمینه تیره #000000 / روشن #ffffff) هستند.
--}}
<style>
  html:not(.light), html:not(.light) body, html:not(.light) body > main { background:#000 !important; }
  .hb-section { --hb-product-card-radius:14px; }
  /* ── ردیف‌های افقی اسکرول‌شونده (اسلایدر محصول/دسته‌بندی/مجموعه) ── */
  .home-cards-scroll {
    display: flex; flex-direction: row; gap: 10px; overflow-x: auto; overflow-y: visible;
    scrollbar-width: none; padding: 10px 0 14px 0; direction: rtl;
    margin: 2px -16px 0 -16px; width: calc(100% + 32px); isolation: isolate;
  }
  .home-cards-scroll::-webkit-scrollbar { display: none; }

  .home-card {
    aspect-ratio: 4 / 5; border-radius:var(--hb-product-card-radius); overflow: hidden; position: relative;
    background-size: cover; background-position: center; cursor: pointer;
    transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s ease;
    will-change: transform; transform-origin: center center; z-index: 0;
    flex: 0 0 auto; width: 148px;
  }
  .home-card:hover { transform: scale(1.035) translateY(-2px); box-shadow: 0 14px 30px rgba(0,0,0,.45); z-index: 20; }
  .home-card:hover .home-card-overlay { background: linear-gradient(to top, rgba(0,0,0,.78) 0%, transparent 65%); }
  .home-card-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.7) 0%, transparent 60%); transition: background .35s ease; }
  .home-card-info { position: absolute; bottom: 8px; right: 8px; text-align: right; }
  .home-card-badge-type, .home-card-badge-tier { position: absolute; top: 7px; color: #fff; font-size: 11px; text-shadow: 0 1px 3px rgba(0,0,0,.65); z-index: 2; }
  .home-card-badge-type { right: 7px; }
  .home-card-badge-tier { left: 7px; }
  .home-card-name { margin: 0; font-size: 12px; font-weight: 700; color: #fff; }
  .home-card-tag { margin: 0; font-size: 10px; color: rgba(255,255,255,.6); }

  .home-card--compact { width: 118px; }

  /* ── تیتر بخش‌ها ── */
  .home-section-title { margin-top:31px; display: flex; justify-content: space-between; align-items: center; direction: rtl; }
  .home-section-title--sub { margin-top:31px; }
  .home-section-title-right { font-size: 15px; font-weight: 700; color: #fff; }
  .home-section-title-caption { margin: 2px 0 0 0; font-size: 10px; font-weight: 400; color: rgba(255,255,255,.5); }
  .home-section-viewall { flex-shrink: 0; display:inline-flex; align-items:center; justify-content:center; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: 6.48px; padding: 4px 10px; font-size: 10.45px; font-weight: 300; color: #fff; cursor: pointer; white-space: nowrap; text-decoration:none; }
  .home-section-viewall:hover { background:#cffe00; border-color:#cffe00; color:#111; }
  html.light .home-section-title-right { color: #000; }
  html.light .home-section-title-caption { color: rgba(0,0,0,.5); }
  html.light .home-section-viewall { background: rgba(0,0,0,.05); border: 1px solid rgba(0,0,0,.1); color: #000; }
  html.light .home-section-viewall:hover { background:#cffe00; border-color:#cffe00; color:#111; }

  /* ── Hero ── */
  .hb-hero { position: relative; margin-top:31px; border-radius: 16px; overflow: hidden; min-height: 220px; background-size: cover; background-position: center; background-color: rgba(255,255,255,.06); display: flex; align-items: flex-end; }
  .hb-hero-inner { position: relative; z-index: 2; padding: 22px; width: 100%; }
  .hb-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.75) 0%, transparent 65%); }
  .hb-hero-heading { margin: 0; font-size: 19px; font-weight: 800; color: #fff; }
  .hb-hero-sub { margin: 6px 0 0; font-size: 12.5px; color: rgba(255,255,255,.75); max-width: 480px; line-height: 1.7; }
  .hb-hero-cta { display: inline-flex; margin-top: 12px; background: #cffe00; color: #111; font-weight: 700; font-size: 12px; padding: 9px 18px; border-radius: 10px; text-decoration: none; }
  .hb-hero--centered .hb-hero-inner { text-align: center; }
  .hb-hero--centered .hb-hero-sub { margin-left: auto; margin-right: auto; }

  /* ── گرید محصولات ── */
  .hb-grid { display: grid; gap: 10px; margin-top: 12px; direction: rtl; }
  .hb-grid.hb-cols-2 { grid-template-columns: repeat(2, 1fr); }
  .hb-grid.hb-cols-3 { grid-template-columns: repeat(3, 1fr); }
  .hb-grid.hb-cols-4 { grid-template-columns: repeat(4, 1fr); }
  .hb-grid .home-card { width: 100%; }
  @media (max-width: 639px) {
    .hb-grid.hb-cols-3, .hb-grid.hb-cols-4 { grid-template-columns: repeat(2, 1fr); }
  }

  /* ── بنر تبلیغاتی ── */
  .hb-banner { margin-top:31px; display: block; }
  .hb-banner img { width: 100%; display: block; object-fit: cover; }
  .hb-banner.hb-size-small img { height: 90px; }
  .hb-banner.hb-size-medium img { height: 150px; }
  .hb-banner.hb-size-large img { height: 240px; }
  .hb-banner.hb-rounded img { border-radius: 16px; }

  /* ── اسلایدر دسته‌بندی ── */
  .hb-cat-card { flex: 0 0 auto; width: 92px; text-align: center; }
  .hb-cat-card-icon { width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); display: flex; align-items: center; justify-content: center; margin: 0 auto 6px; overflow: hidden; }
  .hb-cat-card-icon img { width: 100%; height: 100%; object-fit: cover; }
  .hb-cat-card-name { font-size: 11px; color: #fff; }
  html.light .hb-cat-card-icon { background: rgba(0,0,0,.05); border-color: rgba(0,0,0,.1); }
  html.light .hb-cat-card-name { color: #000; }

  /* ── بلوک متنی ── */
  .hb-text-block { margin-top:31px; direction: rtl; }
  .hb-text-block.hb-align-center { text-align: center; }
  .hb-text-block.hb-align-left { text-align: left; }
  .hb-text-block--statement { position:relative; overflow:hidden; padding:28px 22px; border:1px solid rgba(207,254,0,.18); border-radius:var(--hb-product-card-radius); background:linear-gradient(135deg,rgba(207,254,0,.08),rgba(255,255,255,.025)); }
  .hb-text-block--statement::before { content:''; position:absolute; width:120px; height:120px; top:-76px; left:-38px; border-radius:50%; background:rgba(207,254,0,.12); filter:blur(18px); }
  .hb-text-block--statement .hb-text-heading { font-size:20px; }
  .hb-text-block--statement .hb-text-body { max-width:620px; margin-inline:auto; }
  .hb-text-heading { margin: 0 0 6px; font-size: 16px; font-weight: 800; color: #fff; }
  .hb-text-body { margin: 0; font-size: 13px; line-height: 1.9; color: rgba(255,255,255,.7); }
  html.light .hb-text-heading { color: #000; }
  html.light .hb-text-body { color: rgba(0,0,0,.65); }
  html.light .hb-text-block--statement { background:linear-gradient(135deg,rgba(207,254,0,.16),rgba(0,0,0,.02)); border-color:rgba(0,0,0,.08); }

  /* ── فاصله‌گذار ── */
  .hb-spacer.hb-h-standard { height:31px; }
  .hb-spacer.hb-h-manual { height:var(--hb-space-desktop,31px); }
  @media (min-width:640px) and (max-width:1023px) { .hb-spacer.hb-h-manual { height:var(--hb-space-tablet,31px); } }
  @media (max-width:639px) { .hb-spacer.hb-h-manual { height:var(--hb-space-mobile,31px); } }

  /* ── نمایش/عدم‌نمایش بر اساس دستگاه (تنظیمات Responsive هر Section) ── */
  @media (min-width: 1024px) { .hb-hide-desktop { display: none !important; } }
  @media (min-width: 640px) and (max-width: 1023px) { .hb-hide-tablet { display: none !important; } }
  @media (max-width: 639px) { .hb-hide-mobile { display: none !important; } }

  /* ── دسکتاپ: کارت‌های بزرگ‌تر ── */
  @media (min-width: 640px) {
    .home-cards-scroll { margin-left: 0; margin-right: 0; width: 100%; }
    .home-cards-scroll .home-card { width: 180px; }
    .home-cards-scroll .home-card--compact { width: 140px; }
    .home-section-title-right { font-size: 16px; }
  }
  @media (min-width: 1024px) {
    .home-cards-scroll { gap: 14px; }
    .home-cards-scroll .home-card { width: 200px; }
    .home-section-title-right { font-size: 17px; }
    .hb-grid { gap: 14px; }
  }

  /* ══════════════════════════════════════════════════════════════
     Layoutهای جدید (نمونه‌برداری‌شده از مراجع بصری کاربر — pixifield.com)
     همه از همان رنگ‌های فعلی صفحه Home استفاده می‌کنند: پس‌زمینه مشکی،
     رنگ تاکیدی لیمویی #cffe00 (همان رنگ دکمه جستجو/چیپ‌های موجود صفحه).
     ══════════════════════════════════════════════════════════════ */

  /* ── متن مشترک زیر تصویر کارت (هزینه/عنوان/توضیح) — در Layout لبه‌نما و کارت وایدواید ── */
  .hb-card-cost { display: flex; align-items: center; gap: 4px; font-size: 10px; color: rgba(255,255,255,.55); margin-bottom: 4px; }
  .hb-card-title { font-size: 12.5px; font-weight: 700; color: #fff; margin: 0; }
  .hb-card-desc { font-size: 10.5px; color: rgba(255,255,255,.5); margin: 2px 0 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  html.light .hb-card-cost, html.light .hb-card-desc { color: rgba(0,0,0,.5); }
  html.light .hb-card-title { color: #000; }

  /* ── Layout ۱: اسلایدر لبه‌نما (Peek Scroll) — شات Photoshoot ── */
  .hb-peek-wrap { position: relative; margin-top: 8px; }
  .hb-peek-scroll { display: flex; gap: 12px; overflow-x: auto; scroll-snap-type: x proximity; scrollbar-width: none; padding: 4px 2px 4px; direction: rtl; }
  .hb-peek-scroll::-webkit-scrollbar { display: none; }
  .hb-peek-item { flex: 0 0 auto; width: 150px; scroll-snap-align: start; cursor: pointer; }
  .hb-peek-card { position:relative; width:100%; aspect-ratio:4/5; border-radius:var(--hb-product-card-radius); overflow:hidden; border:1.5px solid transparent; transition:border-color .25s ease,transform .25s ease; background-size:cover; background-position:center; }
  .hb-peek-item:hover .hb-peek-card, .hb-peek-item.is-active .hb-peek-card { border-color: #cffe00; transform: translateY(-2px); }
  .hb-peek-item:hover .hb-card-title, .hb-peek-item.is-active .hb-card-title { color: #cffe00; }
  html.light .hb-peek-item:hover .hb-card-title, html.light .hb-peek-item.is-active .hb-card-title { color: #127a52; }
  .hb-peek-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.55) 0%, transparent 55%); }
  .hb-peek-go { position: absolute; top: 8px; left: 8px; width: 26px; height: 26px; border-radius: 50%; background: #cffe00; color: #111; display: flex; align-items: center; justify-content: center; font-size: 11px; opacity: 0; transform: scale(.6); transition: all .25s ease; }
  .hb-peek-item:hover .hb-peek-go, .hb-peek-item.is-active .hb-peek-go { opacity: 1; transform: scale(1); }
  .hb-peek-meta { padding-top: 8px; }
  .hb-peek-nav { position: absolute; top: 38%; width: 34px; height: 34px; border-radius: 50%; background: rgba(0,0,0,.55); border: 1px solid rgba(255,255,255,.15); color: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; z-index: 5; }
  .hb-peek-nav.hb-prev { right: -8px; } .hb-peek-nav.hb-next { left: -8px; }
  html.light .hb-peek-nav { background: rgba(255,255,255,.9); border-color: rgba(0,0,0,.1); color: #111; }
  @media (max-width: 639px) { .hb-peek-nav { display: none; } .hb-peek-item { width: 130px; } }
  @media (min-width: 1024px) { .hb-peek-item { width: 190px; } }

  /* ── بنتو آینه‌ای: حسن/محمدعلی در دو ردیف قرینه ── */
  .hb-bento { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); grid-template-rows:repeat(4,90px); grid-template-areas:"small-a small-b hero-a hero-a" "small-a small-b hero-a hero-a" "hero-b hero-b small-c small-d" "hero-b hero-b small-c small-d"; gap:12px; margin-top:12px; }
  .hb-bento-item { position:relative; border-radius:var(--hb-product-card-radius); overflow:hidden; background-size:cover; background-position:center; cursor:pointer; border:1.5px solid transparent; transition:border-color .3s ease,transform .3s ease; }
  .hb-bento-item:hover { transform: scale(1.015); border-color: #cffe00; }
  .hb-bento-item--1 { grid-area:hero-a; } .hb-bento-item--2 { grid-area:small-a; } .hb-bento-item--3 { grid-area:small-b; }
  .hb-bento-item--4 { grid-area:small-c; } .hb-bento-item--5 { grid-area:small-d; } .hb-bento-item--6 { grid-area:hero-b; }
  .hb-bento-badge { position: absolute; top: 8px; right: 8px; background: #cffe00; color: #111; font-size: 9.5px; font-weight: 800; padding: 3px 9px; border-radius: 99px; z-index: 2; }
  .hb-bento-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.5) 0%, transparent 45%); opacity: 0; transition: opacity .3s ease; }
  .hb-bento-item:hover .hb-bento-overlay { opacity: 1; }
  @media (max-width:639px) { .hb-bento { grid-template-columns:repeat(2,minmax(0,1fr)); grid-template-rows:230px 145px 145px 230px; grid-template-areas:"hero-a hero-a" "small-a small-b" "small-c small-d" "hero-b hero-b"; gap:10px; } }
  @media (min-width:1024px) { .hb-bento { grid-template-rows:repeat(4,110px); } }

  /* ── دو قاب احساسی خانواده ── */
  .hb-family-duo { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-top:12px; }
  .hb-family-card { position:relative; min-height:420px; overflow:hidden; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; color:#fff; text-decoration:none; border:1.5px solid transparent; transition:.3s ease; }
  .hb-family-card:hover { border-color:#cffe00; transform:translateY(-3px); box-shadow:0 18px 36px rgba(0,0,0,.5); }
  .hb-family-shade { position:absolute; inset:0; background:linear-gradient(to top,rgba(0,0,0,.88),rgba(0,0,0,.05) 70%); }
  .hb-family-kicker { position:absolute; top:14px; right:14px; padding:5px 10px; border-radius:99px; background:rgba(0,0,0,.54); color:#cffe00; font-size:9px; font-weight:800; }
  .hb-family-copy { position:absolute; right:17px; bottom:16px; left:17px; }
  .hb-family-copy b,.hb-family-copy small { display:block; } .hb-family-copy b { font-size:16px; } .hb-family-copy small { margin-top:4px; color:rgba(255,255,255,.68); font-size:10px; line-height:1.7; }
  @media (max-width:639px) { .hb-family-duo { grid-template-columns:1fr; gap:10px; } .hb-family-card { min-height:72vh; border-radius:0; } }

  /* ── ویدیوی ویژه داستانی: یک قاب اصلی + دو قاب مکمل ── */
  .hb-video-spotlight { display:grid; grid-template-columns:1.7fr 1fr; grid-template-rows:repeat(2,190px); gap:12px; margin-top:12px; }
  .hb-video-story { position:relative; overflow:hidden; border-radius:var(--hb-product-card-radius); color:#fff; text-decoration:none; border:1.5px solid transparent; transition:.3s ease; }
  .hb-video-story.is-featured { grid-row:1 / 3; }
  .hb-video-story:hover { border-color:#cffe00; transform:translateY(-2px); }
  .hb-video-story video,.hb-video-story-poster { width:100%; height:100%; display:block; object-fit:cover; background-size:cover; background-position:center; }
  .hb-video-story-shade { position:absolute; inset:0; background:linear-gradient(to top,rgba(0,0,0,.82),transparent 65%); }
  .hb-video-story-play { position:absolute; top:14px; left:14px; width:34px; height:34px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:#cffe00; color:#111; font-size:10px; }
  .hb-video-story-copy { position:absolute; right:14px; bottom:13px; left:14px; }
  .hb-video-story-copy b,.hb-video-story-copy small { display:block; } .hb-video-story-copy b { font-size:13px; } .hb-video-story-copy small { margin-top:3px; color:rgba(255,255,255,.65); font-size:9px; }
  .hb-video-story.is-featured .hb-video-story-copy b { font-size:18px; }
  @media (max-width:639px) { .hb-video-spotlight { grid-template-columns:repeat(2,minmax(0,1fr)); grid-template-rows:300px 150px; gap:9px; } .hb-video-story.is-featured { grid-column:1 / 3; grid-row:auto; border-radius:0; } }

  /* ── Layout ۳: اسلایدر با کارت معرفی — شات Commercial Studio ── */
  .hb-intro-row { display:flex; align-items:stretch; gap:14px; overflow-x:auto; scrollbar-width:none; padding:10px 2px 14px; margin-top:4px; direction:rtl; isolation:isolate; }
  .hb-intro-row::-webkit-scrollbar { display: none; }
  .hb-intro-card { flex:0 0 auto; width:300px; border-radius:16px; padding:14px; background:#111316; border:1px solid rgba(255,255,255,.1); display:flex; flex-direction:column; z-index:3; }
  .hb-intro-row.is-fixed .hb-intro-card { position:sticky; right:0; box-shadow:-18px 0 24px rgba(0,0,0,.38); }
  .hb-intro-products { display:flex; align-items:flex-start; gap:14px; flex:0 0 auto; }
  .hb-intro-badge { display:inline-flex; align-items:center; gap:6px; font-size:9px; font-weight:700; color:rgba(255,255,255,.7); background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); padding:4px 9px; border-radius:99px; width:fit-content; margin-bottom:9px; }
  .hb-intro-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #cffe00; }
  .hb-intro-heading { font-size:16px; font-weight:800; color:#fff; margin:0 0 3px; line-height:1.35; }
  .hb-intro-heading-accent { color: #cffe00; }
  .hb-intro-desc { font-size:10.5px; color:rgba(255,255,255,.55); line-height:1.65; margin:0 0 10px; }
  .hb-intro-steps { display:flex; flex-direction:column; gap:6px; margin-bottom:10px; }
  .hb-intro-step { display:flex; align-items:center; gap:7px; font-size:10.5px; color:rgba(255,255,255,.85); }
  .hb-intro-step-num { width:18px; height:18px; border-radius:50%; background:rgba(207,254,0,.12); color:#cffe00; font-size:9px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
  .hb-intro-note { display:flex; align-items:center; gap:5px; font-size:9.5px; color:rgba(207,254,0,.85); margin-bottom:10px; }
  .hb-intro-cta { margin-top:auto; display:inline-flex; align-items:center; justify-content:center; gap:6px; background:#cffe00; color:#111; font-weight:700; font-size:11px; padding:8px 12px; border-radius:9px; text-decoration:none; }
  .hb-wide-item { flex:0 0 auto; width:190px; text-decoration:none; }
  .hb-wide-card { position:relative; width:100%; aspect-ratio:4/5; border-radius:var(--hb-product-card-radius); overflow:hidden; background-size:cover; background-position:center; cursor:pointer; transition:transform .3s ease; }
  .hb-wide-item:hover .hb-wide-card { transform: translateY(-2px); }
  .hb-wide-meta { padding-top: 8px; }
  .hb-wide-play { position:absolute; inset:0; margin:auto; width:32px; height:32px; font-size:28px; color:#fff; text-shadow:0 2px 6px rgba(0,0,0,.6); }
  .hb-intro-dual-products { flex:0 0 auto; height:500px; display:grid; grid-template-rows:repeat(2,minmax(0,1fr)); grid-auto-flow:column; grid-auto-columns:180px; gap:12px; overflow-x:auto; scrollbar-width:none; padding-left:2px; }
  .hb-intro-dual-products::-webkit-scrollbar { display:none; }
  .hb-intro-dual-card { position:relative; overflow:hidden; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; border:1.5px solid transparent; box-shadow:none; text-decoration:none; }
  .hb-intro-dual-card:hover { border-color:#cffe00; box-shadow:0 0 20px rgba(207,254,0,.3); }
  .hb-intro-dual-credit { display:flex; align-items:center; gap:4px; margin-bottom:3px; color:rgba(255,255,255,.65); font-size:9.5px; font-weight:600; }
  .hb-intro-dual-credit i { color:#cffe00; }
  @media (max-width:639px) {
    .hb-intro-row { flex-direction:column; overflow:visible; gap:12px; }
    .hb-intro-row .hb-intro-card, .hb-intro-row.is-fixed .hb-intro-card { position:relative; right:auto; width:55%; min-width:190px; padding:12px; box-shadow:none; align-self:flex-start; }
    .hb-intro-products { width:calc(100% + 32px); margin-inline:-16px; padding-inline:0; overflow-x:auto; scrollbar-width:none; }
    .hb-intro-products::-webkit-scrollbar { display:none; }
    .hb-wide-item { width:120px; }
    .hb-intro-dual-products { width:calc(100% + 32px); height:430px; margin-inline:-16px; padding-inline:0; grid-auto-columns:145px; overflow-x:auto; }
  }
  @media (min-width:1024px) { .hb-wide-item { width:200px; } }
  html.light .hb-intro-card { background: rgba(0,0,0,.03); border-color: rgba(0,0,0,.08); }
  html.light .hb-intro-heading { color: #000; }
  html.light .hb-intro-desc { color: rgba(0,0,0,.55); }
  html.light .hb-intro-step { color: rgba(0,0,0,.8); }
  html.light .hb-intro-badge { background: rgba(0,0,0,.04); border-color: rgba(0,0,0,.1); color: rgba(0,0,0,.6); }

  /* ── Layout ۴: اسلایدر کارت بزرگ — شات Create ── */
  .hb-large-row { display: flex; gap: 14px; overflow-x: auto; scrollbar-width: none; padding: 8px 2px 14px; margin-top: 4px; direction: rtl; }
  .hb-large-row::-webkit-scrollbar { display: none; }
  .hb-large-card { flex:0 0 auto; width:78%; max-width:340px; border-radius:var(--hb-product-card-radius); overflow:hidden; background:rgba(255,255,255,.04); border:1.5px solid transparent; cursor:pointer; transition:border-color .25s ease,transform .25s ease; }
  .hb-large-card:hover { border-color:#cffe00; transform:translateY(-2px); }
  .hb-large-media { position: relative; aspect-ratio: 16/10; background-size: cover; background-position: center; }
  .hb-large-badge { position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 99px; z-index: 2; }
  .hb-large-badge.hb-badge-new, .hb-large-badge.hb-badge-studio { background: #cffe00; color: #111; }
  .hb-large-badge.hb-badge-tool { background: rgba(0,0,0,.55); color: #fff; border: 1px solid rgba(255,255,255,.25); }
  .hb-large-ribbon { position:absolute; top:15px; left:-32px; width:120px; padding:5px 8px; transform:rotate(-42deg); text-align:center; background:#cffe00; color:#111; font-size:9px; font-weight:900; z-index:3; }
  .hb-large-body { padding: 14px; }
  .hb-large-title { font-size: 14px; font-weight: 800; color: #fff; margin: 0 0 4px; }
  .hb-large-desc { font-size: 11.5px; color: rgba(255,255,255,.55); margin: 0; line-height: 1.7; }
  @media (min-width: 640px) { .hb-large-card { width: 340px; } }
  @media (min-width: 1024px) { .hb-large-card { width: 31%; max-width: 380px; } }
  html.light .hb-large-card { background: rgba(0,0,0,.02); border-color: rgba(0,0,0,.08); }
  html.light .hb-large-card:hover { border-color:#cffe00; }
  html.light .hb-large-title { color: #000; }
  html.light .hb-large-desc { color: rgba(0,0,0,.5); }

  /* ── Layout ۵: تب دسته‌بندی + اسلایدر — شات دسته‌بندی بالای Photoshoot ── */
  .hb-tabs-row { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; padding: 2px 2px 14px; direction: rtl; }
  .hb-tabs-row::-webkit-scrollbar { display: none; }
  .hb-tab-pill { flex: 0 0 auto; font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 99px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: rgba(255,255,255,.7); cursor: pointer; white-space: nowrap; transition: all .2s ease; }
  .hb-tab-pill.is-active { background: #cffe00; color: #111; border-color: #cffe00; font-weight: 800; }
  .hb-tabs-panel { display: none; }
  .hb-tabs-panel.is-active { display: block; }
  .hb-tabs-panel .home-card { border:1.5px solid transparent; }
  .hb-tabs-panel .home-card:hover { border-color:#cffe00; }
  html.light .hb-tab-pill { background: rgba(0,0,0,.04); border-color: rgba(0,0,0,.1); color: rgba(0,0,0,.6); }
  html.light .hb-tab-pill.is-active { background: #cffe00; color: #111; }

  /* ── خط کردیت روی کارت‌های اورلی‌دار (اسلایدر/گرید/مجموعه پیش‌فرض) ── */
  .home-card-credit { margin: 3px 0 0; font-size: 9.5px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 4px; }
  .home-card-credit i { color: #cffe00; font-size: 8.5px; }

  /* ── استایل نمونه «کارت شیشه‌ای» (Glass) ── */
  .hb-glass-row { display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; padding: 8px 2px 14px; margin-top: 4px; direction: rtl; }
  .hb-glass-row::-webkit-scrollbar { display: none; }
  .hb-glass-item { flex:0 0 auto; width:168px; border-radius:var(--hb-product-card-radius); background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.12); backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px); padding:8px; transition:transform .25s ease,border-color .25s ease; }
  .hb-glass-item:hover { transform: translateY(-3px); border-color: rgba(207,254,0,.55); }
  .hb-glass-media { width:100%; aspect-ratio:1/1; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; }
  .hb-glass-body { padding: 8px 4px 4px; }
  html.light .hb-glass-item { background: rgba(0,0,0,.03); border-color: rgba(0,0,0,.1); }
  @media (max-width: 639px) { .hb-glass-item { width: 146px; } }
  @media (min-width: 1024px) { .hb-glass-item { width: 190px; } }

  /* ── استایل نمونه «قاب نئونی» (Neon) ── */
  .hb-neon-row { display: flex; gap: 14px; overflow-x: auto; scrollbar-width: none; padding: 8px 2px 16px; margin-top: 4px; direction: rtl; }
  .hb-neon-row::-webkit-scrollbar { display: none; }
  .hb-neon-card { position:relative; flex:0 0 auto; width:152px; aspect-ratio:4/5; border-radius:var(--hb-product-card-radius); overflow:hidden; background-size:cover; background-position:center; border:1.5px solid transparent; box-shadow:none; transition:border-color .25s ease,box-shadow .25s ease,transform .25s ease; }
  .hb-neon-card:hover { border-color: #cffe00; box-shadow: 0 0 22px rgba(207,254,0,.35); transform: translateY(-2px); }
  .hb-neon-credit { position: absolute; top: 8px; right: 8px; z-index: 2; display: flex; align-items: center; gap: 4px; background: rgba(0,0,0,.6); color: #cffe00; font-size: 9.5px; font-weight: 800; padding: 3px 8px; border-radius: 99px; }
  .hb-neon-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.72) 0%, transparent 55%); }
  .hb-neon-info { position: absolute; bottom: 8px; right: 10px; left: 10px; text-align: right; }
  .hb-neon-name { margin: 0; font-size: 12px; font-weight: 700; color: #fff; }
  .hb-neon-tag { margin: 1px 0 0; font-size: 10px; color: rgba(255,255,255,.6); }
  html.light .hb-neon-card { box-shadow:none; }
  html.light .hb-neon-card:hover { border-color:#cffe00; box-shadow:0 0 22px rgba(207,254,0,.35); }
  @media (max-width: 639px) { .hb-neon-card { width: 134px; } }
  @media (min-width: 1024px) { .hb-neon-card { width: 186px; } }

  /* ── مدل اختصاصی وطن: اسلایدر سینمایی ── */
  .hb-cinema-row { display:flex; gap:14px; overflow-x:auto; scrollbar-width:none; padding:8px 2px 16px; direction:rtl; }
  .hb-cinema-row::-webkit-scrollbar { display:none; }
  .hb-cinema-card { flex:0 0 auto; width:min(78vw,390px); text-decoration:none; }
  .hb-cinema-media { position:relative; aspect-ratio:16/9; overflow:hidden; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; border:1px solid rgba(255,255,255,.1); }
  .hb-cinema-shade { position:absolute; inset:0; background:linear-gradient(90deg,transparent 20%,rgba(0,0,0,.12) 52%,rgba(0,0,0,.88) 100%); }
  .hb-cinema-index { position:absolute; top:14px; right:16px; font-size:11px; letter-spacing:2px; color:#cffe00; font-weight:800; }
  .hb-cinema-copy { position:absolute; right:16px; bottom:15px; left:54px; }
  .hb-cinema-copy p { margin:0; color:#fff; font-size:16px; font-weight:900; }
  .hb-cinema-copy span { display:block; margin-top:3px; color:rgba(255,255,255,.62); font-size:10.5px; }
  .hb-cinema-credit { position:absolute; left:13px; bottom:14px; display:flex; align-items:center; gap:4px; color:#cffe00; font-size:9.5px; font-weight:800; }
  .hb-cinema-card:hover .hb-cinema-media { border-color:#cffe00; }
  @media (max-width:639px) { .hb-cinema-card { width:82vw; } .hb-cinema-copy p { font-size:13px; } }

  /* ── مدل اختصاصی وطن: اسلایدر مینیمال ── */
  .hb-minimal-row { display:flex; gap:12px; overflow-x:auto; scrollbar-width:none; padding:8px 2px 16px; direction:rtl; }
  .hb-minimal-row::-webkit-scrollbar { display:none; }
  .hb-minimal-card { flex:0 0 auto; width:180px; color:#fff; text-decoration:none; }
  .hb-minimal-media { position:relative; aspect-ratio:1/1; border-radius:var(--hb-product-card-radius); overflow:hidden; border:1.5px solid transparent; transition:border-color .25s ease; }
  .hb-minimal-image { position:absolute; inset:0; background-size:cover; background-position:center; transition:transform .32s ease; }
  .hb-minimal-arrow { position:absolute; z-index:2; left:10px; bottom:10px; width:30px; height:30px; display:flex; align-items:center; justify-content:center; border-radius:50%; background:#fff; color:#111; font-size:10px; transform:translateY(8px); opacity:0; transition:.25s; }
  .hb-minimal-card:hover .hb-minimal-media { border-color:#cffe00; }
  .hb-minimal-card:hover .hb-minimal-image { transform:scale(1.045); }
  .hb-minimal-card:hover .hb-minimal-arrow { transform:translateY(0); opacity:1; }
  .hb-minimal-copy { padding:9px 2px 0; }
  .hb-minimal-copy p { margin:5px 0 0; font-size:12.5px; font-weight:800; color:#fff; }
  .hb-minimal-meta { display:flex; align-items:center; justify-content:space-between; gap:8px; font-size:9.5px; color:rgba(255,255,255,.48); }
  .hb-minimal-copy b { color:#cffe00; font-weight:800; }
  html.light .hb-minimal-card, html.light .hb-minimal-copy p { color:#111; }
  html.light .hb-minimal-meta { color:rgba(0,0,0,.48); }
  html.light .hb-minimal-card:hover .hb-minimal-media { border-color:#cffe00; }
  @media (max-width:639px) { .hb-minimal-card { width:145px; } }

  /* ── مدل اختصاصی وطن: گرید ادیتوریال ── */
  .hb-editorial-grid { display:grid; grid-template-columns:1.5fr 1fr 1fr; grid-auto-rows:170px; gap:12px; margin-top:12px; }
  .hb-editorial-card { position:relative; overflow:hidden; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; text-decoration:none; border:1px solid rgba(255,255,255,.1); }
  .hb-editorial-card.is-featured { grid-row:span 2; }
  .hb-editorial-shade { position:absolute; inset:0; background:linear-gradient(to top,rgba(0,0,0,.82),transparent 62%); }
  .hb-editorial-kicker { position:absolute; top:12px; right:12px; padding:4px 9px; border-radius:99px; background:rgba(0,0,0,.55); color:#cffe00; font-size:9px; font-weight:800; }
  .hb-editorial-copy { position:absolute; right:13px; bottom:12px; left:12px; }
  .hb-editorial-copy p { margin:0; color:#fff; font-size:13px; font-weight:900; }
  .hb-editorial-copy span { color:rgba(255,255,255,.58); font-size:9.5px; }
  .hb-editorial-card.is-featured .hb-editorial-copy p { font-size:18px; }
  .hb-editorial-card:hover { border-color:#cffe00; }
  @media (max-width:639px) { .hb-editorial-grid { grid-template-columns:1fr 1fr; grid-auto-rows:145px; } .hb-editorial-card.is-featured { grid-column:span 2; grid-row:span 2; } }

  /* ── آزمایشگاه هاور کارت محصول ── */
  .hb-hover-showcase { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-top:12px; }
  .hb-hover-card { min-width:0; color:#fff; text-decoration:none; }
  .hb-hover-media { position:relative; aspect-ratio:4/5; overflow:hidden; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; border:1.5px solid transparent; transition:transform .3s ease,border-color .3s ease,box-shadow .3s ease; }
  .hb-hover-model-name { position:absolute; top:9px; right:9px; z-index:3; padding:4px 8px; border-radius:99px; background:rgba(0,0,0,.62); color:#cffe00; font-size:9px; font-weight:800; }
  .hb-hover-shade { position:absolute; inset:0; background:linear-gradient(to top,rgba(0,0,0,.82),transparent 62%); transition:opacity .3s ease; }
  .hb-hover-info { position:absolute; z-index:2; right:11px; left:11px; bottom:10px; transition:transform .3s ease,opacity .3s ease; }
  .hb-hover-info p { margin:0; color:#fff; font-size:12px; font-weight:800; }
  .hb-hover-info span { color:rgba(255,255,255,.6); font-size:9.5px; }
  .hb-hover-card small { display:block; margin-top:6px; color:rgba(255,255,255,.38); font-size:9px; direction:ltr; text-align:right; }
  .hb-hover-card.hover_zoom:hover .hb-hover-media { transform:scale(1.045); }
  .hb-hover-card.hover_frame:hover .hb-hover-media { border-color:#cffe00; }
  .hb-hover-card.hover_reveal .hb-hover-info { opacity:0; transform:translateY(12px); }
  .hb-hover-card.hover_reveal:hover .hb-hover-info { opacity:1; transform:translateY(0); }
  .hb-hover-card.hover_reveal:hover .hb-hover-shade { opacity:1; }
  .hb-hover-card.hover_lift:hover .hb-hover-media { transform:translateY(-7px); box-shadow:0 18px 32px rgba(0,0,0,.55); }
  html.light .hb-hover-card small { color:rgba(0,0,0,.42); }
  @media (max-width:900px) { .hb-hover-showcase { grid-template-columns:repeat(2,minmax(0,1fr)); } }

  /* فضای امن افکت: هاله داخل مرز کانتینر اسکرول بریده نمی‌شود. */
  .home-cards-scroll,.hb-peek-scroll,.hb-intro-products,.hb-large-row,.hb-glass-row,.hb-neon-row,.hb-cinema-row,.hb-minimal-row,.hb-motion-row,.hb-video-loop-row {
    padding-top:26px; padding-bottom:30px; margin-top:-18px; margin-bottom:-16px;
  }
  .hb-intro-dual-products { padding-top:24px; padding-bottom:24px; margin-top:-24px; margin-bottom:-24px; }

  /* موتور مشترک ۱۵ مدل هاور؛ مدل انتخاب‌شده روی خود سکشن ذخیره می‌شود. */
  .hb-section :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card),
  .hb-library-media {
    border:1.5px solid transparent; box-shadow:none;
    transition:border-color .28s ease,box-shadow .28s ease,transform .32s ease,filter .32s ease,opacity .32s ease;
  }
  .hb-section :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover { border-color:transparent; box-shadow:none; transform:none; }
  .hb-section:not(.hb-hover-effect--zoom_soft) .hb-minimal-card:hover .hb-minimal-image { transform:none; }
  .hb-hover-effect--neon_glow :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--neon_glow:hover .hb-library-media { border-color:#cffe00; box-shadow:0 0 18px rgba(207,254,0,.3); transform:translateY(-2px); }
  .hb-hover-effect--grayscale_color :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-media,.hb-glass-media,.hb-neon-card,.hb-cinema-media,.hb-minimal-image,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card),
  .hb-library-card.hb-hover-effect--grayscale_color .hb-library-media { filter:grayscale(1); }
  .hb-hover-effect--grayscale_color :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-card,.hb-minimal-card,.hb-editorial-card,.hb-motion-card,.hb-video-loop-card):hover :is(.hb-large-media,.hb-glass-media,.hb-minimal-image),
  .hb-hover-effect--grayscale_color :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-neon-card,.hb-cinema-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--grayscale_color:hover .hb-library-media { filter:grayscale(0); }
  .hb-hover-effect--zoom_soft :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--zoom_soft:hover .hb-library-media { transform:scale(1.045); }
  .hb-hover-effect--lift_shadow :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--lift_shadow:hover .hb-library-media { transform:translateY(-8px); box-shadow:0 18px 34px rgba(0,0,0,.55); }
  .hb-hover-effect--tilt :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--tilt:hover .hb-library-media { transform:perspective(700px) rotateX(4deg) rotateY(-7deg) scale(1.02); }
  .hb-hover-effect--blur_focus :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card),
  .hb-library-card.hb-hover-effect--blur_focus .hb-library-media { filter:blur(2px); }
  .hb-hover-effect--blur_focus :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--blur_focus:hover .hb-library-media { filter:blur(0); }
  .hb-hover-effect--border_draw :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--border_draw:hover .hb-library-media { border-color:#cffe00; box-shadow:inset 0 0 0 2px rgba(207,254,0,.3); }
  .hb-hover-effect--pulse :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--pulse:hover .hb-library-media { animation:hb-pulse .75s ease-in-out infinite alternate; }
  .hb-hover-effect--darken :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--darken:hover .hb-library-media { filter:brightness(.62); }
  .hb-hover-effect--saturate :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--saturate:hover .hb-library-media { filter:saturate(1.8) contrast(1.08); }
  .hb-hover-effect--rotate_soft :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover,
  .hb-library-card.hb-hover-effect--rotate_soft:hover .hb-library-media { transform:rotate(-1.8deg) scale(1.025); }
  .hb-hover-effect--overlay_reveal :is(.home-card-info,.hb-neon-info,.hb-editorial-copy,.hb-library-caption),
  .hb-hover-effect--slide_caption :is(.home-card-info,.hb-neon-info,.hb-editorial-copy,.hb-library-caption) { opacity:0; transform:translateY(14px); transition:.3s ease; }
  .hb-hover-effect--overlay_reveal :is(.home-card,.hb-intro-dual-card,.hb-neon-card,.hb-editorial-card,.hb-library-card):hover :is(.home-card-info,.hb-neon-info,.hb-editorial-copy,.hb-library-caption),
  .hb-hover-effect--slide_caption :is(.home-card,.hb-intro-dual-card,.hb-neon-card,.hb-editorial-card,.hb-library-card):hover :is(.home-card-info,.hb-neon-info,.hb-editorial-copy,.hb-library-caption) { opacity:1; transform:translateY(0); }
  .hb-effect-shine { position:absolute; inset:-30% auto -30% -45%; width:32%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent); transform:skewX(-18deg); opacity:0; pointer-events:none; }
  .hb-library-card.hb-hover-effect--shine:hover .hb-effect-shine { opacity:1; animation:hb-shine .8s ease forwards; }
  .hb-section.hb-hover-effect--shine :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card)::after { content:''; position:absolute; z-index:8; inset:-30% auto -30% -45%; width:32%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent); transform:skewX(-18deg); opacity:0; pointer-events:none; }
  .hb-section.hb-hover-effect--shine :is(.home-card,.hb-peek-card,.hb-bento-item,.hb-wide-card,.hb-intro-dual-card,.hb-large-card,.hb-glass-item,.hb-neon-card,.hb-cinema-media,.hb-minimal-media,.hb-editorial-card,.hb-motion-media,.hb-video-loop-card):hover::after { opacity:1; animation:hb-shine .8s ease forwards; }
  .hb-effect-token { position:absolute; z-index:3; top:10px; left:10px; color:#cffe00; }
  .hb-library-card.hb-hover-effect--token_bounce:hover .hb-effect-token { animation:hb-token-bounce .55s ease-in-out infinite alternate; }
  .hb-section.hb-hover-effect--token_bounce :is(.home-card,.hb-wide-item,.hb-intro-dual-card,.hb-large-card,.hb-neon-card,.hb-cinema-card,.hb-minimal-card,.hb-motion-card):hover :is(.home-card-credit i,.hb-card-credit i,.hb-intro-dual-credit i,.hb-cinema-credit i,.hb-minimal-copy b i,.hb-motion-meta b i) { display:inline-block; animation:hb-token-bounce .55s ease-in-out infinite alternate; }
  @keyframes hb-pulse { to { transform:scale(1.025); box-shadow:0 0 20px rgba(207,254,0,.25); } }
  @keyframes hb-shine { from { left:-45%; } to { left:120%; } }
  @keyframes hb-token-bounce { to { transform:translateY(-7px) rotate(8deg); } }

  /* کتابخانه مدل‌های هاور */
  .hb-hover-library { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; padding-block:24px; }
  .hb-hover-library.hb-hover-cols-2,.hb-hover-showcase.hb-hover-cols-2 { grid-template-columns:repeat(2,minmax(0,1fr)); }
  .hb-hover-library.hb-hover-cols-3,.hb-hover-showcase.hb-hover-cols-3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
  .hb-hover-library.hb-hover-cols-4,.hb-hover-showcase.hb-hover-cols-4 { grid-template-columns:repeat(4,minmax(0,1fr)); }
  .hb-hover-library.hb-hover-cols-5,.hb-hover-showcase.hb-hover-cols-5 { grid-template-columns:repeat(5,minmax(0,1fr)); }
  .hb-library-card { color:#fff; text-decoration:none; min-width:0; }
  .hb-library-media { position:relative; aspect-ratio:4/5; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; overflow:hidden; }
  .hb-library-caption { position:absolute; z-index:2; right:9px; left:9px; bottom:9px; padding:7px; border-radius:9px; background:rgba(0,0,0,.68); }
  .hb-library-caption b,.hb-library-caption small { display:block; }
  .hb-library-caption b { font-size:10px; } .hb-library-caption small { color:#cffe00; font-size:8px; direction:ltr; }

  /* پنج مدل سکشن متحرک */
  .hb-motion-row,.hb-video-loop-row { display:flex; gap:14px; overflow-x:auto; scrollbar-width:none; }
  .hb-motion-row::-webkit-scrollbar,.hb-video-loop-row::-webkit-scrollbar { display:none; }
  .hb-motion-card { flex:0 0 170px; color:#fff; text-decoration:none; }
  .hb-motion-media { position:relative; aspect-ratio:4/5; overflow:hidden; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; }
  .hb-motion-meta { display:flex; justify-content:space-between; gap:8px; padding-top:7px; font-size:10px; }
  .hb-motion-meta b { color:#cffe00; white-space:nowrap; }
  .hb-motion-orbit { position:absolute; z-index:2; top:10px; left:10px; color:#cffe00; }
  .hb-motion-shine { position:absolute; inset:-25% auto -25% -40%; width:28%; background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent); transform:skewX(-18deg); }
  .hb-motion-row--token .hb-motion-meta b i { animation:hb-token-bounce .55s ease-in-out infinite alternate; }
  .hb-motion-row--float .hb-motion-card { animation:hb-float 2.8s ease-in-out infinite alternate; animation-delay:calc(var(--hb-motion-index) * -.18s); }
  .hb-motion-row--shimmer .hb-motion-shine { animation:hb-motion-shine 2.2s ease-in-out infinite; animation-delay:calc(var(--hb-motion-index) * .2s); }
  .hb-motion-row--orbit .hb-motion-orbit { animation:hb-orbit 2.4s linear infinite; transform-origin:70px 86px; }
  .hb-motion-row--wave .hb-motion-card { animation:hb-wave 1.8s ease-in-out infinite alternate; animation-delay:calc(var(--hb-motion-index) * -.15s); }
  @keyframes hb-float { to { transform:translateY(-8px); } }
  @keyframes hb-motion-shine { 0% { left:-40%; } 55%,100% { left:125%; } }
  @keyframes hb-orbit { to { transform:rotate(360deg); } }
  @keyframes hb-wave { to { transform:translateY(-9px); } }

  /* ویدیوی حلقه‌ای */
  .hb-video-loop-card { position:relative; flex:0 0 min(76vw,340px); aspect-ratio:16/10; overflow:hidden; border-radius:var(--hb-product-card-radius); color:#fff; }
  .hb-video-loop-card video,.hb-video-loop-fallback { width:100%; height:100%; object-fit:cover; display:block; background-size:cover; background-position:center; }
  .hb-video-loop-fallback { animation:hb-video-fallback 6s ease-in-out infinite alternate; }
  .hb-video-loop-info { position:absolute; right:12px; bottom:11px; z-index:2; text-shadow:0 2px 7px #000; }
  .hb-video-loop-info b,.hb-video-loop-info small { display:block; } .hb-video-loop-info b { font-size:13px; } .hb-video-loop-info small { font-size:9px; opacity:.7; }
  .hb-video-live { position:absolute; z-index:2; top:10px; right:10px; padding:4px 8px; border-radius:99px; background:rgba(0,0,0,.6); font-size:8px; }
  .hb-video-live i { display:inline-block; width:6px; height:6px; border-radius:50%; background:#cffe00; animation:hb-live 1s infinite alternate; }
  @keyframes hb-video-fallback { to { transform:scale(1.08); } } @keyframes hb-live { to { opacity:.25; } }

  /* چهار مدل اسکرول متفاوت */
  .hb-scroll-vertical { max-height:390px; overflow-y:auto; display:grid; gap:10px; padding:12px 2px; scrollbar-width:thin; }
  .hb-scroll-vertical-card { display:grid; grid-template-columns:72px 1fr 28px; align-items:center; gap:11px; min-height:72px; padding:7px; border-radius:var(--hb-product-card-radius); background:rgba(255,255,255,.05); color:#fff; text-decoration:none; }
  .hb-scroll-vertical-card > span { width:72px; height:58px; border-radius:10px; background-size:cover; background-position:center; }
  .hb-scroll-vertical-card b,.hb-scroll-vertical-card small { display:block; } .hb-scroll-vertical-card b { font-size:11px; } .hb-scroll-vertical-card small { font-size:9px; opacity:.5; }
  .hb-marquee { overflow:hidden; padding-block:24px; }
  .hb-marquee-track { display:flex; width:max-content; gap:12px; animation:hb-marquee 24s linear infinite; }
  .hb-marquee:hover .hb-marquee-track { animation-play-state:paused; }
  .hb-marquee-card { position:relative; width:150px; aspect-ratio:4/5; flex:0 0 auto; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; color:#fff; overflow:hidden; }
  .hb-marquee-card span,.hb-stack-card span,.hb-wheel-card span { position:absolute; right:9px; bottom:8px; left:9px; font-size:10px; font-weight:800; text-shadow:0 2px 6px #000; }
  @keyframes hb-marquee { to { transform:translateX(50%); } }
  .hb-scroll-stack { display:flex; overflow-x:auto; gap:0; padding:30px 20px 34px; scrollbar-width:none; }
  .hb-stack-card { position:relative; flex:0 0 190px; aspect-ratio:4/5; margin-left:-68px; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; transform:rotate(var(--hb-stack-angle)); box-shadow:-12px 4px 25px rgba(0,0,0,.45); color:#fff; transition:.3s; }
  .hb-stack-card:hover { transform:translateY(-12px) rotate(0); z-index:5; }
  .hb-scroll-wheel { display:flex; align-items:center; gap:8px; overflow-x:auto; padding:35px 16px; scrollbar-width:none; perspective:800px; }
  .hb-wheel-card { position:relative; flex:0 0 155px; aspect-ratio:4/5; border-radius:var(--hb-product-card-radius); background-size:cover; background-position:center; color:#fff; transform:rotateY(var(--hb-wheel-angle)) translateY(var(--hb-wheel-drop)); transition:.3s; }
  .hb-wheel-card:hover { transform:rotateY(0) translateY(-8px) scale(1.04); }
  @media (max-width:639px) {
    .hb-hover-library,.hb-hover-showcase { grid-template-columns:repeat(2,minmax(0,1fr)) !important; gap:10px; }
    .hb-motion-card { flex-basis:142px; }
    .hb-video-loop-card { flex-basis:86vw; }
    .hb-stack-card { flex-basis:155px; margin-left:-54px; }
    .hb-wheel-card { flex-basis:138px; }
  }
  @media (prefers-reduced-motion:reduce) {
    .hb-motion-card,.hb-motion-shine,.hb-motion-orbit,.hb-marquee-track,.hb-video-loop-fallback,.hb-video-live i { animation:none !important; }
  }

  /* در موبایل، تمام بدنه‌های تصویری از لبه واقعی viewport شروع می‌شوند؛ عنوان سکشن داخل padding صفحه می‌ماند. */
  @media (max-width:639px) {
    .hb-tabs-row { gap:6px; padding-bottom:10px; }
    .hb-tab-pill { font-size:10.5px; padding:6px 12px; }
    .hb-section .home-cards-scroll,
    .hb-section .hb-peek-scroll,
    .hb-section .hb-intro-products,
    .hb-section .hb-intro-dual-products,
    .hb-section .hb-large-row,
    .hb-section .hb-tabs-row,
    .hb-section .hb-glass-row,
    .hb-section .hb-neon-row,
    .hb-section .hb-cinema-row,
    .hb-section .hb-minimal-row,
    .hb-section .hb-grid,
    .hb-section .hb-bento,
    .hb-section .hb-family-duo,
    .hb-section .hb-video-spotlight,
    .hb-section .hb-editorial-grid,
    .hb-section .hb-hover-showcase,
    .hb-section .hb-hover-library,
    .hb-section .hb-motion-row,
    .hb-section .hb-video-loop-row,
    .hb-section .hb-scroll-vertical,
    .hb-section .hb-marquee,
    .hb-section .hb-scroll-stack,
    .hb-section .hb-scroll-wheel,
    .hb-section .hb-hero,
    .hb-section .hb-banner {
      width:calc(100% + 32px);
      margin-left:-16px;
      margin-right:-16px;
      padding-left:0;
      padding-right:0;
      scroll-padding-inline:0;
    }
    .hb-intro-row--dual { background:transparent; }
  }

</style>
