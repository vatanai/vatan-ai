@extends('layouts.app')

@section('page_title', isset($sitePage) ? ($sitePage->meta_title ?: $sitePage->title) : 'وطن AI')

@section('content')
<div class="home-page" dir="rtl">
  <script>
    if ('IntersectionObserver' in window) {
      document.currentScript.parentElement.classList.add('hb-lazy-images');
      window.homeImageFallback = setTimeout(function () {
        document.querySelector('.home-page')?.classList.remove('hb-lazy-images');
      }, 8000);
    }
  </script>

  {{-- ===== SECTION 2: خوش‌آمدگویی هوشمند ===== --}}
  @if(!isset($sitePage) || $sitePage->content('show_page_title', true))
    <section class="home-greeting">
      <p class="home-greeting-title">{{ isset($sitePage) ? $sitePage->title : 'سلام، خوش اومدی' }}</p>
      <p class="home-greeting-sub">{{ isset($sitePage) ? $sitePage->subtitle : 'می‌خوای چی خلق کنی؟' }}</p>
    </section>
  @endif

  {{-- ===== SECTION 3: جستجوی زنده محصولات + انتقال به کاتالوگ ===== --}}
  @if(!isset($sitePage) || $sitePage->content('show_search', true))
  <section class="home-imagegen">
    <div class="ig-box" dir="rtl">

      {{-- ردیف بالا: ورود سریع به جست‌وجو + پرامپت --}}
      <form class="ig-top" id="home-search-form" data-search-url="{{ route('app.home.search') }}" action="{{ route('products.index') }}" method="GET">
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
  @endif

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
<link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/app-home.css') }}">
@endpush

@push('scripts')
<script src="{{ \App\Support\AppAsset::url('js/app-home.js') }}" defer></script>
@endpush
