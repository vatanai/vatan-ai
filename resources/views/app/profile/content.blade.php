{{-- ===== بخش گرید و مارک‌ها: تب‌ها + محتوای ساخته‌شده + ذخیره‌شده‌ها ===== --}}

{{-- تب‌ها — موبایل: ذخیره شده‌ها | همکاری در فروش | فایل‌ها | محتوا؛ دسکتاپ با CSS برعکس می‌شود --}}
<div class="profile-tabs" dir="rtl">
  <button type="button" class="profile-tab" data-tab="saved">
    <img src="{{ \App\Support\AppAsset::url('assets/img/icons/fi-sr-bookmark.svg') }}" class="tab-icon" width="19" height="19" alt="">
    <span class="tab-label">ذخیره شده‌ها</span>
  </button>
  @if($referralProfileEnabled ?? false)
  <button type="button" class="profile-tab" data-tab="referral" id="referralTab">
    <svg class="tab-icon tab-icon--svg" width="21" height="21" viewBox="0 0 24 24" fill="currentColor">

      <path d="M16 11C17.66 11 18.99 9.66 18.99 8S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05C16.19 13.89 17 15.02 17 16.5V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
    </svg>
    <span class="tab-label">همکاری در فروش</span>
  </button>
  @endif
  <button type="button" class="profile-tab" data-tab="files">
    <img src="{{ \App\Support\AppAsset::url('assets/img/icons/fi-sr-file.svg') }}" class="tab-icon" width="19" height="19" alt="">
    <span class="tab-label">فایل‌ها</span>
  </button>
  <button type="button" class="profile-tab active" data-tab="grid">
    <img src="{{ \App\Support\AppAsset::url('assets/img/icons/fi-sr-grid.svg') }}" class="tab-icon" width="19" height="19" alt="">
    <span class="tab-label">محتوا</span>
  </button>
</div>

{{-- ===== PANEL: گرید (محتوا) — خروجی‌های عکس و ویدیوی واقعی کاربر ===== --}}
<div class="profile-panel panel-grid" data-panel="grid">
  @if(($createdMedia ?? collect())->isEmpty())
    <div class="grid-empty">
      <img src="{{ \App\Support\AppAsset::url('assets/img/icons/fi-sr-grid.svg') }}" width="32" height="32" alt="" style="opacity:.4;">
      <p>هنوز محتوایی نساختی</p>
      <a href="{{ route('app.explore') }}" class="btn-empty-cta">ساخت اولین محتوا</a>
    </div>
  @else
    @include('app.profile.partials.media-items', ['createdMedia' => $createdMedia, 'eagerMedia' => true])
  @endif
</div>
<div class="profile-media-sentinel" data-media-sentinel data-media-endpoint="{{ route('profile.media') }}" data-next-cursor="{{ $initialMediaCursor ?? '' }}" aria-hidden="true"></div>

{{-- ===== PANEL: ذخیره شده‌ها — محصولات واقعاً سیوشده از جدول saved_products ===== --}}
@include('app.profile.partials.lazy-panel', ['panel' => 'saved', 'class' => 'panel-saved'])
