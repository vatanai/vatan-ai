{{--
  آپلودر مشترک «عکس محصول» — همان تجربه‌ی ثبت محصول چهره.
  منطق: public/admin/js/image-optimizer.js
  پارامترها: $inputId, $inputName, $title, $hint, $paths (مسیرهای storage فعلی), $required (bool)
--}}
@php
  $required = $required ?? false;
  $paths = array_values(array_filter((array) ($paths ?? [])));
@endphp
<div class="image-optimizer-group flex flex-col gap-2 mb-4" data-input="{{ $inputId }}" data-existing='@json(array_map(fn($p) => asset("storage/$p"), $paths))'>
  <label class="text-xs font-semibold text-[var(--text2)]">{{ $title }} @if($required)<span class="text-[var(--red)] mr-0.5">*</span>@endif</label>
  <div class="upload-zone border-2 border-dashed border-[var(--b2)] rounded-xl p-5 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors" onclick="document.getElementById('{{ $inputId }}').click()">
    <i class="fa-solid fa-images text-xl text-[var(--text3)] mb-1 block"></i>
    <div class="text-xs text-[var(--text2)] image-file-label">انتخاب تصاویر</div>
    <div class="text-[10px] text-[var(--text3)] mt-1">{{ $hint }}</div>
    <div class="flex flex-wrap gap-2 justify-center mt-3 image-preview-strip"></div>
    <input type="file" id="{{ $inputId }}" name="{{ $inputName }}" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @if($required && empty($paths)) required @endif>
  </div>

  <div class="image-compare-workspace hidden border border-[var(--b1)] rounded-2xl overflow-hidden bg-[var(--s1)]">
    <div class="grid grid-cols-2 gap-0 image-compare-pair">
      <button type="button" class="relative min-w-0 border-l border-[var(--b1)] bg-[var(--bg)] text-right" onclick="openImageCompareModal(this)">
        <div class="absolute top-2 right-2 z-[1] px-2 py-1 rounded-lg bg-[var(--s2)]/90 border border-[var(--b1)] text-[10px] text-[var(--text2)]"><i class="fa-solid fa-file-image ml-1"></i>نسخه اصلی</div>
        <div class="aspect-square flex items-center justify-center overflow-hidden"><img class="image-compare-original w-full h-full object-contain" alt="نسخه اصلی"></div>
        <div class="p-3 border-t border-[var(--b1)] bg-[var(--s2)]">
          <div class="image-original-specs flex items-center justify-center gap-3 overflow-x-auto whitespace-nowrap text-[10px] text-[var(--text3)]"></div>
        </div>
      </button>
      <button type="button" class="relative min-w-0 bg-[var(--bg)] text-right" onclick="openImageCompareModal(this)">
        <div class="absolute top-2 right-2 z-[1] inline-flex items-center gap-1.5 px-2 py-1 rounded-lg bg-[var(--s2)]/90 border border-[var(--b1)] text-[10px] text-[var(--text2)]">
          <i class="image-result-icon fa-solid fa-hourglass-half text-[var(--text3)]"></i><span>نسخه بهینه‌شده</span>
        </div>
        <div class="image-result-loading hidden absolute inset-0 z-[2] bg-[var(--s1)]/85 backdrop-blur-sm items-center justify-center flex-col gap-2 text-[11px] text-[var(--text2)]"><i class="fa-solid fa-spinner fa-spin text-xl text-[var(--accent)]"></i><span>در حال پردازش تصویر…</span></div>
        <div class="aspect-square flex items-center justify-center overflow-hidden"><img class="image-compare-optimized w-full h-full object-contain opacity-30" alt="نسخه بهینه‌شده"></div>
        <div class="p-3 border-t border-[var(--b1)] bg-[var(--s2)]">
          <div class="image-optimized-specs flex items-center justify-center gap-3 overflow-x-auto whitespace-nowrap text-[10px] text-[var(--text3)]"></div>
        </div>
      </button>
    </div>
    <div class="image-compare-thumbs flex gap-2 overflow-x-auto p-3 border-t border-[var(--b1)]"></div>
  </div>

  <div class="flex items-center gap-2 flex-wrap pt-1">
    <button type="button" class="image-optimize-btn btn-pro btn-pro-ghost" onclick="optimizeImageGroup(this)">
      <i class="fa-solid fa-wand-magic-sparkles"></i><span>بهینه‌سازی اتوماتیک</span>
    </button>
    <button type="button" class="btn-pro btn-pro-ghost" onclick="sharpenSelectedImage(this)">
      <i class="fa-solid fa-eye"></i><span>شارپ‌کردن عکس انتخاب‌شده</span>
    </button>
    <span class="image-optimize-status text-[10.5px] text-[var(--text3)]">@if($paths)برای بررسی تصاویر فعلی دکمه را بزنید.@endif</span>
  </div>

  <div class="image-target-panel hidden bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-3">
    <div class="flex items-start justify-between gap-3 mb-3">
      <div><div class="text-[11px] font-semibold text-[var(--text2)]"><i class="fa-solid fa-gauge-high text-[var(--accent)] ml-1.5"></i>انتخاب حجم تقریبی خروجی</div><div class="text-[10px] text-[var(--text3)] mt-1">سه خروجی با کیفیت بیشتر و سه خروجی سبک‌تر از پیشنهاد خودکار. مقدار نهایی ممکن است کمی متفاوت باشد.</div></div>
      <div class="shrink-0 text-left"><small class="block text-[9px] text-[var(--text3)]">پیشنهاد خودکار</small><strong class="image-auto-size text-xs text-[var(--green)]">—</strong></div>
    </div>
    <div class="flex items-center justify-between gap-3 mb-3"><span class="text-[10.5px] text-[var(--text2)]">انتخاب نسخه و حجم خروجی عکس انتخاب‌شده</span><button type="button" class="btn-pro btn-pro-ghost image-reoptimize-btn" onclick="optimizeImageGroup(this)"><i class="fa-solid fa-rotate"></i><span>بررسی مجدد</span></button></div>
    <div class="image-volume-options-grid grid grid-cols-2 md:grid-cols-3 xl:grid-cols-9 gap-2 mb-4">
      <button type="button" data-profile="original" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] rounded-xl p-3 text-right transition-colors" onclick="applyImageQuickPreset(this,'original')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i><span class="block text-[11px] text-[var(--text2)]"><i class="fa-solid fa-file-image text-[var(--accent)] ml-1.5"></i>نسخه اورجینال</span><strong class="image-original-size block text-xs text-[var(--text)] mt-1">—</strong></button>
      <button type="button" data-profile="site-standard" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] rounded-xl p-3 text-right transition-colors" onclick="applyImageQuickPreset(this,'site-standard')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i><span class="block text-[11px] text-[var(--text2)]"><i class="fa-solid fa-star text-[var(--accent)] ml-1.5"></i>استاندارد پیشنهادی</span><strong class="block text-xs text-[var(--text)] mt-1">حدود ۳۰۰ کیلوبایت</strong></button>
      <button type="button" data-profile="site-light" class="image-volume-choice relative border border-[var(--b1)] bg-[var(--s2)] rounded-xl p-3 text-right transition-colors" onclick="applyImageQuickPreset(this,'site-light')"><i class="image-choice-check fa-solid fa-circle-check absolute left-2 top-2 text-[var(--green)]" style="display:none"></i><span class="block text-[11px] text-[var(--text2)]"><i class="fa-solid fa-feather text-[var(--accent)] ml-1.5"></i>استاندارد سبک</span><strong class="block text-xs text-[var(--text)] mt-1">حدود ۱۸۰ کیلوبایت</strong></button>
      <div class="image-target-options contents"></div>
    </div>

    <div class="image-size-timeline border-y border-[var(--b1)] py-4 mb-4">
      <div class="flex items-center justify-between gap-3 mb-2"><span class="text-[10.5px] text-[var(--text2)]"><i class="fa-solid fa-chart-line text-[var(--accent)] ml-1.5"></i>انتخاب آزاد حجم روی نمودار</span><strong class="image-range-value text-xs text-[var(--green)]">—</strong></div>
      <input type="range" min="60" max="1200" step="10" value="300" class="image-size-range w-full accent-[var(--green)] cursor-pointer" oninput="previewImageRange(this)" onchange="applyImageRange(this)">
      <div class="flex justify-between text-[9px] text-[var(--text3)] mt-1"><span>سبک‌تر</span><span>حجم استاندارد سایت</span><span>کیفیت بیشتر</span></div>
    </div>

  </div>

  <div class="image-compare-modal hidden fixed inset-0 z-[130] bg-[var(--page-bg)]/95 backdrop-blur-sm p-4 md:p-8" onclick="if(event.target===this) closeImageCompareModal(this)">
    <div class="w-full h-full max-w-[1500px] mx-auto flex flex-col bg-[var(--s2)] border border-[var(--b1)] rounded-2xl overflow-hidden">
      <div class="h-14 shrink-0 px-4 flex items-center justify-between border-b border-[var(--b1)]"><div class="text-xs font-bold text-[var(--text)]"><i class="fa-solid fa-code-compare text-[var(--accent)] ml-2"></i>مقایسه بزرگ قبل و بعد</div><button type="button" class="w-9 h-9 rounded-lg border border-[var(--b1)] text-[var(--text2)]" onclick="closeImageCompareModal(this.closest('.image-compare-modal'))"><i class="fa-solid fa-xmark"></i></button></div>
      <div class="grid grid-cols-2 gap-0 flex-1 min-h-0">
        <div class="min-w-0 border-l border-[var(--b1)] flex flex-col"><div class="px-3 py-2 text-[11px] text-[var(--text2)] border-b border-[var(--b1)]">نسخه اصلی</div><div class="flex-1 min-h-0 p-2 flex items-center justify-center bg-[var(--bg)]"><img class="image-modal-original max-w-full max-h-full object-contain" alt="نسخه اصلی در اندازه بزرگ"></div></div>
        <div class="min-w-0 flex flex-col"><div class="px-3 py-2 text-[11px] text-[var(--text2)] border-b border-[var(--b1)]">نسخه بهینه‌شده</div><div class="flex-1 min-h-0 p-2 flex items-center justify-center bg-[var(--bg)]"><img class="image-modal-optimized max-w-full max-h-full object-contain" alt="نسخه بهینه‌شده در اندازه بزرگ"></div></div>
      </div>
    </div>
  </div>
</div>
