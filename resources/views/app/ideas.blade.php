@extends('layouts.app')

@section('content')
@php
  // ── سه ردیف دسته‌بندی، هر ردیف با موضوع مستقل (کسب‌وکار / سبک عکاسی / تم و مناسبت) ──
  $xCatBusiness = ['کسب‌وکار','فروشگاه اینترنتی','رستوران و کافه','املاک','پزشکی و سلامت','آموزشی','فناوری و استارتاپ','مد و پوشاک','آرایشی و زیبایی','خودرو','گردشگری','خدمات حقوقی','مالی و بانکی','ساخت‌وساز','کشاورزی','رسانه و تبلیغات','ورزشی','صنعتی'];
  $xCatStyle    = ['پرتره','فشن','مینیمال','سینمایی','خیابانی','وینتیج','سیاه و سفید','ماکرو','هوایی','معماری','استودیویی','مستند','کانسپچوال','رترو','هنری','نور کم','رنگارنگ','کلوزآپ'];
  $xCatTheme    = ['تولد','عروسی','نوروز','کریسمس','عاشقانه','دورهمی دوستان','خانوادگی','حیوان خانگی','فانتزی','علمی‌تخیلی','انیمه','هنر دیجیتال','رویایی','ماجراجویی','طبیعت وحشی','زمستانی','تابستانی','جشن'];

  // ── منابع نمونه (عکس/ویدیو) ──
  $xImages = [
    'assets/img/9cb93b50-d93f-462f-b6d4-113f63ffc603.avif',
    'assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg',
    'assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg',
    'assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg',
    'assets/img/Screenshot-2025-12-09-at-12.33.35-PM.avif',
    'assets/img/ai-photo-editor-prompt.webp',
    'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
    'assets/img/best-friends-ai-prompt-2.webp',
    'assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg',
    'assets/img/elegant-woman-cafe-portrait-by-promptplum.avif',
    'assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp',
    'assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp',
    'assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp',
    'assets/img/hmxsjse1drg8xqmj0mda.webp',
    'assets/img/images.jpg',
    'assets/img/lookaside.fbsbx.webp',
    'assets/img/lookasidwee.fbsbx.webp',
    'assets/img/lookasjide.fbsbx.webp',
    'assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif',
    'assets/img/prompt-for-gemini-ai-girl.webp',
    'assets/img/promptbank176.webp',
    'assets/img/promptbank234.webp',
  ];
  $xVideos = [
    'assets/videos/a1be8a17-0f52-44e3-8693-6f2d7a3056b2.mp4',
    'assets/videos/223e22d8-e83b-4862-9813-cdc873688f9b.mp4',
    'assets/videos/94debe64-1efa-4ef5-a881-1c441f84d10a.mp4',
    'assets/videos/60ed34f8-ed85-4ae0-9b63-191dcbe11800.mp4',
  ];
  $xNames = ['پرتره مدرن','کافه گرل','فشن استودیو','اسب و طبیعت','سینماتیک مرد','کوپل دوچرخه','رترو گل','لحظه احساسی','پرتره خیابانی','مهتاب','رترو چیر','بهترین دوست','ادیت فشن','ادیت طبیعت','پرتره کینگ','لوکیشن باز','لحظه خاص','وینتیج گرل','نگاه هنری','سبک مدرن','پرتره شب','فشن خیابانی'];
  $xTags  = ['پرتره','فشن','کسب‌وکار','طبیعت','سینما','کوپل','وینتیج','هنری'];

  // ── وزن‌دهی نسبت کاشی‌ها: بیشترین سهم ۱:۱ تا فضای خالی باقی نماند (بقیه با کاشی ۱:۱ پر می‌شود) ──
  $xSizeWeights = [
    'size-1x1'  => 64,
    'size-wide' => 14, // نسبت ۲ به ۱
    'size-tall' => 14, // نسبت ۱ به ۲
    'size-big'  => 8,  // نسبت ۲ به ۲
  ];
  $xSizePool = [];
  foreach ($xSizeWeights as $cls => $weight) {
    for ($k = 0; $k < $weight; $k++) { $xSizePool[] = $cls; }
  }

  $xTilesCount = 52;

  // ── پخش رندوم تصاویر بدون تکرار نزدیک به هم (تا دو کاشی مجاور عکس یکسان نشان ندهند) ──
  $xImagePool = [];
  while (count($xImagePool) < $xTilesCount) { $xImagePool = array_merge($xImagePool, $xImages); }
  shuffle($xImagePool);
  for ($i = 1; $i < count($xImagePool); $i++) {
    if ($xImagePool[$i] === $xImagePool[$i - 1]) {
      $swapWith = ($i + 1) < count($xImagePool) ? $i + 1 : 0;
      [$xImagePool[$i], $xImagePool[$swapWith]] = [$xImagePool[$swapWith], $xImagePool[$i]];
    }
  }

  $xTiles = [];
  for ($i = 0; $i < $xTilesCount; $i++) {
    $isVideo = ($i % 6 === 4); // هر ۶ کارت یکی ویدیو
    $xTiles[] = [
      'video' => $isVideo,
      'src'   => $isVideo ? asset($xVideos[$i % count($xVideos)]) : asset($xImagePool[$i]),
      'name'  => $xNames[$i % count($xNames)],
      'tag'   => $xTags[$i % count($xTags)],
      'size'  => $xSizePool[array_rand($xSizePool)],
    ];
  }
@endphp

<div class="explore-page" dir="rtl">

  {{-- ===== هدر: آیکون هوش مصنوعی + عنوان + توضیح + سرچ/فیلتر ===== --}}
  <section class="xp-header">
    <div class="xp-title-row">
      <span class="xp-title-icon">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
      </span>
      <h1 class="xp-title">اکسپلور</h1>
    </div>
    <p class="xp-subtitle">هزاران ایده و پرامپت آماده برای ساخت تصویر و ویدیوی حرفه‌ای با هوش مصنوعی</p>

    <div class="xp-search-row">
      <div class="xp-search-box">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:rgba(255,255,255,0.45);flex-shrink:0;">
          <circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/>
        </svg>
        <input type="text" class="xp-search-input" placeholder="جستجو در ۱۲۰۰+ طرح، سبک یا دسته‌بندی..." dir="rtl">
      </div>

      <button type="button" class="xp-filter-btn" id="xpFilterBtn" aria-expanded="false">
        <i class="fa-solid fa-sliders"></i>
        <span>فیلتر</span>
      </button>

      <div class="xp-filter-panel" id="xpFilterPanel">
        <div class="xp-filter-group">
          <p class="xp-filter-label">دسته‌بندی</p>
          <div class="xp-filter-chips" data-group="cat">
            @foreach ($xCatBusiness as $cat)
              <button type="button" class="xp-filter-chip {{ $loop->first ? 'active' : '' }}">{{ $cat }}</button>
            @endforeach
          </div>
        </div>

        <div class="xp-filter-group">
          <p class="xp-filter-label">نوع محتوا</p>
          <div class="xp-filter-chips" data-group="type" data-mode="single">
            <button type="button" class="xp-filter-chip active">همه</button>
            <button type="button" class="xp-filter-chip">فقط عکس</button>
            <button type="button" class="xp-filter-chip">فقط ویدیو</button>
          </div>
        </div>

        <div class="xp-filter-group">
          <p class="xp-filter-label">مرتب‌سازی</p>
          <select class="xp-filter-select" id="xpFilterSort">
            <option>جدیدترین</option>
            <option>محبوب‌ترین</option>
            <option>تصادفی</option>
          </select>
        </div>

        <div class="xp-filter-actions">
          <button type="button" class="xp-filter-clear" id="xpFilterClear">پاک کردن</button>
          <button type="button" class="xp-filter-apply" id="xpFilterApply">اعمال فیلتر</button>
        </div>
      </div>
    </div>
  </section>

  {{-- ===== دسته‌بندی‌ها: سه ردیف مستقل (کسب‌وکار / سبک عکاسی / تم و مناسبت) ===== --}}
  <section class="xp-cats-section">
    <div class="xp-cats-scroll">
      <div class="xp-cats-row" data-row="business">
        @foreach ($xCatBusiness as $cat)
          <button type="button" class="xp-cat-box {{ $loop->first ? 'active' : '' }}">{{ $cat }}</button>
        @endforeach
      </div>
      <div class="xp-cats-row" data-row="style">
        @foreach ($xCatStyle as $cat)
          <button type="button" class="xp-cat-box {{ $loop->first ? 'active' : '' }}">{{ $cat }}</button>
        @endforeach
      </div>
      <div class="xp-cats-row" data-row="theme">
        @foreach ($xCatTheme as $cat)
          <button type="button" class="xp-cat-box {{ $loop->first ? 'active' : '' }}">{{ $cat }}</button>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ===== گرید محتوا: نسبت‌های ترکیبی رندوم، بدون فضای خالی ===== --}}
  <section class="xp-grid-section">
    <div class="xp-grid">
      @foreach ($xTiles as $tile)
        <div class="xp-tile {{ $tile['size'] }}">
          @if($tile['video'])
            <video class="xp-tile-media" src="{{ $tile['src'] }}" autoplay muted loop playsinline preload="metadata"></video>
          @else
            <img class="xp-tile-media" src="{{ $tile['src'] }}" alt="{{ $tile['name'] }}" loading="lazy">
          @endif
          <div class="xp-tile-overlay"></div>
          <i class="fa-solid {{ $tile['video'] ? 'fa-video' : 'fa-image' }} xp-tile-type"></i>
          <div class="xp-tile-info">
            <p class="xp-tile-name">{{ $tile['name'] }}</p>
            <p class="xp-tile-tag">{{ $tile['tag'] }}</p>
          </div>
        </div>
      @endforeach
    </div>
  </section>

</div>
@endsection

@push('styles')
<style>
  html, body { background: #000000; overflow-x: hidden; }
  html.light, html.light body { background: #ffffff; }

  .explore-page {
    width: 100%; max-width: 480px;
    margin: 0 auto;
    background: var(--bg, #000000);
    min-height: 100vh;
    padding-bottom: 100px;
  }

  .explore-page i[class*="fa-"] { display: inline-block; }

  /* ── هدر ── */
  .xp-header { padding: calc(env(safe-area-inset-top) + 18px) 16px 14px 16px; }

  .xp-title-row { display: flex; align-items: center; gap: 10px; }
  .xp-title-icon {
    width: 34px; height: 34px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    border-radius: 11px;
    background: linear-gradient(135deg, #0BBF53 0%, #0a8f3f 100%);
    box-shadow: 0 4px 14px rgba(11,191,83,0.35);
    color: #ffffff; font-size: 14px;
  }
  .xp-title {
    margin: 0; font-size: 19px; font-weight: 800;
    color: var(--text, #ffffff);
  }
  .xp-subtitle {
    margin: 6px 0 0 0;
    font-size: 12px; line-height: 1.6;
    color: rgba(255,255,255,0.55);
    max-width: 560px;
  }
  html.light .xp-subtitle { color: rgba(0,0,0,0.5); }

  /* ── سرچ + فیلتر ── */
  .xp-search-row {
    position: relative;
    display: flex; align-items: stretch; gap: 8px;
    margin-top: 14px;
  }
  .xp-search-box {
    flex: 1; max-width: 560px; min-width: 0;
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 11px 14px;
  }
  html.light .xp-search-box {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.1);
  }
  .xp-search-input {
    flex: 1; min-width: 0; background: transparent; border: none; outline: none;
    font-size: 14px; color: var(--text, #ffffff);
  }
  .xp-search-input::placeholder { color: rgba(255,255,255,0.4); }
  html.light .xp-search-input::placeholder { color: rgba(0,0,0,0.35); }

  .xp-filter-btn {
    flex-shrink: 0;
    display: flex; align-items: center; gap: 8px;
    padding: 0 14px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.07);
    color: rgba(255,255,255,0.85);
    font-size: 13px; font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
  }
  .xp-filter-btn span { display: none; }
  .xp-filter-btn:hover, .xp-filter-btn[aria-expanded="true"] {
    background: #0BBF53; border-color: #0BBF53; color: #ffffff;
  }
  html.light .xp-filter-btn {
    background: rgba(0,0,0,0.05); border-color: rgba(0,0,0,0.1); color: rgba(0,0,0,0.7);
  }
  html.light .xp-filter-btn:hover, html.light .xp-filter-btn[aria-expanded="true"] {
    background: #0BBF53; border-color: #0BBF53; color: #ffffff;
  }

  .xp-filter-panel {
    position: absolute;
    top: calc(100% + 10px);
    left: 0; right: 0;
    background: #111116;
    border: 1px solid #222230;
    border-radius: 14px;
    box-shadow: 0 12px 32px rgba(0,0,0,0.4);
    padding: 16px;
    z-index: 40;
    display: none;
    opacity: 0; transform: translateY(-8px) scale(0.98);
    transition: opacity 0.18s ease, transform 0.18s ease;
  }
  .xp-filter-panel.is-open {
    display: block;
    opacity: 1; transform: translateY(0) scale(1);
  }
  html.light .xp-filter-panel {
    background: #ffffff; border-color: #E5E6E6; box-shadow: 0 12px 32px rgba(0,0,0,0.12);
  }

  .xp-filter-group { margin-bottom: 14px; }
  .xp-filter-group:last-of-type { margin-bottom: 0; }
  .xp-filter-label {
    margin: 0 0 8px 0; font-size: 12px; font-weight: 700;
    color: rgba(255,255,255,0.5);
  }
  html.light .xp-filter-label { color: rgba(0,0,0,0.45); }

  .xp-filter-chips { display: flex; flex-wrap: wrap; gap: 8px; }
  .xp-filter-chip {
    padding: 6px 14px; border-radius: 999px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.65);
    font-size: 12.5px; cursor: pointer;
    transition: all 0.2s;
  }
  .xp-filter-chip.active {
    background: #0BBF53; border-color: #0BBF53; color: #ffffff; font-weight: 700;
  }
  html.light .xp-filter-chip {
    background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.08); color: rgba(0,0,0,0.6);
  }
  html.light .xp-filter-chip.active { color: #ffffff; }

  .xp-filter-select {
    width: 100%; padding: 9px 12px; border-radius: 10px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: var(--text, #ffffff);
    font-size: 13px; font-family: inherit;
    outline: none; cursor: pointer;
  }
  html.light .xp-filter-select {
    background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.1); color: #000;
  }

  .xp-filter-actions {
    display: flex; justify-content: space-between; gap: 10px;
    margin-top: 16px; padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }
  html.light .xp-filter-actions { border-top-color: rgba(0,0,0,0.08); }
  .xp-filter-clear {
    padding: 9px 16px; border-radius: 10px;
    background: transparent; border: 1px solid rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.6); font-size: 13px; cursor: pointer;
  }
  html.light .xp-filter-clear { border-color: rgba(0,0,0,0.12); color: rgba(0,0,0,0.55); }
  .xp-filter-apply {
    flex: 1; padding: 9px 16px; border-radius: 10px;
    background: #0BBF53; border: none;
    color: #ffffff; font-size: 13px; font-weight: 700; cursor: pointer;
  }

  /* ── دسته‌بندی‌ها: سه ردیف مستقل، بدون آیکون ── */
  .xp-cats-section { padding: 14px 16px 0 16px; }
  .xp-cats-scroll {
    display: flex; flex-direction: column; gap: 6px;
    overflow-x: auto; overflow-y: hidden;
    scrollbar-width: none;
    padding-bottom: 4px;
  }
  .xp-cats-scroll::-webkit-scrollbar { display: none; }
  .xp-cats-row { display: flex; gap: 6px; }
  .xp-cat-box {
    flex-shrink: 0;
    padding: 6px 12px; border-radius: 9px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.75);
    font-size: 11.5px; font-weight: 600;
    cursor: pointer; white-space: nowrap;
    transition: all 0.2s;
  }
  .xp-cat-box:hover, .xp-cat-box.active {
    background: #0BBF53; border-color: #0BBF53; color: #ffffff;
  }
  html.light .xp-cat-box {
    background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.08); color: rgba(0,0,0,0.6);
  }

  /* ── گرید محتوا: بدون فضای خالی (dense) ── */
  .xp-grid-section { margin-top: 14px; }
  .xp-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-auto-rows: var(--xp-unit, 159px);
    grid-auto-flow: dense;
    gap: 2px;
  }
  .xp-tile {
    position: relative; overflow: hidden;
    border-radius: 3px;
    background: rgba(255,255,255,0.05);
    cursor: pointer;
  }
  .xp-tile.size-1x1  { grid-column: span 1; grid-row: span 1; }
  .xp-tile.size-wide { grid-column: span 2; grid-row: span 1; }
  .xp-tile.size-tall { grid-column: span 1; grid-row: span 2; }
  .xp-tile.size-big  { grid-column: span 2; grid-row: span 2; }

  .xp-tile-media { width: 100%; height: 100%; object-fit: cover; display: block; }
  .xp-tile-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 55%);
    transition: background 0.3s ease;
  }
  .xp-tile:hover .xp-tile-overlay {
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, transparent 65%);
  }
  .xp-tile-type {
    position: absolute; top: 7px; right: 7px;
    color: #ffffff; font-size: 10px;
    text-shadow: 0 1px 3px rgba(0,0,0,0.6);
  }
  .xp-tile-info { position: absolute; bottom: 8px; right: 8px; text-align: right; }
  .xp-tile-name { margin: 0; font-size: 11.5px; font-weight: 700; color: #ffffff; }
  .xp-tile-tag  { margin: 2px 0 0; font-size: 10px; color: rgba(255,255,255,0.65); }

  /* ══════════════════════════════════
     TABLET — 640px+
  ══════════════════════════════════ */
  @media (min-width: 640px) {
    .explore-page { max-width: 720px; }

    .xp-header { padding: 28px 28px 18px 28px; }
    .xp-title-icon { width: 38px; height: 38px; border-radius: 12px; font-size: 15px; }
    .xp-title { font-size: 22px; }
    .xp-subtitle { font-size: 13px; margin-top: 8px; }

    .xp-search-row { margin-top: 18px; gap: 10px; }
    .xp-search-box { padding: 12px 16px; }
    .xp-filter-btn { padding: 0 18px; }
    .xp-filter-btn span { display: inline; }
    .xp-filter-panel { padding: 18px; }

    .xp-cats-section { padding: 18px 28px 0 28px; }
    .xp-cats-scroll { gap: 8px; }
    .xp-cats-row { gap: 8px; }
    .xp-cat-box { padding: 8px 14px; border-radius: 10px; font-size: 12.5px; }

    .xp-grid-section { margin-top: 20px; }
    .xp-grid { grid-template-columns: repeat(3, 1fr); grid-auto-rows: var(--xp-unit, 237px); gap: 5px; }
    .xp-tile-name { font-size: 12.5px; }
    .xp-tile-tag  { font-size: 10.5px; }
  }

  /* ══════════════════════════════════
     TABLET بزرگ‌تر — 768px+  |  ۴ ستون
  ══════════════════════════════════ */
  @media (min-width: 768px) {
    .explore-page { max-width: 900px; }

    .xp-header { padding: 32px 36px 20px 36px; }
    .xp-cats-section { padding: 18px 36px 0 36px; }

    .xp-grid-section { padding: 0 36px; }
    .xp-grid { grid-template-columns: repeat(4, 1fr); grid-auto-rows: var(--xp-unit, 203px); gap: 6px; }
  }

  /* ══════════════════════════════════
     DESKTOP — 1024px+  |  ۵ ستون
  ══════════════════════════════════ */
  @media (min-width: 1024px) {
    .explore-page { max-width: 1080px; padding-bottom: 60px; }

    .xp-header { padding: 40px 40px 22px 40px; }
    .xp-title-icon { width: 42px; height: 42px; font-size: 17px; }
    .xp-title { font-size: clamp(24px, 2vw, 28px); }
    .xp-subtitle { font-size: 13.5px; }

    .xp-cats-section { padding: 20px 40px 0 40px; }

    .xp-grid-section { padding: 0 40px; }
    .xp-grid { grid-template-columns: repeat(5, 1fr); grid-auto-rows: var(--xp-unit, 194px); gap: 8px; }
    .xp-tile-name { font-size: 13px; }
    .xp-tile-tag  { font-size: 11px; }
  }

  /* ══════════════════════════════════
     LARGE DESKTOP — 1280px+  |  ۶ ستون
  ══════════════════════════════════ */
  @media (min-width: 1280px) {
    .explore-page { max-width: 1280px; }

    .xp-header { padding: 44px 56px 24px 56px; }
    .xp-cats-section { padding: 22px 56px 0 56px; }

    .xp-grid-section { padding: 0 56px; }
    .xp-grid { grid-template-columns: repeat(6, 1fr); grid-auto-rows: var(--xp-unit, 188px); gap: 8px; }
  }
</style>
@endpush

@push('scripts')
<script>
(function () {
  /* ───── گرید: محاسبه دقیق ارتفاع واحد (مربع کامل) بر اساس عرض واقعی ستون ─────
     چون عرض صفحه بین بریک‌پوینت‌ها به‌صورت سیال تغییر می‌کند، عرض واقعی هر ستون
     همیشه محاسبه و به‌عنوان --xp-unit ست می‌شود تا کاشی‌های ۱:۱ و ۲:۲ همیشه
     دقیقاً مربع باشند و کاشی‌های ۲:۱ / ۱:۲ نسبت درست خودشان را داشته باشند. */
  var xpGrid = document.querySelector('.xp-grid');

  function syncGridUnit() {
    if (!xpGrid) return;
    var cs = getComputedStyle(xpGrid);
    var colCount = cs.gridTemplateColumns.split(' ').filter(Boolean).length;
    if (!colCount) return;
    var gap = parseFloat(cs.columnGap || cs.gap) || 0;
    var totalGap = gap * (colCount - 1);
    var unit = (xpGrid.clientWidth - totalGap) / colCount;
    if (unit > 0) {
      xpGrid.style.setProperty('--xp-unit', unit + 'px');
    }
  }

  syncGridUnit();
  document.addEventListener('DOMContentLoaded', syncGridUnit);
  window.addEventListener('load', syncGridUnit);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(syncGridUnit);
  }
  var xpResizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(xpResizeTimer);
    xpResizeTimer = setTimeout(syncGridUnit, 80);
  });
  if (window.ResizeObserver && xpGrid) {
    new ResizeObserver(syncGridUnit).observe(xpGrid);
  }

  /* ───── دسته‌بندی‌ها: انتخاب مستقل در هر ردیف ───── */
  document.querySelectorAll('.xp-cats-row').forEach(function (row) {
    row.querySelectorAll('.xp-cat-box').forEach(function (box) {
      box.addEventListener('click', function () {
        row.querySelectorAll('.xp-cat-box').forEach(function (b) { b.classList.remove('active'); });
        box.classList.add('active');
      });
    });
  });

  /* ───── کلیک کاشی گرید ───── */
  document.querySelectorAll('.xp-tile').forEach(function (tile) {
    tile.addEventListener('click', function () {
      window.location.href = '/app/product/demo';
    });
  });

  /* ───── پنل فیلتر: باز/بسته شدن ───── */
  var filterBtn   = document.getElementById('xpFilterBtn');
  var filterPanel = document.getElementById('xpFilterPanel');

  function openFilter() {
    filterPanel.classList.add('is-open');
    filterBtn.setAttribute('aria-expanded', 'true');
  }
  function closeFilter() {
    filterPanel.classList.remove('is-open');
    filterBtn.setAttribute('aria-expanded', 'false');
  }

  if (filterBtn && filterPanel) {
    filterBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      filterPanel.classList.contains('is-open') ? closeFilter() : openFilter();
    });
    filterPanel.addEventListener('click', function (e) { e.stopPropagation(); });
    document.addEventListener('click', function () { closeFilter(); });
  }

  /* ───── چیپ‌های داخل پنل فیلتر ───── */
  document.querySelectorAll('.xp-filter-chips').forEach(function (group) {
    var single = group.dataset.mode === 'single';
    group.querySelectorAll('.xp-filter-chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        if (single) {
          group.querySelectorAll('.xp-filter-chip').forEach(function (c) { c.classList.remove('active'); });
          chip.classList.add('active');
        } else {
          chip.classList.toggle('active');
        }
      });
    });
  });

  /* ───── پاک کردن فیلتر ───── */
  var clearBtn = document.getElementById('xpFilterClear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      document.querySelectorAll('.xp-filter-chips').forEach(function (group) {
        var chips = group.querySelectorAll('.xp-filter-chip');
        chips.forEach(function (c, idx) {
          c.classList.toggle('active', idx === 0);
        });
      });
      var sortSelect = document.getElementById('xpFilterSort');
      if (sortSelect) sortSelect.selectedIndex = 0;
    });
  }

  /* ───── اعمال فیلتر: فعلا فقط بستن پنل (نمایشی) ───── */
  var applyBtn = document.getElementById('xpFilterApply');
  if (applyBtn) {
    applyBtn.addEventListener('click', function () { closeFilter(); });
  }
}());
</script>
@endpush
