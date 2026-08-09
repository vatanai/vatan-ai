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
  $redesignFields = $fields->reject(fn ($field) => in_array($field['type'] ?? '', ['image_upload', 'multi_image', 'file_upload', 'info', 'section', 'divider'], true))->values();
  // gender مثل radio یک ورودی پایه و تک‌انتخابی است. نبودن آن در این فهرست
  // باعث می‌شد تنظیمات و پرامپت‌های زن/مرد ذخیره شوند اما در صفحه ساخت دیده نشوند.
  $basicTypes = ['info','section','divider','image_upload','multi_image','textarea','prompt','text','number','radio','gender','select','multi_select','button_group'];
  $outputTypes = ['strength','slider','color','switch','checkbox','style_preset'];
  $advancedTypes = ['negative_prompt','seed','file_upload'];
  $ratioLabels = ['auto' => 'خودکار', '1:1' => 'مربع', '9:16' => '۹:۱۶', '16:9' => '۱۶:۹', '2:3' => '۲:۳', '3:2' => '۳:۲', '3:4' => '۳:۴ عمودی', '4:3' => '۴:۳'];
  $outputRatios = array_values(array_filter((array) ($product['output_aspect_ratios'] ?? [])));
  $outputResolutions = array_values(array_filter((array) ($product['output_resolutions'] ?? ['720', '1080'])));
  $defaultRatio = (string) ($product['default_output_aspect_ratio'] ?? '3:4');
  $defaultResolution = (string) ($product['default_output_resolution'] ?? '720');
@endphp

<div class="cw-page" dir="rtl" data-instance="{{ $instance }}" data-generate-url="{{ $product['generate_url'] ?? '' }}" data-download-track-url="{{ $product['download_track_url'] ?? '' }}" data-login-url="{{ $product['login_url'] ?? route('login', ['redirect' => request()->fullUrl()]) }}" data-authenticated="{{ ($product['is_authenticated'] ?? false) ? '1' : '0' }}" data-preview="{{ ($previewMode ?? false) ? '1' : '0' }}">
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

  <div class="cw-workspace page-container">
    <aside class="cw-panel cw-controls">
      @if($instance === 'redesign')
        <div class="cw-redesign-product-head">
          <div class="cw-product-thumb"><img src="{{ $product['cover'] }}" alt=""></div>
          <div class="cw-redesign-product-copy">
            <h1>{{ $product['name'] }}</h1>
            <span class="cw-redesign-time">{{ $product['estimated_time'] }}</span>
            <strong class="cw-redesign-cost"><i class="fa-solid fa-bolt"></i> {{ $product['cost'] }} توکن</strong>
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
            @foreach($redesignFields as $field)
              @include('app.partials.create-field', ['field' => $field, 'instance' => $instance])
            @endforeach
          </div>
        @else
        <div class="cw-tab-panel active" data-panel="basic">
          @if(!empty($product['identity']['available']))
            <div class="cw-identity-option" data-identity-extra="{{ (int)$product['identity']['extra_cost'] }}">
              <label class="cw-toggle"><span><b>حفظ دقیق شباهت چهره</b><small>Grade A · کیفیت High · +{{ (int)$product['identity']['extra_cost'] }} توکن</small></span><input name="identity_preservation" value="1" type="checkbox" data-identity-toggle><i></i></label>
              <p><i class="fa-solid fa-images"></i> برای نتیجه بهتر ۲ تا {{ (int)$product['identity']['max_images'] }} عکس واضح از زوایای مختلف اضافه کنید. هر عکس مرجع پردازش بیشتری مصرف می‌کند؛ سقف ۳ عکس است.</p>
            </div>
          @endif
          @foreach($fields->whereIn('type', $basicTypes) as $field)
            @include('app.partials.create-field', ['field' => $field, 'instance' => $instance])
          @endforeach
        </div>
        <div class="cw-tab-panel" data-panel="output">
          @if(count($outputRatios))
            <div class="cw-field cw-output-options" data-output-options>
              <label class="cw-label"><span>سایز خروجی</span><small>نسبت تصویر موردنظر را انتخاب کنید</small></label>
              <div class="cw-ratios">
                @foreach($outputRatios as $ratio)
                  <label>
                    <input type="radio" name="output[aspect_ratio]" value="{{ $ratio }}" {{ $ratio === $defaultRatio ? 'checked' : '' }}>
                    <span><i style="--ratio:{{ $ratio === 'auto' ? '1/1' : str_replace(':', '/', $ratio) }}"></i><b>{{ $ratioLabels[$ratio] ?? $ratio }}</b><small>{{ $ratio }}</small></span>
                  </label>
                @endforeach
              </div>
            </div>
          @endif
          @if(count($outputResolutions))
            <div class="cw-field cw-output-options" data-output-options>
              <label class="cw-label"><span>کیفیت خروجی</span><small>کیفیت تصویر نهایی را انتخاب کنید</small></label>
              <div class="cw-resolution">
                @foreach($outputResolutions as $resolution)
                  <label>
                    <input type="radio" name="output[quality]" value="{{ $resolution }}" {{ $resolution === $defaultResolution ? 'checked' : '' }}>
                    <span><b>{{ $resolution }}</b><small>{{ $resolution === '720' ? 'استاندارد' : 'بالاتر' }}</small></span>
                  </label>
                @endforeach
              </div>
            </div>
          @endif
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
            @include('app.partials.create-field', ['field' => $field, 'instance' => $instance])
          @endforeach
        </div>
        <div class="cw-tab-panel" data-panel="advanced">
          <div class="cw-section-note"><i class="fa-solid fa-sliders"></i><div><strong>تنظیمات حرفه‌ای</strong><span>مقادیر پیش‌فرض برای بیشتر کاربران بهترین نتیجه را می‌دهند.</span></div></div>
          @foreach($fields->whereIn('type', $advancedTypes) as $field)
            @include('app.partials.create-field', ['field' => $field, 'instance' => $instance])
          @endforeach
        </div>
        @endif
      </form>

      <div class="cw-submit-wrap">
        @if($instance !== 'redesign')
          <div class="cw-cost-row"><span><i class="fa-solid fa-bolt"></i> هزینه این ساخت</span><strong><b data-cost>{{ $product['cost'] }}</b> توکن</strong></div>
          <div class="cw-discount-field">
            <label for="{{ $discountId }}">کد تخفیف</label>
            <input id="{{ $discountId }}" form="{{ $formId }}" name="discount_code" type="text" maxlength="40" autocomplete="off" placeholder="اختیاری" dir="ltr">
          </div>
        @else
          <input type="hidden" name="redesign_cost" value="{{ $product['cost'] }}">
        @endif
        @if($instance === 'redesign')
          <div class="cw-score-card cw-redesign-readiness-card">
            <div><span>آمادگی برای ساخت</span><strong data-readiness>۳۵٪</strong></div>
            <div class="cw-score-track"><i data-score-bar></i></div>
            <p data-readiness-text>ابتدا تصویر اصلی را اضافه کنید.</p>
          </div>
        @endif
        <div class="cw-form-alert" data-form-alert hidden><i class="fa-solid fa-circle-exclamation"></i><span>برای ادامه، تصویر اصلی چهره را اضافه کنید.</span></div>
        <button type="button" class="cw-generate" data-action="generate"><span>بساز</span><small>{{ $product['estimated_time'] }}</small><i class="fa-solid fa-wand-magic-sparkles"></i></button>
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
        <div class="cw-progress" data-progress hidden>
          <div class="cw-progress-orbit"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
          <strong>در حال ساخت تصویر شما...</strong><span data-progress-text>در حال بررسی ورودی‌ها</span>
          <div class="cw-progress-track"><i></i></div>
        </div>
        <div class="cw-result" data-result hidden>
          <img src="{{ $product['cover'] }}" alt="نمونه خروجی">
          <div class="cw-result-count"><i class="fa-solid fa-circle-check"></i> ۴ خروجی آماده شد</div>
          <div class="cw-result-strip">
            @foreach(range(1, $product['output_count'] ?? 4) as $outputIndex)
              <button type="button" class="{{ $outputIndex === 1 ? 'active' : '' }}"><img src="{{ $product['cover'] }}" alt="خروجی {{ $outputIndex }}"><span>{{ $outputIndex }}</span></button>
            @endforeach
          </div>
          <div class="cw-result-actions"><button type="button" disabled><i class="fa-solid fa-check"></i> ذخیره شد</button><button type="button" data-action="download"><i class="fa-solid fa-download"></i> دانلود</button><button type="button" data-action="regenerate"><i class="fa-solid fa-rotate"></i> ساخت دوباره</button></div>
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
