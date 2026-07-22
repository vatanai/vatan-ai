{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: عکس‌های قبل — تصاویر خامی که این محصول (مدل هوش مصنوعی) با آن‌ها ساخته شده
     فقط وقتی before_images محصول خالی نباشد نمایش داده می‌شود.
     ═══════════════════════════════════════════════════════════════ --}}
@php
  $__beforeImages = is_array($product->before_images ?? null) ? array_values($product->before_images) : [];
@endphp
@if(!empty($__beforeImages))
<section class="w-full max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
  <div class="rounded-xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] p-5 sm:p-6">
    <h2 class="text-sm font-bold text-[var(--text-primary)] mb-3">عکس‌های قبل</h2>
    <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2.5">
      @foreach($__beforeImages as $__bimg)
        <div class="aspect-square rounded-xl overflow-hidden bg-[var(--bg-page)] border border-[var(--border-subtle)]">
          <img src="{{ asset('storage/'.$__bimg) }}" alt="عکس قبل — {{ $product->name_fa }}" class="w-full h-full object-cover">
        </div>
      @endforeach
    </div>
  </div>
</section>
@endif
