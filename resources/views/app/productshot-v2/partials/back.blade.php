{{--
  فلش برگشت یکسان صفحه‌های پروداکتی (صفحه‌ی محصول /app/pro و صفحه‌ی ساخت /app/crepro).
  همیشه: فلش راست، گوشه‌ی بالا-راست، یک اندازه و یک ظاهر (استایل در css/productshot-common.css).
  اگر کاربر از داخل سایت آمده باشد، به همان صفحه‌ی قبلی برمی‌گردد؛ وگرنه به $href.
  پارامترها: $href (مقصد پیش‌فرض)، $label (متن دسترسی‌پذیری)، $class (کلاس اضافه، اختیاری)
--}}
<a class="psb-back {{ $class ?? '' }}" href="{{ $href }}" data-psb-back aria-label="{{ $label ?? 'برگشت' }}" title="{{ $label ?? 'برگشت' }}">
  <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
</a>
@once
  @push('scripts')
    <script>
      (function () {
        document.addEventListener('click', function (e) {
          var link = e.target.closest && e.target.closest('[data-psb-back]');
          if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.button > 0) return;
          var ref = document.referrer;
          var sameSite = false;
          try { sameSite = !!ref && new URL(ref).origin === window.location.origin && ref !== window.location.href; } catch (err) {}
          if (sameSite && window.history.length > 1) {
            e.preventDefault();
            window.history.back();
          }
        });
      })();
    </script>
  @endpush
@endonce
