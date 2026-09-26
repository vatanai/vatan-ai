@extends('layouts.app')

@section('page_title', isset($sitePage) ? ($sitePage->meta_title ?: $sitePage->title) : 'پروفایل | وطن AI')

@section('content')
<div class="profile-page" dir="rtl" data-authenticated="{{ ($isGuest ?? false) ? '0' : '1' }}">

  {{-- ۱) هدر و نمایش بالا: آواتار، اطلاعات، آمار، اکشن‌ها --}}
  @include('app.profile.header')

  <section class="tabs-section">

    {{-- ۲) گرید و مارک‌ها: تب‌ها + محتوای ساخته‌شده + ذخیره‌شده‌ها --}}
    @include('app.profile.content')

    {{-- ۳) فایل‌های تو — بعد از آماده‌شدن پاسخ اصلی در پس‌زمینه آماده می‌شود --}}
    @include('app.profile.partials.lazy-panel', ['panel' => 'files'])

    @if($referralProfileEnabled ?? false)
      {{-- ۴) همکاری در فروش و مسیر کاربر — بعد از آماده‌شدن پاسخ اصلی در پس‌زمینه آماده می‌شود --}}
      @include('app.profile.partials.lazy-panel', ['panel' => 'referral'])
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
        <p class="grid-preview-date" id="gridPreviewDate"></p>
        <button type="button" class="grid-preview-close" id="gridPreviewClose" aria-label="بستن">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17"></path></svg>
        </button>
      </header>

      <div class="grid-preview-media">
        <div class="grid-preview-media-glow"></div>
        <div class="grid-preview-img-wrap">
          <img id="gridPreviewImg" src="" alt="پیش‌نمایش عکس ساخته‌شده">
          <video id="gridPreviewVideo" controls playsinline preload="metadata" hidden></video>
          <button type="button" class="grid-preview-play" id="gridPreviewPlay" aria-label="پخش ویدیو" hidden><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M8 5v14l11-7L8 5Z"></path></svg></button>
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
          <span>اشتراک</span>
        </button>
        <a id="gridPreviewRecreate" href="#" class="grid-preview-btn grid-preview-btn--recreate is-disabled" aria-disabled="true">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 0 1 13.7-5.6L20 8.7M20 5v3.7h-3.7M20 12a8 8 0 0 1-13.7 5.6L4 15.3M4 19v-3.7h3.7"></path></svg>
          <span>ساخت مجدد</span>
        </a>
        <button type="button" id="gridPreviewDelete" class="grid-preview-btn grid-preview-btn--delete">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7V4h6v3m-9 0 1 13h10l1-13"></path></svg>
          <span>حذف</span>
        </button>
      </div>
      <div id="gridPreviewDeleteConfirm" class="grid-preview-delete-confirm" hidden role="dialog" aria-live="polite" aria-label="تأیید حذف خروجی">
        <p>از حذف این عکس مطمئن هستین؟</p>
        <form id="gridPreviewDeleteForm" method="POST">
          @csrf
          @method('DELETE')
          <button type="submit" class="grid-preview-delete-confirm__yes">آره، پاک بشه</button>
          <button type="button" id="gridPreviewDeleteCancel" class="grid-preview-delete-confirm__no">خیر، منصرف شدم</button>
        </form>
      </div>

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
