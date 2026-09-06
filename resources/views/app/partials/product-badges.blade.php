{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: دسته‌بندی + تعداد ساخت (در صورت وجود) + قیمت/توکن (نسخه دسکتاپ)
     موبایل: زیر عنوان/توضیح کوتاه | دسکتاپ: زیر ردیف سیو/اشتراک
     ═══════════════════════════════════════════════════════════════ --}}
@php
  $__buildCount    = $product->generatedImages()->count();
  $__categoryLabel = $product->subcategory ?: $product->category;
@endphp

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
