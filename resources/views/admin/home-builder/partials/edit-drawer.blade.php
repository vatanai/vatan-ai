{{-- Drawer تنظیمات یک Section (هم برای Section تازه‌ساخته‌شده، هم برای ویرایش Sectionهای موجود) --}}
<div class="drawer-overlay" id="hb-edit-overlay" onclick="HomeBuilder.closeEditDrawer()"></div>
<div class="drawer-panel" id="hb-edit-panel">

  <div class="drawer-section" style="position:sticky;top:0;background:var(--card-bg);z-index:5;display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div class="text-[14px] font-bold" style="color:var(--text-h);" id="hb-edit-title">تنظیمات Section</div>
      <div class="text-[10.5px]" style="color:var(--text-soft);" id="hb-edit-subtitle">—</div>
    </div>
    <button onclick="HomeBuilder.closeEditDrawer()" class="icon-action-btn"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="drawer-section hb-live-preview-section">
    <div class="flex items-center justify-between gap-3 mb-3">
      <div>
        <div class="drawer-label mb-0">پیش‌نمایش زنده سایت</div>
        <div class="text-[10px] mt-1" style="color:var(--text-soft);">با داده‌ها و تصاویر واقعی محصولات فعال</div>
      </div>
      <div class="hb-preview-devices" role="group" aria-label="اندازه پیش‌نمایش">
        <button type="button" class="is-active" data-device="desktop" onclick="HomeBuilder.setPreviewDevice('desktop')" title="دسکتاپ"><i class="fa-solid fa-desktop"></i></button>
        <button type="button" data-device="tablet" onclick="HomeBuilder.setPreviewDevice('tablet')" title="تبلت"><i class="fa-solid fa-tablet-screen-button"></i></button>
        <button type="button" data-device="mobile" onclick="HomeBuilder.setPreviewDevice('mobile')" title="موبایل"><i class="fa-solid fa-mobile-screen"></i></button>
      </div>
    </div>
    <div class="hb-live-preview-frame is-desktop" id="hb-live-preview-frame">
      <div class="hb-preview-loading"><i class="fa-solid fa-spinner fa-spin"></i> در حال ساخت پیش‌نمایش واقعی…</div>
      <iframe id="hb-live-preview-iframe" title="پیش‌نمایش زنده سکشن" loading="eager"></iframe>
    </div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label mb-1">عنوان بخش</div>
    <input type="text" id="hb-f-title_fa" class="input-pro" placeholder="مثلاً ترندهای امروز">
  </div>

  <div class="drawer-section">
    <div class="drawer-label mb-1">زیرعنوان</div>
    <input type="text" id="hb-f-subtitle_fa" class="input-pro" placeholder="اختیاری">
  </div>

  <div class="drawer-section">
    <div class="drawer-label mb-2">Layout</div>
    <div class="grid grid-cols-1 gap-2" id="hb-edit-layout-gallery"></div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label mb-2">تنظیمات اختصاصی</div>
    <div class="flex flex-col gap-3" id="hb-edit-fields"></div>
  </div>

  <div class="drawer-section">
    <div class="drawer-label mb-2">نمایش بر اساس دستگاه</div>
    <div class="flex items-center gap-4 flex-wrap">
      <label class="flex items-center gap-1.5 text-[12px] cursor-pointer" style="color:var(--text-main);">
        <input type="checkbox" id="hb-f-resp-desktop" checked> <i class="fa-solid fa-desktop"></i> دسکتاپ
      </label>
      <label class="flex items-center gap-1.5 text-[12px] cursor-pointer" style="color:var(--text-main);">
        <input type="checkbox" id="hb-f-resp-tablet" checked> <i class="fa-solid fa-tablet-screen-button"></i> تبلت
      </label>
      <label class="flex items-center gap-1.5 text-[12px] cursor-pointer" style="color:var(--text-main);">
        <input type="checkbox" id="hb-f-resp-mobile" checked> <i class="fa-solid fa-mobile-screen"></i> موبایل
      </label>
    </div>
    <div class="mt-3">
      <div class="drawer-label mb-1">Layout اختصاصی موبایل (اختیاری)</div>
      <select id="hb-f-resp-mobile-layout" class="input-pro">
        <option value="">— همان Layout اصلی —</option>
      </select>
    </div>
  </div>

  <div class="drawer-section" style="position:sticky;bottom:0;background:var(--card-bg);display:flex;gap:8px;">
    <button type="button" class="btn-pro btn-pro-ghost" style="flex:1;justify-content:center;" onclick="HomeBuilder.saveSection('draft')">
      <i class="fa-solid fa-file-pen text-[11px]"></i> ذخیره پیش‌نویس
    </button>
    <button type="button" class="btn-pro btn-pro-primary" style="flex:1;justify-content:center;" onclick="HomeBuilder.saveSection('published')">
      <i class="fa-solid fa-circle-check text-[11px]"></i> ذخیره و انتشار
    </button>
  </div>

</div>
