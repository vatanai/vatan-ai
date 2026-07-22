@php
  $fields = collect($product['fields'] ?? []);
  $basicTypes = ['info','section','divider','image_upload','multi_image','textarea','prompt','text','number','radio','select','multi_select','button_group'];
  $outputTypes = ['strength','slider','color','switch','checkbox','style_preset','aspect_ratio','resolution'];
  $advancedTypes = ['negative_prompt','seed','file_upload'];
@endphp

<div class="cw-page" dir="rtl" data-generate-url="{{ $product['generate_url'] ?? '' }}" data-login-url="{{ $product['login_url'] ?? route('login') }}" data-authenticated="{{ ($product['is_authenticated'] ?? false) ? '1' : '0' }}" data-preview="{{ ($previewMode ?? false) ? '1' : '0' }}">
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
      <button type="button" class="cw-ghost-btn" data-action="reset"><i class="fa-solid fa-rotate-left"></i> شروع دوباره</button>
      <button type="button" class="cw-icon-btn" aria-label="راهنما"><i class="fa-regular fa-circle-question"></i></button>
    </div>
  </div>

  <div class="cw-workspace page-container">
    <aside class="cw-panel cw-controls">
      <div class="cw-panel-head">
        <div><strong>تنظیمات ساخت</strong><span>ورودی‌ها را برای نتیجه دلخواه تنظیم کنید</span></div>
        <span class="cw-step">۱ از ۲</span>
      </div>

      <div class="cw-tabs" role="tablist">
        <button type="button" class="active" data-tab="basic">ورودی‌ها</button>
        <button type="button" data-tab="output">خروجی</button>
        <button type="button" data-tab="advanced">پیشرفته</button>
      </div>

      <form class="cw-form" id="createPreviewForm" enctype="multipart/form-data">
        <div class="cw-tab-panel active" data-panel="basic">
          @foreach($fields->whereIn('type', $basicTypes) as $field)
            @include('app.partials.create-field', ['field' => $field])
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
            @include('app.partials.create-field', ['field' => $field])
          @endforeach
        </div>
        <div class="cw-tab-panel" data-panel="advanced">
          <div class="cw-section-note"><i class="fa-solid fa-sliders"></i><div><strong>تنظیمات حرفه‌ای</strong><span>مقادیر پیش‌فرض برای بیشتر کاربران بهترین نتیجه را می‌دهند.</span></div></div>
          @foreach($fields->whereIn('type', $advancedTypes) as $field)
            @include('app.partials.create-field', ['field' => $field])
          @endforeach
        </div>
      </form>

      <div class="cw-submit-wrap">
        <div class="cw-cost-row"><span><i class="fa-solid fa-bolt"></i> هزینه این ساخت</span><strong><b data-cost>{{ $product['cost'] }}</b> توکن</strong></div>
        <div class="cw-discount-field">
          <label for="cw-discount-code">کد تخفیف</label>
          <input id="cw-discount-code" form="createPreviewForm" name="discount_code" type="text" maxlength="40" autocomplete="off" placeholder="اختیاری" dir="ltr">
        </div>
        <div class="cw-form-alert" data-form-alert hidden><i class="fa-solid fa-circle-exclamation"></i><span>برای ادامه، تصویر اصلی چهره را اضافه کنید.</span></div>
        <button type="button" class="cw-generate" data-action="generate"><span>بساز</span><small>{{ $product['estimated_time'] }}</small><i class="fa-solid fa-wand-magic-sparkles"></i></button>
      </div>
    </aside>

    <main class="cw-stage">
      <div class="cw-stage-head">
        <div class="cw-view-switch"><button type="button" class="active"><i class="fa-regular fa-image"></i> نتیجه</button><button type="button"><i class="fa-solid fa-code-compare"></i> مقایسه</button></div>
        <div class="cw-stage-tools"><button type="button" class="cw-icon-btn"><i class="fa-solid fa-magnifying-glass-plus"></i></button><button type="button" class="cw-icon-btn"><i class="fa-solid fa-expand"></i></button></div>
      </div>

      <div class="cw-canvas" data-canvas>
        <div class="cw-canvas-glow"></div>
        <div class="cw-empty-state" data-empty>
          <div class="cw-empty-art"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
          <strong>خروجی شما اینجا ساخته می‌شود</strong>
          <span>تصویر را بارگذاری و تنظیمات را انتخاب کنید؛ بقیه مسیر با وطن.</span>
          <div class="cw-empty-chips"><span><i class="fa-solid fa-user-check"></i> حفظ دقیق هویت</span><span><i class="fa-solid fa-shield-halved"></i> پردازش امن</span><span><i class="fa-solid fa-image"></i> کیفیت 2K</span></div>
        </div>
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

      <div class="cw-stage-foot">
        <span><i class="fa-solid fa-lock"></i> تصاویر شما خصوصی هستند و فقط برای ساخت این خروجی استفاده می‌شوند.</span>
        <span>خروجی آزمایشی</span>
      </div>
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
      <div class="cw-specs"><div><span>مدل خروجی</span><strong>کیفیت حرفه‌ای</strong></div><div><span>نسبت</span><strong data-ratio-label>۴:۵</strong></div><div><span>تعداد</span><strong>۱ تصویر</strong></div></div>
    </aside>
  </div>
</div>
