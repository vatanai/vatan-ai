@extends('layouts.app')

@section('page_title', 'مقایسه نسخه‌های بساز | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-versions-compare.css') }}?v={{ filemtime(public_path('css/create-versions-compare.css')) }}">
@endpush

@section('content')
<div class="create-versions-compare-page" dir="rtl">
  <header class="create-versions-compare-header">
    <div>
      <span>مقایسه‌ی کاملاً جدا از صفحات اصلی</span>
      <h1>نسخه‌های «بساز» و «بساز محصول»</h1>
      <p>صفحات بکاپ داخل پنجره‌های مستقل نمایش داده می‌شوند؛ استایل پروژه‌ی فعلی به بکاپ‌ها وارد نمی‌شود.</p>
    </div>
    <code>?product={{ $productSlug }}</code>
  </header>

  <div class="create-versions-compare-grid">
    <article class="create-versions-compare-card create-versions-compare-card--current">
      <header><span>پروژه فعلی</span><h2>بساز محصول</h2><small><code>app.create?product=...</code></small></header>
      <iframe title="بساز محصول پروژه فعلی" src="{{ route('app.create', ['product' => $productSlug]) }}" loading="lazy"></iframe>
    </article>

    <article class="create-versions-compare-card create-versions-compare-card--current">
      <header><span>پروژه فعلی</span><h2>بساز</h2><small><code>app.create</code></small></header>
      <iframe title="بساز پروژه فعلی" src="{{ route('app.create') }}" loading="lazy"></iframe>
    </article>

    <article class="create-versions-compare-card create-versions-compare-card--backup">
      <header><span>بکاپ اول</span><h2>بساز محصول</h2><small><code>/Users/mohsenmac/Downloads/app</code></small></header>
      <iframe title="بساز محصول بکاپ اول" src="{{ route('app.create.versions.compare.legacy', ['source' => 'backup-one', 'page' => 'product', 'product' => $productSlug]) }}" loading="lazy"></iframe>
    </article>

    <article class="create-versions-compare-card create-versions-compare-card--backup">
      <header><span>بکاپ اول</span><h2>بساز</h2><small><code>/Users/mohsenmac/Downloads/app</code></small></header>
      <iframe title="بساز بکاپ اول" src="{{ route('app.create.versions.compare.legacy', ['source' => 'backup-one', 'page' => 'create', 'product' => $productSlug]) }}" loading="lazy"></iframe>
    </article>

    <article class="create-versions-compare-card create-versions-compare-card--backup">
      <header><span>بکاپ دوم</span><h2>بساز محصول</h2><small><code>/Users/mohsenmac/Downloads/app 2</code></small></header>
      <iframe title="بساز محصول بکاپ دوم" src="{{ route('app.create.versions.compare.legacy', ['source' => 'backup-two', 'page' => 'product', 'product' => $productSlug]) }}" loading="lazy"></iframe>
    </article>

    <article class="create-versions-compare-card create-versions-compare-card--backup">
      <header><span>بکاپ دوم</span><h2>بساز</h2><small><code>/Users/mohsenmac/Downloads/app 2</code></small></header>
      <iframe title="بساز بکاپ دوم" src="{{ route('app.create.versions.compare.legacy', ['source' => 'backup-two', 'page' => 'create', 'product' => $productSlug]) }}" loading="lazy"></iframe>
    </article>
  </div>
</div>
@endsection
