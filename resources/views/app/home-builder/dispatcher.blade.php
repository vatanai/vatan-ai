{{--
  دیسپچر رندر Section — بر اساس نوع Section، partial متناظر را در sections/ صدا می‌زند
  و کلاس‌های نمایش/عدم‌نمایش هر دستگاه (تنظیمات Responsive هر Section) را اعمال می‌کند.
  افزودن نوع Section جدید در آینده فقط با ساخت یک partial هم‌نام در sections/ ممکن می‌شود،
  بدون نیاز به تغییر این فایل.
--}}
@php
  $section = $item['section'];
  $visibilityClasses = collect(['desktop', 'tablet', 'mobile'])
    ->reject(fn ($device) => $section->isVisibleOn($device))
    ->map(fn ($device) => 'hb-hide-' . $device)
    ->implode(' ');
@endphp
<div class="hb-section {{ $visibilityClasses }}" data-section-type="{{ $section->type }}" data-section-id="{{ $section->id }}">
  @includeIf('app.home-builder.sections.' . $section->type, $item)
</div>
