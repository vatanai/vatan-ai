@extends('layouts.app')
@section('page_title', $author->name . ' | نویسندگان وطن')
@push('meta')
  <meta name="description" content="{{ $author->bio }}"><link rel="canonical" href="{{ $author->publicUrl() }}">
  <script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'ProfilePage','mainEntity'=>['@type'=>'Organization','name'=>$author->name,'description'=>$author->bio,'url'=>$author->publicUrl()]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('assets/site/css/articles.css') }}?v={{ filemtime(public_path('assets/site/css/articles.css')) }}">@endpush
@section('content')
<div class="articles-shell"><section class="article-author-hero"><div class="vp-container"><div class="article-author-hero__avatar">{{ mb_substr($author->name,0,1) }}</div><div><span>{{ $author->title }}</span><h1>{{ $author->name }}</h1><p>{{ $author->bio }}</p></div></div></section><div class="vp-container articles-main"><section class="articles-latest"><div class="article-section-heading"><div><span>نوشته‌های نویسنده</span><h2>{{ number_format($articles->total()) }} مقاله منتشرشده</h2></div></div><div class="articles-grid">@foreach($articles as $article)@include('articles.partials.card', ['article'=>$article])@endforeach</div>{{ $articles->links() }}</section></div></div>
@endsection
