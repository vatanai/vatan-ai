@extends('layouts.app')
@section('page_title', ($category->meta_title ?: $category->name . ' | مقالات وطن'))
@push('meta')
  <meta name="description" content="{{ $category->meta_description ?: $category->description }}">
  <meta name="robots" content="{{ $category->is_indexable && $articles->total() >= 3 ? 'index,follow,max-image-preview:large' : 'noindex,follow' }}">
  <link rel="canonical" href="{{ $category->publicUrl() }}{{ $articles->currentPage() > 1 ? '?page=' . $articles->currentPage() : '' }}">
@endpush
@push('styles')<link rel="stylesheet" href="{{ \App\Support\AppAsset::url('assets/site/css/articles.css') }}">@endpush
@section('content')
<div class="articles-shell"><section class="articles-archive-hero"><div class="vp-container"><nav><a href="{{ route('site.home.root') }}">خانه</a><i class="fa-solid fa-angle-left"></i><a href="{{ route('articles.index') }}">مقالات</a><i class="fa-solid fa-angle-left"></i><span>{{ $category->name }}</span></nav><span>دسته‌بندی مقالات</span><h1>{{ $category->name }}</h1><p>{{ $category->description }}</p></div></section><div class="vp-container articles-main"><nav class="article-category-nav" aria-label="دسته‌بندی مقالات"><a href="{{ route('articles.index') }}">همه مقالات</a>@foreach($categories as $item)<a href="{{ $item->publicUrl() }}" class="{{ $item->is($category) ? 'is-active' : '' }}">{{ $item->name }} <small>{{ $item->articles_count }}</small></a>@endforeach</nav><section class="articles-latest"><div class="article-section-heading"><div><span>آرشیو موضوعی</span><h2>{{ number_format($articles->total()) }} مقاله در این دسته</h2></div></div><div class="articles-grid">@foreach($articles as $article)@include('articles.partials.card', ['article' => $article])@endforeach</div>{{ $articles->links() }}</section></div></div>
@endsection
