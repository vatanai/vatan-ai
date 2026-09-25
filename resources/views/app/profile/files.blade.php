{{-- ===== بخش فایل‌ها: فضای ذخیره‌سازی + کارکتر شیت + محصولات استفاده‌شده ===== --}}
<div class="profile-panel" data-panel="files" style="display:none;">

  @if($isGuest ?? false)
    <div class="grid-empty profile-guest-panel">
      <img src="{{ \App\Support\AppAsset::url('assets/img/icons/fi-sr-file.svg') }}" width="32" height="32" alt="" style="opacity:.4;">
      <p>برای مشاهدهٔ فایل‌های خود وارد حساب شو.</p>
      <a href="{{ route('login', ['redirect' => route('app.profile', ['tab' => 'files'])]) }}" class="btn-empty-cta">ورود به حساب</a>
    </div>
  @else

  @if(session('success'))
    <div class="face-profile-form-success" role="status"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ session('success') }}</div>
  @endif

  @php
    $storageLimit = max(1, (float) ($storageTotal ?? 100));
    $storageAmount = max(0, (float) ($storageUsed ?? 0));
    $storagePercent = min(100, round(($storageAmount / $storageLimit) * 100, 2));
    $storageAmountLabel = rtrim(rtrim(number_format($storageAmount, 2, '.', ''), '0'), '.');
    $storageFreeLabel = rtrim(rtrim(number_format(max(0, $storageLimit - $storageAmount), 2, '.', ''), '0'), '.');
    $storageLimitLabel = rtrim(rtrim(number_format($storageLimit, 2, '.', ''), '0'), '.');
  @endphp

  <div class="storage-card">
    <div class="storage-header">
      <span class="storage-title">فضای ذخیره‌سازی</span>
      <span class="storage-used">{{ $storageAmountLabel }} از {{ $storageLimitLabel }} مگابایت</span>
    </div>
    <div class="storage-bar">
      <div class="storage-fill" style="width:{{ $storagePercent }}%;"></div>
    </div>
    <div class="storage-footer">
      <span>{{ $storageAmountLabel }} مگ استفاده شده</span>
      <span class="storage-free">{{ $storageFreeLabel }} مگ آزاد</span>
    </div>
  </div>

  <div class="files-sub-tabs" dir="rtl">
    <button type="button" class="files-sub-tab active" data-sub="face-profiles"><i class="fa-solid fa-user" aria-hidden="true"></i> کارکتر چهره شما</button>
    <button type="button" class="files-sub-tab" data-sub="used-products">محصولات استفاده شده</button>
  </div>

  <div id="files-face-profiles" class="face-profiles-panel">
    <div class="face-profiles-intro">
      <span class="face-profiles-intro__icon"><i class="fa-solid fa-user-astronaut" aria-hidden="true"></i></span>
      <div>
        <span class="face-profiles-intro__kicker">مرجع ثابت برای ساخت‌های بعدی</span>
        <h2>کارکتر چهره‌ات را یک‌بار بساز</h2>
        <p>با ذخیره‌ی چند تصویر مرجع، لازم نیست در هر ساخت دوباره عکس آپلود کنی. پروفایل چهره کمک می‌کند هویت تصویری شخصیتت در پرتره‌ها، تصاویر و پروژه‌های مختلف هماهنگ‌تر بماند.</p>
      </div>
      <a class="face-profiles-intro__cta" href="{{ route('landing.character-sheet') }}">آشنایی با کارکتر شیت <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a>
    </div>

    <div class="face-profile-create-card">
      <div class="face-profile-create-card__head">
        <div><span class="face-profile-create-card__kicker">پروفایل جدید</span><h3>مرجع چهره‌ات را ذخیره کن</h3></div>
        <span class="face-profile-limit">{{ number_format($faceProfiles->count()) }} از {{ number_format(auth()->user()->faceProfileLimit()) }} پروفایل مجاز</span>
      </div>
      @if(auth()->user()->faceProfileLimit() > $faceProfiles->count())
        <form action="{{ route('profile.face-profiles.store') }}" method="POST" enctype="multipart/form-data" class="face-profile-form">
          @csrf
          <label><span>نام پروفایل</span><input type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="مثلاً: کارکتر اصلی من" required></label>
          <label><span>تصاویر مرجع <small>۱ تا ۳ تصویر واضح</small></span><input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required></label>
          <button type="submit"><i class="fa-solid fa-plus" aria-hidden="true"></i> ذخیره پروفایل</button>
        </form>
        @if($errors->has('face_profile') || $errors->has('images') || $errors->has('images.0') || $errors->has('name'))
          <div class="face-profile-form-error">{{ $errors->first('face_profile') ?: $errors->first('images') ?: $errors->first('images.0') ?: $errors->first('name') }}</div>
        @endif
      @else
        <div class="face-profile-form-error">سقف پروفایل چهره‌ی پلن شما تکمیل شده است.</div>
      @endif
    </div>

    <div class="face-profile-grid">
      @forelse($faceProfiles as $faceProfile)
        @php $faceProfileCover = $faceProfile->coverUrl(); @endphp
        <article class="face-profile-card">
          <div class="face-profile-card__media">
            @if($faceProfileCover)<img src="{{ $faceProfileCover }}" alt="تصویر مرجع {{ $faceProfile->name }}" loading="lazy" decoding="async">@else<span><i class="fa-solid fa-user" aria-hidden="true"></i></span>@endif
            <span class="face-profile-card__badge"><i class="fa-solid fa-check" aria-hidden="true"></i> آماده استفاده</span>
          </div>
          <div class="face-profile-card__body">
            <form action="{{ route('profile.face-profiles.update', $faceProfile) }}" method="POST" class="face-profile-rename-form">
              @csrf
              @method('PATCH')
              <input type="text" name="name" value="{{ $faceProfile->name }}" maxlength="80" aria-label="نام پروفایل {{ $faceProfile->name }}" required>
              <button type="submit" title="ذخیره نام" aria-label="ذخیره نام"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
            </form>
            <span>{{ number_format(count($faceProfile->referenceImageEntries())) }} تصویر مرجع</span>
          </div>
          <form action="{{ route('profile.face-profiles.destroy', $faceProfile) }}" method="POST" class="face-profile-delete-form" onsubmit="return confirm('این پروفایل چهره حذف شود؟');">
            @csrf
            @method('DELETE')
            <button type="submit"><i class="fa-solid fa-trash-can" aria-hidden="true"></i> حذف</button>
          </form>
        </article>
      @empty
        <div class="face-profile-empty"><span><i class="fa-solid fa-sparkles" aria-hidden="true"></i></span><strong>هنوز کارکتر چهره‌ای نساختی</strong><p>اولین پروفایل چهره‌ات را بساز تا در ساخت‌های بعدی سریع‌تر شروع کنی.</p><a href="{{ route('landing.character-sheet') }}">شروع با کارکتر شیت <i class="fa-solid fa-arrow-left" aria-hidden="true"></i></a></div>
      @endforelse
    </div>
  </div>

  <div id="files-used-products" class="files-grid files-grid--products" style="display:none;">
    @forelse (($usedProducts ?? collect()) as $product)
      <a href="{{ route('app.product', $product->route_slug) }}" class="files-cell files-product-cell">
        <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa ?: $product->name_en }}" class="grid-img" loading="lazy" decoding="async">
        <span class="files-product-name">{{ $product->name_fa ?: $product->name_en }}</span>
      </a>
    @empty
      <div class="grid-empty"><p>هنوز محصولی برای ساخت استفاده نکردی</p></div>
    @endforelse
  </div>

  @endif

</div>
