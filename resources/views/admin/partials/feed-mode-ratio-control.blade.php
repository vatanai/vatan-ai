@php($configuredProductRatio = max(0, min(100, (int) ($feedSetting->product_ratio ?? 65))))
<section class="rounded-xl border p-4 mb-4" style="border-color:var(--border);background:var(--input-bg);" aria-label="تنظیم سهم نوع محصولات">
  <div class="flex items-start justify-between gap-3 flex-wrap mb-3">
    <div>
      <div class="text-[12px] font-extrabold" style="color:var(--text-h);"><i class="fa-solid fa-sliders ml-1" style="color:var(--primary);"></i>سهم نمایش نوع محصولات</div>
      <div class="text-[10.5px] mt-1" style="color:var(--text-soft);">این تنظیم فقط نسبت پروداکتی و پرتره‌ای را عوض می‌کند؛ چیدمان کارت‌ها بدون تغییر می‌ماند.</div>
    </div>
    <strong class="text-[11px] rounded-lg px-3 py-2" style="background:var(--card-bg);color:var(--text-h);" data-mode-ratio-label>{{ $configuredProductRatio }}٪ پروداکتی · {{ 100 - $configuredProductRatio }}٪ پرتره‌ای</strong>
  </div>
  <div class="grid grid-cols-[1fr_minmax(180px,3fr)_1fr] items-center gap-3" dir="ltr">
    <span class="text-[10px] font-bold text-left" style="color:var(--text-main);" dir="rtl">پروداکتی <b data-product-share>{{ $configuredProductRatio }}٪</b></span>
    <input type="range" min="0" max="100" step="1" value="{{ 100 - $configuredProductRatio }}" class="w-full cursor-pointer" style="accent-color:var(--primary);" data-mode-ratio-slider aria-label="درصد نمایش محصولات پروداکتی">
    <span class="text-[10px] font-bold text-right" style="color:var(--text-main);" dir="rtl">پرتره‌ای <b data-portrait-share>{{ 100 - $configuredProductRatio }}٪</b></span>
  </div>
  <input type="hidden" name="product_ratio" value="{{ $configuredProductRatio }}" data-mode-ratio-value>
</section>
<script>
  (function () {
    var control = document.currentScript.previousElementSibling;
    if (!control) return;
    var slider = control.querySelector('[data-mode-ratio-slider]');
    var value = control.querySelector('[data-mode-ratio-value]');
    var label = control.querySelector('[data-mode-ratio-label]');
    var product = control.querySelector('[data-product-share]');
    var portrait = control.querySelector('[data-portrait-share]');
    var update = function () {
      var portraitRatio = Math.max(0, Math.min(100, Number(slider.value) || 0));
      var productRatio = 100 - portraitRatio;
      value.value = productRatio;
      product.textContent = productRatio + '٪';
      portrait.textContent = portraitRatio + '٪';
      label.textContent = productRatio + '٪ پروداکتی · ' + portraitRatio + '٪ پرتره‌ای';
    };
    slider.addEventListener('input', update);
    update();
  })();
</script>
