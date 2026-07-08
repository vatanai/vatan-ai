@extends('layouts.app')

@section('content')
<div class="profile-page" dir="rtl">

  {{-- ۱) هدر و نمایش بالا: آواتار، اطلاعات، آمار، اکشن‌ها --}}
  @include('app.profile.header')

  <section class="tabs-section">

    {{-- ۲) گرید و مارک‌ها: تب‌ها + محتوای ساخته‌شده + ذخیره‌شده‌ها --}}
    @include('app.profile.content')

    {{-- ۳) فایل‌های تو --}}
    @include('app.profile.files')

    {{-- ۴) همکاری در فروش --}}
    @include('app.profile.referral')

  </section>

  {{-- ===== مودال پیش‌نمایش عکس ساخته‌شده ===== --}}
  <div id="gridPreviewModal" class="grid-preview-modal" style="display:none;">
    <div class="grid-preview-backdrop"></div>
    <div class="grid-preview-box">
      <button type="button" class="grid-preview-close" id="gridPreviewClose" aria-label="بستن">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="grid-preview-img-wrap">
        <img id="gridPreviewImg" src="" alt="پیش‌نمایش عکس ساخته‌شده">
      </div>

      <div class="grid-preview-actions">
        <a id="gridPreviewDownload" href="" download class="grid-preview-btn">
          <i class="fa-solid fa-download"></i>
          <span>دانلود</span>
        </a>
        <button type="button" id="gridPreviewShare" class="grid-preview-btn">
          <i class="fa-solid fa-share-nodes"></i>
          <span>اشتراک‌گذاری</span>
        </button>
      </div>

      <p class="grid-preview-date" id="gridPreviewDate"></p>

      <a id="gridPreviewProductLink" href="#" class="grid-preview-product-link">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
        <span>محصول اصلی: <b id="gridPreviewProductName"></b></span>
        <i class="fa-solid fa-chevron-left grid-preview-product-arrow"></i>
      </a>
    </div>
  </div>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/profile.js') }}" defer></script>
@endpush
