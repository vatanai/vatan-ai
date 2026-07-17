{{-- پارشیال: انتخاب «مدل‌های خروجی چندگانه» در صفحه ساخت (مودال میز کار) --}}
{{-- فقط وقتی محصول واریانت دارد رندر می‌شود؛ کاربر مدل‌های دلخواه را تیک می‌زند،
     دور کارت انتخاب‌شده کادر سبز + تیک می‌افتد و جمع توکن بالای دکمه «بساز» به‌روز می‌شود. --}}

@php $__variants = $product->outputVariantList(); @endphp

@if(count($__variants))
<div class="shrink-0 pt-3 border-t border-white/[0.03]" id="variantPickerBox">
  <div class="flex items-center justify-between mb-2">
    <p class="text-[10px] font-bold text-gray-500">کدام مدل‌های خروجی ساخته شوند؟</p>
    <span class="text-[9px] text-gray-600" id="variantPickerCount"></span>
  </div>

  <div class="grid grid-cols-3 gap-2 max-h-[150px] overflow-y-auto pl-1"
       style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.08) transparent" id="variantPickerGrid">
    @foreach($__variants as $i => $v)
      <button type="button" class="variant-card relative rounded-xl overflow-hidden border-2 border-white/[0.06] bg-white/[0.02] text-right transition-all focus:outline-none {{ $i === 0 ? 'is-selected' : '' }}"
              data-key="{{ $v['key'] }}" onclick="toggleVariantCard(this)" title="{{ $v['title'] }}">
        <div class="aspect-square w-full bg-[#070708] flex items-center justify-center overflow-hidden">
          @if(!empty($v['image']))
            <img src="{{ asset('storage/'.$v['image']) }}" alt="{{ $v['title'] }}" class="w-full h-full object-cover">
          @else
            <i class="fa-regular fa-image text-gray-700"></i>
          @endif
        </div>
        <div class="px-1.5 py-1.5">
          <p class="text-[9px] font-bold text-gray-300 leading-snug truncate">{{ $v['title'] }}</p>
        </div>
        <span class="variant-check absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-emerald-500 text-black text-[9px] items-center justify-center shadow-lg hidden">
          <i class="fa-solid fa-check"></i>
        </span>
      </button>
    @endforeach
  </div>
</div>

<style>
  #variantPickerGrid .variant-card.is-selected {
    border-color: #10b981; /* emerald-500 — کادر سبز دور مدل انتخاب‌شده */
    box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.35);
    background: rgba(16, 185, 129, 0.06);
  }
  #variantPickerGrid .variant-card.is-selected .variant-check { display: flex; }
</style>
@endif
