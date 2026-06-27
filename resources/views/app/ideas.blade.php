@extends('layouts.app')

@section('content')
<div class="explore-page" dir="rtl">

  {{-- ===== HEADER ===== --}}
  <section class="explore-header">
    <h1 class="explore-title">اکسپلور هوش مصنوعی</h1>

    {{-- سرچ --}}
    <div class="explore-search-wrap">
      <div class="explore-search-box">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:rgba(255,255,255,0.45);flex-shrink:0;">
          <circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="22" y2="22"/>
        </svg>
        <input type="text" class="explore-input" placeholder="جستجو در ۱۲۰۰+ طرح..." dir="rtl">
      </div>
    </div>

    {{-- دسته‌بندی‌ها --}}
    <div class="explore-chips">
      <button class="explore-chip active">همه</button>
      <button class="explore-chip">پرتره</button>
      <button class="explore-chip">فشن</button>
      <button class="explore-chip">کسب‌وکار</button>
      <button class="explore-chip">طبیعت</button>
      <button class="explore-chip">ویدیو</button>
    </div>
  </section>

  {{-- ===== GRID کارت‌ها ===== --}}
  @php
    $exploreCards = [
      ['img' => asset('assets/img/prompt-for-gemini-ai-girl.webp'),        'name' => 'پرتره مدرن',        'tag' => 'پرتره',    'video' => false],
      ['img' => asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif'), 'name' => 'کافه گرل', 'tag' => 'فشن',     'video' => false],
      ['img' => asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg'), 'name' => 'فشن استودیو', 'tag' => 'فشن', 'video' => false],
      ['img' => asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif'), 'name' => 'اسب و طبیعت', 'tag' => 'طبیعت', 'video' => false],
      ['img' => asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg'), 'name' => 'سینماتیک مرد', 'tag' => 'سینما', 'video' => true],
      ['img' => asset('assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg'), 'name' => 'کوپل دوچرخه', 'tag' => 'کوپل', 'video' => false],
      ['img' => asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp'), 'name' => 'رترو گل', 'tag' => 'وینتیج', 'video' => false],
      ['img' => asset('assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg'), 'name' => 'لحظه احساسی', 'tag' => 'سینما', 'video' => true],
      ['img' => asset('assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp'), 'name' => 'پرتره خیابانی', 'tag' => 'پرتره', 'video' => false],
      ['img' => asset('assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg'), 'name' => 'مهتاب', 'tag' => 'طبیعت', 'video' => false],
    ];
  @endphp

  <div class="explore-grid">
    @foreach ($exploreCards as $card)
      <div class="explore-card" style="background-image:url('{{ $card['img'] }}');">
        <div class="explore-card-overlay"></div>
        @if($card['video'])
          <i class="fa-solid fa-video explore-card-type"></i>
        @else
          <i class="fa-solid fa-image explore-card-type"></i>
        @endif
        <div class="explore-card-info">
          <p class="explore-card-name">{{ $card['name'] }}</p>
          <p class="explore-card-tag">{{ $card['tag'] }}</p>
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

  .explore-page {
    width: 100%; max-width: 480px;
    margin: 0 auto;
    background: var(--bg, #000000);
    min-height: 100vh;
    padding-bottom: 120px;
  }

  /* HEADER */
  .explore-header {
    padding: calc(env(safe-area-inset-top) + 18px) 16px 16px 16px;
  }

  .explore-title {
    font-size: 20px; font-weight: 700;
    color: var(--text, #ffffff);
    margin: 0 0 16px 0; text-align: right;
  }

  /* SEARCH */
  .explore-search-wrap { margin-bottom: 12px; }
  .explore-search-box {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    padding: 11px 14px;
  }
  html.light .explore-search-box {
    background: rgba(0,0,0,0.05);
    border-color: rgba(0,0,0,0.1);
  }
  .explore-input {
    flex: 1; background: transparent; border: none; outline: none;
    font-size: 14px; color: var(--text, #ffffff);
  }
  .explore-input::placeholder { color: rgba(255,255,255,0.4); }
  html.light .explore-input::placeholder { color: rgba(0,0,0,0.35); }

  /* CHIPS */
  .explore-chips {
    display: flex; gap: 8px;
    overflow-x: auto; scrollbar-width: none;
    padding-bottom: 4px; direction: rtl;
  }
  .explore-chips::-webkit-scrollbar { display: none; }
  .explore-chip {
    flex-shrink: 0; padding: 6px 14px;
    border-radius: 12px;
    border: 1px solid var(--b1, #222230);
    background: var(--s1, #111116);
    color: rgba(255,255,255,0.6);
    font-size: 13px; cursor: pointer;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
  }
  .explore-chip.active {
    background: #0BBF53; border-color: #0BBF53; color: #ffffff; font-weight: 700;
  }
  html.light .explore-chip { color: rgba(0,0,0,0.55); }
  html.light .explore-chip.active { color: #ffffff; }

  /* GRID */
  .explore-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
    padding: 8px 12px;
  }

  .explore-card {
    aspect-ratio: 3/4; border-radius: 12px;
    overflow: hidden; position: relative;
    background-size: cover; background-position: center;
  }
  .explore-card-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 55%);
  }
  .explore-card-type {
    position: absolute; top: 8px; right: 8px;
    color: #ffffff; font-size: 11px;
    text-shadow: 0 1px 3px rgba(0,0,0,0.6);
  }
  .explore-card-info {
    position: absolute; bottom: 10px; right: 10px; text-align: right;
  }
  .explore-card-name { margin: 0; font-size: 13px; font-weight: 700; color: #ffffff; }
  .explore-card-tag  { margin: 2px 0 0; font-size: 11px; color: rgba(255,255,255,0.6); }

  .explore-page i[class*="fa-"] {
    font-family: "Font Awesome 6 Free" !important; font-weight: 900 !important; display: inline-block;
  }

  /* CHIPS active toggle */
</style>
@endpush

@push('scripts')
<script>
(function () {
  document.querySelectorAll('.explore-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
      document.querySelectorAll('.explore-chip').forEach(function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
    });
  });
}());
</script>
@endpush
