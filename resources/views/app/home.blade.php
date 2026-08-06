@extends('layouts.app')

@section('content')
<div class="home-page" dir="rtl">

  {{-- ===== SECTION 2: خوش‌آمدگویی هوشمند ===== --}}
  <section class="home-greeting">
    <p class="home-greeting-title">سلام، خوش اومدی</p>
    <p class="home-greeting-sub">می‌خوای چی خلق کنی؟</p>
  </section>

  {{-- ===== SECTION 3: جستجوی زنده محصولات + انتقال به کاتالوگ ===== --}}
  <section class="home-imagegen">
    <div class="ig-box" dir="rtl">

      {{-- ردیف بالا: ورود سریع به جست‌وجو + پرامپت --}}
      <form class="ig-top" id="home-search-form" action="{{ route('products.index') }}" method="GET">
        <button type="button" class="ig-plus" id="ig-focus-search" aria-label="شروع تایپ در جست‌وجو">
          <div class="ig-plus-inner">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
          </div>
        </button>
        <div class="ig-prompt-wrap">
          <textarea id="igPrompt" name="search" class="ig-prompt" rows="2" autocomplete="off" aria-label="فقط بنویس دنبال چی هستی"></textarea>
          <div class="ig-prompt-copy" aria-hidden="true">
            <div class="ig-prompt-title">فقط بنویس دنبال چی هستی<span class="typing-dots"><span>.</span><span>.</span><span>.</span></span></div>
            <div class="ig-prompt-hint">بیش از ۱۲۰۰ طرح آماده و ۷۰ مدل هوش مصنوعی در اختیار توست</div>
          </div>
          <div class="ig-search-results" id="ig-search-results" hidden></div>
        </div>
      </form>

      {{-- ردیف کنترل‌ها --}}
      <div class="ig-controls">
        {{-- ثبت فرم، کاربر را به صفحه نتایج کامل کاتالوگ می‌برد. --}}
        <button type="submit" form="home-search-form" class="ig-generate" data-ig="generate">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="21" y2="21"/></svg>
          <span>جست و جوی هوشمند</span>
        </button>
        <div class="ig-left">
          @include('app.partials.home-quick-chips')
        </div>
      </div>

    </div>
  </section>

  {{-- ===== SECTION 4: Sectionهای داینامیک صفحه هوم (مدیریت از پنل ادمین → مدیریت صفحه هوم) ===== --}}
  @include('app.home-builder.partials.styles')

  <section class="home-products">
    @forelse($renderedSections as $item)
      @include('app.home-builder.dispatcher', ['item' => $item])
    @empty
      {{-- هنوز هیچ Section منتشرشده‌ای برای صفحه هوم تعریف نشده --}}
    @endforelse
  </section>

</div>
@endsection

@push('styles')
<style>
  html, body { background: var(--vatan-bg-page); overflow-x: hidden; }
  html.light, html.light body { background: var(--vatan-bg-page); }
  :root { --bg: var(--vatan-bg-page); }

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
    background: var(--vatan-bg-page);
    max-width: 480px;
    margin: 0 auto;
    min-height: 100vh;
    padding: 18px 16px 120px;
    direction: rtl;
    font-family: inherit;
  }
  html.light .home-page { background: var(--vatan-bg-page); }

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
    background: #cffe00;
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

  /* ═══════════════ باکس تولید تصویر (Image Generator) — مطابق دقیق سایت نمونه ═══════════════ */
  .home-imagegen {
    margin-top: 12px;
    max-width: 842px;
    margin-left: auto;
    margin-right: auto;
    position: relative;
    z-index: 100;
  }

  /* کانتینر: bg-[#1a1a1a]/95 backdrop-blur-xl rounded-2xl border-[#2a2a2a] p-4 shadow-2xl */
  .ig-box {
    background: var(--bg-card);
    -webkit-backdrop-filter: blur(24px);
    backdrop-filter: blur(24px);
    border: 1px solid var(--border-subtle);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 18px 42px -18px rgba(0, 0, 0, 0.45);
    transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
  }

  /* ردیف بالا: flex items-center gap-3 mb-4 */
  .ig-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    direction: rtl;
  }

  /* دکمه +: w-10 h-10 rounded-xl border-2 border-dashed border-[#333] */
  .ig-plus {
    flex-shrink: 0;
    cursor: pointer;
    display: block;
    padding: 0;
    border: 0;
    background: transparent;
    color: inherit;
    font: inherit;
  }
  .ig-plus-inner {
    width: 40px;
    height: 40px;
    border: 2px dashed var(--border-subtle);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.2s ease, background 0.2s ease;
    color: var(--green);
    transform: translateY(-2px);
  }
  .ig-plus:hover .ig-plus-inner {
    border-color: var(--green);
    background: var(--bg-surface);
  }
  .ig-plus-inner svg { width: 20px; height: 20px; }
  .ig-file {
    position: absolute;
    width: 1px; height: 1px;
    padding: 0; margin: -1px;
    overflow: hidden; clip: rect(0, 0, 0, 0);
    white-space: nowrap; border: 0;
  }

  /* متن جستجو */
  .ig-prompt-wrap { flex: 1; min-width: 0; position: relative; }
  .ig-prompt {
    width: 100%;
    background: transparent;
    border: none;
    outline: none;
    resize: none;
    font-family: inherit;
    font-size: 16px;
    line-height: 1.5;
    color: var(--text-primary);
    padding: 4px 0;
    min-height: 54px;
    max-height: 120px;
    overflow-y: auto;
    direction: rtl;
    text-align: right;
  }
  .ig-prompt::placeholder { color: var(--text-secondary); }
  .ig-prompt::-webkit-scrollbar { display: none; }

  .ig-prompt-copy {
    position: absolute;
    inset: 4px 0 auto 0;
    pointer-events: none;
    text-align: right;
    direction: rtl;
    transition: opacity 0.15s ease;
  }
  .ig-prompt-wrap:focus-within .ig-prompt-copy,
  .ig-prompt-wrap.has-value .ig-prompt-copy { opacity: 0; }
  .ig-prompt-title {
    color: var(--text-primary);
    font-size: 15px;
    line-height: 1.55;
  }
  .ig-prompt-hint {
    margin-top: 3px;
    color: var(--text-secondary);
    font-size: 10.5px;
    line-height: 1.5;
  }
  .typing-dots { display: inline-flex; direction: ltr; margin-right: 2px; }
  .typing-dots span { animation: typing-dot 1.2s infinite; opacity: 0.2; }
  .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
  .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
  @keyframes typing-dot {
    0%, 20% { opacity: 0.15; }
    45% { opacity: 1; }
    70%, 100% { opacity: 0.15; }
  }

  /* ردیف کنترل‌ها: flex items-center justify-between */
  .ig-controls {
    display: flex;
    align-items: stretch;
    gap: 14px;
    direction: ltr;
  }
  .ig-left {
    display: flex;
    align-items: stretch;
    flex: 3.9 1 0;
    min-width: 0;
  }

  /* پیل‌ها: bg-[#222] border-[#333] text-white h-9 text-sm rounded-lg */
  .ig-pill {
    display: flex;
    align-items: center;
    height: 36px;
    padding: 0 12px;
    background: #222222;
    border: 1px solid #333333;
    border-radius: 8px;
    color: #ffffff;
    font-family: inherit;
    font-size: 14px;
    font-weight: 400;
    cursor: pointer;
    white-space: nowrap;
  }
  .ig-pill--model { justify-content: space-between; min-width: 160px; }
  .ig-pill--w20 { justify-content: space-between; width: 80px; }

  .ig-inner { display: flex; align-items: center; gap: 8px; }
  .ig-inner--tight { gap: 6px; }
  .ig-pill svg { display: block; }
  .ig-chev { width: 16px; height: 16px; opacity: 0.5; flex-shrink: 0; }
  .ig-ic { width: 14px; height: 14px; color: #9ca3af; }
  .ig-model-label { font-weight: 500; }

  /* نشان G: w-4 h-4 rounded bg-[#CFFD00] text-black text-[10px] font-bold */
  .ig-g-badge {
    width: 16px;
    height: 16px;
    background: var(--green);
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #000000;
    font-weight: 700;
    font-size: 10px;
  }
  .ig-heart { color: #9ca3af; font-size: 14px; line-height: 1; }

  /* خط جداکننده: w-px h-6 bg-[#333] */
  .ig-divider { width: 1px; height: 24px; background: #333333; flex-shrink: 0; }

  /* استپر تعداد: bg-[#222] rounded-lg px-2 h-9 border-[#333] gap-1 */
  .ig-step { gap: 4px; padding: 0 8px; }
  .ig-step-btn {
    background: transparent;
    border: none;
    color: #9ca3af;
    padding: 0 4px;
    font-size: 15px;
    line-height: 1;
    cursor: pointer;
    font-family: inherit;
  }
  .ig-step-btn:hover { color: #ffffff; }
  .ig-step-val {
    color: #ffffff;
    font-size: 14px;
    font-weight: 500;
    width: 32px;
    text-align: center;
  }

  /* Draw: text-gray-400 hover:text-white */
  .ig-draw { gap: 6px; color: #9ca3af; }
  .ig-draw:hover { color: #ffffff; }
  .ig-draw .ig-ic { width: 16px; height: 16px; color: currentColor; }

  /* دکمه جستجو هم‌اندازه با هرکدام از چهار آیتم سریع و در سمت چپ آن‌ها */
  .ig-generate {
    display: flex;
    flex-direction: row;
    direction: rtl;
    align-items: center;
    justify-content: center;
    gap: 8px;
    height: 40px;
    min-height: 40px;
    padding: 6px 8px;
    background: var(--green);
    border: none;
    border-radius: 12px;
    color: #000000;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    transition: background 0.15s ease;
    flex: 0 0 154px;
    min-width: 0;
    box-sizing: border-box;
  }
  .ig-generate:hover { filter: brightness(0.92); }
  .ig-generate svg { width: 18px; height: 18px; }

  /* ═══════════════ آیتم‌های سریع (جایگزین پیل‌های مدل/نسبت/...) ═══════════════ */
  .ig-quick-row {
    display: flex;
    align-items: stretch;
    gap: 8px;
    flex: 1;
    min-width: 0;
    direction: rtl;
    justify-content: flex-start;
  }

  .ig-quick-item {
    display: flex;
    flex-direction: row;
    direction: rtl;
    justify-content: center;
    align-items: center;
    gap: 8px;
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    padding: 6px 10px;
    cursor: pointer;
    text-align: center;
    font-family: inherit;
    transition: background 0.18s ease, border-color 0.18s ease;
    flex: 0 1 calc(20.5% - 6px);
    min-width: 0;
    height: 40px;
    min-height: 40px;
    box-sizing: border-box;
  }

  .ig-quick-item:hover {
    background: var(--bg-card);
    border-color: var(--green);
  }

  .ig-quick-icon {
    width: 28px;
    height: 28px;
    flex-shrink: 0;
    border-radius: 8px;
    background: var(--bg-card);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .ig-quick-icon svg {
    width: 18px;
    height: 18px;
    color: var(--green);
    display: block;
  }

  .ig-quick-text {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
    align-items: center;
    text-align: center;
    min-width: 0;
  }

  .ig-quick-title {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text-primary);
    white-space: nowrap;
  }

  .ig-quick-sub {
    margin-top: 1px;
    font-size: 10.5px;
    font-weight: 400;
    color: var(--text-secondary);
    white-space: nowrap;
    text-align: center;
  }

  html.light .ig-box {
    background: color-mix(in srgb, var(--bg-card) 96%, var(--green) 4%);
    border-color: color-mix(in srgb, var(--border-subtle) 76%, var(--green) 24%);
    box-shadow: 0 18px 45px -28px color-mix(in srgb, var(--green) 34%, transparent);
  }
  html.light .ig-plus-inner,
  html.light .ig-quick-item {
    background: color-mix(in srgb, var(--bg-surface) 96%, var(--green) 4%);
  }
  html.light .ig-quick-icon {
    background: color-mix(in srgb, var(--bg-card) 90%, var(--green) 10%);
  }

  .ig-attachments {
    display:flex;
    gap:7px;
    flex-wrap:wrap;
    margin:-7px 52px 12px 0;
  }
  .ig-attachment {
    display:inline-flex;
    align-items:center;
    gap:6px;
    max-width:180px;
    height:30px;
    padding:0 9px;
    border-radius:9px;
    border:1px solid var(--border-subtle);
    background:var(--bg-surface);
    color:var(--text-secondary);
    font-size:9.5px;
  }
  .ig-attachment span { overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
  .ig-attachment button { border:0;background:transparent;color:var(--text-secondary);cursor:pointer;padding:0; }
  .ig-search-results {
    position:absolute;
    top:calc(100% + 10px);
    right:0;
    left:0;
    z-index:1000;
    padding:7px;
    border-radius:14px;
    border:1px solid var(--border-subtle);
    background:var(--bg-card);
    box-shadow:0 18px 45px rgba(0,0,0,.3);
  }
  .ig-search-result {
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px;
    border-radius:10px;
    color:var(--text-primary);
    text-decoration:none;
  }
  .ig-search-result:hover { background:var(--bg-surface); }
  .ig-search-result img { width:38px;height:38px;border-radius:9px;object-fit:cover;flex:none; }
  .ig-search-result strong { display:block;font-size:11.5px; }
  .ig-search-result small { display:block;margin-top:2px;color:var(--text-secondary);font-size:9px; }
  .ig-search-all { display:flex;justify-content:center;padding:8px;color:var(--green);font-size:10px;font-weight:800;text-decoration:none;border-top:1px solid var(--border-subtle); }
  .ig-search-empty { padding:12px;text-align:center;color:var(--text-secondary);font-size:10.5px; }

  @media (max-width: 639px) {
    .ig-box { padding: 12px; }
    .ig-controls { gap: 9px; }
    .ig-quick-row { gap: 5px; }
    .ig-generate,
    .ig-quick-item {
      height: 36px;
      min-height: 36px;
      padding: 3px;
      border-radius: 9px;
    }
    .ig-generate { font-size: 9px; gap: 3px; }
    .ig-generate { flex: 0.65 1 0; }
    .ig-generate svg { width: 13px; height: 13px; }
    .ig-quick-item {
      gap: 3px;
      flex-basis: calc(22.55% - 5px);
    }
    .ig-quick-icon { width: 20px; height: 20px; border-radius: 6px; }
    .ig-quick-icon svg { width: 13px; height: 13px; }
    .ig-quick-title { font-size: 8px; }
    .ig-quick-sub { font-size: 6.5px; line-height: 1.15; }
    .ig-attachments { margin-right:0; }
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
    margin-top: 31px;
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
    margin-top: 31px;
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
    color: #cffe00;
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
    .home-page {
      max-width: 680px;
      padding: 24px 28px 80px;
    }

    .home-greeting-title { font-size: 24px; }

    .home-search-card { border-radius: 18px; }
    .ig-box { border-radius: 18px; }
    .ig-quick-item { flex-basis: calc(23.575% - 6px); }

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
      max-width: none;
      padding: 32px 114px 60px;
    }

    /* هدینگ و سرچ — سانتر و محدود */
    .home-greeting { margin-top: 16px; }
    .home-greeting-title { font-size: clamp(22px, 2vw, 28px); }

    .home-search { max-width: 680px; margin-left: auto; margin-right: auto; }
    .ig-quick-item { flex-basis: calc(20.5% - 6px); }

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
    .home-page { max-width: none; padding: 36px var(--home-desktop-grid-gutter) 60px; }
    .home-cards-scroll .home-card { width: 220px; max-width: 230px; }
  }
</style>
@endpush

@push('scripts')
<script>
(function () {

  // ===== باکس تولید تصویر =====
  var igCountEl = document.getElementById('igCount');
  var igMax = 4, igMin = 1;
  function igSet(n) {
    n = Math.max(igMin, Math.min(igMax, n));
    if (igCountEl) igCountEl.textContent = n;
  }
  document.querySelectorAll('[data-ig="inc"]').forEach(function (b) {
    b.addEventListener('click', function () { igSet(parseInt(igCountEl.textContent, 10) + 1); });
  });
  document.querySelectorAll('[data-ig="dec"]').forEach(function (b) {
    b.addEventListener('click', function () { igSet(parseInt(igCountEl.textContent, 10) - 1); });
  });

  // بزرگ‌شدن خودکار پرامپت
  var igPrompt = document.getElementById('igPrompt');
  var igForm = document.getElementById('home-search-form');
  var igResults = document.getElementById('ig-search-results');
  var igSearchTimer = null;
  var igSearchRequest = null;
  if (igPrompt) {
    igPrompt.addEventListener('input', function () {
      igPrompt.parentElement.classList.toggle('has-value', igPrompt.value.trim().length > 0);
      igPrompt.style.height = 'auto';
      igPrompt.style.height = igPrompt.scrollHeight + 'px';
      clearTimeout(igSearchTimer);
      if (igPrompt.value.trim().length < 2) {
        if (igResults) { igResults.hidden = true; igResults.innerHTML = ''; }
        return;
      }
      igSearchTimer = setTimeout(runHomeSearch, 260);
    });
    igPrompt.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        igForm?.requestSubmit();
      }
    });
  }

  document.getElementById('ig-focus-search')?.addEventListener('click', function () {
    igPrompt?.focus({ preventScroll: true });
    var cursorPosition = igPrompt?.value.length || 0;
    igPrompt?.setSelectionRange(cursorPosition, cursorPosition);
  });

  if (igForm) {
    igForm.addEventListener('submit', function (event) {
      if ((igPrompt?.value || '').trim().length < 2) {
        event.preventDefault();
        igPrompt?.focus();
      }
    });
  }

  function runHomeSearch() {
    var query = (igPrompt?.value || '').trim();
    if (query.length < 2 || !igResults) return;
    if (igSearchRequest) igSearchRequest.abort();
    igSearchRequest = new AbortController();
    fetch(@json(route('app.home.search')) + '?q=' + encodeURIComponent(query), {
      headers: { 'Accept': 'application/json' },
      signal: igSearchRequest.signal,
      credentials: 'same-origin'
    }).then(function (response) {
      if (!response.ok) throw new Error('search_failed');
      return response.json();
    }).then(function (data) {
      igResults.innerHTML = '';
      (data.items || []).forEach(function (item) {
        var link = document.createElement('a');
        link.className = 'ig-search-result';
        link.href = item.url;
        var image = document.createElement('img');
        image.src = item.image;
        image.alt = '';
        var copy = document.createElement('span');
        var title = document.createElement('strong');
        title.textContent = item.name;
        var meta = document.createElement('small');
        meta.textContent = item.meta || 'محصول هوش مصنوعی';
        copy.append(title, meta);
        link.append(image, copy);
        igResults.appendChild(link);
      });
      if (!(data.items || []).length) {
        var empty = document.createElement('div');
        empty.className = 'ig-search-empty';
        empty.textContent = 'محصولی با این عبارت پیدا نشد';
        igResults.appendChild(empty);
      } else {
        var all = document.createElement('a');
        all.className = 'ig-search-all';
        all.href = data.all_results_url;
        all.textContent = 'نمایش همه نتایج';
        igResults.appendChild(all);
      }
      igResults.hidden = false;
    }).catch(function (error) {
      if (error.name !== 'AbortError') igResults.hidden = true;
    });
  }

  document.addEventListener('click', function (event) {
    if (igResults && !event.target.closest('.ig-prompt-wrap')) igResults.hidden = true;
  });

})();
</script>
@endpush
