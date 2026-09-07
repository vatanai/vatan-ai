<div class="g-empty">
  <div class="g-empty-icon"><i class="fa-solid {{ $icon ?? 'fa-chart-line' }}"></i></div>
  <h3>{{ $emptyTitle ?? 'هنوز داده‌ای ثبت نشده است' }}</h3>
  <p>{{ $emptyText ?? 'پس از ساخت لینک یا ثبت محتوا، داده‌های واقعی همین‌جا نمایش داده می‌شوند.' }}</p>
  @isset($emptyAction)
    <a class="g-btn g-btn-primary" href="{{ $emptyAction }}"><i class="fa-solid fa-plus"></i>{{ $emptyActionLabel ?? 'شروع کنید' }}</a>
  @endisset
</div>
