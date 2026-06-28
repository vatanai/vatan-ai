@extends('layouts.app')

@section('content')
<div class="trends-page" dir="rtl">

  {{-- ===== HEADER ===== --}}
  <section class="trends-header">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h1 class="trends-title">ترندز</h1>
      <span class="trends-live-badge">
        <span class="trends-live-dot"></span>
        زنده
      </span>
    </div>

    {{-- سرچ --}}
    <div class="trends-search-wrap">
      <div class="trends-search-box">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:rgba(255,255,255,0.45);flex-shrink:0;">
          <circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/>
        </svg>
        <input type="text" class="trends-input" placeholder="جستجو در ترندها..." dir="rtl">
      </div>
    </div>

    {{-- دسته‌بندی‌ها --}}
    <div class="trends-chips">
      <button class="trends-chip active">همه</button>
      <button class="trends-chip">پرتره</button>
      <button class="trends-chip">فشن</button>
      <button class="trends-chip">طبیعت</button>
      <button class="trends-chip">سینما</button>
      <button class="trends-chip">کوپل</button>
    </div>
  </section>

  {{-- ===== GRID کارت‌ها ===== --}}
  @php
    $trendsCards = [
      ['img' => asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp'), 'name' => 'رترو چیر', 'tag' => 'ترند #۱', 'hot' => true],
      ['img' => asset('assets/img/best-friends-ai-prompt-2.webp'), 'name' => 'بهترین دوست', 'tag' => 'ترند #۲', 'hot' => true],
      ['img' => asset('assets/img/ai-photo-editor-prompt.webp'), 'name' => 'ادیت فشن', 'tag' => 'ترند #۳', 'hot' => false],
      ['img' => asset('assets/img/9cb93b50-d93f-462f-b6d4-113f63ffc603.avif'), 'name' => 'ادیت طبیعت', 'tag' => 'ترند #۴', 'hot' => false],
      ['img' => asset('assets/img/promptbank234.webp'), 'name' => 'پرتره کینگ', 'tag' => 'ترند #۵', 'hot' => true],
      ['img' => asset('assets/img/hmxsjse1drg8xqmj0mda.webp'), 'name' => 'لوکیشن باز', 'tag' => 'ترند #۶', 'hot' => false],
      ['img' => asset('assets/img/Screenshot-2025-12-09-at-12.33.35-PM.avif'), 'name' => 'لحظه خاص', 'tag' => 'ترند #۷', 'hot' => false],
      ['img' => asset('assets/img/promptbank176.webp'), 'name' => 'وینتیج گرل', 'tag' => 'ترند #۸', 'hot' => false],
      ['img' => asset('assets/img/images.jpg'), 'name' => 'نگاه هنری', 'tag' => 'ترند #۹', 'hot' => false],
      ['img' => asset('assets/img/lookaside.fbsbx.webp'), 'name' => 'سبک مدرن', 'tag' => 'ترند #۱۰', 'hot' => false],
    ];
  @endphp

  <div class="trends-grid">
    @foreach ($trendsCards as $card)
      <div class="trends-card" style="background-image:url('{{ $card['img'] }}');">
        <div class="trends-card-overlay"></div>
        @if($card['hot'])
          <div class="trends-hot-badge">
            <svg width="9" height="9" viewBox="0 0 24 24" fill="currentColor"><path d="M17.66 11.2C17.43 10.9 17.15 10.64 16.89 10.38C16.22 9.78 15.46 9.35 14.82 8.72C13.33 7.26 13 4.85 13.95 3C13 3.23 12.17 3.75 11.46 4.32C8.87 6.4 7.85 10.07 9.07 13.22C9.11 13.32 9.15 13.42 9.15 13.55C9.15 13.77 9 13.97 8.8 14.05C8.57 14.15 8.33 14.09 8.14 13.93C8.08 13.88 8.04 13.83 8 13.76C6.87 12.33 6.69 10.28 7.45 8.64C5.78 10 4.87 12.3 5 14.47C5.06 14.97 5.12 15.47 5.29 15.97C5.43 16.57 5.7 17.17 6 17.7C7.08 19.43 8.95 20.67 10.96 20.92C13.1 21.19 15.39 20.8 17.03 19.32C18.86 17.66 19.5 15 18.56 12.72L18.43 12.46C18.22 12 17.66 11.2 17.66 11.2Z"/></svg>
            داغ
          </div>
        @endif
        <div class="trends-card-rank">{{ $card['tag'] }}</div>
        <div class="trends-card-info">
          <p class="trends-card-name">{{ $card['name'] }}</p>
        </div>
      </div>
    @endforeach
  </div>

</div>
@endsection

@push('styles')
<style>
  html, body { background: #000000; overflow-x: hidden; }
  html.light, html.light body { background: #ffffff; }

  .trends-page {
    width: 100%; max-width: 480px;
    margin: 0 auto;
    background: var(--bg, #000000);
    min-height: 100vh;
    padding-bottom: 120px;
  }

  .trends-header {
    padding: calc(env(safe-area-inset-top) + 18px) 16px 16px 16px;
  }

  .trends-title {
    font-size: 20px; font-weight: 700;
    color: var(--text, #ffffff);
    margin: 0;
  }

  .trends-live-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(233,30,140,0.15);
    border: 1px solid rgba(233,30,140,0.35);
    border-radius: 8px;
    padding: 4px 10px;
    font-size: 12px; font-weight: 700; color: #e91e8c;
  }
  .trends-live-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #e91e8c;
    animation: livePulse 1.4s infinite;
  }
  @keyframes livePulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.7); }
  }

  .trends-search-wrap { margin-bottom: 12px; }
  .trends-search-box {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 11px 14px;
  }
  html.light .trends-search-box {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.1);
  }
  .trends-input {
    flex: 1; background: transparent; border: none; outline: none;
    font-size: 14px; color: var(--text, #ffffff);
  }
  .trends-input::placeholder { color: rgba(255,255,255,0.4); }
  html.light .trends-input::placeholder { color: rgba(0,0,0,0.35); }

  .trends-chips {
    display: flex; gap: 8px;
    overflow-x: auto; scrollbar-width: none;
    padding-bottom: 4px; direction: rtl;
  }
  .trends-chips::-webkit-scrollbar { display: none; }
  .trends-chip {
    flex-shrink: 0; padding: 6px 14px;
    border-radius: 12px;
    border: 1px solid var(--b1, #222230);
    background: var(--s1, #111116);
    color: rgba(255,255,255,0.6);
    font-size: 13px; cursor: pointer;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
  }
  .trends-chip.active {
    background: #e91e8c; border-color: #e91e8c; color: #ffffff; font-weight: 700;
  }
  html.light .trends-chip { color: rgba(0,0,0,0.55); }
  html.light .trends-chip.active { color: #ffffff; }

  .trends-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
    padding: 8px 12px;
  }

  .trends-card {
    aspect-ratio: 3/4; border-radius: 12px;
    overflow: hidden; position: relative;
    background-size: cover; background-position: center;
    cursor: pointer;
  }
  .trends-card-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 55%);
  }

  .trends-hot-badge {
    position: absolute; top: 8px; right: 8px;
    display: inline-flex; align-items: center; gap: 4px;
    background: #e91e8c; color: #ffffff;
    font-size: 10px; font-weight: 700;
    padding: 3px 8px; border-radius: 6px;
  }

  .trends-card-rank {
    position: absolute; top: 8px; left: 8px;
    background: rgba(0,0,0,0.55);
    color: rgba(255,255,255,0.75);
    font-size: 10px; font-weight: 700;
    padding: 3px 7px; border-radius: 6px;
    backdrop-filter: blur(4px);
  }

  .trends-card-info {
    position: absolute; bottom: 10px; right: 10px; text-align: right;
  }
  .trends-card-name { margin: 0; font-size: 13px; font-weight: 700; color: #ffffff; }

  /* ══════════════════════════════════
     TABLET — 640px+  |  ۳ ستون
  ══════════════════════════════════ */
  @media (min-width: 640px) {
    .trends-page {
      max-width: 720px;
    }
    .trends-header {
      padding: calc(env(safe-area-inset-top) + 24px) 28px 20px 28px;
    }
    .trends-title { font-size: 22px; }
    .trends-grid {
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      padding: 8px 28px;
    }
    .trends-chips {
      flex-wrap: wrap;
      overflow-x: visible;
    }
  }

  /* ══════════════════════════════════
     DESKTOP — 1024px+  |  ۴ ستون
  ══════════════════════════════════ */
  @media (min-width: 1024px) {
    .trends-page {
      max-width: 1080px;
      padding-bottom: 60px;
    }
    .trends-header {
      padding: 40px 40px 24px 40px;
    }
    .trends-title { font-size: clamp(20px, 1.8vw, 26px); }
    .trends-search-box { max-width: 560px; }
    .trends-grid {
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      padding: 8px 40px;
    }
    .trends-card { border-radius: 14px; }
    .trends-card-name { font-size: 14px; }
  }

  /* ══════════════════════════════════
     LARGE DESKTOP — 1280px+  |  ۵ ستون
  ══════════════════════════════════ */
  @media (min-width: 1280px) {
    .trends-page { max-width: 1280px; }
    .trends-grid {
      grid-template-columns: repeat(5, 1fr);
      padding: 8px 56px;
    }
    .trends-header { padding: 44px 56px 24px 56px; }
  }
</style>
@endpush

@push('scripts')
<script>
(function () {
  document.querySelectorAll('.trends-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      document.querySelectorAll('.trends-chip').forEach(function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
    });
  });

  /* ── کلیک روی کارت محصول ── */
  document.querySelectorAll('.trends-card').forEach(function (card) {
    card.addEventListener('click', function () {
      window.location.href = '/app/product/demo';
    });
  });
}());
</script>
@endpush
