@php
  [$appFooterYear] = \App\Support\Jalali::toJalaliYmd(
      (int) now()->format('Y'),
      (int) now()->format('n'),
      (int) now()->format('j')
  );
  $appFooterYear = \App\Support\Jalali::toPersianDigits((string) $appFooterYear);
  $adminDashboardVersion = null;
  $versionFilePath = base_path('VERSION');
  if (is_file($versionFilePath)) {
      $versionFileContent = file_get_contents($versionFilePath);
      if (preg_match('/ورژن داشبورد\s*:\s*(\d+)/u', $versionFileContent, $versionMatch)) {
          $adminDashboardVersion = $versionMatch[1];
      }
  }
@endphp

{{--
  کامپوننت ثابت فوتر اپ
  قرارداد پروژه: ساختار، آیتم‌ها و کلاس‌های این پارشیال فقط با درخواست صریح کاربر تغییر کنند.
  استایل مستقل: public/css/app-footer.css
--}}
<footer class="app-footer" aria-label="اطلاعات و پشتیبانی وطن" dir="rtl">
  <div class="app-footer__inner">
    <div class="app-footer__identity">
      <a href="{{ route('app.home') }}" class="app-footer__brand" aria-label="وطن — صفحه اصلی اپ">
        <img class="app-footer__brand-icon" src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="24" height="24">
        <img class="app-footer__brand-wordmark" src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن" width="55" height="24">
      </a>
      <span class="app-footer__copy">© {{ $appFooterYear }}، تمام حقوق محفوظ است.</span>
      @if($adminDashboardVersion)
        <span class="app-footer__version" title="نسخه داشبورد">V.{{ $adminDashboardVersion }}</span>
      @endif
    </div>

    <nav class="app-footer__links" aria-label="لینک‌های فوتر اپ">
      <a href="{{ route('site.home.root') }}#faq">راهنما و سوالات</a>
      <span class="app-footer__dot" aria-hidden="true"></span>
      <a href="{{ route('pricing.index') }}">تعرفه‌ها</a>
      <span class="app-footer__dot" aria-hidden="true"></span>
      <span class="app-footer__pending" title="بزودی" aria-label="قوانین — بزودی">قوانین</span>
      <span class="app-footer__dot" aria-hidden="true"></span>
      <a href="{{ route('privacy') }}">حریم خصوصی</a>
      <span class="app-footer__dot" aria-hidden="true"></span>
      <a href="https://t.me/vatanstudio_bot" target="_blank" rel="noopener noreferrer">پشتیبانی</a>
    </nav>
  </div>
</footer>
