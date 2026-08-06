{{-- گالری مستقیم تمام مدل‌های واقعی؛ انتخاب هر مدل، Drawer تنظیمات همان سکشن را باز می‌کند. --}}
<div class="drawer-overlay" id="hb-add-overlay" onclick="HomeBuilder.closeAddDrawer()"></div>
<div class="drawer-panel" id="hb-add-panel">

  <div class="drawer-section" style="position:sticky;top:0;background:var(--card-bg);z-index:5;display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div class="text-[14px] font-bold" style="color:var(--text-h);" id="hb-add-title">افزودن سکشن — انتخاب مدل نمایشی</div>
      <div class="text-[10.5px] mt-1" style="color:var(--text-soft);">تمام مدل‌ها با ظاهر واقعی سایت و محتوای فعال نمایش داده می‌شوند.</div>
    </div>
    <button onclick="HomeBuilder.closeAddDrawer()" class="icon-action-btn"><i class="fa-solid fa-xmark"></i></button>
  </div>

  <div class="drawer-section hb-all-layouts-gallery" id="hb-layout-gallery">
    @foreach($typeRegistry as $typeKey => $type)
      <section class="hb-layout-group" data-layout-type="{{ $typeKey }}">
        <div class="hb-layout-group-head">
          <span class="hb-layout-group-icon"><i class="{{ $type['icon'] }}"></i></span>
          <div>
            <h3>{{ $type['label'] }}</h3>
            <p>{{ $type['description'] }}</p>
          </div>
        </div>
        <div class="grid grid-cols-1 gap-2.5">
          @foreach($type['layouts'] as $layoutKey => $layout)
            <div class="hb-layout-card" role="button" tabindex="0"
                 onclick="HomeBuilder.selectLayout('{{ $typeKey }}','{{ $layoutKey }}')"
                 onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();HomeBuilder.selectLayout('{{ $typeKey }}','{{ $layoutKey }}')}"
                 style="text-align:center;padding:8px;border:1px solid var(--border);border-radius:12px;background:var(--input-bg);cursor:pointer;">
              <div class="hb-layout-preview" id="hb-add-preview-{{ $typeKey }}-{{ $layoutKey }}" data-preview-type="{{ $typeKey }}" data-preview-layout="{{ $layoutKey }}">
                <div class="hb-preview-loading"><i class="fa-solid fa-spinner fa-spin"></i> پیش‌نمایش واقعی</div>
                <iframe title="پیش‌نمایش {{ $layout['label'] }}" tabindex="-1"></iframe>
              </div>
              <div class="text-[11.5px] font-bold" style="color:var(--text-h);">{{ $layout['label'] }}</div>
            </div>
          @endforeach
        </div>
      </section>
    @endforeach
  </div>

</div>
