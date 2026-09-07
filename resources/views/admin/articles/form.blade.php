@extends('layouts.admin')
@section('title', ($article->exists ? 'ویرایش مقاله' : 'مقاله جدید') . ' — وطن استودیو')
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/articles.css') }}?v={{ filemtime(public_path('admin/css/articles.css')) }}">@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">
    <div class="article-admin">
      <header class="article-page-head"><div class="article-page-head__copy"><small>مرکز مقالات</small><h1>{{ $article->exists ? 'ویرایش مقاله' : 'ثبت مقاله جدید' }}</h1><p>{{ $article->exists ? 'نسخه‌های قبلی حفظ می‌شوند و تغییر نامک، ریدایرکت دائمی می‌سازد.' : 'محتوا را بلوکی بسازید؛ سئو و زمان مطالعه می‌تواند خودکار تکمیل شود.' }}</p></div><div class="article-page-actions"><a class="article-btn" href="{{ route('admin.articles.index') }}"><i class="fa-solid fa-arrow-right"></i> فهرست مقالات</a>@if($article->exists)<a class="article-btn" href="{{ $article->publicUrl() }}?preview=1" target="_blank"><i class="fa-regular fa-eye"></i> پیش‌نمایش</a><a class="article-btn" href="{{ route('admin.articles.analytics',$article) }}"><i class="fa-solid fa-chart-line"></i> گزارش</a>@endif</div></header>
      @include('admin.articles.partials.messages')
      <form method="POST" action="{{ $article->exists ? route('admin.articles.update',$article) : route('admin.articles.store') }}" enctype="multipart/form-data" data-article-form>@csrf @if($article->exists)@method('PUT')@endif
        <div class="article-form-layout">
          <div class="article-form-main">
            @include('admin.articles.partials.form-identity')
            @include('admin.articles.partials.form-editor')
            @include('admin.articles.partials.form-relations')
            @include('admin.articles.partials.form-galleries')
          </div>
          <aside class="article-form-side">
            @include('admin.articles.partials.form-publishing')
            @include('admin.articles.partials.form-seo')
            @include('admin.articles.partials.form-history')
          </aside>
        </div>
      </form>
      @if($article->exists)
        <form id="article-archive-form" method="POST" action="{{ route('admin.articles.archive',$article) }}">@csrf @method('PATCH')</form>
        <form id="article-delete-form" method="POST" action="{{ route('admin.articles.destroy',$article) }}" onsubmit="return confirm('مقاله به زباله‌دان منتقل شود؟')">@csrf @method('DELETE')</form>
        @foreach($article->revisions as $revision)<form id="revision-restore-{{ $revision->id }}" method="POST" action="{{ route('admin.articles.revisions.restore',[$article,$revision]) }}">@csrf</form>@endforeach
      @endif
    </div>
  </div>
</main>
@endsection

@section('scripts')<script src="{{ asset('admin/js/articles.js') }}?v={{ filemtime(public_path('admin/js/articles.js')) }}"></script>@endsection
