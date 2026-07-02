{{-- ══════════════════════════════════════════════════════
     TAB BAR — RTL راست به چپ: ذخیره | همکاری | فایلها | محتوا
══════════════════════════════════════════════════════ --}}
<div class="profile-tabs" dir="rtl">

  <button type="button" class="profile-tab" data-tab="saved">
    <img src="{{ asset('assets/img/icons/fi-sr-bookmark.svg') }}"
         class="tab-icon" width="19" height="19" alt="">
    <span class="tab-label">ذخیره شده‌ها</span>
  </button>

  <button type="button" class="profile-tab" data-tab="referral">
    <svg class="tab-icon tab-icon--svg" width="19" height="19"
         viewBox="0 0 24 24" fill="currentColor">
      <path d="M16 11C17.66 11 18.99 9.66 18.99 8S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05C16.19 13.89 17 15.02 17 16.5V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
    </svg>
    <span class="tab-label">همکاری در فروش</span>
  </button>

  <button type="button" class="profile-tab" data-tab="files">
    <img src="{{ asset('assets/img/icons/fi-sr-file.svg') }}"
         class="tab-icon" width="19" height="19" alt="">
    <span class="tab-label">فایلهای تو</span>
  </button>

  <button type="button" class="profile-tab active" data-tab="grid">
    <img src="{{ asset('assets/img/icons/fi-sr-grid.svg') }}"
         class="tab-icon" width="19" height="19" alt="">
    <span class="tab-label">محتوا</span>
  </button>

</div>
