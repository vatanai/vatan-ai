@extends('layouts.admin')
@section('title', 'مرکز مقالات — وطن استودیو')
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/articles.css') }}?v={{ filemtime(public_path('admin/css/articles.css')) }}">@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">
    <div class="article-admin">
      <header class="article-page-head"><div class="article-page-head__copy"><small>مدیریت محتوای سایت</small><h1>مرکز مقالات وطن</h1><p>ثبت، انتشار، آرشیو، سئو، گالری و پایش عملکرد مقاله‌ها از یک نقطه.</p></div><div class="article-page-actions"><a class="article-btn" href="{{ route('admin.articles.analytics-overview') }}"><i class="fa-solid fa-chart-line"></i> گزارش کلی</a><a class="article-btn" href="{{ route('admin.article-comments.index') }}"><i class="fa-regular fa-comments"></i> دیدگاه‌ها @if($stats['pending_comments'])<b>{{ $stats['pending_comments'] }}</b>@endif</a><a class="article-btn" href="{{ route('admin.article-categories.index') }}"><i class="fa-solid fa-folder-tree"></i> دسته‌بندی‌ها</a><a class="article-btn article-btn--primary" href="{{ route('admin.articles.create') }}"><i class="fa-solid fa-plus"></i> مقاله جدید</a></div></header>
      @include('admin.articles.partials.messages')
      @include('admin.articles.partials.stats')
      <section class="article-card-admin">
        @include('admin.articles.partials.filters')
        @include('admin.articles.partials.table')
      </section>
    </div>
  </div>
</main>
@endsection
