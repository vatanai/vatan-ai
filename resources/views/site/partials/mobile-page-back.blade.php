@php($mobileBackFallback = $mobileBackFallback ?? route('app.home'))

<button
  type="button"
  class="mobile-page-back"
  data-mobile-page-back
  data-fallback-url="{{ $mobileBackFallback }}"
  aria-label="بازگشت به صفحه قبل"
  title="بازگشت به صفحه قبل"
>
  <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
</button>
