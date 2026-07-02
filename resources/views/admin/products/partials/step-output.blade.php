{{-- پارشیال: گام سوم — خروجی و قیمت‌گذاری --}}

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-file-export text-[#a07af5]"></i> تنظیمات خروجی</div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
    <div class="flex items-center justify-between p-2.5 bg-[#111116] border border-[#222230] rounded-lg">
      <div>
        <div class="text-[12.5px] font-semibold text-[#a8c4a8]">فعال‌سازی واترمارک</div>
      </div>
      <label class="relative w-9 h-5 shrink-0 block cursor-pointer">
        <input type="checkbox" name="watermark_enabled" value="1" checked class="sr-only peer">
        <span class="absolute inset-0 bg-[#2e2e3e] rounded-full transition-colors peer-checked:bg-[#0BBF53] before:content-[''] before:absolute before:w-3.5 before:h-3.5 before:right-[3px] before:top-[3px] before:bg-[#4d7a56] before:rounded-full transition-all peer-checked:before:-translate-x-[16px] peer-checked:before:bg-white"></span>
      </label>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">موقعیت واترمارک</label>
      <select name="watermark_position" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
        <option value="corner">corner — گوشه</option>
        <option value="center">center — وسط</option>
        <option value="none">none — بدون واترمارک</option>
      </select>
    </div>
  </div>
</div>

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-coins text-[#a07af5]"></i> قیمت‌گذاری</div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">مدل قیمت‌گذاری <span class="text-[#f05c5c] mr-0.5">*</span></label>
      <select name="pricing_model" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" onchange="toggleCreditCost(this)">
        <option value="free">رایگان (Free)</option>
        <option value="per_credit">به ازای هر کردیت (Per Credit)</option>
        <option value="subscription">اشتراکی (Subscription)</option>
      </select>
    </div>
    <div class="flex flex-col gap-1.5 transition-all opacity-30 pointer-events-none" id="credit-cost-wrap">
      <label class="text-xs font-semibold text-[#a8c4a8]">هزینه کردیت محصول</label>
      <input type="number" name="credit_cost" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" placeholder="مثال: 5" value="0">
    </div>
  </div>
</div>

<div class="bg-[#16161c] border border-[#222230] rounded-xl p-5">
  <div class="text-xs font-bold text-white mb-4 flex items-center gap-2 pb-3 border-b border-[#222230]"><i class="fa-solid fa-sliders text-[#a07af5]"></i> تنظیمات کارت و گالری</div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 mb-3.5">
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">حالت نمایش</label>
      <select name="display_mode" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
        <option value="card">card — کارت استاندارد</option>
        <option value="featured">featured — ویژه بزرگ</option>
        <option value="simple">simple — ساده</option>
      </select>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">شکل کارت</label>
      <select name="card_shape" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
        <option value="portrait">portrait — عمودی</option>
        <option value="landscape">landscape — افقی</option>
        <option value="square">square — مربع</option>
      </select>
    </div>
    <div class="flex flex-col gap-1.5">
      <label class="text-xs font-semibold text-[#a8c4a8]">چیدمان گالری</label>
      <select name="gallery_layout" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white">
        <option value="grid">grid — شبکه</option>
        <option value="masonry">masonry — آبشاری</option>
        <option value="slider">slider — اسلایدر</option>
      </select>
    </div>
  </div>
  <div class="flex flex-col gap-1.5">
    <label class="text-xs font-semibold text-[#a8c4a8]">برچسب اختیاری روی کارت</label>
    <input type="text" name="card_label" class="bg-[#111116] border border-[#222230] rounded-lg p-2.5 text-xs text-white" placeholder="مثلاً: هدیه، پیشنهاد ویژه">
  </div>
</div>