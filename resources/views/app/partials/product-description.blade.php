{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: توضیحات کامل محصول — بخش ۲ صفحه محصول
     فقط در صورت وجود description_fa رندر می‌شود (متن کامل، نه نسخه کوتاه‌شده بالای صفحه)
     ═══════════════════════════════════════════════════════════════ --}}
@if($product->description_fa)
<section class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
  <div class="rounded-3xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] p-6 sm:p-7">
    <h2 class="text-base font-bold text-[var(--text-primary)] mb-3">توضیحات محصول</h2>
    <p class="text-[15px] leading-8 text-[var(--text-secondary)] whitespace-pre-line">{{ $product->description_fa }}</p>
  </div>
</section>
@endif
