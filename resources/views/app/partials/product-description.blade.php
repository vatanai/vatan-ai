{{-- ═══════════════════════════════════════════════════════════════
     پارشیال: باکس توضیحات کامل محصول
     فقط در صورت وجود description_fa رندر می‌شود (متن کامل، نه نسخه کوتاه‌شده بالای صفحه)
     نکته: این پارشیال فقط باکس را می‌سازد (بدون <section>/عرض/فاصله بیرونی) چون در دو جای متفاوت
     include می‌شود: نسخه موبایل (زیر گالری/قبل از محصولات مشابه) و نسخه دسکتاپ (داخل هیرو، زیر عنوان).
     هر Caller خودش فاصله/عرض بیرونی مناسب را با یک wrapper تعیین می‌کند.
     ═══════════════════════════════════════════════════════════════ --}}
@if($product->description_fa)
  <div class="rounded-xl bg-[var(--bg-surface)] border border-[var(--border-subtle)] p-6 sm:p-7">
    <h2 class="text-base font-bold text-[var(--text-primary)] mb-3">توضیحات محصول</h2>
    <p class="text-[15px] leading-8 text-[var(--text-secondary)] whitespace-pre-line">{{ $product->description_fa }}</p>
  </div>
@endif
