@extends('layouts.app')

@section('page_title', isset($sitePage) ? ($sitePage->meta_title ?: $sitePage->title) : 'پروفایل | وطن AI')

@section('content')
<div class="profile-page" dir="rtl">

  {{-- ۱) هدر و نمایش بالا: آواتار، اطلاعات، آمار، اکشن‌ها --}}
  @include('app.profile.header')

  <section class="tabs-section">

    {{-- ۲) گرید و مارک‌ها: تب‌ها + محتوای ساخته‌شده + ذخیره‌شده‌ها --}}
    @include('app.profile.content')

    {{-- ۳) فایل‌های تو --}}
    @include('app.profile.files')

    @if($referralProfileEnabled ?? false)
      {{-- ۴) همکاری در فروش --}}
      @include('app.profile.referral')
    @endif

  </section>

  {{-- ===== مودال حرفه‌ای پیش‌نمایش خروجی ===== --}}
  <div id="gridPreviewModal" class="grid-preview-modal" style="display:none;">
    <div class="grid-preview-backdrop"></div>
    <div class="grid-preview-box">
      <header class="grid-preview-header">
        <div class="grid-preview-heading">
          <span class="grid-preview-eyebrow">خروجی ذخیره‌شده</span>
          <strong>پیش‌نمایش خروجی</strong>
        </div>
        <button type="button" class="grid-preview-close" id="gridPreviewClose" aria-label="بستن">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17"></path></svg>
        </button>
      </header>

      <div class="grid-preview-media">
        <div class="grid-preview-media-glow"></div>
        <div class="grid-preview-img-wrap">
          <img id="gridPreviewImg" src="" alt="پیش‌نمایش عکس ساخته‌شده">
          <video id="gridPreviewVideo" controls playsinline preload="metadata" hidden></video>
        </div>
        <span class="grid-preview-media-badge">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
          آماده
        </span>
      </div>

      <div class="grid-preview-actions">
        <a id="gridPreviewDownload" href="" download class="grid-preview-btn grid-preview-btn--primary">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 18v2h14v-2"></path></svg>
          <span>دانلود</span>
        </a>
        <button type="button" id="gridPreviewShare" class="grid-preview-btn">
          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="2.5"></circle><circle cx="6" cy="12" r="2.5"></circle><circle cx="18" cy="19" r="2.5"></circle><path d="m8.3 10.8 7.4-4.5m-7.4 7.1 7.4 4.5"></path></svg>
          <span>اشتراک‌گذاری</span>
        </button>
        <a id="gridPreviewRecreate" href="#" class="grid-preview-btn grid-preview-btn--recreate is-disabled" aria-disabled="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 0 1 13.7-5.6L20 8.7M20 5v3.7h-3.7M20 12a8 8 0 0 1-13.7 5.6L4 15.3M4 19v-3.7h3.7"></path></svg>
          <span>ساخت مجدد با همین محصول</span>
        </a>
      </div>

      <div class="grid-preview-meta">
        <span class="grid-preview-meta-line"></span>
        <p class="grid-preview-date" id="gridPreviewDate"></p>
        <span class="grid-preview-meta-line"></span>
      </div>

      <a id="gridPreviewProductLink" href="#" class="grid-preview-product-link">
        <span class="grid-preview-product-icon">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 1.7 5.3L19 10l-5.3 1.7L12 17l-1.7-5.3L5 10l5.3-1.7L12 3Zm6.5 12.5.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8.8-2.2Z"></path></svg>
        </span>
        <span class="grid-preview-product-copy"><small>محصول استفاده‌شده</small><b id="gridPreviewProductName"></b></span>
        <svg class="grid-preview-product-arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m15 5-7 7 7 7"></path></svg>
      </a>
    </div>
  </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/profile.css') }}">
@endpush

@push('scripts')
<script src="{{ \App\Support\AppAsset::url('js/profile.js') }}" defer></script>
@endpush
