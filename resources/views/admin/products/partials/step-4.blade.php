{{-- پارشیال: گام چهارم — خروجی و قیمت --}}
{{-- بازطراحی UI طبق «سند شماره ۱ - ثبت محصول»، بخش سوم. تمام name های واقعی و مقادیر مجاز آن‌ها
     دقیقاً با Validation کنترلر (watermark_position: corner/center/none،
     gallery_layout: grid/masonry/slider) هماهنگ نگه داشته شده‌اند.
     Card «خلاصه نهایی» به step-5.blade.php منتقل شد (گام پنجم جدید، هنگام تبدیل ویزارد به ۵ مرحله). --}}

@php
  $newBadge = '<span class="inline-flex items-center gap-1 bg-[var(--orange)]/10 text-[var(--orange)] border border-[var(--orange)]/30 rounded px-1.5 py-[1px] text-[9px] font-bold shrink-0 whitespace-nowrap"><i class="fa-solid fa-code text-[8px]"></i> برنامه‌نویسی شود</span>';
@endphp

{{-- ═══════════════════ Card ۱ — تنظیمات خروجی (واترمارک) ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-file-export text-[var(--accent)]"></i> تنظیمات خروجی</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">نحوه واترمارک‌گذاری روی تصاویر خروجی</div>
  </div>

  <div class="flex items-center justify-between p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg mb-3.5">
    <div>
      <div class="text-[12.5px] font-semibold text-[var(--text2)]">فعال‌سازی واترمارک</div>
      <div class="text-[11px] text-[var(--text3)] mt-0.5">در صورت غیرفعال بودن، تنظیمات زیر مخفی می‌شوند</div>
    </div>
    @php $wmEnabled = old('watermark_enabled', $duplicateFrom ? $duplicateFrom->watermark_enabled : true); @endphp
    <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
      <input type="checkbox" name="watermark_enabled" value="1" {{ $wmEnabled ? 'checked' : '' }} class="sr-only peer" id="watermark-enabled-input" onchange="toggleWatermarkSettings()">
      <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
    </label>
  </div>

  <div id="watermark-settings-wrap" class="{{ $wmEnabled ? '' : 'hidden' }}">
    <div class="flex flex-col gap-1.5 mb-3.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">موقعیت واترمارک <span class="pro-tooltip-wrap" style="display:inline-flex;"><i class="fa-solid fa-circle-question text-[10px] text-[var(--text3)] cursor-help"></i><span class="pro-tooltip" style="width:220px;">تعیین می‌کند لوگو یا متن واترمارک روی کدام قسمت تصویر خروجی قرار بگیرد.</span></span></label>
      @php $curWmPos = old('watermark_position', optional($duplicateFrom)->watermark_position ?? 'corner'); @endphp
      <div class="grid grid-cols-3 gap-2.5">
        <label class="wm-pos-card flex flex-col items-center gap-1.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curWmPos == 'corner' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
          <input type="radio" name="watermark_position" value="corner" {{ $curWmPos == 'corner' ? 'checked' : '' }} class="hidden" onchange="onWatermarkPosChange()">
          <i class="fa-solid fa-crop-simple text-sm text-[var(--text2)]"></i>
          <span class="text-[11px] text-[var(--text2)]">گوشه</span>
        </label>
        <label class="wm-pos-card flex flex-col items-center gap-1.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curWmPos == 'center' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
          <input type="radio" name="watermark_position" value="center" {{ $curWmPos == 'center' ? 'checked' : '' }} class="hidden" onchange="onWatermarkPosChange()">
          <i class="fa-solid fa-align-center text-sm text-[var(--text2)]"></i>
          <span class="text-[11px] text-[var(--text2)]">وسط</span>
        </label>
        <label class="wm-pos-card flex flex-col items-center gap-1.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curWmPos == 'none' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
          <input type="radio" name="watermark_position" value="none" {{ $curWmPos == 'none' ? 'checked' : '' }} class="hidden" onchange="onWatermarkPosChange()">
          <i class="fa-solid fa-ban text-sm text-[var(--text2)]"></i>
          <span class="text-[11px] text-[var(--text2)]">بدون واترمارک</span>
        </label>
      </div>

      <div id="wm-precise-corner-wrap" class="{{ $curWmPos == 'corner' ? '' : 'hidden' }} mt-1.5">
        <label class="text-[11px] font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">دقت موقعیت گوشه</label>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-1.5">
          <button type="button" class="corner-precise-btn text-[10.5px] p-2 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[var(--text3)]" data-corner="tl" onclick="setPreciseCorner('tl')"><i class="fa-solid fa-arrow-up-right-from-square rotate-180 block mb-1"></i>بالا چپ</button>
          <button type="button" class="corner-precise-btn text-[10.5px] p-2 rounded-lg border border-[var(--accent)] bg-[var(--accent)]/8 text-[var(--text)]" data-corner="tr" onclick="setPreciseCorner('tr')"><i class="fa-solid fa-arrow-up-right-from-square block mb-1"></i>بالا راست</button>
          <button type="button" class="corner-precise-btn text-[10.5px] p-2 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[var(--text3)]" data-corner="bl" onclick="setPreciseCorner('bl')"><i class="fa-solid fa-arrow-down-left-and-arrow-up-right-to-center block mb-1"></i>پایین چپ</button>
          <button type="button" class="corner-precise-btn text-[10.5px] p-2 rounded-lg border border-[var(--b1)] bg-[var(--s1)] text-[var(--text3)]" data-corner="br" onclick="setPreciseCorner('br')"><i class="fa-solid fa-arrow-down-left-and-arrow-up-right-to-center rotate-90 block mb-1"></i>پایین راست</button>
        </div>
        <input type="hidden" name="new_watermark_corner_precise" id="new-watermark-corner-precise" value="tr">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center justify-between flex-wrap gap-1.5">
          <span class="flex items-center gap-1.5 flex-wrap">شفافیت واترمارک</span>
          <span class="text-[var(--accent)] font-mono text-[11px]" id="wm-opacity-val">70%</span>
        </label>
        <input type="range" name="new_watermark_opacity" min="0" max="100" value="70" class="w-full accent-[var(--accent)]" oninput="document.getElementById('wm-opacity-val').textContent = this.value + '%'">
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center justify-between flex-wrap gap-1.5">
          <span class="flex items-center gap-1.5 flex-wrap">اندازه واترمارک</span>
          <span class="text-[var(--accent)] font-mono text-[11px]" id="wm-size-val">30%</span>
        </label>
        <input type="range" name="new_watermark_size" min="10" max="100" value="30" class="w-full accent-[var(--accent)]" oninput="document.getElementById('wm-size-val').textContent = this.value + '%'">
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">نوع واترمارک</label>
        <div class="grid grid-cols-2 gap-2">
          <label class="flex items-center justify-center gap-1.5 p-2.5 bg-[var(--s1)] border border-[var(--accent)] bg-[var(--accent)]/8 rounded-lg cursor-pointer text-xs text-[var(--text)]">
            <input type="radio" name="new_watermark_type" value="logo" checked class="accent-[var(--accent)]"> Logo
          </label>
          <label class="flex items-center justify-center gap-1.5 p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer text-xs text-[var(--text2)]">
            <input type="radio" name="new_watermark_type" value="text" class="accent-[var(--accent)]"> Text
          </label>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">رنگ متن واترمارک</label>
        <div class="flex items-center gap-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <input type="color" name="new_watermark_text_color" value="#ffffff" class="w-9 h-9 rounded-md border border-[var(--b1)] bg-transparent cursor-pointer shrink-0" oninput="document.getElementById('wm-text-color-hex').value = this.value.toUpperCase()">
          <input type="text" id="wm-text-color-hex" class="bg-transparent border-none outline-none text-xs text-[var(--text)] ltr text-left flex-1" value="#FFFFFF" readonly>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════ Card ۲ — قیمت‌گذاری ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-coins text-[var(--accent)]"></i> قیمت‌گذاری</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">نحوه محاسبه هزینه استفاده از محصول</div>
  </div>

  @php $curPricing = old('pricing_model', optional($duplicateFrom)->pricing_model ?? 'per_credit'); @endphp
  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">مدل قیمت‌گذاری <span class="text-[var(--red)] mr-0.5">*</span> <span class="pro-tooltip-wrap" style="display:inline-flex;"><i class="fa-solid fa-circle-question text-[10px] text-[var(--text3)] cursor-help"></i><span class="pro-tooltip" style="width:230px;">رایگان: بدون هزینه — کردیتی: به‌ازای هر اجرا کردیت کم می‌شود — اشتراکی: نیازمند اشتراک فعال کاربر.</span></span></label>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2.5">
      <label class="pricing-card flex items-center gap-2.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curPricing == 'free' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
        <input type="radio" name="pricing_model" value="free" {{ $curPricing == 'free' ? 'checked' : '' }} class="accent-[var(--accent)]" onchange="toggleCreditCost(this)">
        <span class="text-xs font-semibold text-[var(--text2)]"><i class="fa-solid fa-gift ml-1 text-[var(--text3)]"></i> رایگان</span>
      </label>
      <label class="pricing-card flex items-center gap-2.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curPricing == 'per_credit' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
        <input type="radio" name="pricing_model" value="per_credit" {{ $curPricing == 'per_credit' ? 'checked' : '' }} class="accent-[var(--accent)]" onchange="toggleCreditCost(this)">
        <span class="text-xs font-semibold text-[var(--text2)]"><i class="fa-solid fa-coins ml-1 text-[var(--text3)]"></i> کردیتی</span>
      </label>
      <label class="pricing-card flex items-center gap-2.5 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curPricing == 'subscription' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
        <input type="radio" name="pricing_model" value="subscription" {{ $curPricing == 'subscription' ? 'checked' : '' }} class="accent-[var(--accent)]" onchange="toggleCreditCost(this)">
        <span class="text-xs font-semibold text-[var(--text2)]"><i class="fa-solid fa-rotate ml-1 text-[var(--text3)]"></i> اشتراکی</span>
      </label>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5 transition-all {{ $curPricing != 'per_credit' ? 'opacity-30 pointer-events-none' : '' }}" id="credit-cost-wrap">
      <label class="text-xs font-semibold text-[var(--text2)]">هزینه کردیت محصول</label>
      <input type="number" name="credit_cost" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="مثال: 5" value="{{ old('credit_cost', optional($duplicateFrom)->credit_cost ?? 0) }}">
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">حداقل کردیت لازم</label>
      <input type="number" name="new_min_credit_required" min="0" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="مثلاً: 1" value="{{ old('new_min_credit_required', optional($duplicateFrom)->new_min_credit_required ?? 0) }}">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">حداکثر تعداد اجرا برای هر کاربر</label>
      <input type="number" name="new_max_run_per_user" min="1" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="بدون محدودیت" value="{{ old('new_max_run_per_user', optional($duplicateFrom)->new_max_run_per_user) }}">
    </div>
    <div class="flex items-center justify-between p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg mt-auto">
      <div>
        <div class="text-[12.5px] font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">نمایش برچسب رایگان</div>
      </div>
      <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
        <input type="checkbox" name="new_show_free_badge" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </label>
    </div>
  </div>

  <div class="flex flex-col gap-1.5">
    <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5 flex-wrap">برچسب اختصاصی قیمت</label>
    <input type="text" name="new_price_custom_label" maxlength="100" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="مثلاً: ویژه، اقتصادی، هدیه" value="{{ old('new_price_custom_label', optional($duplicateFrom)->new_price_custom_label) }}">
    <div class="flex gap-1.5 flex-wrap">
      @foreach (['ویژه','اقتصادی','هدیه'] as $preset)
        <span class="text-[10.5px] bg-[var(--b1)] border border-[var(--b2)] rounded px-2 py-0.5 cursor-pointer text-[var(--text2)] hover:border-[var(--accent)]" onclick="document.querySelector('[name=new_price_custom_label]').value='{{ $preset }}'">{{ $preset }}</span>
      @endforeach
    </div>
  </div>
</div>

{{-- ═══════════════════ Card ۳ — تنظیمات کارت و گالری ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-sliders text-[var(--accent)]"></i> تنظیمات کارت و گالری</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">این تنظیمات تعیین می‌کند محصول در اپ و نسخه موبایل چگونه نمایش داده شود</div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">حالت نمایش</label>
      @php $curDisplayMode = old('display_mode', optional($duplicateFrom)->display_mode ?? 'card'); @endphp
      <div class="flex flex-col gap-2">
        @foreach(['card' => ['کارت استاندارد','fa-square'], 'featured' => ['ویژه بزرگ','fa-star'], 'simple' => ['ساده','fa-minus']] as $val => $meta)
          <label class="preview-card-option flex items-center gap-2.5 p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curDisplayMode == $val ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
            <input type="radio" name="display_mode" value="{{ $val }}" {{ $curDisplayMode == $val ? 'checked' : '' }} class="accent-[var(--accent)]">
            <i class="fa-solid {{ $meta[1] }} text-[var(--text3)] text-xs"></i>
            <span class="text-xs text-[var(--text2)]">{{ $meta[0] }}</span>
          </label>
        @endforeach
      </div>
    </div>

    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">شکل کارت <span class="text-[10px] text-[var(--text3)] font-normal">(اولویت با موبایل)</span></label>
      @php $curCardShape = old('card_shape', optional($duplicateFrom)->card_shape ?? 'portrait'); @endphp
      <div class="flex flex-col gap-2">
        <label class="shape-card-option flex items-center gap-2.5 p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curCardShape == 'portrait' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
          <input type="radio" name="card_shape" value="portrait" {{ $curCardShape == 'portrait' ? 'checked' : '' }} class="accent-[var(--accent)]">
          <span class="block w-4 h-5 rounded-sm border border-[var(--text3)] shrink-0"></span>
          <span class="text-xs text-[var(--text2)]">عمودی (Portrait)</span>
          <span class="text-[9px] bg-[var(--green)]/15 text-[var(--green)] rounded px-1 py-0.5 mr-auto">پیشنهادی موبایل</span>
        </label>
        <label class="shape-card-option flex items-center gap-2.5 p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curCardShape == 'landscape' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
          <input type="radio" name="card_shape" value="landscape" {{ $curCardShape == 'landscape' ? 'checked' : '' }} class="accent-[var(--accent)]">
          <span class="block w-5 h-4 rounded-sm border border-[var(--text3)] shrink-0"></span>
          <span class="text-xs text-[var(--text2)]">افقی (Landscape)</span>
        </label>
        <label class="shape-card-option flex items-center gap-2.5 p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curCardShape == 'square' ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
          <input type="radio" name="card_shape" value="square" {{ $curCardShape == 'square' ? 'checked' : '' }} class="accent-[var(--accent)]">
          <span class="block w-4 h-4 rounded-sm border border-[var(--text3)] shrink-0"></span>
          <span class="text-xs text-[var(--text2)]">مربع (Square)</span>
        </label>
      </div>
    </div>

    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)] flex items-center gap-1.5">چیدمان گالری <span class="pro-tooltip-wrap" style="display:inline-flex;"><i class="fa-solid fa-circle-question text-[10px] text-[var(--text3)] cursor-help"></i><span class="pro-tooltip" style="width:220px;">نحوه‌ی چیده‌شدن نمونه‌خروجی‌ها در صفحه محصول: شبکه‌ای منظم، آبشاری یا اسلایدر.</span></span></label>
      @php $curGalleryLayout = old('gallery_layout', optional($duplicateFrom)->gallery_layout ?? 'grid'); @endphp
      <div class="flex flex-col gap-2">
        @foreach(['grid' => ['شبکه','fa-table-cells'], 'masonry' => ['آبشاری','fa-grip'], 'slider' => ['اسلایدر','fa-images']] as $val => $meta)
          <label class="preview-card-option flex items-center gap-2.5 p-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-all {{ $curGalleryLayout == $val ? 'border-[var(--accent)] bg-[var(--accent)]/8' : '' }}">
            <input type="radio" name="gallery_layout" value="{{ $val }}" {{ $curGalleryLayout == $val ? 'checked' : '' }} class="accent-[var(--accent)]">
            <i class="fa-solid {{ $meta[1] }} text-[var(--text3)] text-xs"></i>
            <span class="text-xs text-[var(--text2)]">{{ $meta[0] }}</span>
          </label>
        @endforeach
      </div>
    </div>
  </div>

  <div class="flex flex-col gap-1.5 mb-4">
    <label class="text-xs font-semibold text-[var(--text2)]">برچسب اختیاری روی کارت</label>
    <input type="text" name="card_label" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="مثلاً: هدیه، پیشنهاد ویژه" value="{{ old('card_label', optional($duplicateFrom)->card_label) }}">
  </div>

  {{-- بند ۲۰: ظاهر کارت محصول (NEW / فقط UI) — رنگ Badge، رنگ پس‌زمینه کارت، نمایش آیکون/Badge، اولویت صفحه اصلی --}}
  <div class="border-t border-dashed border-[var(--b2)] pt-4 mt-1">
    <div class="text-[10.5px] font-bold text-[var(--text3)] mb-3 tracking-wide uppercase flex items-center gap-1.5 flex-wrap"><i class="fa-solid fa-palette text-[10px]"></i> ظاهر کارت محصول {!! $newBadge !!}</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)]">رنگ Badge</label>
        <div class="flex items-center gap-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <input type="color" name="new_badge_color" value="#16a34a" class="w-9 h-9 rounded-md border border-[var(--b1)] bg-transparent cursor-pointer shrink-0" oninput="document.getElementById('new-badge-color-hex').value = this.value.toUpperCase()">
          <input type="text" id="new-badge-color-hex" class="bg-transparent border-none outline-none text-xs text-[var(--text)] ltr text-left flex-1" value="#16A34A" readonly>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="text-xs font-semibold text-[var(--text2)]">رنگ پس‌زمینه کارت</label>
        <div class="flex items-center gap-2.5 bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2">
          <input type="color" name="new_card_bg_color" value="#030f09" class="w-9 h-9 rounded-md border border-[var(--b1)] bg-transparent cursor-pointer shrink-0" oninput="document.getElementById('new-card-bg-color-hex').value = this.value.toUpperCase()">
          <input type="text" id="new-card-bg-color-hex" class="bg-transparent border-none outline-none text-xs text-[var(--text)] ltr text-left flex-1" value="#030F09" readonly>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3.5">
      <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
        <div class="min-w-0">
          <div class="text-[12.5px] font-semibold text-[var(--text2)]">نمایش آیکون روی کارت</div>
          <div class="text-[11px] text-[var(--text3)] mt-0.5">آیکون محصول روی کارت دیده شود</div>
        </div>
        <span class="relative w-9 h-5 shrink-0 block">
          <input type="checkbox" name="new_show_icon_on_card" value="1" class="sr-only peer">
          <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
        </span>
      </label>

      <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
        <div class="min-w-0">
          <div class="text-[12.5px] font-semibold text-[var(--text2)]">نمایش Badge روی کارت</div>
          <div class="text-[11px] text-[var(--text3)] mt-0.5">برچسب/نشان روی کارت دیده شود</div>
        </div>
        <span class="relative w-9 h-5 shrink-0 block">
          <input type="checkbox" name="new_show_badge_on_card" value="1" class="sr-only peer">
          <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
        </span>
      </label>
    </div>

    <div class="flex flex-col gap-1.5 md:max-w-xs">
      <label class="text-xs font-semibold text-[var(--text2)]">اولویت نمایش در صفحه اصلی</label>
      <input type="number" name="new_home_priority" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="مثلاً: 1 (عدد کوچک‌تر = بالاتر)">
    </div>
  </div>


</div>

{{-- ═══════════════════ Card — انتشار محصول (NEW / فقط UI — بند ۲۱) ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)] flex items-center justify-between flex-wrap gap-2">
    <div>
      <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2 flex-wrap"><i class="fa-solid fa-rocket text-[var(--accent)]"></i> انتشار محصول {!! $newBadge !!}</div>
      <div class="text-[10.5px] text-[var(--text3)] mt-1">زمان‌بندی و محل نمایش محصول پس از انتشار</div>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">وضعیت انتشار</label>
      <select name="new_publish_status" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]">
        <option value="draft">پیش‌نویس</option>
        <option value="published">منتشر شده</option>
        <option value="inactive">غیرفعال</option>
      </select>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[var(--text2)]">تاریخ انتشار</label>
      <input type="date" name="new_publish_date" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] ltr text-left">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3.5">
    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)]">نمایش در صفحه اول</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">در بخش اصلی سایت دیده شود</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="new_publish_on_home" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)]">نمایش در پیشنهادات</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">در بخش پیشنهادها فهرست شود</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="new_publish_in_suggestions" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>

    <label class="toggle-card flex items-start justify-between gap-2 p-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg cursor-pointer transition-colors hover:border-[var(--b2)]">
      <div class="min-w-0">
        <div class="text-[12.5px] font-semibold text-[var(--text2)]">نمایش در محبوب‌ها</div>
        <div class="text-[11px] text-[var(--text3)] mt-0.5">در بخش پرطرفدارها دیده شود</div>
      </div>
      <span class="relative w-9 h-5 shrink-0 block">
        <input type="checkbox" name="new_publish_in_popular" value="1" class="sr-only peer">
        <span class="absolute inset-0 bg-[var(--b2)] rounded-full transition-colors peer-checked:bg-[var(--green)] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[var(--text3)] before:rounded-full before:transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </span>
    </label>
  </div>

  <div class="flex flex-col gap-1.5 md:max-w-xs">
    <label class="text-xs font-semibold text-[var(--text2)]">اولویت نمایش</label>
    <input type="number" name="new_publish_priority" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)]" placeholder="مثلاً: 1 (عدد کوچک‌تر = بالاتر)">
  </div>
</div>

{{-- ═══════════════════ Card — سئو (SEO) ═══════════════════ --}}
<div class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5">
  <div class="mb-4 pb-3 border-b border-[var(--b1)]">
    <div class="text-xs font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-magnifying-glass-chart text-[var(--accent)]"></i> سئو (بهینه‌سازی موتور جستجو)</div>
    <div class="text-[10.5px] text-[var(--text3)] mt-1">اگر خالی بماند، سیستم از نام و توضیح و کاور محصول به‌صورت خودکار می‌سازد</div>
  </div>

  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)] flex items-center justify-between">
      <span>عنوان متا (Meta Title)</span>
      <span class="text-[10px] text-[var(--text3)]"><span id="meta-title-count">0</span>/60</span>
    </label>
    <input type="text" name="meta_title" maxlength="70" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full" placeholder="مثلاً: عکس حرفه‌ای لینکدین با هوش مصنوعی | وطن AI" value="{{ old('meta_title', optional($duplicateFrom)->meta_title) }}" oninput="document.getElementById('meta-title-count').textContent=this.value.length">
    <div class="text-[10px] text-[var(--text3)]">بهترین طول: تا حدود ۶۰ کاراکتر.</div>
  </div>

  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)] flex items-center justify-between">
      <span>توضیحات متا (Meta Description)</span>
      <span class="text-[10px] text-[var(--text3)]"><span id="meta-desc-count">0</span>/160</span>
    </label>
    <textarea name="meta_description" rows="3" maxlength="300" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full resize-y" placeholder="توضیح کوتاه و جذاب محصول برای نتایج جستجوی گوگل..." oninput="document.getElementById('meta-desc-count').textContent=this.value.length">{{ old('meta_description', optional($duplicateFrom)->meta_description) }}</textarea>
    <div class="text-[10px] text-[var(--text3)]">بهترین طول: تا حدود ۱۶۰ کاراکتر.</div>
  </div>

  <div class="flex flex-col gap-1.5 mb-3.5">
    <label class="text-xs font-semibold text-[var(--text2)]">کلمات کلیدی (با ویرگول جدا کنید)</label>
    <input type="text" name="meta_keywords" class="bg-[var(--s1)] border border-[var(--b1)] rounded-lg p-2.5 text-xs text-[var(--text)] w-full" placeholder="عکس لینکدین, پرتره هوش مصنوعی, عکس پروفایل" value="{{ old('meta_keywords', optional($duplicateFrom)->meta_keywords) }}">
  </div>

  <div class="flex flex-col gap-1.5 md:max-w-md">
    <label class="text-xs font-semibold text-[var(--text2)]">تصویر اشتراک‌گذاری شبکه‌های اجتماعی (OG Image)</label>
    <div class="border-2 border-dashed border-[var(--b2)] rounded-xl p-4 text-center cursor-pointer bg-[var(--s1)] hover:border-[var(--accent)] transition-colors w-full" onclick="document.getElementById('og-image-file').click()">
      <i class="fa-solid fa-share-nodes text-lg text-[var(--text3)] mb-1 block"></i>
      <div class="text-[11px] text-[var(--text2)]" id="og-image-title">آپلود تصویر (پیشنهادی ۱۲۰۰×۶۳۰)</div>
      <input type="file" id="og-image-file" name="og_image" accept="image/*" class="hidden" onchange="updateFileLabel(this,'og-image-title')">
    </div>
    <div class="text-[10px] text-[var(--text3)]">اگر خالی بماند، از کاور محصول استفاده می‌شود.</div>
  </div>
</div>

<script>
/* ══════ Card ۱ — نمایش/مخفی‌سازی تنظیمات واترمارک + دقت گوشه ══════ */
function toggleWatermarkSettings() {
  const enabled = document.getElementById('watermark-enabled-input').checked;
  document.getElementById('watermark-settings-wrap').classList.toggle('hidden', !enabled);
}
function onWatermarkPosChange() {
  document.querySelectorAll('.wm-pos-card').forEach(card => card.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
  const checked = document.querySelector('input[name="watermark_position"]:checked');
  if (checked) checked.closest('.wm-pos-card').classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
  document.getElementById('wm-precise-corner-wrap').classList.toggle('hidden', !checked || checked.value !== 'corner');
}
function setPreciseCorner(corner) {
  document.getElementById('new-watermark-corner-precise').value = corner;
  document.querySelectorAll('.corner-precise-btn').forEach(btn => {
    const active = btn.dataset.corner === corner;
    btn.classList.toggle('border-[var(--accent)]', active);
    btn.classList.toggle('bg-[var(--accent)]/8', active);
    btn.classList.toggle('text-[var(--text)]', active);
    btn.classList.toggle('border-[var(--b1)]', !active);
    btn.classList.toggle('bg-[var(--s1)]', !active);
    btn.classList.toggle('text-[var(--text3)]', !active);
  });
}

/* ══════ Card ۲/۳ — رادیوکارت‌های Pricing/Display/Shape/Gallery: هایلایت کارت انتخاب‌شده ══════ */
function wireCardRadioGroup(selector) {
  document.querySelectorAll(selector + ' input[type="radio"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll(selector).forEach(card => card.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
      if (radio.checked) radio.closest(selector.replace(' input','')).classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
      if (typeof refreshFinalSummary === 'function') refreshFinalSummary();
    });
  });
}
document.querySelectorAll('.pricing-card, .preview-card-option, .shape-card-option').forEach(card => {
  const radio = card.querySelector('input[type="radio"]');
  if (!radio) return;
  radio.addEventListener('change', () => {
    document.querySelectorAll('.' + card.className.split(' ')[0]).forEach(c => c.classList.remove('border-[var(--accent)]', 'bg-[var(--accent)]/8'));
    if (radio.checked) card.classList.add('border-[var(--accent)]', 'bg-[var(--accent)]/8');
    refreshFinalSummary();
  });
});

/* توجه: تابع refreshFinalSummary و کارت «خلاصه نهایی» به step-5.blade.php منتقل شدند (گام پنجم).
   توابع بالا (toggleWatermarkSettings/onWatermarkPosChange) و رادیوکارت‌های این صفحه در صورت وجود
   refreshFinalSummary در Scope سراسری آن را صدا می‌زنند تا خلاصه گام پنجم زنده به‌روزرسانی شود. */
document.addEventListener('DOMContentLoaded', () => {
  toggleWatermarkSettings();
  onWatermarkPosChange();
});
</script>
