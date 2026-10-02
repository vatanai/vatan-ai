{{-- گام ۱ ثبت محصول: انتخاب نوع. فقط وقتی «استودیو محصول» روشن است رندر می‌شود. --}}
<div class="ps-mode-cards" role="group" aria-label="نوع محصول">
  <a href="{{ route('admin.products.create') }}" class="ps-mode-card {{ $current === 'portrait' ? 'is-current' : '' }}" @if($current === 'portrait') aria-current="page" @endif>
    <span class="ps-mode-icon"><i class="fa-solid fa-user"></i></span>
    <span>
      <strong>چهره‌محور</strong>
      <span>همان فرم ۵ مرحله‌ای فعلی: پرتره، حفظ هویت چهره، فیلدهای سفارشی.</span>
    </span>
  </a>
  <a href="{{ route('admin.product-shots.products.create') }}" class="ps-mode-card {{ $current === 'product' ? 'is-current' : '' }}" @if($current === 'product') aria-current="page" @endif>
    <span class="ps-mode-icon"><i class="fa-solid fa-bottle-droplet"></i></span>
    <span>
      <strong>پروداکتی (جدید) — پک شات</strong>
      <span>کاربر ۱ عکس محصول می‌دهد و چند شات تبلیغاتی سینمایی می‌گیرد. فرم ساده‌تر با کتابخانه‌ی شات.</span>
    </span>
  </a>
</div>
