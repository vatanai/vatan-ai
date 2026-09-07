@extends('layouts.app')

@section('page_title', 'ساخت ' . ($product->name_fa ?: $product->name_en) . ' | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-samples-workspace.css') }}?v={{ filemtime(public_path('css/create-samples-workspace.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/create-video-product.css') }}?v={{ filemtime(public_path('css/create-video-product.css')) }}">
@endpush

@section('content')
@php
  $video = $buildProduct['video'];
  $workflow = $video['workflow'];
  $needsImage = $workflow === 'image_to_video';
  $needsVideo = $workflow === 'video_to_video';
  $faceEnabled = ($video['face_profile_mode'] ?? 'disabled') !== 'disabled';
  $durationCosts = (array) ($video['credit_costs_by_duration'] ?? []);
  $videoClientConfig = [
      'duration_costs' => $durationCosts,
      'base_cost' => (int) $buildProduct['cost'],
      'quality_costs' => (array) ($video['quality_costs'] ?? ['480p'=>0,'580p'=>1,'720p'=>2,'1080p'=>5,'4K'=>10]),
      'balance' => (int) (auth()->user()?->tokens ?? 0),
      'workflow' => $workflow,
      'face_mode' => $video['face_profile_mode'] ?? 'disabled',
      'status_url_template' => $video['status_url_template'],
  ];
@endphp
<div class="vv-page" dir="rtl" data-video-workspace>
  <button type="button" class="vv-close" data-video-close aria-label="برگشت"><i class="fa-solid fa-xmark"></i></button>

  <div class="vv-shell">
    <form class="vv-controls" action="{{ $buildProduct['generate_url'] }}" method="post" enctype="multipart/form-data"
          data-video-form data-workflow="{{ $workflow }}" data-authenticated="{{ $buildProduct['is_authenticated'] ? '1' : '0' }}"
          data-login-url="{{ $buildProduct['login_url'] }}">
      @csrf
      <header class="vv-product-head">
        <img src="{{ $buildProduct['cover'] }}" alt="{{ $buildProduct['name'] }}">
        <div>
          <span><i class="fa-solid fa-film"></i> استودیو ویدیو</span>
          <h1>{{ $buildProduct['name'] }}</h1>
          <p>{{ $buildProduct['estimated_time'] }}</p>
        </div>
      </header>

      <div class="vv-form-scroll">
        @if($faceEnabled)
          @include('app.partials.face-source-selector', ['product' => $buildProduct])
        @endif

        @if($needsImage)
          <section class="vv-section" data-source-section="image">
            <div class="vv-section-title"><strong>تصویر شروع</strong><small>تصویر واضح، بدون تاری و با نور مناسب</small></div>
            <label class="vv-drop" data-source-drop>
              <input type="file" name="source_image" accept="image/jpeg,image/png,image/webp,image/avif" data-source-image>
              <span class="vv-drop-icon"><i class="fa-solid fa-image"></i></span>
              <span><b>انتخاب عکس جدید</b><small>حداکثر ۱۲ مگابایت</small></span>
              <img alt="پیش‌نمایش تصویر ورودی" data-source-preview hidden>
            </label>
            <div class="vv-profile-selected" data-profile-selected hidden><i class="fa-solid fa-circle-check"></i><span>تصاویر پروفایل چهره به‌عنوان مرجع استفاده می‌شوند.</span></div>
          </section>
        @elseif($needsVideo)
          <section class="vv-section" data-source-section="video">
            <div class="vv-section-title"><strong>ویدیوی ورودی</strong><small>فایل کوتاه با حرکت واضح نتیجه بهتری می‌دهد</small></div>
            <label class="vv-drop">
              <input type="file" name="source_video" accept="video/mp4,video/webm,video/quicktime" data-source-video>
              <span class="vv-drop-icon"><i class="fa-solid fa-video"></i></span>
              <span><b>انتخاب ویدیوی ورودی</b><small>حداکثر ۱۰۰ مگابایت</small></span>
              <video muted playsinline controls data-source-video-preview hidden></video>
            </label>
          </section>
        @endif

        <section class="vv-section">
          <label class="vv-section-title" for="vv-prompt"><strong>شرح صحنه و حرکت</strong><small>سوژه، محیط، حرکت و حال‌وهوا را کوتاه و روشن بنویسید</small></label>
          <textarea id="vv-prompt" name="prompt" class="cw-input vv-prompt" rows="4" placeholder="مثلاً دوربین آرام به سوژه نزدیک شود، نور طلایی غروب و حرکت طبیعی لباس..." maxlength="5000"></textarea>
          <button type="button" class="vv-enhance" data-prompt-example><i class="fa-solid fa-wand-magic-sparkles"></i> پیشنهاد یک دستور استاندارد</button>
        </section>

        @if(!empty($buildProduct['fields']))
          <section class="vv-section vv-schema-fields">
            <div class="vv-section-title"><strong>ویژگی‌های اختصاصی</strong><small>این گزینه‌ها برای همین محصول تعریف شده‌اند</small></div>
            @foreach($buildProduct['fields'] as $field)
              @include('app.partials.create-field', ['field' => $field, 'instance' => 'video'])
            @endforeach
          </section>
        @endif

        @if(!empty($video['motion_presets']))
          <section class="vv-section">
            <div class="vv-section-title"><strong>حرکت دوربین</strong><small>الگوی حرکتی مناسب روایت را انتخاب کنید</small></div>
            <div class="vv-motion-grid">
              @foreach($video['motion_presets'] as $preset)
                <label>
                  <input type="radio" name="video[motion_preset]" value="{{ $preset['key'] }}" @checked($loop->first)>
                  <span><i class="fa-solid {{ $preset['icon'] ?? 'fa-video' }}"></i><b>{{ $preset['label'] }}</b><small>{{ $preset['description'] ?? '' }}</small></span>
                </label>
              @endforeach
            </div>
          </section>
        @endif

        <section class="vv-section vv-output-settings">
          <div class="vv-section-title"><strong>تنظیمات خروجی</strong><small>هزینه پیش از ساخت به‌روز می‌شود</small></div>

          <div class="vv-option-group">
            <span>مدت ویدیو</span>
            <div class="vv-chips">
              @foreach($video['durations'] as $duration)
                <label><input type="radio" name="video[duration]" value="{{ $duration }}" @checked((int) $duration === (int) $video['default_duration'])><span>{{ $duration }} ثانیه</span></label>
              @endforeach
            </div>
          </div>

          <div class="vv-option-group">
            <span>نسبت تصویر</span>
            <div class="vv-chips">
              @foreach($video['aspect_ratios'] as $ratio)
                <label><input type="radio" name="video[aspect_ratio]" value="{{ $ratio }}" @checked($ratio === $video['default_aspect_ratio'])><span>{{ $ratio }}</span></label>
              @endforeach
            </div>
          </div>

          <div class="vv-option-group">
            <span>کیفیت</span>
            <div class="vv-chips">
              @foreach($video['resolutions'] as $resolution)
                <label><input type="radio" name="video[resolution]" value="{{ $resolution }}" @checked($resolution === $video['default_resolution'])><span>{{ $resolution }}</span></label>
              @endforeach
            </div>
          </div>

          @if($video['audio_allowed'] ?? false)
            <input type="hidden" name="video[generate_audio]" value="0">
            <label class="vv-switch"><span><b>تولید صدای هماهنگ</b><small>در صورت پشتیبانی مدل، صدا همراه ویدیو ساخته می‌شود</small></span><input type="checkbox" name="video[generate_audio]" value="1" @checked($video['audio_default'] ?? false)><i></i></label>
          @endif
        </section>

        <div class="vv-error" data-video-error hidden></div>
      </div>

      <footer class="vv-submit-wrap">
        <div><span>هزینه قطعی پیش از ساخت</span><strong><b data-video-cost>{{ number_format((int) $buildProduct['cost']) }}</b> اعتبار</strong><small data-video-balance></small></div>
        <button type="submit" class="vv-generate"><i class="fa-solid fa-clapperboard"></i><span>ساخت ویدیو</span></button>
      </footer>
    </form>

    <section class="vv-stage" aria-live="polite">
      <header>
        <div><i class="fa-solid fa-play"></i><strong>پیش‌نمایش و خروجی</strong></div>
        <span data-video-status>آماده ساخت</span>
      </header>
      <div class="vv-canvas" data-video-canvas>
        @if(!empty($video['preview_url']))
          <video src="{{ $video['preview_url'] }}" autoplay muted loop playsinline controls data-product-preview></video>
        @else
          <img src="{{ $buildProduct['cover'] }}" alt="{{ $buildProduct['name'] }}" data-product-preview>
        @endif
        <div class="vv-result-placeholder" data-result-placeholder>
          <span><i class="fa-solid fa-wand-magic-sparkles"></i></span>
          <strong>ویدیوی شما اینجا نمایش داده می‌شود</strong>
          <p>تنظیمات را کامل کنید و «ساخت ویدیو» را بزنید.</p>
        </div>
        <div class="vv-processing" data-video-processing hidden>
          <span class="vv-loader"></span>
          <strong>در حال ساخت ویدیو</strong>
          <p data-processing-message>درخواست در صف پردازش قرار گرفت.</p>
          <div><i></i></div>
          <small>می‌توانید این صفحه را باز نگه دارید؛ نتیجه خودکار ظاهر می‌شود.</small>
        </div>
        <video controls playsinline data-result-video hidden></video>
      </div>
      <footer>
        <span><i class="fa-solid fa-shield-halved"></i> پردازش امن فایل ورودی</span>
        <a href="{{ route('app.profile', ['tab' => 'files']) }}"><i class="fa-regular fa-folder-open"></i> خروجی‌های من</a>
      </footer>
    </section>
  </div>

  <script type="application/json" data-video-config>@json($videoClientConfig)</script>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/create-video-product.js') }}?v={{ filemtime(public_path('js/create-video-product.js')) }}"></script>
@endpush
