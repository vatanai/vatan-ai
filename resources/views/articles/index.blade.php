@extends('layouts.app')
@section('page_title', ($sitePage->meta_title ?? null) ?: 'مقالات و آموزش هوش مصنوعی | وطن')

@push('meta')
  @if(request()->filled('q'))<meta name="robots" content="noindex,follow">@endif
  <link rel="alternate" type="application/rss+xml" title="خوراک مقالات وطن" href="{{ route('articles.feed') }}">
@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('assets/site/css/articles.css') }}?v={{ filemtime(public_path('assets/site/css/articles.css')) }}">@endpush

@section('content')
<div class="articles-shell">
  <section class="articles-hero">
    <div class="vp-container">
      <div class="articles-hero__copy"><span><i class="fa-regular fa-newspaper"></i> مرکز آموزش وطن</span><h1>{{ $sitePage->title ?? 'مقالات وطن' }}</h1><p>{{ $sitePage->subtitle ?? 'راهنماهای عملی، پرامپت‌های تست‌شده و تجربه‌های واقعی ساخت تصویر و ویدیو با هوش مصنوعی.' }}</p></div>
      <form class="articles-search" method="GET" action="{{ route('articles.index') }}"><label for="articles-query">جست‌وجو در مقالات</label><div><i class="fa-solid fa-magnifying-glass"></i><input id="articles-query" type="search" name="q" value="{{ $search }}" placeholder="مثلاً ساخت عکس محصول..."><button type="submit">جست‌وجو</button></div></form>
    </div>
  </section>

  <div class="vp-container articles-main">
    @if(!request()->filled('q') && $featuredArticles->isNotEmpty())
      <section class="articles-featured"><div class="article-section-heading"><div><span>پیشنهاد سردبیر</span><h2>از این مقاله‌ها شروع کنید</h2></div></div><div class="articles-featured__grid">@foreach($featuredArticles as $article)@include('articles.partials.card', ['article' => $article])@endforeach</div></section>
    @endif

    <nav class="article-category-nav" aria-label="دسته‌بندی مقالات"><a href="{{ route('articles.index') }}" class="is-active">همه مقالات</a>@foreach($categories as $category)<a href="{{ $category->publicUrl() }}">{{ $category->name }} <small>{{ $category->articles_count }}</small></a>@endforeach</nav>

    <section class="articles-latest"><div class="article-section-heading"><div><span>{{ request()->filled('q') ? 'نتیجه جست‌وجو' : 'تازه‌ترین آموزش‌ها' }}</span><h2>{{ request()->filled('q') ? 'نتایج برای «' . $search . '»' : 'مقالات جدید وطن' }}</h2></div><p>{{ number_format($articles->total()) }} مقاله</p></div><div class="articles-grid">@forelse($articles as $article)@include('articles.partials.card', ['article' => $article])@empty<div class="articles-empty"><i class="fa-regular fa-folder-open"></i><strong>مقاله‌ای پیدا نشد</strong><p>عبارت دیگری را جست‌وجو کنید یا یکی از دسته‌بندی‌ها را انتخاب کنید.</p></div>@endforelse</div>{{ $articles->links() }}</section>
  </div>
</div>
@endsection
