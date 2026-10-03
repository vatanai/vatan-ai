@php
  $exploreTiles = (array)old('explore_tiles',$product?->explore_tiles ?? ['1x1']);
  $frameDefs = ['1x1'=>['مربع','1/1'],'2x2'=>['مربع بزرگ','1/1'],'1x2'=>['عمودی','1/2'],'2x1'=>['افقی','2/1']];
@endphp
<div class="space-y-4">
  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-bolt"></i> مصرف اعتبار سه‌سطحی</div><div class="ps-card-desc">اعتبار نهایی هر خروجی در خود شات تعیین شده است. جمع کاربر برابر مجموع شات‌های انتخابی در سطح کیفیت انتخاب‌شده خواهد بود.</div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">@foreach($qualityLevels as $qualityKey=>$qualityLabel)<div class="ps-quality-total" data-quality-total="{{ $qualityKey }}"><small>{{ $qualityLabel }}</small><strong>۰ اعتبار</strong><span>مجموع شات‌های بسته آماده</span></div>@endforeach</div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-file-export"></i> تنظیمات خروجی و نمایش</div><div class="ps-card-desc">تنظیمات عمومی صفحه و خروجی؛ ساخت اسلایدی پک در صفحه‌ی کاربر به‌صورت مستقل مدیریت می‌شود.</div>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-4">
      <div class="space-y-3">
        <label class="ps-setting-row"><span><strong>واترمارک</strong><small>اعمال نشان وطن روی خروجی‌های این محصول</small></span><span class="ps-toggle"><input type="hidden" name="watermark_enabled" value="0"><input type="checkbox" name="watermark_enabled" value="1" @checked((bool)old('watermark_enabled',$product?->watermark_enabled ?? false))><span></span></span></label>
        <div class="ps-field"><label>موقعیت واترمارک</label><select name="watermark_position" class="input-pro"><option value="corner" @selected(old('watermark_position',$product?->watermark_position ?? 'corner')==='corner')>گوشه تصویر</option><option value="center" @selected(old('watermark_position',$product?->watermark_position)==='center')>مرکز تصویر</option><option value="none" @selected(old('watermark_position',$product?->watermark_position)==='none')>بدون واترمارک</option></select></div>
        <div class="ps-field"><label>حالت نمایش محصول</label><select name="display_mode" class="input-pro"><option value="card" @selected(old('display_mode',$product?->display_mode ?? 'card')==='card')>کارت پک</option><option value="slider" @selected(old('display_mode',$product?->display_mode)==='slider')>اسلایدر</option></select></div>
      </div>
      <div class="space-y-3">
        <div class="ps-field"><label>شکل پیش‌فرض کارت</label><select name="card_shape" class="input-pro"><option value="portrait" @selected(old('card_shape',$product?->card_shape ?? 'portrait')==='portrait')>عمودی</option><option value="square" @selected(old('card_shape',$product?->card_shape)==='square')>مربع</option><option value="landscape" @selected(old('card_shape',$product?->card_shape)==='landscape')>افقی</option></select></div>
        <div class="ps-field"><label>چیدمان نمونه‌ها</label><select name="gallery_layout" class="input-pro"><option value="slider" @selected(old('gallery_layout',$product?->gallery_layout ?? 'slider')==='slider')>اسلایدی</option><option value="grid" @selected(old('gallery_layout',$product?->gallery_layout)==='grid')>شبکه‌ای</option><option value="masonry" @selected(old('gallery_layout',$product?->gallery_layout)==='masonry')>آبشاری</option></select></div>
        <label class="ps-setting-row"><span><strong>نشان روی کارت</strong><small>مثلاً «پک» یا «جدید»</small></span><span class="ps-toggle"><input type="hidden" name="card_label_enabled" value="0"><input type="checkbox" name="card_label_enabled" value="1" @checked((bool)old('card_label_enabled',$product?->card_label_enabled ?? true))><span></span></span></label>
        <div class="ps-field"><label>متن نشان</label><input name="card_label" class="input-pro" value="{{ old('card_label',$product?->card_label ?? 'پک') }}"></div>
      </div>
    </div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-table-cells"></i> قاب‌های مجاز در اکسپلور</div><div class="ps-card-desc">کولاژ چهار شات باید در هر قاب فعال، بدون برش نامناسب محصول و با تمرکز بصری درست نمایش داده شود.</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">@foreach($frameDefs as $key=>[$label,$ratio])<label class="ps-frame-option"><span class="ps-frame-demo" style="aspect-ratio:{{ $ratio }}"><i class="fa-solid fa-images"></i></span><span>{{ $label }}</span><input type="checkbox" name="explore_tiles[]" value="{{ $key }}" @checked(in_array($key,$exploreTiles,true))></label>@endforeach</div>
  </section>

  <section class="content-card p-5"><div class="ps-card-title"><i class="fa-solid fa-rocket"></i> وضعیت انتشار</div><div class="ps-card-desc">دکمه «ذخیره پیش‌نویس» محصول را مخفی نگه می‌دارد؛ «ثبت نهایی محصول» آن را منتشر می‌کند.</div><div class="mt-4 flex items-center gap-2 text-xs text-[var(--text-main)]"><span class="w-2 h-2 rounded-full bg-[var(--warning)]"></span><span data-publish-status>{{ old('status',$product?->status ?? 'draft') === 'active' ? 'محصول منتشر می‌شود' : 'محصول به‌صورت پیش‌نویس ذخیره می‌شود' }}</span></div></section>
</div>
