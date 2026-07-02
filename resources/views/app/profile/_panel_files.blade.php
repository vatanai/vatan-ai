{{-- ══════════════════════════════════════════════════════
     PANEL: فایل‌های تو — فضا + گرید
══════════════════════════════════════════════════════ --}}
<div class="profile-panel" data-panel="files" style="display:none; padding:16px;">

  {{-- نوار فضای ذخیره‌سازی --}}
  <div class="storage-card">
    <div class="storage-header">
      <span class="storage-title">فضای ذخیره‌سازی</span>
      <span class="storage-used">۴۰ از ۱۰۰ مگابایت</span>
    </div>
    <div class="storage-bar">
      <div class="storage-fill" style="width:40%;"></div>
    </div>
    <div class="storage-footer">
      <span>۴۰ مگ استفاده شده</span>
      <span class="storage-free">۶۰ مگ آزاد</span>
    </div>
  </div>

  {{-- زیرتب‌ها --}}
  <div class="files-sub-tabs" dir="rtl">
    <button type="button" class="files-sub-tab active" data-sub="created">خلق شده</button>
    <button type="button" class="files-sub-tab" data-sub="personal">عکس‌های شخصی</button>
  </div>

  {{-- گرید: خلق شده --}}
  <div id="files-created" class="files-grid">
    @foreach ([
      asset('assets/img/prompt-for-gemini-ai-girl.webp'),
      asset('assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg'),
      asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp'),
    ] as $img)
      <div class="files-cell">
        <img src="{{ $img }}" alt="" class="grid-img" loading="lazy">
      </div>
    @endforeach
  </div>

  {{-- گرید: عکس‌های شخصی --}}
  <div id="files-personal" class="files-grid" style="display:none;">
    @foreach ([
      asset('assets/img/lookaside.fbsbx.webp'),
      asset('assets/img/lookasidwee.fbsbx.webp'),
      asset('assets/img/lookasjide.fbsbx.webp'),
    ] as $img)
      <div class="files-cell">
        <img src="{{ $img }}" alt="" class="grid-img" loading="lazy">
      </div>
    @endforeach
  </div>

</div>
