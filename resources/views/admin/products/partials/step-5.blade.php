{{-- پارشیال: گام پنجم — بازبینی نهایی --}}
{{-- جدا شده از step-4.blade.php هنگام تبدیل ویزارد ۳ مرحله‌ای به ۵ مرحله‌ای (طبق درخواست کاربر).
     یک Step مستقل و اختصاصی برای مرور نهایی قبل از ثبت — الگوی رایج در ویزاردهای SaaS مدرن
     (مثل مراحل پایانی Checkout در Stripe). هیچ فیلد جدیدی اینجا نیست، فقط خواندن مقادیر فرم. --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';

  // آیکون «راهنمایی آیتم» — فقط برای فیلدهای واقعاً وصل‌شده به Backend (متن کامل از config/product_field_help.php خوانده می‌شود)
  // نکته مهم: عمداً <span role="button"> است نه <button> واقعی — چون این آیکون گاهی داخل عناصر <label>
  // (از جمله لیبل خودِ سوییچ روشن/خاموش) قرار می‌گیرد؛ <button> چون خودش هم «Labelable» است ممکن بود مرورگر
  // آن را به‌جای چک‌باکس واقعی «کنترل صاحب لیبل» در نظر بگیرد و با کلیک روی خودِ سوییچ (نه آیکون)، به‌جای
  // تغییر وضعیت چک‌باکس، پنجره راهنما باز شود. با <span> این تداخل کاملاً از بین می‌رود.
  $__help = function (string $key, string $title) {
      $text = config('product_field_help.' . $key, '');
      if ($text === '') return '';
      return '<span class="field-help-btn inline-flex items-center justify-center shrink-0 cursor-pointer text-[var(--text3)] hover:text-[var(--accent)] transition-colors" role="button" tabindex="0" data-help-title="' . e($title) . '" data-help-text="' . e($text) . '" aria-label="راهنمایی آیتم"><i class="fa-solid fa-circle-question text-[10px]"></i></span>';
  };
@endphp

{{-- ═══════════════════ نحوه نمایش در اکسپلور و اپ ═══════════════════ --}}
@php
  $curTiles = old('explore_tiles', optional($duplicateFrom)->explore_tiles ?? ['1x1','2x2','1x2','2x1']);
  if (!is_array($curTiles) || empty($curTiles)) $curTiles = ['1x1','2x2','1x2','2x1'];
  $tileDefs = [
    '1x1' => ['۱ × ۱ (مربع)', '1 / 1'],
    '2x2' => ['۲ × ۲ (بزرگ)', '1 / 1'],
    '1x2' => ['۱ × ۲ (عمودی)', '1 / 2'],
    '2x1' => ['۲ × ۱ (افقی)', '2 / 1'],
  ];
@endphp
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-table-cells text-[var(--accent)]"></i> نحوه نمایش در اکسپلور و اپ {!! $__help('explore_tiles', 'نحوه نمایش در اکسپلور و اپ') !!}</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">این محصول در کدام قالب‌های کاشی نمایش داده شود؟ حداقل یکی باید روشن باشد. اگر حالتی خاموش شود، محصول دیگر در آن قالب در اکسپلور دیده نمی‌شود. کاور محصول در هر قاب پیش‌نمایش می‌شود تا تناسبش را ببینید.</div>
  </div>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="explore-tiles-grid">
    @foreach($tileDefs as $mode => $def)
      <label class="explore-tile-card relative flex flex-col gap-2 p-2.5 bg-[var(--s1)] border rounded-xl cursor-pointer transition-all {{ in_array($mode, $curTiles) ? 'border-[var(--accent)]' : 'border-[var(--b1)]' }}">
        <div class="mx-auto rounded-lg overflow-hidden bg-[var(--bg)] border border-[var(--b1)] flex items-center justify-center" style="height:7rem;aspect-ratio:{{ $def[1] }};max-width:100%">
          <img class="explore-tile-cover w-full h-full object-cover hidden" alt="پیش‌نمایش کاور">
          <i class="explore-tile-placeholder fa-solid fa-image text-[var(--text3)]"></i>
        </div>
        <div class="flex items-center justify-between gap-1">
          <span class="text-[11px] font-semibold text-[var(--text2)]">{{ $def[0] }}</span>
          <span class="relative w-8 h-[18px] shrink-0 block">
            <input type="checkbox" name="explore_tiles[]" value="{{ $mode }}" class="sr-only peer explore-tile-checkbox" {{ in_array($mode, $curTiles) ? 'checked' : '' }} onchange="onExploreTileToggle(this)">
            <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3 before:h-3 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[14px] peer-checked:before:bg-white"></span>
          </span>
        </div>
      </label>
    @endforeach
  </div>
  <div id="explore-tiles-warn" class="hidden text-[10.5px] text-[var(--red)] mt-2"><i class="fa-solid fa-triangle-exclamation"></i> حداقل یک حالت نمایش باید روشن بماند.</div>

  <script>
    function onExploreTileToggle(cb) {
      var checks = document.querySelectorAll('.explore-tile-checkbox');
      var onCount = Array.prototype.filter.call(checks, function(c){ return c.checked; }).length;
      if (onCount === 0) {
        cb.checked = true;
        var w = document.getElementById('explore-tiles-warn');
        if (w) { w.classList.remove('hidden'); setTimeout(function(){ w.classList.add('hidden'); }, 2500); }
      }
      var card = cb.closest('.explore-tile-card');
      if (card) {
        card.classList.toggle('border-[var(--accent)]', cb.checked);
        card.classList.toggle('border-[var(--b1)]', !cb.checked);
      }
    }
    function applyExploreCover(src) {
      document.querySelectorAll('.explore-tile-cover').forEach(function(im){ im.src = src; im.classList.remove('hidden'); });
      document.querySelectorAll('.explore-tile-placeholder').forEach(function(p){ p.classList.add('hidden'); });
    }
    function updateExploreTileCovers() {
      var cover = document.getElementById('cover-file');
      var thumb = document.getElementById('thumbnail-file');
      var chosen = (cover && cover.files && cover.files[0]) ? cover.files[0]
                 : (thumb && thumb.files && thumb.files[0]) ? thumb.files[0] : null;
      if (!chosen) return;
      var r = new FileReader();
      r.onload = function(e){ applyExploreCover(e.target.result); };
      r.readAsDataURL(chosen);
    }
    document.addEventListener('DOMContentLoaded', function(){
      var cover = document.getElementById('cover-file');
      var thumb = document.getElementById('thumbnail-file');
      if (cover) cover.addEventListener('change', updateExploreTileCovers);
      if (thumb) thumb.addEventListener('change', updateExploreTileCovers);
      updateExploreTileCovers();
    });
  </script>
</div>

<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-clipboard-check text-[var(--accent)]"></i> خلاصه نهایی</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">پیش از ثبت، اطلاعات محصول را مرور کنید</div>
    </div>
    <span id="summary-status-badge" class="text-[10.5px] font-bold rounded-full px-2.5 py-1 bg-[var(--orange)]/15 text-[var(--orange)] border border-[var(--orange)]/30">Incomplete</span>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5" id="final-summary-grid">
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">نام محصول</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-name">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">دسته</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-category">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">مدل AI</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-model">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">قیمت</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-price">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">نوع خروجی</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-media">—</span>
    </div>
    <div class="flex items-center justify-between bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5">
      <span class="text-[11px] text-[var(--text3)]">وضعیت</span>
      <span class="text-xs text-[var(--text)] font-semibold" id="sum-status">پیش‌نویس</span>
    </div>
  </div>

  <div class="text-[10.5px] text-[var(--text3)] mt-3 leading-relaxed">
    اگر موردی نیاز به اصلاح دارد، از Stepper بالا به همان مرحله برگردید — بازگشت همیشه آزاد است و اطلاعات از دست نمی‌رود.
  </div>

  <button type="button" class="inline-flex items-center gap-2 px-4 h-9 rounded-lg text-xs font-bold bg-[var(--text)]/5 text-[var(--text2)] hover:text-[var(--text)] transition-all mt-4" onclick="alert('پیش‌نمایش محصول — فقط UI، در فاز بعد به صفحه واقعی محصول متصل می‌شود.')">
    <i class="fa-solid fa-eye"></i> NEW پیش‌نمایش محصول {!! $newBadge !!}
  </button>
</div>

{{-- ═══════════════════ کد محصول ۸ رقمی خودکار (NEW / فقط UI — بند ۵۱) ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mt-5">
  <div class="mb-3 flex items-center justify-between flex-wrap gap-2">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-barcode text-[var(--accent)]"></i> کد محصول {!! $newBadge !!}</div>
  </div>
  <div class="flex items-center gap-3 flex-wrap bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-3">
    <span class="text-lg font-bold font-mono tracking-widest text-[var(--accent)]" id="product-code-display" dir="ltr">--------</span>
    <span class="text-[10.5px] text-[var(--text3)]">خودکار ساخته می‌شود</span>
  </div>
</div>

{{-- ═══════════════════ پیش‌نمایش دقیق صفحه محصول در سایت (NEW / فقط UI — بند ۵۰) ═══════════════════
     توجه: رنگ‌های داخل این باکس عمداً hex مستقیم‌اند چون صفحه‌ی دیگری (سایت اصلی، نه پنل ادمین) را
     شبیه‌سازی می‌کنند — پس‌زمینه نزدیک به مشکی و رنگ اصلی Indigo، مطابق resources/views/app/product.blade.php. --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mt-5">
  <div class="mb-3 flex items-center justify-between flex-wrap gap-2">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-mobile-screen text-[var(--accent)]"></i> پیش‌نمایش صفحه محصول در سایت {!! $newBadge !!}</div>
    <span class="text-[10.5px] text-[var(--text3)]">با مقادیر واقعی فرم به‌روزرسانی می‌شود</span>
  </div>

  <div class="mx-auto max-w-sm rounded-2xl overflow-hidden border" style="background:#0a0a0c;border-color:#1c1c22;" dir="rtl">
    <div class="w-full aspect-video flex items-center justify-center overflow-hidden" style="background:#121214;">
      <img id="pp-thumb" class="w-full h-full object-cover hidden" alt="">
      <i class="fa-solid fa-image text-3xl" style="color:#3a3a44;" id="pp-thumb-ph"></i>
    </div>
    <div class="p-4">
      <div class="text-[10px] font-semibold mb-1" style="color:#8b8bf5;" id="pp-category">دسته‌بندی</div>
      <div class="text-base font-extrabold mb-1.5" style="color:#f4f4f6;" id="pp-title">نام محصول</div>
      <div class="text-[11px] leading-relaxed mb-3 line-clamp-3" style="color:#a1a1ab;" id="pp-desc">توضیحات محصول اینجا نمایش داده می‌شود…</div>
      <div class="flex items-center justify-between gap-2 flex-wrap">
        <span class="text-xs font-bold" style="color:#c7c7ff;" id="pp-price">رایگان</span>
        <span class="inline-flex items-center gap-2 px-4 h-9 rounded-xl text-xs font-bold" style="background:#4f46e5;color:#ffffff;">
          <i class="fa-solid fa-wand-magic-sparkles"></i> ورود به کارگاه ساخت تصویر
        </span>
      </div>
      <div class="grid grid-cols-4 gap-1.5 mt-3 hidden" id="pp-samples"></div>
    </div>
  </div>
</div>

<script>
/* ══════ خلاصه نهایی زنده (فقط خواندن مقادیر واقعی فرم از تمام مراحل، بدون فیلد جدید) ══════ */
function refreshFinalSummary() {
  const nameFa = document.querySelector('[name="name_fa"]')?.value.trim();
  const modelSel = document.getElementById('primary-model-select');
  const priceSel = document.querySelector('[name="pricing_model"]:checked');
  const mediaSel = document.querySelector('[name="media_type"]:checked');
  const statusInput = document.getElementById('product-status');

  // دسته‌بندی چندگانه (تگ‌های چیپ گام اول) — به‌جای سلکت تک‌انتخابی قبلی
  const catText = (typeof getSelectedCategoryNames === 'function' && getSelectedCategoryNames()) || null;
  const modelText = modelSel && modelSel.value ? modelSel.options[modelSel.selectedIndex].textContent : null;
  const priceText = priceSel ? ({free:'رایگان', per_credit:'کردیتی', subscription:'اشتراکی'}[priceSel.value]) : null;
  const mediaText = mediaSel ? ({photo:'عکس', video:'ویدیو', both:'هر دو'}[mediaSel.value]) : null;

  const fields = { 'sum-name': nameFa, 'sum-category': catText, 'sum-model': modelText, 'sum-price': priceText, 'sum-media': mediaText };
  let complete = true;
  Object.keys(fields).forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    if (fields[id]) { el.textContent = fields[id]; el.classList.remove('text-[var(--text3)]'); }
    else { el.textContent = 'تکمیل‌نشده'; el.classList.add('text-[var(--text3)]'); complete = false; }
  });

  const statusEl = document.getElementById('sum-status');
  if (statusEl && statusInput) statusEl.textContent = statusInput.value === 'active' ? 'ثبت نهایی' : 'پیش‌نویس';

  const badge = document.getElementById('summary-status-badge');
  if (complete) {
    badge.textContent = 'Ready';
    badge.className = 'text-[10.5px] font-bold rounded-full px-2.5 py-1 bg-[var(--green)]/15 text-[var(--green)] border border-[var(--green)]/30';
  } else {
    badge.textContent = 'Incomplete';
    badge.className = 'text-[10.5px] font-bold rounded-full px-2.5 py-1 bg-[var(--orange)]/15 text-[var(--orange)] border border-[var(--orange)]/30';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  refreshFinalSummary();
  // به‌روزرسانی زنده خلاصه با تغییر فیلدهای کلیدی در سایر مراحل
  // (تغییر دسته‌بندی چندگانه مستقیماً از renderCatChips در step-1.blade.php این تابع را صدا می‌زند)
  document.querySelector('[name="name_fa"]')?.addEventListener('input', refreshFinalSummary);
  document.getElementById('primary-model-select')?.addEventListener('change', refreshFinalSummary);
  document.querySelectorAll('[name="media_type"]').forEach(r => r.addEventListener('change', refreshFinalSummary));
  document.querySelectorAll('[name="pricing_model"]').forEach(r => r.addEventListener('change', refreshFinalSummary));
});
</script>

<script>
/* ══════ کد محصول ۸ رقمی (بند ۵۱) — فقط UI، یک‌بار در بارگذاری ساخته می‌شود تا حین تایپ عوض نشود ══════ */
function generateProductCode() {
  var el = document.getElementById('product-code-display');
  if (!el) return;
  var code = '';
  for (var k = 0; k < 8; k++) code += Math.floor(Math.random() * 10);
  el.textContent = code;
}

/* ══════ پیش‌نمایش زنده‌ی صفحه محصول در سایت (بند ۵۰) — فقط خواندن مقادیر واقعی فرم ══════ */
function refreshProductPreview() {
  var nameEl = document.querySelector('[name="name_fa"]');
  var descEl = document.querySelector('[name="description_fa"]');
  var priceSel = document.querySelector('[name="pricing_model"]:checked');
  var creditEl = document.querySelector('[name="credit_cost"]');

  var title = document.getElementById('pp-title');
  var cat = document.getElementById('pp-category');
  var desc = document.getElementById('pp-desc');
  var price = document.getElementById('pp-price');

  // دسته‌بندی چندگانه (تگ‌های چیپ گام اول) — به‌جای سلکت تک‌انتخابی قبلی
  var catNames = (typeof getSelectedCategoryNames === 'function') ? getSelectedCategoryNames() : '';

  if (title) title.textContent = (nameEl && nameEl.value.trim()) ? nameEl.value.trim() : 'نام محصول';
  if (cat)   cat.textContent   = catNames || 'دسته‌بندی';
  if (desc)  desc.textContent  = (descEl && descEl.value.trim()) ? descEl.value.trim() : 'توضیحات محصول اینجا نمایش داده می‌شود…';

  if (price) {
    var pv = priceSel ? priceSel.value : 'free';
    if (pv === 'free') price.textContent = 'رایگان';
    else if (pv === 'subscription') price.textContent = 'اشتراکی';
    else {
      var c = (creditEl && creditEl.value) ? creditEl.value : '۰';
      price.textContent = c + ' کردیت';
    }
  }
}

/* پیش‌نمایش کاور/تصویر و نمونه خروجی‌ها از روی فایل‌های واقعی آپلودی */
function refreshProductPreviewMedia() {
  var thumbInput = document.getElementById('thumbnail-file');
  var img = document.getElementById('pp-thumb');
  var ph = document.getElementById('pp-thumb-ph');
  if (thumbInput && thumbInput.files && thumbInput.files[0] && img) {
    var r = new FileReader();
    r.onload = function (e) { img.src = e.target.result; img.classList.remove('hidden'); if (ph) ph.classList.add('hidden'); };
    r.readAsDataURL(thumbInput.files[0]);
  }
  var samplesInput = document.getElementById('samples-file');
  var grid = document.getElementById('pp-samples');
  if (samplesInput && grid) {
    grid.innerHTML = '';
    var files = samplesInput.files ? Array.prototype.slice.call(samplesInput.files, 0, 4) : [];
    if (!files.length) { grid.classList.add('hidden'); return; }
    grid.classList.remove('hidden');
    files.forEach(function (f) {
      var rr = new FileReader();
      rr.onload = function (e) {
        var im = document.createElement('img');
        im.src = e.target.result;
        im.className = 'w-full aspect-square object-cover rounded-md';
        grid.appendChild(im);
      };
      rr.readAsDataURL(f);
    });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  generateProductCode();
  refreshProductPreview();

  ['name_fa', 'description_fa', 'credit_cost'].forEach(function (nm) {
    var el = document.querySelector('[name="' + nm + '"]');
    if (el) el.addEventListener('input', refreshProductPreview);
  });
  // تغییر دسته‌بندی چندگانه مستقیماً از renderCatChips در step-1.blade.php این تابع را صدا می‌زند
  document.querySelectorAll('[name="pricing_model"]').forEach(function (r) { r.addEventListener('change', refreshProductPreview); });

  var thumbInput = document.getElementById('thumbnail-file');
  if (thumbInput) thumbInput.addEventListener('change', refreshProductPreviewMedia);
  var samplesInput = document.getElementById('samples-file');
  if (samplesInput) samplesInput.addEventListener('change', refreshProductPreviewMedia);
});
</script>
