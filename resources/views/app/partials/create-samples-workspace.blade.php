@php
  $instance = $instance ?? 'default';
  $formId = 'createPreviewForm-' . $instance;
  $discountId = 'cw-discount-code-' . $instance;
  $fields = collect($product['fields'] ?? []);
  $uploadFields = $fields->filter(fn ($field) => in_array($field['type'] ?? '', ['image_upload', 'multi_image', 'file_upload'], true));
  $primaryUpload = $uploadFields->first();
  $primaryUploadId = $primaryUpload['id'] ?? 'stage';
  $primaryUploadIsMultiple = ($primaryUpload['type'] ?? '') === 'multi_image';
  $primaryUploadMaxFiles = min(3, max(1, (int) ($primaryUpload['max_files'] ?? 1)));
  // در نسخه‌ی بازطراحی‌شده، محصولاتی که حفظ هویت دارند بدون تصویر ورودی
  // قابل ساخت نیستند؛ این الزام باید قبل از ارسال فرم هم برای کاربر روشن باشد.
  $primaryUploadRequired = (bool) ($primaryUpload['required'] ?? false)
    || ($instance === 'redesign' && !empty($product['identity']['available']));
  $redesignFields = $fields->reject(fn ($field) => in_array($field['type'] ?? '', ['image_upload', 'multi_image', 'file_upload', 'info', 'section', 'divider', 'aspect_ratio', 'resolution'], true))->values();
  // gender مثل radio یک ورودی پایه و تک‌انتخابی است. نبودن آن در این فهرست
  // باعث می‌شد تنظیمات و پرامپت‌های زن/مرد ذخیره شوند اما در صفحه ساخت دیده نشوند.
  $basicTypes = ['info','section','divider','image_upload','multi_image','textarea','prompt','text','number','radio','gender','select','multi_select','button_group'];
  $outputTypes = ['strength','slider','color','switch','checkbox','style_preset'];
  $advancedTypes = ['negative_prompt','seed','file_upload'];
  $ratioTitles = ['3:4' => 'عمودی', '4:3' => 'افقی', '1:1' => 'مربع', '4:5' => 'عمودی', '9:16' => 'عمودی', '16:9' => 'افقی', '2:3' => 'عمودی', '3:2' => 'افقی'];
  $formatRatio = static fn (string $ratio): string => strtr($ratio, [
    '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
    '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
  ]);
  $outputRatios = array_values(array_intersect(
    \App\Models\Product::supportedAspectRatios(),
    array_map('strval', (array) ($product['output_aspect_ratios'] ?? []))
  ));
  $supportedOutputResolutions = \App\Models\Product::supportedOutputResolutions();
  $outputResolutions = array_values(array_intersect(
    $supportedOutputResolutions,
    array_map('strval', (array) ($product['output_resolutions'] ?? \App\Models\Product::DEFAULT_OUTPUT_RESOLUTIONS))
  ));
  $outputResolutions = $outputResolutions ?: \App\Models\Product::DEFAULT_OUTPUT_RESOLUTIONS;
  $defaultRatio = in_array('3:4', $outputRatios, true)
    ? '3:4'
    : (string) ($product['default_output_aspect_ratio'] ?? ($outputRatios[0] ?? '3:4'));
  $defaultResolution = in_array((string) ($product['default_output_resolution'] ?? ''), $outputResolutions, true)
    ? (string) $product['default_output_resolution']
    : ($outputResolutions[0] ?? '720');
  $mainQualityOptions = array_values(array_filter((array) ($product['main_quality_options'] ?? []), fn ($quality) => is_array($quality) && !empty($quality['key'])));
  $defaultMainQuality = collect($mainQualityOptions)->firstWhere('key', 'standard') ?: ($mainQualityOptions[0] ?? [
    'key' => 'standard', 'name' => 'استاندارد', 'description' => 'متعادل برای ساخت روزمره', 'credits' => 10, 'grade' => 3, 'display_grade' => 3, 'available' => true,
  ]);
  $showOutputQualitySelector = (bool) ($product['show_output_quality_selector'] ?? false);
  $relatedVideoProducts = array_values(array_filter((array) ($product['related_video_products'] ?? [])));
  $isSampleOnly = (bool) ($previewMode ?? false);
  $loaderDemo = app()->environment('local') && request()->boolean('loader_demo');
  $resultDemo = app()->environment('local') && request()->boolean('result_demo');
@endphp

<div class="cw-page" dir="rtl" data-instance="{{ $instance }}" data-sample-only="{{ $isSampleOnly ? '1' : '0' }}" data-loader-demo="{{ $loaderDemo ? '1' : '0' }}" data-result-demo="{{ $resultDemo ? '1' : '0' }}" data-generate-url="{{ $isSampleOnly ? '' : ($product['generate_url'] ?? '') }}" data-download-track-url="{{ $isSampleOnly ? '' : ($product['download_track_url'] ?? '') }}" data-login-url="{{ $product['login_url'] ?? route('login', ['redirect' => request()->fullUrl()]) }}" data-authenticated="{{ ($product['is_authenticated'] ?? false) ? '1' : '0' }}" data-preview="{{ $isSampleOnly ? '1' : '0' }}" data-default-output-quality="{{ $defaultResolution }}" data-default-output-aspect-ratio="{{ $defaultRatio }}">
  @if($instance !== 'redesign')
  <div class="cw-topbar page-container">
    <div class="cw-title-wrap">
      <a href="{{ route('app.home') }}" class="cw-icon-btn" aria-label="بازگشت"><i class="fa-solid fa-arrow-right"></i></a>
      <div class="cw-product-thumb"><img src="{{ $product['cover'] }}" alt=""></div>
      <div>
        <div class="cw-eyebrow"><span class="cw-live-dot"></span> استودیوی ساخت</div>
        <h1>{{ $product['name'] }}</h1>
      </div>
    </div>
    <div class="cw-top-actions">
      @if($previewMode ?? false)<span class="cw-preview-badge">نسخه آزمایشی دسکتاپ</span>@endif
    </div>
  </div>
  @endif

  <div class="cw-workspace page-container" data-default-main-quality="{{ $defaultMainQuality['key'] }}">
    <aside class="cw-panel cw-controls">
      @if($instance === 'redesign')
        <div class="cw-redesign-product-head">
          <div class="cw-product-thumb"><img src="{{ $product['cover'] }}" alt=""></div>
          <div class="cw-redesign-product-copy">
            <h1>{{ $product['name'] }}</h1>
            <span class="cw-redesign-time">{{ $product['estimated_time'] }}</span>
            <strong class="cw-redesign-cost"><i class="fa-solid fa-bolt"></i> <b data-cost>{{ $defaultMainQuality['credits'] }}</b> اعتبار</strong>
            <span class="cw-redesign-time"><i class="fa-solid fa-layer-group"></i> ساخت با کیفیت <b data-build-quality-name>{{ $defaultMainQuality['name'] }}</b> · گرید <b data-build-quality-grade>{{ $defaultMainQuality['display_grade'] ?? $defaultMainQuality['grade'] }}</b></span>
          </div>
        </div>
      @endif
      <div class="cw-panel-head">
        <div><strong>تنظیمات ساخت</strong><span>ورودی‌ها را برای نتیجه دلخواه تنظیم کنید</span></div>
        <span class="cw-step">۱ از ۲</span>
      </div>

      @if($instance !== 'redesign')
        <div class="cw-tabs" role="tablist">
          <button type="button" class="active" data-tab="basic">ورودی‌ها</button>
          <button type="button" data-tab="output">خروجی</button>
          <button type="button" data-tab="advanced">پیشرفته</button>
        </div>
      @endif

      <form class="cw-form" id="{{ $formId }}" enctype="multipart/form-data">
        @if($instance === 'redesign')
          @if(!empty($product['identity']['available']))
            {{-- در طراحی جدید حفظ هویت بخشی از رفتار اصلی محصول است و باید
                 همراه فرم به بک‌اند منتقل شود، حتی اگر کنترل جداگانه نداشته باشد. --}}
            <input type="hidden" name="identity_preservation" value="1">
          @endif
          <div class="cw-tab-panel active" data-panel="basic">
            <div class="cw-mobile-output-row">
              @if($showOutputQualitySelector)
                <div class="cw-field cw-main-quality-field" data-output-options>
                  <label class="cw-label"><span>کیفیت خروجی مدل</span><small>کیفیت مورد نظر را انتخاب کنید</small></label>
                  <div class="cw-main-quality-grid" role="radiogroup" aria-label="کیفیت خروجی مدل">
                    @foreach($mainQualityOptions as $qualityOption)
                      @php
                        $qualityAvailable = (bool) ($qualityOption['available'] ?? false);
                        $qualitySelected = ($qualityOption['key'] ?? '') === ($defaultMainQuality['key'] ?? 'standard');
                        $qualityGrade = (int) ($qualityOption['display_grade'] ?? $qualityOption['grade'] ?? 0);
                      @endphp
                      <label class="cw-main-quality-option {{ !$qualityAvailable ? 'is-locked' : '' }}">
                        <input type="radio" name="output[main_quality]" value="{{ $qualityOption['key'] }}" data-main-quality data-credit-cost="{{ (int) ($qualityOption['credits'] ?? 0) }}" data-quality-name="{{ $qualityOption['name'] }}" data-quality-grade="{{ $qualityGrade }}" @checked($qualitySelected) @disabled(!$qualityAvailable)>
                        <span>
                          <b>{{ $qualityOption['name'] }}</b>
                          <em><i class="fa-solid fa-bolt"></i> {{ number_format((int) ($qualityOption['credits'] ?? 0)) }} اعتبار</em>
                          @if(!$qualityAvailable)<strong><i class="fa-solid fa-lock"></i> نیازمند پلن اعتباری</strong>@endif
                        </span>
                      </label>
                    @endforeach
                  </div>
                </div>
              @else
                {{-- وقتی مدیر انتخاب کیفیت را فعال نکرده، ساخت همیشه با استاندارد آغاز می‌شود. --}}
                <input type="hidden" name="output[main_quality]" value="standard" data-main-quality data-credit-cost="{{ (int) ($defaultMainQuality['credits'] ?? 12) }}" data-quality-name="استاندارد" data-quality-grade="{{ (int) ($defaultMainQuality['display_grade'] ?? $defaultMainQuality['grade'] ?? 3) }}">
              @endif
              @if(count($outputRatios))
                <div class="cw-field cw-output-options" data-output-options>
                  <label class="cw-label"><span>سایز خروجی</span><small>نسبت تصویر موردنظر را انتخاب کنید</small></label>
                  <details class="cw-ratio-dropdown" data-ratio-dropdown>
                    <summary data-ratio-summary><b dir="rtl">{{ $formatRatio($defaultRatio) }} {{ $ratioTitles[$defaultRatio] ?? '' }}</b></summary>
                    <div class="cw-ratio-menu" role="radiogroup" aria-label="انتخاب سایز خروجی">
                      @foreach($outputRatios as $ratio)
                        <label class="cw-ratio-option">
                          <input type="radio" name="output[aspect_ratio]" value="{{ $ratio }}" data-ratio-label="{{ $formatRatio($ratio) }} {{ $ratioTitles[$ratio] ?? '' }}" @checked($ratio === $defaultRatio)>
                          <span>
                            <i class="cw-ratio-frame" style="--ratio:{{ str_replace(':', '/', $ratio) }}"></i>
                            <span class="cw-ratio-option-meta" dir="rtl"><b dir="ltr">{{ $formatRatio($ratio) }}</b><small>{{ $ratioTitles[$ratio] ?? '' }}</small></span>
                          </span>
                        </label>
                      @endforeach
                    </div>
                  </details>
                </div>
              @endif
            </div>
            @include('app.partials.face-source-selector')
            @foreach($redesignFields as $field)
              @include('app.partials.create-samples-field', ['field' => $field, 'instance' => $instance])
            @endforeach
          </div>
        @else
        <div class="cw-tab-panel active" data-panel="basic">
          @if(!empty($product['identity']['available']))
            <div class="cw-identity-option" data-identity-extra="{{ (int)$product['identity']['extra_cost'] }}">
              <label class="cw-toggle"><span><b>حفظ دقیق شباهت چهره</b><small>Grade A · کیفیت High · +{{ (int)$product['identity']['extra_cost'] }} اعتبار</small></span><input name="identity_preservation" value="1" type="checkbox" data-identity-toggle><i></i></label>
              <p><i class="fa-solid fa-images"></i> برای نتیجه بهتر ۲ تا {{ (int)$product['identity']['max_images'] }} عکس واضح از زوایای مختلف اضافه کنید. هر عکس مرجع پردازش بیشتری مصرف می‌کند؛ سقف ۳ عکس است.</p>
            </div>
          @endif
          @foreach($fields->whereIn('type', $basicTypes) as $field)
            @include('app.partials.create-samples-field', ['field' => $field, 'instance' => $instance])
          @endforeach
        </div>
        <div class="cw-tab-panel" data-panel="output">
          @if(!empty($product['output_variants']))
            <div class="cw-field" data-field-type="output_variants">
              <label class="cw-label"><span>مدل‌های خروجی <b>*</b></span><small>یک یا چند خروجی را انتخاب کنید</small></label>
              <div class="cw-variant-grid">
                @foreach($product['output_variants'] as $variant)
                  <label>
                    <input type="checkbox" name="variants[]" value="{{ $variant['key'] }}" {{ $loop->first ? 'checked' : '' }}>
                    <span>@if($variant['image'])<img src="{{ str_starts_with($variant['image'], 'http') ? $variant['image'] : asset('storage/'.ltrim($variant['image'], '/')) }}" alt="">@else<i class="fa-solid fa-wand-magic-sparkles"></i>@endif<b>{{ $variant['title'] }}</b><em><i class="fa-solid fa-check"></i></em></span>
                  </label>
                @endforeach
              </div>
            </div>
          @endif
          @foreach($fields->whereIn('type', $outputTypes) as $field)
            @include('app.partials.create-samples-field', ['field' => $field, 'instance' => $instance])
          @endforeach
        </div>
        <div class="cw-tab-panel" data-panel="advanced">
          <div class="cw-section-note"><i class="fa-solid fa-sliders"></i><div><strong>تنظیمات حرفه‌ای</strong><span>مقادیر پیش‌فرض برای بیشتر کاربران بهترین نتیجه را می‌دهند.</span></div></div>
          @foreach($fields->whereIn('type', $advancedTypes) as $field)
            @include('app.partials.create-samples-field', ['field' => $field, 'instance' => $instance])
          @endforeach
        </div>
        @endif
      </form>

      <div class="cw-submit-wrap">
        @if($instance !== 'redesign')
          <div class="cw-cost-row"><span><i class="fa-solid fa-bolt"></i> هزینه این ساخت</span><strong><b data-cost>{{ $product['cost'] }}</b> اعتبار</strong></div>
          <div class="cw-discount-field">
            <label for="{{ $discountId }}">کد تخفیف</label>
            <input id="{{ $discountId }}" form="{{ $formId }}" name="discount_code" type="text" maxlength="40" autocomplete="off" placeholder="اختیاری" dir="ltr">
          </div>
        @else
          <input type="hidden" name="redesign_cost" value="{{ $defaultMainQuality['credits'] }}">
        @endif
        <div class="cw-form-alert" data-form-alert hidden><i class="fa-solid fa-circle-exclamation"></i><span>برای ادامه، تصویر اصلی چهره را اضافه کنید.</span></div>
        <button type="button" class="cw-generate" data-action="generate" disabled aria-disabled="true"><span>بساز</span><i class="fa-solid fa-wand-magic-sparkles"></i></button>
      </div>
    </aside>

    <main class="cw-stage">
      <div class="cw-stage-head">
        @if($instance === 'redesign')
          <div class="cw-view-switch cw-redesign-stage-tabs" role="tablist">
            <button type="button" class="active" data-stage-tab="upload"><i class="fa-solid fa-cloud-arrow-up"></i> آپلود عکس</button>
            <button type="button" data-stage-tab="output" data-output-tab><i class="fa-solid fa-image"></i> خروجی نهایی</button>
          </div>
        @else
          <div class="cw-view-switch"><button type="button" class="active"><i class="fa-regular fa-image"></i> نتیجه</button><button type="button"><i class="fa-solid fa-code-compare"></i> مقایسه</button></div>
          <div class="cw-stage-tools"><button type="button" class="cw-icon-btn"><i class="fa-solid fa-magnifying-glass-plus"></i></button><button type="button" class="cw-icon-btn"><i class="fa-solid fa-expand"></i></button></div>
        @endif
      </div>

      <div class="cw-canvas" data-canvas>
        <div class="cw-canvas-glow"></div>
        @if($instance === 'redesign')
          <div class="cw-redesign-upload-state" data-empty>
            <label class="cw-upload cw-redesign-stage-upload" for="cw-{{ $instance }}-{{ $primaryUploadId }}" data-required-upload="{{ $primaryUploadRequired ? '1' : '0' }}">
              <input id="cw-{{ $instance }}-{{ $primaryUploadId }}" form="{{ $formId }}" name="uploads[{{ $primaryUploadId }}]{{ $primaryUploadIsMultiple ? '[]' : '' }}" type="file" {{ $primaryUploadIsMultiple ? 'multiple' : '' }} accept="{{ ($primaryUpload['accept'] ?? '') ?: 'image/*' }}" data-upload-input data-max-files="{{ $primaryUploadMaxFiles }}">
              <span class="cw-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
              <span class="cw-upload-copy"><strong>آپلود عکس</strong><small>برای انتخاب عکس کلیک کنید یا فایل را اینجا رها کنید</small></span>
              <span class="cw-upload-action">انتخاب عکس</span>
              <span class="cw-info cw-redesign-upload-info"><i class="fa-solid fa-lightbulb"></i><span>برای بیشترین شباهت، یک عکس واضح و روبه‌رو با نور طبیعی انتخاب کنید.</span></span>
            </label>
            <div class="cw-upload-preview" data-upload-preview></div>
          </div>
        @else
          <div class="cw-empty-state" data-empty>
            <div class="cw-empty-art"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
            <strong>خروجی شما اینجا ساخته می‌شود</strong>
            <span>تصویر را بارگذاری و تنظیمات را انتخاب کنید؛ بقیه مسیر با وطن.</span>
            <div class="cw-empty-chips"><span><i class="fa-solid fa-user-check"></i> حفظ دقیق هویت</span><span><i class="fa-solid fa-shield-halved"></i> پردازش امن</span><span><i class="fa-solid fa-image"></i> کیفیت 2K</span></div>
          </div>
        @endif
        @if($instance === 'redesign')
          <div class="cw-redesign-output-placeholder" data-output-placeholder hidden>
            <div class="cw-redesign-output-placeholder-art"><i class="fa-solid fa-image"></i></div>
            <strong>خروجی نهایی اینجا نمایش داده می‌شود</strong>
            <span>بعد از ساخت تصویر، نتیجه‌ی نهایی شما در این بخش قرار می‌گیرد.</span>
          </div>
        @endif
        <div class="cw-progress" data-progress role="status" aria-live="polite" hidden>
          <div class="cw-loader-brand" aria-hidden="true">
            <div class="cw-loader-mark">
              <span class="cw-loader-ring cw-loader-ring--outer"></span>
              <span class="cw-loader-ring cw-loader-ring--inner"></span>
              <img src="{{ \App\Support\AppAsset::url('assets/img/icon_vatan.svg') }}" alt="">
            </div>
            <span class="cw-loader-wordmark"></span>
          </div>
          <div class="cw-progress-heading">
            <strong>پلتفرم وطن در حال ساخت تصویر شماست</strong>
            <b data-progress-value aria-hidden="true">۰٪</b>
          </div>
          <span data-progress-text>در حال بررسی ورودی‌ها</span>
          <div class="cw-progress-track" role="progressbar" aria-label="پیشرفت تقریبی ساخت تصویر" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><i data-progress-bar></i></div>
          <div class="cw-progress-meta" aria-hidden="true">
            <span data-progress-time>زمان سپری‌شده ۰۰:۰۰</span>
            <span data-progress-note>زمان هدف حدود ۰۰:۳۰</span>
          </div>
        </div>
        <div class="cw-result" data-result hidden>
          <img src="{{ $product['cover'] }}" alt="نمونه خروجی">
          <div class="cw-result-count"><i class="fa-solid fa-circle-check"></i> یک خروجی آماده و در بخش پروفایل ذخیره شد</div>
          <div class="cw-result-strip">
            @foreach(range(1, $product['output_count'] ?? 4) as $outputIndex)
              <button type="button" class="{{ $outputIndex === 1 ? 'active' : '' }}"><img src="{{ $product['cover'] }}" alt="خروجی {{ $outputIndex }}"><span>{{ $outputIndex }}</span></button>
            @endforeach
          </div>
          <div class="cw-result-actions"><button type="button" data-action="download"><i class="fa-solid fa-download"></i><span>دانلود</span></button><button type="button" data-action="regenerate"><i class="fa-solid fa-rotate"></i><span>ساخت دوباره</span></button></div>
          @if($relatedVideoProducts)
            <div class="cw-related-video-actions" data-related-video-actions hidden>
              <strong><i class="fa-solid fa-film"></i> این عکس را متحرک کنید</strong>
              <div>@foreach($relatedVideoProducts as $videoProduct)<a href="{{ $videoProduct['url'] }}" data-convert-video data-video-url="{{ $videoProduct['url'] }}">{{ $videoProduct['name'] }} <i class="fa-solid fa-arrow-left"></i></a>@endforeach</div>
            </div>
          @endif
        </div>
      </div>

      @if($instance !== 'redesign')
        <div class="cw-stage-foot">
          <span><i class="fa-solid fa-lock"></i> تصاویر شما خصوصی هستند و فقط برای ساخت این خروجی استفاده می‌شوند.</span>
          <span>خروجی آزمایشی</span>
        </div>
      @endif
    </main>

    <aside class="cw-panel cw-summary">
      <div class="cw-panel-head"><div><strong>راهنمای نتیجه بهتر</strong><span>چند نکته ساده، تفاوتی بزرگ</span></div></div>
      <div class="cw-reference-card"><img src="{{ $product['cover'] }}" alt="نمونه محصول"><div class="cw-ref-overlay"><span>نمونه خروجی</span><button type="button"><i class="fa-solid fa-expand"></i></button></div></div>
      <p class="cw-description">{{ $product['description'] }}</p>
      <div class="cw-quality-list">
        <div><i class="fa-solid fa-check"></i><span><strong>چهره واضح و بدون پوشش</strong><small>صورت کامل داخل کادر باشد</small></span></div>
        <div><i class="fa-solid fa-check"></i><span><strong>نور طبیعی و متعادل</strong><small>از سایه‌های شدید پرهیز کنید</small></span></div>
        <div><i class="fa-solid fa-check"></i><span><strong>فقط یک نفر در تصویر</strong><small>برای تشخیص دقیق‌تر هویت</small></span></div>
      </div>
      <div class="cw-score-card"><div><span>آمادگی برای ساخت</span><strong data-readiness>۳۵٪</strong></div><div class="cw-score-track"><i data-score-bar></i></div><p data-readiness-text>ابتدا تصویر اصلی را اضافه کنید.</p></div>
      <div class="cw-specs"><div><span>مدل خروجی</span><strong data-grade-label>Grade B · Medium</strong></div><div><span>نسبت</span><strong data-ratio-label>۴:۵</strong></div><div><span>تعداد</span><strong>۱ تصویر</strong></div></div>
    </aside>
  </div>
</div>
