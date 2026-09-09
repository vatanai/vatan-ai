{{-- ===== بخش فایل‌ها: فضای ذخیره‌سازی + خلق‌شده + عکس‌های ورودی ===== --}}
<div class="profile-panel" data-panel="files" style="display:none;padding:16px;">

  @if($isGuest ?? false)
    <div class="files-guest-message">برای مشاهده فایل‌های خود، لطفاً وارد سایت شوید.</div>
  @else

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
    <button type="button" class="files-sub-tab active" data-sub="created">خلق شده</button>
    <button type="button" class="files-sub-tab" data-sub="personal">عکس‌های ورودی</button>
    <button type="button" class="files-sub-tab" data-sub="gallery-inputs">ورودی‌های آزمایش</button>
  </div>

  <div id="files-created" class="files-grid">
    @forelse ($createdImages ?? [] as $item)
      @php $createdPath = $item->image_path ?? ''; @endphp
      <div class="files-cell"><img src="{{ filter_var($createdPath, FILTER_VALIDATE_URL) ? $createdPath : asset('storage/' . ltrim($createdPath, '/')) }}" alt="تصویر خلق‌شده" class="grid-img" loading="lazy" decoding="async"></div>
    @empty
      <div class="grid-empty"><p>هنوز تصویری خلق نکردی</p></div>
    @endforelse
  </div>

  <div id="files-personal" class="files-grid" style="display:none;">
    @forelse (($personalImages ?? collect())->filter(fn ($upload) => blank($upload->mime_type) || str_starts_with((string) $upload->mime_type, 'image/')) as $upload)
      @php $personalPath = $upload->file_path ?? ''; @endphp
      <div class="files-cell"><img src="{{ filter_var($personalPath, FILTER_VALIDATE_URL) ? $personalPath : asset('storage/' . ltrim($personalPath, '/')) }}" alt="عکس شخصی آپلودشده" class="grid-img" loading="lazy" decoding="async"></div>
    @empty
      <div class="grid-empty"><p>هنوز عکس ورودی برای ساخت وارد نکردی</p></div>
    @endforelse
  </div>

  <div id="files-gallery-inputs" class="files-grid" style="display:none;">
    @forelse (($galleryItems ?? collect()) as $item)
      @php($galleryMime = strtolower((string) $item->mime_type))
      @if (str_starts_with($galleryMime, 'image/'))
        <a href="{{ route('profile.gallery.original', $item) }}" target="_blank" rel="noopener" class="files-cell">
          <img src="{{ route('profile.gallery.preview', $item) }}" alt="عکس ورودی آزمایش" class="grid-img" loading="lazy">
        </a>
      @elseif (str_starts_with($galleryMime, 'video/'))
        <div class="files-cell"><video src="{{ route('profile.gallery.original', $item) }}" controls preload="none" style="width:100%;height:100%;object-fit:contain;"></video></div>
      @else
        <div class="files-cell" style="padding:14px;line-height:2;white-space:pre-wrap;overflow:auto;">{{ $item->display_text ?? data_get($item->metadata, 'text', 'متن ورودی') }}</div>
      @endif
    @empty
      <div class="grid-empty"><p>هنوز ورودی‌ای از آزمایش‌ها در گالری ثبت نشده است</p></div>
    @endforelse
  </div>

  @endif

</div>
