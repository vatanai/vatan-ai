@extends('layouts.app')

@section('page_title', 'نقشه سایت وطن | دسترسی سریع به همه صفحات')

@push('meta')
  <meta name="description" content="نقشه سایت وطن؛ دسترسی سریع به صفحات اصلی، محصولات، دسته‌بندی‌ها، مقالات و آموزش‌های هوش مصنوعی.">
  <meta name="robots" content="index,follow,max-image-preview:large">
  <link rel="canonical" href="{{ route('site.sitemap') }}">
  <meta property="og:type" content="website">
  <meta property="og:title" content="نقشه سایت وطن">
  <meta property="og:description" content="دسترسی سریع و مرتب به همه صفحات عمومی وطن.">
  <meta property="og:url" content="{{ route('site.sitemap') }}">
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/site/css/sitemap.css') }}?v={{ filemtime(public_path('assets/site/css/sitemap.css')) }}">
@endpush

@php
  $totalLinks = collect($sections)->sum('count');
  $articleSection = collect($sections)->firstWhere('key', 'articles');
  $productSection = collect($sections)->firstWhere('key', 'products');
@endphp

@section('content')
<div class="sitemap-page" dir="rtl">
  <section class="sitemap-hero">
    <div class="vp-container sitemap-hero__inner">
      <div class="sitemap-hero__copy">
        <nav class="sitemap-breadcrumb" aria-label="مسیر صفحه">
          <a href="{{ route('site.home.root') }}">خانه</a>
          <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
          <span>نقشه سایت</span>
        </nav>
        <span class="sitemap-kicker"><i class="fa-solid fa-sitemap" aria-hidden="true"></i> مسیرهای وطن، یک‌جا</span>
        <h1>هر چیزی که دنبالشی، <em>اینجاست.</em></h1>
        <p>از صفحه‌های اصلی و محصولات تا مقاله‌ها و آموزش‌های هوش مصنوعی؛ همه‌ی مسیرهای عمومی وطن را مرتب و قابل دسترس ببین.</p>
        <div class="sitemap-hero__actions">
          <a class="sitemap-button sitemap-button--primary" href="{{ route('sitemap') }}" target="_blank" rel="noopener">
            <i class="fa-solid fa-code" aria-hidden="true"></i>
            <span>مشاهده نسخه XML</span>
            <i class="fa-solid fa-arrow-up-left-from-circle" aria-hidden="true"></i>
          </a>
          <a class="sitemap-button sitemap-button--secondary" href="{{ route('site.home.root') }}">
            <span>بازگشت به وطن</span>
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </a>
        </div>
      </div>

      <div class="sitemap-orbit" aria-hidden="true">
        <div class="sitemap-orbit__ring sitemap-orbit__ring--one"></div>
        <div class="sitemap-orbit__ring sitemap-orbit__ring--two"></div>
        <div class="sitemap-orbit__core"><i class="fa-solid fa-sitemap"></i><span>وطن</span></div>
        <span class="sitemap-orbit__node sitemap-orbit__node--one"><i class="fa-solid fa-newspaper"></i></span>
        <span class="sitemap-orbit__node sitemap-orbit__node--two"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
        <span class="sitemap-orbit__node sitemap-orbit__node--three"><i class="fa-solid fa-compass"></i></span>
        <span class="sitemap-orbit__node sitemap-orbit__node--four"><i class="fa-solid fa-layer-group"></i></span>
      </div>
    </div>
  </section>

  <main class="vp-container sitemap-content">
    <div class="sitemap-overview">
      <div class="sitemap-overview__intro">
        <span class="sitemap-section-label">نمای کلی</span>
        <h2>مسیر درست را سریع پیدا کن</h2>
        <p>این صفحه برای دسترسی راحت کاربران ساخته شده است؛ برای موتورهای جست‌وجو هم نسخه‌ی استاندارد XML در دسترس است.</p>
      </div>
      <div class="sitemap-stats" aria-label="آمار نقشه سایت">
        <div><strong>{{ number_format($totalLinks) }}</strong><span>نشانی عمومی</span></div>
        <div><strong>{{ number_format($articleSection['count'] ?? 0) }}</strong><span>مسیر محتوایی</span></div>
        <div><strong>{{ number_format($productSection['count'] ?? 0) }}</strong><span>مسیر محصولی</span></div>
      </div>
    </div>

    <div class="sitemap-sections">
      @foreach($sections as $section)
        <section class="sitemap-card sitemap-card--{{ $section['key'] }}">
          <header class="sitemap-card__header">
            <span class="sitemap-card__icon"><i class="fa-solid {{ $section['icon'] }}" aria-hidden="true"></i></span>
            <div><span>{{ sprintf('%02d', $loop->iteration) }}</span><h2>{{ $section['title'] }}</h2><p>{{ $section['description'] }}</p></div>
            <b>{{ number_format($section['count']) }} مسیر</b>
          </header>
          <div class="sitemap-links">
            @foreach($section['links'] as $link)
              <a class="sitemap-link" href="{{ $link['loc'] }}">
                <span class="sitemap-link__mark"><i class="fa-solid {{ $link['icon'] }}" aria-hidden="true"></i></span>
                <span class="sitemap-link__body"><strong>{{ $link['title'] }}</strong><small>{{ \Illuminate\Support\Str::limit(strip_tags((string) ($link['description'] ?? '')), 108) }}</small></span>
                <i class="fa-solid fa-arrow-left sitemap-link__arrow" aria-hidden="true"></i>
              </a>
            @endforeach
          </div>
        </section>
      @endforeach
    </div>

    <aside class="sitemap-xml-note">
      <span class="sitemap-xml-note__icon"><i class="fa-solid fa-file-code" aria-hidden="true"></i></span>
      <div><strong>نسخه مناسب موتورهای جست‌وجو</strong><p>برای ثبت در Google Search Console یا استفاده‌ی فنی، نسخه‌ی استاندارد و پویا را باز کن.</p></div>
      <a href="{{ route('sitemap') }}" target="_blank" rel="noopener">باز کردن sitemap.xml <i class="fa-solid fa-arrow-up-left-from-circle" aria-hidden="true"></i></a>
    </aside>
  </main>
</div>
@endsection
