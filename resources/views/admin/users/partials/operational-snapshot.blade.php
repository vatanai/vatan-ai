@php
  $operationalUser = $snapshotUser ?? null;
  $lastLoginEvent = $operationalUser?->relationLoaded('lastSuccessfulLogin')
    ? $operationalUser->lastSuccessfulLogin
    : null;
  $lastLoginAt = $lastLoginEvent?->occurred_at ?: $operationalUser?->last_login_at;
  $generatedImagesCount = (int) ($operationalUser?->generated_images_count ?? 0);
  $generatedVideosCount = (int) ($operationalUser?->generated_videos_count ?? 0);
@endphp

@if($operationalUser)
  <div class="admin-user-operational" aria-label="اطلاعات یکپارچه ورود و خروجی‌های کاربر">
    <div class="admin-user-operational-grid">
      <div class="admin-user-operational-item">
        <div class="admin-user-operational-summary-line">
          <span><i class="fa-solid fa-right-to-bracket"></i> تعداد ورود</span>
          <strong>{{ number_format((int) ($operationalUser->login_count ?? 0)) }}</strong>
        </div>
        <div class="admin-user-operational-summary-line">
          <span><i class="fa-regular fa-clock"></i> آخرین ورود</span>
          <strong>{{ \App\Support\Jalali::formatNumeric($lastLoginAt) }}</strong>
        </div>
      </div>
      <a class="admin-user-operational-item admin-user-operational-output" href="{{ route('admin.users.index', ['show_user' => $operationalUser->id]) }}" title="مشاهده خروجی‌های ساخته‌شده کاربر">
        <div class="admin-user-operational-summary-line">
          <span><i class="fa-solid fa-image"></i> تعداد خروجی عکس</span>
          <strong>{{ number_format($generatedImagesCount) }}</strong>
        </div>
        <div class="admin-user-operational-summary-line">
          <span><i class="fa-solid fa-video"></i> تعداد خروجی ویدیو</span>
          <strong>{{ number_format($generatedVideosCount) }}</strong>
        </div>
      </a>
    </div>
  </div>
@else
  <span class="admin-user-operational-missing">کاربر حذف‌شده</span>
@endif
