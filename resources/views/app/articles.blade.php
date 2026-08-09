@extends('layouts.app')

@section('page_title', isset($sitePage) ? ($sitePage->meta_title ?: $sitePage->title) : 'مقالات وطن')

@section('content')
<div class="articles-page" dir="rtl">
  @if(!isset($sitePage) || $sitePage->content('show_page_title', true))
    <header><span><i class="fa-solid fa-newspaper"></i></span><h1>{{ $sitePage->title ?? 'مقالات وطن' }}</h1><p>{{ $sitePage->subtitle ?? 'آموزش‌ها و تازه‌های هوش مصنوعی' }}</p></header>
  @endif
  <section><i class="fa-solid fa-pen-ruler"></i><strong>بزودی</strong><p>زیرساخت مدیریت و انتشار این صفحه آماده است و محتوای مقالات در مرحله بعد به آن اضافه می‌شود.</p></section>
</div>
@endsection

@push('styles')
<style>
  .articles-page{width:min(100% - 28px,960px);margin:0 auto;padding:28px 0 100px}.articles-page header{text-align:center}.articles-page header span{display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:14px;color:var(--vatan-accent-lime);background:var(--vatan-surface-card)}.articles-page h1{margin:12px 0 4px;color:var(--vatan-text-page);font-size:24px}.articles-page header p,.articles-page section p{color:var(--vatan-text-muted);font-size:12px}.articles-page section{display:grid;place-items:center;gap:7px;min-height:260px;margin-top:24px;border:1px solid var(--vatan-border);border-radius:18px;background:var(--vatan-surface-card);text-align:center}.articles-page section>i{color:var(--vatan-accent-lime);font-size:24px}.articles-page section strong{color:var(--vatan-text-page);font-size:16px}.articles-page section p{max-width:380px;margin:0;line-height:1.9}
</style>
@endpush
