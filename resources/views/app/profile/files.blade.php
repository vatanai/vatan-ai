{{-- ===== فایل‌ها: حساب، پروفایل چهره و تصاویر ورودی ===== --}}
<div class="profile-panel" data-panel="files" style="display:none;padding:16px;">
  <div class="files-sub-tabs" dir="rtl" role="tablist" aria-label="بخش‌های فایل‌ها">
    <button type="button" class="files-sub-tab active" data-sub="account">حساب و پرداخت‌ها</button>
    <button type="button" class="files-sub-tab" data-sub="face-profiles">پروفایل چهره</button>
    <button type="button" class="files-sub-tab" data-sub="personal">عکس‌های ورودی</button>
  </div>

  @include('app.profile.account')

  @if(!($isGuest ?? false))
    @php
      $storageLimit = max(1, (float) ($storageTotal ?? 100));
      $storageAmount = max(0, (float) ($storageUsed ?? 0));
      $storagePercent = min(100, round(($storageAmount / $storageLimit) * 100, 2));
      $storageAmountLabel = rtrim(rtrim(number_format($storageAmount, 2, '.', ''), '0'), '.');
      $storageFreeLabel = rtrim(rtrim(number_format(max(0, $storageLimit - $storageAmount), 2, '.', ''), '0'), '.');
      $storageLimitLabel = rtrim(rtrim(number_format($storageLimit, 2, '.', ''), '0'), '.');
      $faceProfileCount = ($faceProfiles ?? collect())->count();
      $canCreateFaceProfile = (int) ($faceProfileLimit ?? 0) > $faceProfileCount;
    @endphp

    <div id="files-face-profiles" class="files-sub-panel face-profiles-panel" style="display:none;">
      <div class="storage-card">
        <div class="storage-header">
          <span class="storage-title">فضای ذخیره‌سازی</span>
          <span class="storage-used">{{ $storageAmountLabel }} از {{ $storageLimitLabel }} مگابایت</span>
        </div>
        <div class="storage-bar"><div class="storage-fill" style="width:{{ $storagePercent }}%;"></div></div>
        <div class="storage-footer"><span>{{ $storageAmountLabel }} مگ استفاده شده</span><span class="storage-free">{{ $storageFreeLabel }} مگ آزاد</span></div>
      </div>

      <section class="face-profile-section-head">
        <div><h2>پروفایل‌های چهره</h2><p>یک‌بار عکس‌های مرجع را ذخیره کن و در ساخت‌های بعدی بدون آپلود دوباره از آن استفاده کن.</p></div>
        <span>{{ number_format($faceProfileCount) }} از {{ number_format((int) ($faceProfileLimit ?? 0)) }}</span>
      </section>

      @if(session('success'))<div class="files-feedback files-feedback--success">{{ session('success') }}</div>@endif
      @if($errors->has('face_profile'))<div class="files-feedback files-feedback--error">{{ $errors->first('face_profile') }}</div>@endif

      @if((int) ($faceProfileLimit ?? 0) < 1)
        <div class="face-profile-locked"><i class="fa-solid fa-lock"></i><div><strong>پروفایل چهره در پلن فعلی فعال نیست</strong><span>با ارتقای پلن، عکس‌های مرجع خود را برای استفاده‌های بعدی ذخیره کن.</span></div><a href="{{ route('pricing.index') }}">مشاهده پلن‌ها</a></div>
      @else
        <div class="face-profile-grid">
          @foreach(($faceProfiles ?? collect()) as $profile)
            @php $coverUrl = $profile->coverUrl(); $imageCount = count($profile->referenceImageEntries()); @endphp
            <article class="face-profile-folder">
              <div class="face-profile-folder-cover">
                @if($coverUrl)<img src="{{ $coverUrl }}" alt="{{ $profile->name }}">@else<i class="fa-solid fa-user"></i>@endif
              </div>
              <div class="face-profile-folder-info">
                <strong>{{ $profile->name }}</strong>
                <small>{{ number_format($imageCount) }} تصویر مرجع</small>
                <form method="POST" action="{{ route('profile.face-profiles.destroy', $profile) }}" onsubmit="return confirm('این پروفایل چهره و تصاویر مرجع آن حذف شود؟')">
                  @csrf @method('DELETE')
                  <button type="submit" class="face-profile-delete"><i class="fa-solid fa-trash"></i> حذف</button>
                </form>
              </div>
            </article>
          @endforeach

          @if($canCreateFaceProfile)
            <form method="POST" action="{{ route('profile.face-profiles.store') }}" enctype="multipart/form-data" class="face-profile-create">
              @csrf
              <label><span>نام پروفایل</span><input name="name" maxlength="80" required placeholder="مثلاً پروفایل اصلی"></label>
              <label><span>۱ تا ۳ عکس مرجع</span><input name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required></label>
              <button type="submit"><i class="fa-solid fa-plus"></i> ذخیره پروفایل</button>
            </form>
          @endif
        </div>
      @endif
    </div>

    <div id="files-personal" class="files-sub-panel files-grid" style="display:none;">
      @forelse (($personalImages ?? collect())->filter(fn ($upload) => blank($upload->mime_type) || str_starts_with((string) $upload->mime_type, 'image/')) as $upload)
        @php $personalPath = $upload->file_path ?? ''; @endphp
        <div class="files-cell"><img src="{{ filter_var($personalPath, FILTER_VALIDATE_URL) ? $personalPath : asset('storage/' . ltrim($personalPath, '/')) }}" alt="عکس شخصی آپلودشده" class="grid-img" loading="lazy"></div>
      @empty
        <div class="grid-empty"><p>هنوز عکس ورودی برای ساخت وارد نکردی</p></div>
      @endforelse
    </div>
  @else
    <div id="files-face-profiles" class="files-sub-panel files-guest-message" style="display:none;">برای ساخت و نگهداری پروفایل چهره، ابتدا وارد حساب خود شوید.</div>
    <div id="files-personal" class="files-sub-panel files-guest-message" style="display:none;">برای مشاهده عکس‌های ورودی، ابتدا وارد حساب خود شوید.</div>
  @endif
</div>
