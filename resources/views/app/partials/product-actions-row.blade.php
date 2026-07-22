{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: ردیف بازگشت + سیو + اشتراک‌گذاری (+ توکن موبایل)
     موبایل: بلافاصله زیر گالری | دسکتاپ: زیر باکس توضیحات محصول
     ═══════════════════════════════════════════════════════════════ --}}
<div class="flex items-center justify-between">
  <button type="button" onclick="history.length>1?history.back():location.href='/app/home'"
    class="hidden lg:flex w-10 h-10 items-center justify-center rounded-xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition-colors">
    <i class="fa-solid fa-arrow-right text-xs"></i>
  </button>
  <div class="flex items-center gap-2 flex-wrap">
    <button id="btnBookmark" type="button" data-saved="{{ $isSaved ? '1' : '0' }}"
      class="px-4 h-10 rounded-xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] flex items-center gap-1.5 text-[11px] font-bold transition-colors {{ $isSaved ? 'text-[var(--green)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]' }}">
      <i id="iconBkm" class="{{ $isSaved ? 'fa-solid' : 'fa-regular' }} fa-bookmark text-[11px]"></i>
      <span>ذخیره</span>
    </button>
    <button id="btnShare" type="button"
      class="w-10 h-10 flex items-center justify-center rounded-xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition-colors">
      <i class="fa-solid fa-share-nodes text-xs"></i>
    </button>

    {{-- میزان مصرف توکن/رایگان — فقط نسخه موبایل، کنار ذخیره و اشتراک‌گذاری --}}
    @if($product->pricing_model === 'per_credit' && $product->credit_cost > 0)
      <span class="lg:hidden px-3 h-10 inline-flex items-center rounded-full bg-[var(--bg-surface)] border border-[var(--orange)] text-[11px] font-bold text-[var(--orange)]">
        <i class="fa-solid fa-bolt text-[10px] ml-1.5"></i>{{ $product->credit_cost }} توکن
      </span>
    @elseif($product->pricing_model === 'free')
      <span class="lg:hidden px-3 h-10 inline-flex items-center rounded-full bg-[var(--bg-surface)] border border-[var(--green)] text-[11px] font-bold text-[var(--green)]">
        رایگان
      </span>
    @endif
  </div>
</div>
