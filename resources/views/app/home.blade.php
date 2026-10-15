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

  @php
    // ویترین: هیروی «بالای صفحه» قبل از جست‌وجو رندر می‌شود و جای تیتر خوشامد را می‌گیرد؛
    // اگر هیروی بالای صفحه منتشر باشد، تیتر خوش‌آمدگویی جایش را به ویترین می‌دهد.
    $vtTopItems = $renderedSections->filter(fn ($i) => $i['section']->type === 'vt_hero'
      && $i['section']->setting('placement', 'top') === 'top')->values();
    $vtFlowItems = $renderedSections->reject(fn ($i) => $i['section']->type === 'vt_hero'
      && $i['section']->setting('placement', 'top') === 'top')->values();
    $vtHasTopHero = $vtTopItems->contains(fn ($i) => ($i['products'] ?? collect())->isNotEmpty());
  @endphp

  @include('app.home-builder.partials.styles')

  @foreach($vtTopItems as $item)
    @include('app.home-builder.dispatcher', ['item' => $item])
  @endforeach

  {{-- ===== SECTION 2: خوش‌آمدگویی هوشمند ===== --}}
  @if(!$vtHasTopHero && (!isset($sitePage) || $sitePage->content('show_page_title', true)))
    <section class="home-greeting">
      <p class="home-greeting-title">{{ isset($sitePage) ? $sitePage->title : 'سلام، خوش اومدی' }}</p>
      <p class="home-greeting-sub">{{ isset($sitePage) ? $sitePage->subtitle : 'می‌خوای چی خلق کنی؟' }}</p>
    </section>
  @endif

  {{-- باکس جست‌وجوی هوشمند صفحه‌ی خانه --}}
  @if(!isset($sitePage) || $sitePage->content('show_search', true))
    @include('app.partials.home-search-variants')
  @endif

  {{-- ===== SECTION 4: Sectionهای داینامیک صفحه هوم (مدیریت از پنل ادمین → مدیریت صفحه هوم) ===== --}}
  <section class="home-products">
    @forelse($vtFlowItems as $item)
      @include('app.home-builder.dispatcher', ['item' => $item])
    @empty
      {{-- هنوز هیچ Section منتشرشده‌ای برای صفحه هوم تعریف نشده --}}
    @endforelse
  </section>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/app-home.css') }}">
<link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/app-home-search-variants.css') }}">
@endpush

@push('scripts')
<script src="{{ \App\Support\AppAsset::url('js/app-home.js') }}" defer></script>
<script src="{{ \App\Support\AppAsset::url('js/app-home-search-variants.js') }}" defer></script>
@endpush
