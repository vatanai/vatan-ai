{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت مستقل: Skeleton Loading صفحه لیست محصولات
  ──────────────────────────────────────────────────────────────────
  فقط یک جلوه‌ی بصری (UI) است؛ هیچ داده یا Query واقعی ندارد و به هیچ
  بخشی از Backend وصل نیست. به‌صورت پیش‌فرض مخفی است (display:none) و
  توسط اسکریپت انتهای index.blade.php، هنگام ارسال فیلتر / کلیک روی
  چیپ سریع / صفحه‌بندی / دکمه‌ی بروزرسانی، به‌جای ناحیه‌ی زنده (
  #products-live-region) نمایش داده می‌شود تا بارگذاری صفحه از سرور
  حسِ سریع‌تری داشته باشد.
  ══════════════════════════════════════════════════════════════════
--}}
<div id="products-skeleton" style="display:none;">

  {{-- اسکلت کارت‌های آماری --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3.5 mb-5">
    @for ($i = 0; $i < 7; $i++)
      <div class="stat-card">
        <div class="sk-block" style="width:42px;height:42px;border-radius:11px;flex-shrink:0;"></div>
        <div class="min-w-0 flex-1">
          <div class="sk-block" style="width:55%;height:16px;border-radius:5px;margin-bottom:7px;"></div>
          <div class="sk-block" style="width:80%;height:10px;border-radius:5px;"></div>
        </div>
      </div>
    @endfor
  </div>

  {{-- اسکلت نوار جستجو + فیلترها --}}
  <div class="content-card p-3.5 mb-4">
    <div class="flex items-center gap-2.5 flex-wrap">
      <div class="sk-block" style="flex:1;min-width:240px;height:44px;border-radius:10px;"></div>
      <div class="sk-block" style="width:86px;height:36px;border-radius:10px;"></div>
      <div class="sk-block" style="width:130px;height:36px;border-radius:10px;"></div>
    </div>
    <div class="flex items-center gap-2 flex-wrap mt-3.5">
      @for ($i = 0; $i < 9; $i++)
        <div class="sk-block" style="width:64px;height:30px;border-radius:999px;"></div>
      @endfor
    </div>
  </div>

  {{-- اسکلت جدول --}}
  <div class="content-card overflow-hidden">
    <div style="padding:6px 14px;">
      @for ($i = 0; $i < 8; $i++)
        <div class="flex items-center gap-3" style="padding:13px 0; {{ $i < 7 ? 'border-bottom:1px solid var(--divider);' : '' }}">
          <div class="sk-block" style="width:52px;height:52px;border-radius:10px;flex-shrink:0;"></div>
          <div style="flex:1;min-width:0;">
            <div class="sk-block" style="width:35%;height:13px;border-radius:5px;margin-bottom:6px;"></div>
            <div class="sk-block" style="width:22%;height:9px;border-radius:5px;"></div>
          </div>
          <div class="sk-block" style="width:70px;height:22px;border-radius:7px;flex-shrink:0;" ></div>
          <div class="sk-block" style="width:56px;height:22px;border-radius:7px;flex-shrink:0;" ></div>
          <div class="sk-block" style="width:64px;height:22px;border-radius:7px;flex-shrink:0;" ></div>
        </div>
      @endfor
    </div>
  </div>

</div>
