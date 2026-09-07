<div class="g-page-head">
  <div>
    <div class="g-eyebrow">مرکز رشد وطن</div>
    <h1>{{ $heading ?? $title }}</h1>
    <p>{{ $subtitle ?? 'پایش یکپارچه مسیر جذب، تبدیل و بازگشت کاربران در کانال‌های مختلف' }}</p>
  </div>
  @isset($actions)
    <div class="g-actions">{!! $actions !!}</div>
  @endisset
</div>
