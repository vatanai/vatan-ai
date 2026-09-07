@php
  $operationalUser = $snapshotUser ?? null;
  $lastLoginEvent = $operationalUser?->relationLoaded('lastSuccessfulLogin')
    ? $operationalUser->lastSuccessfulLogin
    : null;
  $loginPhone = $lastLoginEvent?->phone ?: $operationalUser?->phone;
  $lastLoginAt = $lastLoginEvent?->occurred_at ?: $operationalUser?->last_login_at;
  $generatedImagesCount = (int) ($operationalUser?->generated_images_count ?? 0);
@endphp

@if($operationalUser)
  <div class="admin-user-operational" aria-label="اطلاعات یکپارچه ورود و خروجی‌های کاربر">
    <div class="admin-user-operational-grid">
      <div class="admin-user-operational-item">
        <span><i class="fa-solid fa-mobile-screen-button"></i> شماره ورود</span>
        <strong dir="ltr">{{ $loginPhone ?: '—' }}</strong>
      </div>
      <div class="admin-user-operational-item">
        <span><i class="fa-regular fa-clock"></i> آخرین ورود</span>
        <strong>{{ \App\Support\Jalali::formatNumeric($lastLoginAt) }}</strong>
      </div>
      <div class="admin-user-operational-item">
        <span><i class="fa-solid fa-right-to-bracket"></i> تعداد ورود</span>
        <strong>{{ number_format((int) ($operationalUser->login_count ?? 0)) }}</strong>
      </div>
      <a class="admin-user-operational-item admin-user-operational-output" href="{{ route('admin.users.index', ['show_user' => $operationalUser->id]) }}" title="مشاهده خروجی‌های ساخته‌شده کاربر">
        <span><i class="fa-solid fa-images"></i> خروجی ساخته‌شده</span>
        <strong>{{ number_format($generatedImagesCount) }}</strong>
      </a>
    </div>
  </div>
@else
  <span class="admin-user-operational-missing">کاربر حذف‌شده</span>
@endif
