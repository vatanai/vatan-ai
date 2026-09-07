@php
  $faceProfiles = collect($product['face_profiles'] ?? []);
  $profileUrl = $product['profile_url'] ?? route('app.profile', ['tab' => 'files', 'file_tab' => 'face-profiles']);
@endphp

<div class="cw-face-source" data-face-source>
  <input type="hidden" name="face_profile_id" value="" data-face-profile-input>
  <label class="cw-label"><span>منبع چهره</span><small>پروفایل ذخیره‌شده یا عکس جدید</small></label>
  <div class="cw-face-source-picker">
    <button type="button" class="cw-face-source-toggle" data-face-source-toggle aria-expanded="false">
      <span class="cw-face-source-current"><i class="fa-solid fa-camera"></i><b data-face-source-label>عکس جدید</b></span>
      <i class="fa-solid fa-chevron-down"></i>
    </button>
    <div class="cw-face-source-menu" data-face-source-menu hidden>
      <button type="button" class="cw-face-source-option selected" data-face-source-option data-face-profile-id="">
        <i class="fa-solid fa-camera"></i>
        <span><b>عکس جدید</b><small>عکس را برای همین ساخت انتخاب کن</small></span>
        <i class="fa-solid fa-check"></i>
      </button>
      @foreach($faceProfiles as $profile)
        <button type="button" class="cw-face-source-option" data-face-source-option data-face-profile-id="{{ $profile['id'] }}">
          @if(!empty($profile['cover_url']))<img src="{{ $profile['cover_url'] }}" alt="" class="cw-face-source-option__image">@else<i class="fa-solid fa-user"></i>@endif
          <span><b>{{ $profile['name'] }}</b><small>{{ number_format((int) $profile['image_count']) }} تصویر مرجع ذخیره‌شده</small></span>
          <i class="fa-solid fa-check"></i>
        </button>
      @endforeach
      <a href="{{ $profileUrl }}" class="cw-face-source-manage"><i class="fa-solid fa-plus"></i> مدیریت پروفایل‌های چهره</a>
    </div>
  </div>
</div>
