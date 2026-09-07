@extends('layouts.app')
@section('page_title', $article->meta_title ?: $article->title . ' | وطن')
@push('meta')
  <meta name="description" content="{{ $article->meta_description ?: $article->excerpt }}">
  <meta name="robots" content="{{ $isAdminPreview || !$article->is_indexable ? 'noindex,nofollow' : 'index,follow,max-image-preview:large' }}">
  <link rel="canonical" href="{{ $article->canonicalUrl() }}">
  <meta property="og:type" content="article"><meta property="og:title" content="{{ $article->meta_title ?: $article->title }}"><meta property="og:description" content="{{ $article->meta_description ?: $article->excerpt }}"><meta property="og:url" content="{{ $article->canonicalUrl() }}">@if($article->imageUrl($article->og_image ?: $article->featured_image))<meta property="og:image" content="{{ $article->imageUrl($article->og_image ?: $article->featured_image) }}">@endif
  <meta name="twitter:card" content="summary_large_image">
  <script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>$article->content_type === 'news' ? 'NewsArticle' : 'BlogPosting','mainEntityOfPage'=>$article->canonicalUrl(),'headline'=>$article->title,'description'=>$article->excerpt,'image'=>array_values(array_filter([$article->imageUrl($article->og_image ?: $article->featured_image)])),'datePublished'=>$article->published_at?->toIso8601String(),'dateModified'=>$article->updated_at?->toIso8601String(),'author'=>['@type'=>'Organization','name'=>$article->author->name,'url'=>$article->author->publicUrl()],'publisher'=>['@type'=>'Organization','name'=>'وطن','url'=>route('site.home.root')]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
  <script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>'مقالات','item'=>route('articles.index')],['@type'=>'ListItem','position'=>2,'name'=>$article->category->name,'item'=>$article->category->publicUrl()],['@type'=>'ListItem','position'=>3,'name'=>$article->title,'item'=>$article->canonicalUrl()]]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
@push('styles')<link rel="stylesheet" href="{{ asset('assets/site/css/articles.css') }}?v={{ filemtime(public_path('assets/site/css/articles.css')) }}">@endpush

@section('content')
<article class="article-detail" data-article-id="{{ $article->id }}" data-event-url="{{ route('articles.events', $article) }}">
  @if($isAdminPreview)<div class="article-preview-notice">پیش‌نمایش مدیریتی؛ این نسخه برای موتورهای جست‌وجو قابل‌ایندکس نیست.</div>@endif
  <header class="article-detail__header"><div class="vp-container"><nav class="article-breadcrumb"><a href="{{ route('site.home.root') }}">خانه</a><i class="fa-solid fa-angle-left"></i><a href="{{ route('articles.index') }}">مقالات</a><i class="fa-solid fa-angle-left"></i><a href="{{ $article->category->publicUrl() }}">{{ $article->category->name }}</a></nav><div class="article-detail__category">{{ $article->category->name }}</div><h1>{{ $article->title }}</h1><p>{{ $article->excerpt }}</p><div class="article-detail__byline"><a href="{{ $article->author->publicUrl() }}"><span>{{ mb_substr($article->author->name,0,1) }}</span><div><strong>{{ $article->author->name }}</strong><small>{{ $article->author->title }}</small></div></a><div><span>انتشار {{ $article->published_at?->locale('fa')->translatedFormat('j F Y') }}</span>@if($article->updated_at->gt($article->published_at))<span>بروزرسانی {{ $article->updated_at->locale('fa')->translatedFormat('j F Y') }}</span>@endif<span>{{ $article->reading_minutes }} دقیقه مطالعه</span></div></div></div></header>
  @if($article->imageUrl())<div class="vp-container article-detail__cover"><img src="{{ $article->imageUrl() }}" alt="{{ $article->featured_image_alt ?: $article->title }}" width="1280" height="720" fetchpriority="high"></div>@endif
  <div class="vp-container article-detail__layout"><main class="article-content">@include('articles.partials.blocks')
      @if($article->products->isNotEmpty())<section class="article-products"><div class="article-section-heading"><div><span>ابزارهای مرتبط</span><h2>این آموزش را در وطن اجرا کنید</h2></div></div><div>@foreach($article->products as $product)<a href="{{ route('app.product',$product->route_slug) }}" data-article-event="product_cta_click" data-product-id="{{ $product->id }}"><strong>{{ $product->name_fa }}</strong><span>{{ $product->credit_cost }} اعتبار</span><i class="fa-solid fa-arrow-left"></i></a>@endforeach</div></section>@endif
    </main><aside class="article-toc"><strong>در این مقاله</strong><nav data-article-toc></nav><div class="article-share"><span>اشتراک‌گذاری</span><button type="button" data-share-article><i class="fa-solid fa-share-nodes"></i> اشتراک</button></div></aside></div>
  <div class="vp-container">@include('articles.partials.gallery')
    @if($article->tags->isNotEmpty())<div class="article-tags"><span>برچسب‌ها:</span>@foreach($article->tags as $tag)<em>#{{ $tag->name }}</em>@endforeach</div>@endif
    @if($relatedArticles->isNotEmpty())<section class="article-related"><div class="article-section-heading"><div><span>مطالعه بیشتر</span><h2>مقالات مرتبط</h2></div></div><div class="articles-grid">@foreach($relatedArticles as $related)@include('articles.partials.card',['article'=>$related])@endforeach</div></section>@endif
    @include('articles.partials.comments')
  </div>
</article>
@endsection
@push('scripts')<script src="{{ asset('assets/site/js/articles.js') }}?v={{ filemtime(public_path('assets/site/js/articles.js')) }}" defer></script>@endpush
