{{--
  Drawer دو-مرحله‌ای افزودن Section:
  گام ۱) انتخاب نوع Section
  گام ۲) انتخاب Layout (با Thumbnail) — بعد از انتخاب، Section به‌صورت پیش‌نویس ساخته و
         بلافاصله Drawer تنظیمات (edit-drawer) برای پرکردن جزئیات باز می‌شود.
--}}
<div class="drawer-overlay" id="hb-add-overlay" onclick="HomeBuilder.closeAddDrawer()"></div>
<div class="drawer-panel" id="hb-add-panel">

  <div class="drawer-section" style="position:sticky;top:0;background:var(--card-bg);z-index:5;display:flex;align-items:center;justify-content:space-between;">
    <div class="text-[14px] font-bold" style="color:var(--text-h);" id="hb-add-title">افزودن Section — انتخاب نوع</div>
    <button onclick="HomeBuilder.closeAddDrawer()" class="icon-action-btn"><i class="fa-solid fa-xmark"></i></button>
  </div>

  {{-- گام ۱: انتخاب نوع --}}
  <div class="drawer-section" id="hb-add-step-type">
    <div class="grid grid-cols-2 gap-2.5">
      @foreach($typeRegistry as $typeKey => $type)
        <button type="button" class="hb-type-card" onclick="HomeBuilder.selectType('{{ $typeKey }}')"
                style="text-align:right;display:flex;flex-direction:column;gap:6px;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg);cursor:pointer;">
          <div style="width:34px;height:34px;border-radius:9px;background:var(--card-bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--primary);">
            <i class="{{ $type['icon'] }}"></i>
          </div>
          <div class="text-[12.5px] font-bold" style="color:var(--text-h);">{{ $type['label'] }}</div>
          <div class="text-[10.5px]" style="color:var(--text-soft);line-height:1.6;">{{ $type['description'] }}</div>
        </button>
      @endforeach
    </div>
  </div>

  {{-- گام ۲: انتخاب Layout --}}
  <div class="drawer-section" id="hb-add-step-layout" style="display:none;">
    <button type="button" class="btn-pro btn-pro-ghost mb-3" onclick="HomeBuilder.backToTypeStep()">
      <i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت
    </button>
    <div class="grid grid-cols-2 gap-2.5" id="hb-layout-gallery"></div>
  </div>

</div>
