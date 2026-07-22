{{--
  استایل مشترک تمام Sectionهای Home Builder (اسلایدر/گرید/هیرو/بنر/متن/فاصله‌گذار).
  از app/home.blade.php و هر partial رندر داخل sections/ استفاده می‌شود تا کلاس‌ها
  یک‌بار تعریف شوند، نه در هر partial جداگانه (جلوگیری از تکرار/ناهماهنگی رنگ).
  رنگ‌ها هم‌راستا با پالت فعلی صفحه Home (پس‌زمینه تیره #000000 / روشن #ffffff) هستند.
--}}
<style>
  /* ── ردیف‌های افقی اسکرول‌شونده (اسلایدر محصول/دسته‌بندی/مجموعه) ── */
  .home-cards-scroll {
    display: flex; flex-direction: row; gap: 10px; overflow-x: auto; overflow-y: visible;
    scrollbar-width: none; padding: 10px 0 14px 0; direction: rtl;
    margin: 2px -16px 0 -16px; width: calc(100% + 32px); isolation: isolate;
  }
  .home-cards-scroll::-webkit-scrollbar { display: none; }

  .home-card {
    aspect-ratio: 4 / 5; border-radius: 4px; overflow: hidden; position: relative;
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
  .home-section-title { margin-top: 28px; display: flex; justify-content: space-between; align-items: center; direction: rtl; }
  .home-section-title--sub { margin-top: 28px; }
  .home-section-title-right { font-size: 15px; font-weight: 700; color: #fff; }
  .home-section-title-caption { margin: 2px 0 0 0; font-size: 10px; font-weight: 400; color: rgba(255,255,255,.5); }
  .home-section-viewall { flex-shrink: 0; background: rgba(255,255,255,.08); border: 1px solid rgba(255,255,255,.12); border-radius: 6.48px; padding: 4px 10px; font-size: 10.45px; font-weight: 300; color: #fff; cursor: pointer; white-space: nowrap; }
  html.light .home-section-title-right { color: #000; }
  html.light .home-section-title-caption { color: rgba(0,0,0,.5); }
  html.light .home-section-viewall { background: rgba(0,0,0,.05); border: 1px solid rgba(0,0,0,.1); color: #000; }

  /* ── Hero ── */
  .hb-hero { position: relative; margin-top: 28px; border-radius: 16px; overflow: hidden; min-height: 220px; background-size: cover; background-position: center; background-color: rgba(255,255,255,.06); display: flex; align-items: flex-end; }
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
  .hb-banner { margin-top: 28px; display: block; }
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
  .hb-text-block { margin-top: 28px; direction: rtl; }
  .hb-text-block.hb-align-center { text-align: center; }
  .hb-text-block.hb-align-left { text-align: left; }
  .hb-text-heading { margin: 0 0 6px; font-size: 16px; font-weight: 800; color: #fff; }
  .hb-text-body { margin: 0; font-size: 13px; line-height: 1.9; color: rgba(255,255,255,.7); }
  html.light .hb-text-heading { color: #000; }
  html.light .hb-text-body { color: rgba(0,0,0,.65); }

  /* ── فاصله‌گذار ── */
  .hb-spacer.hb-h-small { height: 16px; }
  .hb-spacer.hb-h-medium { height: 32px; }
  .hb-spacer.hb-h-large { height: 64px; }

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
  .hb-peek-card { position: relative; width: 100%; aspect-ratio: 4/5; border-radius: 14px; overflow: hidden; border: 1.5px solid transparent; transition: border-color .25s ease, transform .25s ease; background-size: cover; background-position: center; }
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

  /* ── Layout ۲: گرید بنتو (اندازه‌های نامتقارن) — شات Trending Creations ── */
  .hb-bento { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 90px; gap: 12px; margin-top: 12px; direction: rtl; }
  .hb-bento-item { position: relative; border-radius: 14px; overflow: hidden; background-size: cover; background-position: center; cursor: pointer; border: 1.5px solid transparent; transition: border-color .3s ease, transform .3s ease; }
  .hb-bento-item:hover { transform: scale(1.015); border-color: #cffe00; }
  .hb-bento-item.hb-span-2x2 { grid-column: span 2; grid-row: span 2; }
  .hb-bento-item.hb-span-2x1 { grid-column: span 2; grid-row: span 1; }
  .hb-bento-item.hb-span-1x2 { grid-column: span 1; grid-row: span 2; }
  .hb-bento-badge { position: absolute; top: 8px; right: 8px; background: #cffe00; color: #111; font-size: 9.5px; font-weight: 800; padding: 3px 9px; border-radius: 99px; z-index: 2; }
  .hb-bento-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.5) 0%, transparent 45%); opacity: 0; transition: opacity .3s ease; }
  .hb-bento-item:hover .hb-bento-overlay { opacity: 1; }
  @media (max-width: 639px) { .hb-bento { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 120px; } .hb-bento-item.hb-span-2x2, .hb-bento-item.hb-span-2x1 { grid-column: span 2; } }
  @media (min-width: 1024px) { .hb-bento { grid-auto-rows: 110px; } }

  /* ── Layout ۳: اسلایدر با کارت معرفی — شات Commercial Studio ── */
  .hb-intro-row { display: flex; gap: 14px; overflow-x: auto; scrollbar-width: none; padding: 10px 2px 14px; margin-top: 4px; direction: rtl; }
  .hb-intro-row::-webkit-scrollbar { display: none; }
  .hb-intro-card { flex: 0 0 auto; width: 280px; border-radius: 16px; padding: 20px; background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1); display: flex; flex-direction: column; }
  .hb-intro-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 700; color: rgba(255,255,255,.7); background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); padding: 5px 10px; border-radius: 99px; width: fit-content; margin-bottom: 14px; }
  .hb-intro-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #cffe00; }
  .hb-intro-heading { font-size: 19px; font-weight: 800; color: #fff; margin: 0 0 4px; line-height: 1.4; }
  .hb-intro-heading-accent { color: #cffe00; }
  .hb-intro-desc { font-size: 12px; color: rgba(255,255,255,.55); line-height: 1.8; margin: 0 0 16px; }
  .hb-intro-steps { display: flex; flex-direction: column; gap: 10px; margin-bottom: 16px; }
  .hb-intro-step { display: flex; align-items: center; gap: 10px; font-size: 12px; color: rgba(255,255,255,.85); }
  .hb-intro-step-num { width: 22px; height: 22px; border-radius: 50%; background: rgba(207,254,0,.12); color: #cffe00; font-size: 10.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
  .hb-intro-note { display: flex; align-items: center; gap: 6px; font-size: 10.5px; color: rgba(207,254,0,.85); margin-bottom: 16px; }
  .hb-intro-cta { margin-top: auto; display: inline-flex; align-items: center; justify-content: center; gap: 6px; background: #cffe00; color: #111; font-weight: 700; font-size: 12.5px; padding: 11px 16px; border-radius: 10px; text-decoration: none; }
  .hb-wide-item { flex: 0 0 auto; width: 200px; }
  .hb-wide-card { position: relative; width: 100%; aspect-ratio: 16/10; border-radius: 14px; overflow: hidden; background-size: cover; background-position: center; cursor: pointer; transition: transform .3s ease; }
  .hb-wide-item:hover .hb-wide-card { transform: translateY(-2px); }
  .hb-wide-meta { padding-top: 8px; }
  @media (max-width: 639px) { .hb-intro-card { width: 240px; padding: 16px; } .hb-wide-item { width: 160px; } }
  @media (min-width: 1024px) { .hb-intro-card { width: 320px; } .hb-wide-item { width: 230px; } }
  html.light .hb-intro-card { background: rgba(0,0,0,.03); border-color: rgba(0,0,0,.08); }
  html.light .hb-intro-heading { color: #000; }
  html.light .hb-intro-desc { color: rgba(0,0,0,.55); }
  html.light .hb-intro-step { color: rgba(0,0,0,.8); }
  html.light .hb-intro-badge { background: rgba(0,0,0,.04); border-color: rgba(0,0,0,.1); color: rgba(0,0,0,.6); }

  /* ── Layout ۴: اسلایدر کارت بزرگ — شات Create ── */
  .hb-large-row { display: flex; gap: 14px; overflow-x: auto; scrollbar-width: none; padding: 8px 2px 14px; margin-top: 4px; direction: rtl; }
  .hb-large-row::-webkit-scrollbar { display: none; }
  .hb-large-card { flex: 0 0 auto; width: 78%; max-width: 340px; border-radius: 16px; overflow: hidden; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); cursor: pointer; }
  .hb-large-media { position: relative; aspect-ratio: 16/10; background-size: cover; background-position: center; }
  .hb-large-badge { position: absolute; top: 10px; right: 10px; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 99px; z-index: 2; }
  .hb-large-badge.hb-badge-new, .hb-large-badge.hb-badge-studio { background: #cffe00; color: #111; }
  .hb-large-badge.hb-badge-tool { background: rgba(0,0,0,.55); color: #fff; border: 1px solid rgba(255,255,255,.25); }
  .hb-large-body { padding: 14px; }
  .hb-large-title { font-size: 14px; font-weight: 800; color: #fff; margin: 0 0 4px; }
  .hb-large-desc { font-size: 11.5px; color: rgba(255,255,255,.55); margin: 0; line-height: 1.7; }
  @media (min-width: 640px) { .hb-large-card { width: 340px; } }
  @media (min-width: 1024px) { .hb-large-card { width: 31%; max-width: 380px; } }
  html.light .hb-large-card { background: rgba(0,0,0,.02); border-color: rgba(0,0,0,.08); }
  html.light .hb-large-title { color: #000; }
  html.light .hb-large-desc { color: rgba(0,0,0,.5); }

  /* ── Layout ۵: تب دسته‌بندی + اسلایدر — شات دسته‌بندی بالای Photoshoot ── */
  .hb-tabs-row { display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; padding: 2px 2px 14px; direction: rtl; }
  .hb-tabs-row::-webkit-scrollbar { display: none; }
  .hb-tab-pill { flex: 0 0 auto; font-size: 12px; font-weight: 600; padding: 8px 16px; border-radius: 99px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1); color: rgba(255,255,255,.7); cursor: pointer; white-space: nowrap; transition: all .2s ease; }
  .hb-tab-pill.is-active { background: #cffe00; color: #111; border-color: #cffe00; font-weight: 800; }
  .hb-tabs-panel { display: none; }
  .hb-tabs-panel.is-active { display: block; }
  html.light .hb-tab-pill { background: rgba(0,0,0,.04); border-color: rgba(0,0,0,.1); color: rgba(0,0,0,.6); }
  html.light .hb-tab-pill.is-active { background: #cffe00; color: #111; }

  /* ── خط کردیت روی کارت‌های اورلی‌دار (اسلایدر/گرید/مجموعه پیش‌فرض) ── */
  .home-card-credit { margin: 3px 0 0; font-size: 9.5px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 4px; }
  .home-card-credit i { color: #cffe00; font-size: 8.5px; }

  /* ── استایل نمونه «کارت شیشه‌ای» (Glass) ── */
  .hb-glass-row { display: flex; gap: 12px; overflow-x: auto; scrollbar-width: none; padding: 8px 2px 14px; margin-top: 4px; direction: rtl; }
  .hb-glass-row::-webkit-scrollbar { display: none; }
  .hb-glass-item { flex: 0 0 auto; width: 168px; border-radius: 16px; background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); padding: 8px; transition: transform .25s ease, border-color .25s ease; }
  .hb-glass-item:hover { transform: translateY(-3px); border-color: rgba(207,254,0,.55); }
  .hb-glass-media { width: 100%; aspect-ratio: 1/1; border-radius: 10px; background-size: cover; background-position: center; }
  .hb-glass-body { padding: 8px 4px 4px; }
  html.light .hb-glass-item { background: rgba(0,0,0,.03); border-color: rgba(0,0,0,.1); }
  @media (max-width: 639px) { .hb-glass-item { width: 146px; } }
  @media (min-width: 1024px) { .hb-glass-item { width: 190px; } }

  /* ── استایل نمونه «قاب نئونی» (Neon) ── */
  .hb-neon-row { display: flex; gap: 14px; overflow-x: auto; scrollbar-width: none; padding: 8px 2px 16px; margin-top: 4px; direction: rtl; }
  .hb-neon-row::-webkit-scrollbar { display: none; }
  .hb-neon-card { position: relative; flex: 0 0 auto; width: 152px; aspect-ratio: 4/5; border-radius: 14px; overflow: hidden; background-size: cover; background-position: center; border: 1.5px solid rgba(207,254,0,.45); box-shadow: 0 0 12px rgba(207,254,0,.14); transition: border-color .25s ease, box-shadow .25s ease, transform .25s ease; }
  .hb-neon-card:hover { border-color: #cffe00; box-shadow: 0 0 22px rgba(207,254,0,.35); transform: translateY(-2px); }
  .hb-neon-credit { position: absolute; top: 8px; right: 8px; z-index: 2; display: flex; align-items: center; gap: 4px; background: rgba(0,0,0,.6); color: #cffe00; font-size: 9.5px; font-weight: 800; padding: 3px 8px; border-radius: 99px; }
  .hb-neon-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.72) 0%, transparent 55%); }
  .hb-neon-info { position: absolute; bottom: 8px; right: 10px; left: 10px; text-align: right; }
  .hb-neon-name { margin: 0; font-size: 12px; font-weight: 700; color: #fff; }
  .hb-neon-tag { margin: 1px 0 0; font-size: 10px; color: rgba(255,255,255,.6); }
  html.light .hb-neon-card { box-shadow: 0 0 12px rgba(207,254,0,.3); }
  @media (max-width: 639px) { .hb-neon-card { width: 134px; } }
  @media (min-width: 1024px) { .hb-neon-card { width: 186px; } }

</style>
