@php
  $collage = $product ? app(\App\Services\ProductShots\ShotPackService::class)->collage($product) : [];
  $previewUrl = $product?->exists ? route('admin.product-shots.products.preview-page',$product) : null;
@endphp
<div class="space-y-4">
  <section class="content-card p-5">
    <div class="flex items-start justify-between gap-3"><div><div class="ps-card-title"><i class="fa-solid fa-clipboard-check"></i> خلاصه نهایی</div><div class="ps-card-desc">پیش از ثبت، هویت، مدل‌ها، شات‌ها و قیمت‌ها را یکجا مرور کنید.</div></div><span class="badge-pro badge-neutral" data-final-status>نیازمند بررسی</span></div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-4"><div class="ps-summary-row"><span>نام محصول</span><strong data-summary="name">—</strong></div><div class="ps-summary-row"><span>اصناف</span><strong data-summary="occupations">—</strong></div><div class="ps-summary-row"><span>شات‌های فعال</span><strong data-summary="shots">—</strong></div><div class="ps-summary-row"><span>شات‌های بسته آماده</span><strong data-summary="defaults">—</strong></div><div class="ps-summary-row"><span>مدل سطح استاندارد</span><strong data-summary="model">—</strong></div><div class="ps-summary-row"><span>اعتبار بسته استاندارد</span><strong data-summary="credits">—</strong></div></div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-table-cells-large"></i> پیش‌نمایش کارت در قاب‌های مختلف</div><div class="ps-card-desc">کولاژ به‌صورت مرکزگرا و متناسب با قاب رندر می‌شود؛ قاب‌های غیرفعال کم‌رنگ خواهند بود.</div>
    <div class="ps-explore-preview mt-4" data-explore-preview>
      @foreach(['1x1'=>['مربع','1/1'],'2x2'=>['مربع بزرگ','1/1'],'1x2'=>['عمودی','1/2'],'2x1'=>['افقی','2/1']] as $key=>[$label,$ratio])
        <figure data-preview-frame="{{ $key }}"><div class="ps-preview-collage" style="aspect-ratio:{{ $ratio }}">@forelse($collage as $url)<img src="{{ $url }}" alt="">@empty<span><i class="fa-solid fa-images"></i><small>نمونه‌های شات بعد از ساخت پیش‌نمایش اینجا قرار می‌گیرند</small></span>@endforelse</div><figcaption>{{ $label }}</figcaption></figure>
      @endforeach
    </div>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-window-maximize"></i> صفحه‌ی واقعی محصول</div><div class="ps-card-desc">همان صفحه‌ای که کاربر برای بارگذاری تصاویر، انتخاب کیفیت و شات‌ها می‌بیند.</div>
    @if($previewUrl)<div class="mt-4 rounded-2xl overflow-hidden border border-[var(--border)]"><iframe src="{{ $previewUrl }}" title="پیش‌نمایش صفحه واقعی محصول" class="block w-full h-[780px] border-0 bg-[var(--page-bg)]" loading="lazy"></iframe></div>@else<div class="mt-4 p-8 rounded-xl border border-dashed border-[var(--border)] text-center text-xs text-[var(--text-soft)]"><i class="fa-solid fa-floppy-disk text-2xl block mb-3"></i>برای نمایش آدرس واقعی، ابتدا محصول را به‌صورت پیش‌نویس ذخیره کنید؛ خلاصه و قاب‌های بالا به‌صورت زنده به‌روز می‌شوند.</div>@endif
  </section>
</div>
