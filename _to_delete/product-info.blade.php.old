{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: اطلاعات محصول — ستون راست صفحه محصول (دسکتاپ ~۴۰٪)
     ترتیب دقیق طبق سند: نام محصول → توضیح کوتاه → دسته‌بندی → تعداد ساخت (در صورت وجود) → قیمت
     ادامه‌ی این ستون (تنظیمات داینامیک + دکمه بزرگ «شروع ساخت») در پارشیال product-options است.
     ═══════════════════════════════════════════════════════════════ --}}
@php
  $__buildCount    = $product->generatedImages()->count();
  $__categoryLabel = $product->subcategory ?: $product->category;
  $__shortDesc     = $product->description_fa
      ? \Illuminate\Support\Str::limit(trim(strip_tags($product->description_fa)), 140)
      : null;
@endphp

<div class="w-full flex flex-col gap-4">

  {{-- ردیف بالا: بازگشت + سیو + اشتراک‌گذاری --}}
  <div class="flex items-center justify-between">
    <button type="button" onclick="history.length>1?history.back():location.href='/app/home'"
      class="hidden lg:flex w-10 h-10 items-center justify-center rounded-2xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition-colors">
      <i class="fa-solid fa-arrow-right text-xs"></i>
    </button>
    <div class="flex items-center gap-2 flex-wrap">
      <button id="btnBookmark" type="button" data-saved="{{ $isSaved ? '1' : '0' }}"
        class="px-4 h-10 rounded-2xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] flex items-center gap-1.5 text-[11px] font-bold transition-colors {{ $isSaved ? 'text-[var(--green)]' : 'text-[var(--text-secondary)] hover:text-[var(--text-primary)]' }}">
        <i id="iconBkm" class="{{ $isSaved ? 'fa-solid' : 'fa-regular' }} fa-bookmark text-[11px]"></i>
        <span>ذخیره</span>
      </button>
      <button id="btnShare" type="button"
        class="w-10 h-10 flex items-center justify-center rounded-2xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] text-[var(--text-secondary)] hover:text-[var(--text-primary)] transition-colors">
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

  {{-- ۱. نام محصول --}}
  <h1 class="text-xl sm:text-2xl font-bold text-[var(--text-primary)] leading-snug">{{ $product->name_fa }}</h1>

  {{-- ۲. توضیح کوتاه --}}
  @if($__shortDesc)
    <p class="text-sm text-[var(--text-secondary)] leading-relaxed">{{ $__shortDesc }}</p>
  @endif

  {{-- ۳. دسته‌بندی + ۴. تعداد ساخت (در صورت وجود) + ۵. قیمت --}}
  <div class="flex items-center gap-2 flex-wrap">

    @if($__categoryLabel)
      <span class="px-3 h-8 inline-flex items-center rounded-full bg-[var(--bg-surface)] border border-[var(--border-subtle)] text-[11px] font-bold text-[var(--text-secondary)]">
        <i class="fa-solid fa-tag text-[10px] ml-1.5"></i>{{ $__categoryLabel }}
      </span>
    @endif

    @if($__buildCount > 0)
      <span class="px-3 h-8 inline-flex items-center rounded-full bg-[var(--bg-surface)] border border-[var(--border-subtle)] text-[11px] font-bold text-[var(--text-secondary)]">
        <i class="fa-solid fa-wand-magic-sparkles text-[10px] ml-1.5"></i>{{ number_format($__buildCount) }} بار ساخته شده
      </span>
    @endif

    @if($product->pricing_model === 'per_credit' && $product->credit_cost > 0)
      <span class="hidden lg:inline-flex px-3 h-8 items-center rounded-full bg-[var(--bg-surface)] border border-[var(--orange)] text-[11px] font-bold text-[var(--orange)]">
        <i class="fa-solid fa-bolt text-[10px] ml-1.5"></i>{{ $product->credit_cost }} توکن
      </span>
    @elseif($product->pricing_model === 'free')
      <span class="hidden lg:inline-flex px-3 h-8 items-center rounded-full bg-[var(--bg-surface)] border border-[var(--green)] text-[11px] font-bold text-[var(--green)]">
        رایگان
      </span>
    @endif

  </div>

</div>
