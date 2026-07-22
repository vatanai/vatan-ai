{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: عنوان محصول + توضیح کوتاه
     موبایل: بعد از ردیف بازگشت/سیو/اشتراک | دسکتاپ: اولین آیتم هیرو
     توضیح کوتاه فقط در موبایل نمایش داده می‌شود (در دسکتاپ باکس توضیحات کامل جایگزین آن است)
     ═══════════════════════════════════════════════════════════════ --}}
@php
  $__shortDesc = $product->description_fa
      ? \Illuminate\Support\Str::limit(trim(strip_tags($product->description_fa)), 140)
      : null;
@endphp

<div class="w-full flex flex-col gap-2">
  <h1 class="text-xl sm:text-2xl font-bold text-[var(--text-primary)] leading-snug">{{ $product->name_fa }}</h1>

  @if($__shortDesc)
    <p class="lg:hidden text-sm text-[var(--text-secondary)] leading-relaxed">{{ $__shortDesc }}</p>
  @endif
</div>
