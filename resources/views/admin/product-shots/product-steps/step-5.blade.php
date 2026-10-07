@php
  $collage = $product ? app(\App\Services\ProductShots\ShotPackService::class)->collage($product) : [];
  $previewUrl = $product?->exists
    ? route('admin.product-shots.products.preview-page', $product)
    : route('admin.product-shots.products.preview-draft');
  $currentTiles = old('explore_tiles', $product?->explore_tiles ?? ['1x1']);
  if (!is_array($currentTiles) || !$currentTiles) $currentTiles = ['1x1'];
  $tileDefinitions = [
    '1x1' => ['۱ × ۱ (مربع)', '1 / 1'],
    '2x2' => ['۲ × ۲ (بزرگ)', '1 / 1'],
    '1x2' => ['۱ × ۲ (عمودی)', '1 / 2'],
    '2x1' => ['۲ × ۱ (افقی)', '2 / 1'],
  ];
@endphp

<div class="space-y-5">
  <section class="content-card p-5">
    <div class="mb-4 pb-3 border-b border-[var(--border)]">
      <div class="ps-card-title"><i class="fa-solid fa-table-cells-large"></i> نحوه نمایش در هوم و اکسپلور</div>
      <div class="ps-card-desc !mb-0">قاب‌هایی که در گام چهارم فعال کرده‌اید با نمونه‌ی اسلایدهای همین محصول نمایش داده می‌شوند.</div>
    </div>
    <div class="ps-explore-preview" data-explore-preview>
      @foreach($tileDefinitions as $key => [$label, $ratio])
        <label class="ps-frame-option {{ in_array($key, $currentTiles, true) ? '' : 'is-disabled' }}" data-preview-frame="{{ $key }}">
          <div class="ps-preview-collage" style="aspect-ratio:{{ $ratio }}" data-preview-collage>
            @forelse($collage as $url)
              <img src="{{ $url }}" alt="">
            @empty
              <span data-preview-empty><i class="fa-solid fa-images"></i><small>تصاویر نمونه‌ی اسلایدها اینجا دیده می‌شوند</small></span>
            @endforelse
          </div>
          <span class="flex items-center justify-between gap-2"><span>{{ $label }}</span><input type="checkbox" name="explore_tiles[]" value="{{ $key }}" @checked(in_array($key, $currentTiles, true))></span>
        </label>
      @endforeach
    </div>
    <div class="hidden mt-3 text-[10.5px] text-[var(--danger)]" data-explore-warning><i class="fa-solid fa-triangle-exclamation"></i> حداقل یک حالت نمایش باید روشن بماند.</div>
  </section>

  <section class="content-card p-5">
    <div class="flex items-start justify-between gap-3 mb-4 pb-3 border-b border-[var(--border)]">
      <div><div class="ps-card-title"><i class="fa-solid fa-clipboard-check"></i> خلاصه نهایی</div><div class="ps-card-desc !mb-0">پیش از ثبت، اطلاعات محصول پروداکتی را یکجا مرور کنید.</div></div>
      <span class="badge-pro badge-neutral" data-final-status>نیازمند تکمیل</span>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
      <div class="ps-summary-row"><span>نام محصول</span><strong data-summary="name">—</strong></div>
      <div class="ps-summary-row"><span>دسته‌بندی</span><strong data-summary="categories">—</strong></div>
      <div class="ps-summary-row"><span>اصناف</span><strong data-summary="occupations">—</strong></div>
      <div class="ps-summary-row"><span>مدل هوش مصنوعی</span><strong data-summary="model">—</strong></div>
      <div class="ps-summary-row"><span>اعتبار استاندارد</span><strong data-summary="credits">—</strong></div>
      <div class="ps-summary-row"><span>نوع خروجی</span><strong>عکس</strong></div>
      <div class="ps-summary-row"><span>اسلایدهای فعال</span><strong data-summary="shots">—</strong></div>
      <div class="ps-summary-row"><span>اسلایدهای منتخب اولیه</span><strong data-summary="defaults">—</strong></div>
      <div class="ps-summary-row"><span>وضعیت ثبت</span><strong data-summary="publish-status">{{ old('status', $product?->status ?? 'draft') === 'active' ? 'ثبت نهایی' : 'پیش‌نویس' }}</strong></div>
    </div>
    <button type="button" class="btn-pro btn-pro-ghost mt-4" data-scroll-product-preview><i class="fa-solid fa-eye"></i> پیش‌نمایش محصول</button>
  </section>

  <section class="content-card p-5">
    <div class="ps-card-title"><i class="fa-solid fa-barcode"></i> کد محصول</div>
    <div class="ps-product-code mt-3"><strong dir="ltr" data-product-code>{{ $product?->product_code ?: '--------' }}</strong><span>{{ $product?->product_code ? 'کد یکتای همین محصول' : 'هنگام اولین ذخیره، خودکار ساخته می‌شود' }}</span></div>
  </section>

  <section class="content-card p-5" id="product-shot-live-preview">
    <div class="flex items-center justify-between gap-3 flex-wrap mb-4">
      <div><div class="ps-card-title"><i class="fa-solid fa-window-maximize"></i> صفحه واقعی محصول</div><div class="ps-card-desc !mb-0">همان صفحه‌ای که کاربر برای واردکردن سه زاویه از یک محصول، انتخاب اسلاید و کیفیت می‌بیند.</div></div>
      <button type="button" class="btn-pro btn-pro-ghost" data-open-product-preview><i class="fa-solid fa-arrow-up-right-from-square"></i> باز کردن در صفحه جدید</button>
    </div>
    <div class="rounded-2xl overflow-hidden border border-[var(--border)] bg-[var(--page-bg)]">
      <iframe src="{{ $previewUrl }}" title="پیش‌نمایش صفحه واقعی محصول" class="block w-full h-[780px] border-0 bg-[var(--page-bg)]" loading="lazy" data-product-preview-frame></iframe>
    </div>
  </section>
</div>
