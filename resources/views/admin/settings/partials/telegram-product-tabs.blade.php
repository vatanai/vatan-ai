{{-- سربرگ‌های تنظیمات بات ثبت محصول: عمومی/پرتره و پروداکتی --}}
@php($tpsActive = $active ?? 'general')
<nav class="tps-tabs" aria-label="بخش‌های تنظیمات بات ثبت محصول">
  <a href="{{ route('admin.settings.telegram.product-bot') }}" class="tps-tab {{ $tpsActive === 'general' ? 'is-active' : '' }}" @if($tpsActive === 'general') aria-current="page" @endif>
    <i class="fa-solid fa-user"></i><span>عمومی و پرتره</span>
  </a>
  <a href="{{ route('admin.settings.telegram.product-bot.productshot') }}" class="tps-tab {{ $tpsActive === 'productshot' ? 'is-active' : '' }}" @if($tpsActive === 'productshot') aria-current="page" @endif>
    <i class="fa-solid fa-box-open"></i><span>پروداکتی</span>
  </a>
</nav>
