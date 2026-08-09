@extends('layouts.app')

@section('content')
@php
  $xTiles = $tiles ?? [];
  $xTermRows = $termRows ?? [[], []];
  $xQuery = $query ?? '';
  $xLayoutStyle = $layoutStyle ?? 'excel_11';
  $xLayoutPatterns = $layoutPatterns ?? \App\Models\FeedSetting::DISPLAY_PATTERNS;
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

    <form class="xp-search-row" action="{{ route('app.explore') }}" method="GET" role="search">
      <div class="xp-search-box">
        <button type="submit" class="xp-search-submit" aria-label="جستجو">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/>
          </svg>
        </button>
        <input type="text" inputmode="search" enterkeyhint="search" name="q" value="{{ $xQuery }}" class="xp-search-input" placeholder="جستجو در محصولات، سبک‌ها، تگ‌ها و دسته‌بندی‌ها..." dir="rtl" autocomplete="off">
        @if($xQuery !== '')
          <a class="xp-search-clear" href="{{ route('app.explore') }}" aria-label="پاک کردن جستجو"><i class="fa-solid fa-xmark"></i></a>
        @endif
      </div>
    </form>
  </section>

  {{-- دسته‌بندی‌ها و هشتگ‌ها مستقیماً از دیتابیس؛ دو ردیف مستقل و بی‌انتها در دسکتاپ --}}
  <section class="xp-cats-section">
    <div class="xp-marquee-list">
      @foreach($xTermRows as $rowIndex => $terms)
        <div class="xp-marquee" data-speed="{{ $rowIndex === 0 ? 20 : 27 }}">
          <div class="xp-marquee-track">
            <div class="xp-marquee-group" data-marquee-source>
              @foreach($terms as $term)
                <a class="xp-cat-box" href="{{ route('app.explore', ['q' => $term['query']]) }}" dir="rtl">{{ $term['label'] }}</a>
              @endforeach
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </section>

  {{-- ===== گرید محتوا: خروجی موتور فید هوشمند — نسبت/رندوم/سبک از داشبورد کنترل می‌شود ===== --}}
  <section class="xp-grid-section">
    <div class="xp-grid" data-search-filtered="{{ $xQuery !== '' ? '1' : '0' }}">
      @forelse ($xTiles as $tile)
        <a href="{{ $tile['link'] ?? '#' }}" class="xp-tile {{ $tile['size'] }} {{ ($tile['type'] ?? 'product') === 'campaign' ? 'xp-tile--campaign' : '' }}" data-tile-size="{{ $tile['size'] }}" data-original-tile-size="{{ $tile['size'] }}" data-tile-type="{{ $tile['type'] ?? 'product' }}" data-allowed-sizes='@json($tile['allowed_sizes'] ?? [$tile['size']])'>
          @if($tile['video'])
            <video class="xp-tile-media" src="{{ $tile['src'] }}" poster="{{ $tile['poster'] ?? '' }}" autoplay muted loop playsinline preload="metadata"></video>
          @else
            <img class="xp-tile-media" src="{{ $tile['src'] }}" alt="{{ $tile['name'] }}" loading="lazy">
          @endif
          <div class="xp-tile-overlay"></div>
          <i class="fa-solid {{ $tile['video'] ? 'fa-video' : 'fa-image' }} xp-tile-type"></i>
          @if(($tile['type'] ?? 'product') === 'campaign')
            <span class="xp-tile-campaign-label">پیشنهاد ویژه</span>
          @endif
          <div class="xp-tile-info">
            <p class="xp-tile-name">{{ $tile['name'] }}</p>
            <p class="xp-tile-tag">{{ $tile['tag'] }}</p>
          </div>
        </a>
      @empty
        <div class="xp-empty" style="grid-column:1/-1; text-align:center; padding:40px 16px; color:rgba(255,255,255,.5);">
          {{ $xQuery !== '' ? 'محصولی مطابق جستجوی شما پیدا نشد.' : 'هنوز محصولی برای نمایش وجود ندارد.' }}
        </div>
      @endforelse
    </div>
  </section>

</div>
@endsection

@push('styles')
<style>
  html, body { background: var(--vatan-bg-page); overflow-x: hidden; }
  html.light, html.light body { background: var(--vatan-bg-page); }

  .explore-page {
    width: 100%; max-width: 480px;
    margin: 0 auto;
    background: var(--bg, var(--vatan-bg-page));
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
    background: linear-gradient(135deg, #cffe00 0%, #0a8f3f 100%);
    box-shadow: 0 4px 14px rgba(207,254,0,0.35);
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
  .xp-search-submit,
  .xp-search-clear {
    width: 22px; height: 22px; flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0; border: 0; background: transparent;
    color: rgba(255,255,255,0.45); cursor: pointer; text-decoration: none;
  }
  .xp-search-submit:hover,
  .xp-search-clear:hover { color: #cffe00; }
  html.light .xp-search-box {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.1);
  }
  .xp-search-input {
    flex: 1; min-width: 0; background: transparent; border: none; outline: none;
    font-size: 14px; color: var(--text, #ffffff);
  }
  .xp-search-input::-webkit-search-cancel-button,
  .xp-search-input::-webkit-search-decoration {
    -webkit-appearance: none; appearance: none; display: none;
  }
  .xp-search-input::placeholder { color: rgba(255,255,255,0.4); }
  html.light .xp-search-input::placeholder { color: rgba(0,0,0,0.35); }
  html.light .xp-search-submit,
  html.light .xp-search-clear { color: rgba(0,0,0,0.45); }
  html.light .xp-search-submit:hover,
  html.light .xp-search-clear:hover { color: #0a8f3f; }

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
    background: #cffe00; border-color: #cffe00; color: #12160a;
  }
  html.light .xp-filter-btn {
    background: rgba(0,0,0,0.05); border-color: rgba(0,0,0,0.1); color: rgba(0,0,0,0.7);
  }
  html.light .xp-filter-btn:hover, html.light .xp-filter-btn[aria-expanded="true"] {
    background: #cffe00; border-color: #cffe00; color: #12160a;
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
    background: #cffe00; border-color: #cffe00; color: #12160a; font-weight: 700;
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
    background: #cffe00; border: none;
    color: #ffffff; font-size: 13px; font-weight: 700; cursor: pointer;
  }

  /* ── دسته‌بندی‌ها و هشتگ‌های داینامیک ── */
  .xp-cats-section { padding: 14px 16px 0 16px; }
  .xp-marquee-list { display: flex; flex-direction: column; gap: 7px; }
  .xp-marquee {
    width: 100%; overflow: hidden; scrollbar-width: none;
    -webkit-mask-image: linear-gradient(to right, transparent 0, #000 clamp(28px, 7vw, 76px), #000 calc(100% - clamp(28px, 7vw, 76px)), transparent 100%);
    mask-image: linear-gradient(to right, transparent 0, #000 clamp(28px, 7vw, 76px), #000 calc(100% - clamp(28px, 7vw, 76px)), transparent 100%);
  }
  .xp-marquee::-webkit-scrollbar { display: none; }
  .xp-marquee-track {
    display: flex; width: max-content; direction: ltr;
    animation: xp-marquee-left var(--xp-marquee-duration, 40s) linear infinite;
    will-change: transform;
  }
  .xp-marquee-group { display: flex; gap: 7px; padding-left: 7px; }
  .xp-cat-box {
    flex-shrink: 0;
    display: inline-flex; align-items: center; justify-content: center;
    padding: 6px 12px; border-radius: 9px;
    border: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.06);
    color: rgba(255,255,255,0.75);
    font-size: 11.5px; font-weight: 600;
    cursor: pointer; white-space: nowrap; text-decoration: none;
    transition: all 0.2s;
  }
  .xp-cat-box:hover {
    background: #cffe00; border-color: #cffe00; color: #12160a;
  }
  html.light .xp-cat-box {
    background: rgba(0,0,0,0.04); border-color: rgba(0,0,0,0.08); color: rgba(0,0,0,0.6);
  }
  html.light .xp-cat-box:hover { background: #cffe00; border-color: #cffe00; color: #12160a; }

  @keyframes xp-marquee-left {
    from { transform: translate3d(0, 0, 0); }
    to { transform: translate3d(var(--xp-marquee-shift, -50%), 0, 0); }
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
  .xp-grid.is-pattern-layout { direction: ltr; }
  .xp-grid.is-pattern-layout .xp-tile { direction: rtl; }
  .xp-tile {
    position: relative; overflow: hidden;
    border-radius: 3px;
    background: rgba(255,255,255,0.05);
    cursor: pointer;
    display: block; text-decoration: none; color: inherit;
  }
  .xp-tile.size-1x1  { grid-column: span 1; grid-row: span 1; }
  .xp-tile.size-wide { grid-column: span 2; grid-row: span 1; }
  .xp-tile.size-tall { grid-column: span 1; grid-row: span 2; }
  .xp-tile.size-big  { grid-column: span 2; grid-row: span 2; }
  .xp-tile--campaign {
    border: 1px solid rgba(207,254,0,0.48);
    box-shadow: inset 0 0 24px rgba(207,254,0,0.08);
  }
  .xp-tile-campaign-label {
    position: absolute; top: 7px; left: 7px; z-index: 2;
    padding: 3px 7px; border-radius: 999px;
    background: #cffe00; color: #12160a;
    font-size: 9px; font-weight: 800;
  }

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

  @media (max-width: 639px) {
    .xp-grid-section {
      width: 100vw;
      margin-right: calc(50% - 50vw);
      margin-left: calc(50% - 50vw);
      padding-right: 0;
      padding-left: 0;
    }
    .xp-grid { width: 100%; }
    body.explore-search-focused #vatan-nav { display: none !important; }
  }

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
    .xp-marquee-list { gap: 8px; }
    .xp-marquee-group { gap: 8px; padding-left: 8px; }
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
    .explore-page { max-width: none; padding: 0 114px 60px; }

    .xp-header { padding: 40px 0 22px; }
    .xp-title-icon { width: 42px; height: 42px; font-size: 17px; }
    .xp-title { font-size: clamp(24px, 2vw, 28px); }
    .xp-subtitle { font-size: 13.5px; }

    .xp-cats-section { padding: 20px 0 0; }
    .xp-marquee {
      -webkit-mask-image: linear-gradient(to right, transparent 0, #000 76px, #000 calc(100% - 76px), transparent 100%);
      mask-image: linear-gradient(to right, transparent 0, #000 76px, #000 calc(100% - 76px), transparent 100%);
    }

    .xp-grid-section { padding: 0; }
    .xp-grid { grid-template-columns: repeat(5, 1fr); grid-auto-rows: var(--xp-unit, 194px); gap: 8px; }
    .xp-tile-name { font-size: 13px; }
    .xp-tile-tag  { font-size: 11px; }
  }

  /* ══════════════════════════════════
     LARGE DESKTOP — 1280px+  |  ۶ ستون
  ══════════════════════════════════ */
  @media (min-width: 1280px) {
    .explore-page { max-width: none; padding-right: var(--home-desktop-grid-gutter); padding-left: var(--home-desktop-grid-gutter); }

    .xp-header { padding: 44px 0 24px; }
    .xp-cats-section { padding: 22px 0 0; }

    .xp-grid-section { padding: 0; }
    .xp-grid { grid-template-columns: repeat(6, 1fr); grid-auto-rows: var(--xp-unit, 188px); gap: 8px; }
  }
</style>
@endpush

@push('scripts')
<script>
(function () {
  var exploreSearchInput = document.querySelector('.xp-search-input');
  if (exploreSearchInput) {
    exploreSearchInput.addEventListener('focus', function () {
      document.body.classList.add('explore-search-focused');
    });
    exploreSearchInput.addEventListener('blur', function () {
      window.setTimeout(function () {
        document.body.classList.remove('explore-search-focused');
      }, 120);
    });
    window.addEventListener('pagehide', function () {
      document.body.classList.remove('explore-search-focused');
    });
  }

  /* ───── گرید: محاسبه دقیق ارتفاع واحد (مربع کامل) بر اساس عرض واقعی ستون ─────
     چون عرض صفحه بین بریک‌پوینت‌ها به‌صورت سیال تغییر می‌کند، عرض واقعی هر ستون
     همیشه محاسبه و به‌عنوان --xp-unit ست می‌شود تا کاشی‌های ۱:۱ و ۲:۲ همیشه
     دقیقاً مربع باشند و کاشی‌های ۲:۱ / ۱:۲ نسبت درست خودشان را داشته باشند. */
  var xpGrid = document.querySelector('.xp-grid');

  var tileSizeClasses = ['size-1x1', 'size-wide', 'size-tall', 'size-big'];
  var selectedPattern = @json($xLayoutStyle);
  var patternDefinitions = @json($xLayoutPatterns);
  var dimensions = {
    'size-1x1': [1, 1],
    'size-wide': [2, 1],
    'size-tall': [1, 2],
    'size-big': [2, 2]
  };

  function setTileSize(tile, size) {
    tileSizeClasses.forEach(function (className) { tile.classList.remove(className); });
    tile.classList.add(size);
    tile.dataset.tileSize = size;
  }

  function occupy(map, size, row, col) {
    var dim = dimensions[size] || dimensions['size-1x1'];
    for (var r = row; r < row + dim[1]; r++) {
      for (var c = col; c < col + dim[0]; c++) map[r + ':' + c] = true;
    }
  }

  function completeCycle(cols, rows, anchors) {
    var occupied = {};
    var slots = anchors.map(function (anchor) {
      occupy(occupied, anchor[0], anchor[1], anchor[2]);
      return anchor.slice();
    });
    for (var row = 1; row <= rows; row++) {
      for (var col = 1; col <= cols; col++) {
        if (!occupied[row + ':' + col]) slots.push(['size-1x1', row, col]);
      }
    }
    return slots.sort(function (a, b) { return a[1] - b[1] || a[2] - b[2]; });
  }

  function canPlaceAnchor(map, size, row, col, cols, rows) {
    var dim = dimensions[size];
    if (!dim || col + dim[0] - 1 > cols || row + dim[1] - 1 > rows) return false;
    for (var r = row - 1; r <= row + dim[1]; r++) {
      for (var c = col - 1; c <= col + dim[0]; c++) {
        if (r >= 1 && c >= 1 && r <= rows && c <= cols && map[r + ':' + c]) return false;
      }
    }
    return true;
  }

  /* روی موبایل مختصات نمونه‌ها عیناً استفاده می‌شود. برای تبلت و دسکتاپ،
     توالی همان سبک با تعداد ستون دستگاه بازچینی می‌شود و بین هر دو کاشی غیر ۱×۱
     دست‌کم یک سلول فاصله می‌ماند. */
  function responsiveCycle(style, cols) {
    var definition = patternDefinitions[style] || patternDefinitions.excel_11;
    if (cols === 3) return {
      rows: definition.rows,
      slots: completeCycle(cols, definition.rows, definition.anchors)
    };

    var rows = cols === 4 ? 12 : (cols === 5 ? 10 : 8);
    var occupied = {};
    var anchors = [];
    var sequence = definition.anchors.map(function (anchor) { return anchor[0]; });
    var styleOffset = ['excel_11', 'balanced', 'vertical', 'banner'].indexOf(style);
    if (styleOffset < 0) styleOffset = 0;

    sequence.forEach(function (size, index) {
      var placed = false;
      for (var row = 1; row <= rows && !placed; row++) {
        var start = ((index * 2 + row + styleOffset) % cols) + 1;
        for (var step = 0; step < cols && !placed; step++) {
          var col = ((start - 1 + step) % cols) + 1;
          if (!canPlaceAnchor(occupied, size, row, col, cols, rows)) continue;
          anchors.push([size, row, col]);
          occupy(occupied, size, row, col);
          placed = true;
        }
      }
    });

    return { rows: rows, slots: completeCycle(cols, rows, anchors) };
  }

  function allowedSizes(tile) {
    try {
      var parsed = JSON.parse(tile.dataset.allowedSizes || '[]');
      return Array.isArray(parsed) && parsed.length ? parsed : [tile.dataset.originalTileSize || 'size-1x1'];
    } catch (error) {
      return [tile.dataset.originalTileSize || 'size-1x1'];
    }
  }

  function placeTile(tile, size, row, col) {
    var dim = dimensions[size] || dimensions['size-1x1'];
    setTileSize(tile, size);
    tile.style.display = '';
    tile.style.gridColumn = col + ' / span ' + dim[0];
    tile.style.gridRow = row + ' / span ' + dim[1];
  }

  function applyPatternLayout(colCount) {
    if (!xpGrid) return;
    xpGrid.querySelectorAll('[data-repeat-fill]').forEach(function (tile) { tile.remove(); });
    var originals = Array.prototype.slice.call(xpGrid.querySelectorAll('.xp-tile'));
    var remaining = originals.slice();
    if (!remaining.length) return;

    /* جست‌وجو فقط نتایج واقعی را نشان می‌دهد؛ پر کردن خانه‌های خالی با clone
       برای فید عادی مناسب است، اما در نتیجه‌ی جست‌وجو محصول را تکراری می‌کند. */
    if (xpGrid.dataset.searchFiltered === '1') {
      xpGrid.classList.remove('is-pattern-layout');
      originals.forEach(function (tile) {
        tile.style.display = '';
        tile.style.removeProperty('grid-column');
        tile.style.removeProperty('grid-row');
        setTileSize(tile, tile.dataset.originalTileSize || 'size-1x1');
      });
      return;
    }

    var cycle = responsiveCycle(selectedPattern, colCount);
    var cycleIndex = 0;
    var safetyLimit = remaining.length * 4 + 8;
    var cycleStates = [];

    xpGrid.classList.add('is-pattern-layout');
    originals.forEach(function (tile) {
      tile.style.display = 'none';
      tile.style.removeProperty('grid-column');
      tile.style.removeProperty('grid-row');
    });

    while (remaining.length && cycleIndex < safetyLimit) {
      var rowOffset = cycleIndex * cycle.rows;
      var assignedThisCycle = 0;
      var occupied = {};

      cycle.slots.forEach(function (slot) {
        var size = slot[0];
        var matchingIndex = remaining.findIndex(function (tile) {
          return allowedSizes(tile).indexOf(size) !== -1;
        });
        if (matchingIndex === -1 && size !== 'size-1x1') {
          var dim = dimensions[size];
          for (var localRow = 0; localRow < dim[1]; localRow++) {
            for (var localCol = 0; localCol < dim[0]; localCol++) {
              var smallIndex = remaining.findIndex(function (tile) {
                return allowedSizes(tile).indexOf('size-1x1') !== -1;
              });
              if (smallIndex === -1) continue;
              var smallTile = remaining.splice(smallIndex, 1)[0];
              placeTile(smallTile, 'size-1x1', slot[1] + rowOffset + localRow, slot[2] + localCol);
              occupy(occupied, 'size-1x1', slot[1] + localRow, slot[2] + localCol);
              assignedThisCycle++;
            }
          }
          return;
        }
        if (matchingIndex === -1) return;
        var tile = remaining.splice(matchingIndex, 1)[0];
        placeTile(tile, size, slot[1] + rowOffset, slot[2]);
        occupy(occupied, size, slot[1], slot[2]);
        assignedThisCycle++;
      });

      if (!assignedThisCycle) break;
      cycleStates.push({ index: cycleIndex, occupied: occupied });
      cycleIndex++;
    }

    /* ابتدا همه محصولات واقعی چیده می‌شوند. سپس فقط سلول‌های خالی چرخه‌های
       استفاده‌شده با نسخه تکراری محصول سازگار پر می‌شوند؛ کمپین‌ها تکرار نمی‌شوند. */
    var fillSources = originals.filter(function (tile) { return tile.dataset.tileType === 'product'; });
    var sourceCursor = {};
    function nextFillSource(size) {
      var compatible = fillSources.filter(function (tile) { return allowedSizes(tile).indexOf(size) !== -1; });
      if (!compatible.length) return null;
      var cursor = sourceCursor[size] || 0;
      sourceCursor[size] = cursor + 1;
      return compatible[cursor % compatible.length];
    }
    function placeClone(source, size, row, col, occupied) {
      if (!source) return false;
      var clone = source.cloneNode(true);
      clone.setAttribute('data-repeat-fill', 'true');
      clone.removeAttribute('id');
      placeTile(clone, size, row, col);
      xpGrid.appendChild(clone);
      occupy(occupied, size, row - (Math.floor((row - 1) / cycle.rows) * cycle.rows), col);
      return true;
    }

    cycleStates.forEach(function (state) {
      var rowOffset = state.index * cycle.rows;
      cycle.slots.forEach(function (slot) {
        var size = slot[0];
        var dim = dimensions[size] || dimensions['size-1x1'];
        var emptyCells = [];
        for (var localRow = 0; localRow < dim[1]; localRow++) {
          for (var localCol = 0; localCol < dim[0]; localCol++) {
            var cellRow = slot[1] + localRow;
            var cellCol = slot[2] + localCol;
            if (!state.occupied[cellRow + ':' + cellCol]) emptyCells.push([cellRow, cellCol]);
          }
        }
        if (!emptyCells.length) return;

        if (emptyCells.length === dim[0] * dim[1]) {
          var matchingSource = nextFillSource(size);
          if (placeClone(matchingSource, size, slot[1] + rowOffset, slot[2], state.occupied)) return;
        }

        emptyCells.forEach(function (cell) {
          placeClone(nextFillSource('size-1x1'), 'size-1x1', cell[0] + rowOffset, cell[1], state.occupied);
        });
      });
    });
  }

  function syncGridUnit() {
    if (!xpGrid) return;
    var cs = getComputedStyle(xpGrid);
    // مختصات صریح چرخه ممکن است ستون ضمنی بسازد؛ بنابراین تعداد ستون را از
    // همان breakpointهای CSS می‌خوانیم، نه از gridTemplateColumns محاسبه‌شده.
    var colCount = window.matchMedia('(min-width: 1280px)').matches ? 6
      : (window.matchMedia('(min-width: 1024px)').matches ? 5
        : (window.matchMedia('(min-width: 768px)').matches ? 4 : 3));
    var gap = parseFloat(cs.columnGap || cs.gap) || 0;
    var totalGap = gap * (colCount - 1);
    var unit = (xpGrid.clientWidth - totalGap) / colCount;
    if (unit > 0) {
      xpGrid.style.setProperty('--xp-unit', unit + 'px');
    }
    applyPatternLayout(colCount);
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

  /* هر ردیف به‌اندازه‌ای تکثیر می‌شود که حتی اگر طول لیست از عرض صفحه کمتر
     باشد، هنگام خروج یک چرخه هیچ بخش خالی دیده نشود. جابه‌جایی دقیقاً برابر
     عرض یک گروه است؛ بنابراین انتهای نسخه قبلی بدون پرش به ابتدای بعدی می‌رسد. */
  function syncInfiniteMarquees() {
    document.querySelectorAll('.xp-marquee').forEach(function (marquee) {
      var track = marquee.querySelector('.xp-marquee-track');
      var group = track?.querySelector('[data-marquee-source]');
      var speed = parseFloat(marquee.dataset.speed) || 20;
      if (!track || !group || !group.querySelector('.xp-cat-box')) return;

      var cycleWidth = group.getBoundingClientRect().width;
      if (cycleWidth <= 0) return;
      var requiredGroups = Math.max(3, Math.ceil(marquee.clientWidth / cycleWidth) + 2);
      var signature = Math.round(marquee.clientWidth) + ':' + Math.round(cycleWidth) + ':' + requiredGroups;
      if (marquee.dataset.marqueeSignature === signature
          && track.querySelectorAll('.xp-marquee-group').length === requiredGroups) return;

      track.querySelectorAll('[data-marquee-clone]').forEach(function (clone) { clone.remove(); });
      for (var index = 1; index < requiredGroups; index++) {
        var clone = group.cloneNode(true);
        clone.removeAttribute('data-marquee-source');
        clone.setAttribute('data-marquee-clone', '');
        clone.setAttribute('aria-hidden', 'true');
        clone.querySelectorAll('a, button, input').forEach(function (item) { item.setAttribute('tabindex', '-1'); });
        track.appendChild(clone);
      }

      marquee.dataset.marqueeSignature = signature;
      marquee.style.setProperty('--xp-marquee-shift', (-cycleWidth) + 'px');
      marquee.style.setProperty('--xp-marquee-duration', (cycleWidth / speed) + 's');
      track.style.animation = 'none';
      void track.offsetWidth;
      track.style.removeProperty('animation');
    });
  }
  syncInfiniteMarquees();
  document.addEventListener('DOMContentLoaded', syncInfiniteMarquees);
  window.addEventListener('load', syncInfiniteMarquees);
  window.addEventListener('resize', syncInfiniteMarquees);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(syncInfiniteMarquees);
  if (window.ResizeObserver) {
    document.querySelectorAll('.xp-marquee').forEach(function (marquee) {
      new ResizeObserver(syncInfiniteMarquees).observe(marquee);
    });
  }
}());
</script>
@endpush
