@extends('layouts.app')

@section('page_title', 'استودیوی ساخت | پلتفرم هوش مصنوعی وطن')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-studio.css') }}?v={{ filemtime(public_path('css/create-studio.css')) }}">
  @if($experimental ?? false)
    <link rel="stylesheet" href="{{ asset('css/create-studio-workflows.css') }}?v={{ filemtime(public_path('css/create-studio-workflows.css')) }}">
  @endif
@endpush

@section('content')
<div class="create-studio-page" dir="rtl" data-create-studio @if($experimental ?? false) data-workflow-studio @endif data-mode="image">
  <script type="application/json" data-studio-config>@json($studioConfig + ['quote_url' => route('app.create.studio.quote')])</script>
  <div class="create-studio-shell">
    <aside class="create-studio-sidebar" aria-label="تنظیمات ساخت">
      <nav class="create-studio-mode-tabs" role="tablist" aria-label="نوع ساخت">
        <button type="button" class="create-studio-mode-tab is-active" data-studio-mode="image" role="tab" aria-selected="true"><span>ساخت عکس</span><small>عکس</small></button>
        <button type="button" class="create-studio-mode-tab" data-studio-mode="video" role="tab" aria-selected="false"><span>ساخت ویدیو</span><small>ویدیو</small></button>
      </nav>
      @if($experimental ?? false)
        <nav class="create-studio-workflow-tabs" data-workflow-tabs hidden aria-label="نوع ورودی ویدیو">
          <button type="button" class="is-active" data-workflow="text_to_video"><i class="fa-solid fa-pen-nib"></i><span>متن به ویدیو</span><small>فقط پرامپت</small></button>
          <button type="button" data-workflow="image_to_video"><i class="fa-regular fa-images"></i><span>عکس به ویدیو</span><small>یک یا چند عکس</small></button>
          <button type="button" data-workflow="video_to_video"><i class="fa-solid fa-film"></i><span>ویدیو به ویدیو</span><small>ویدیوی مرجع</small></button>
        </nav>
        <div class="create-studio-workflow-note" data-workflow-note hidden></div>
        <div class="create-studio-workflow-subtabs" data-workflow-image-options hidden aria-label="روش استفاده از تصاویر">
          <button type="button" class="is-active" data-workflow-submode="image_to_video"><span>تصویر مرجع</span><small>یک یا چند عکس برای هدایت صحنه</small></button>
          <button type="button" data-workflow-submode="image_sequence_to_video"><span>چند عکس پشت‌سرهم</span><small>عکس‌ها به ترتیب وارد ویدیو می‌شوند</small></button>
        </div>
      @endif

      <div class="create-studio-sidebar-scroll">
        <section class="create-studio-model-card" aria-label="مدل انتخاب‌شده">
          <div class="create-studio-model-card-top"><span class="create-studio-model-status"><i></i> آماده ساخت</span></div>
          <strong data-studio-model-title>استودیوی ویدیو</strong><span data-studio-model-subtitle>ساخت ویدیو با مدل واقعی وطن</span><b data-studio-model-name>مدل ویدیو <i class="fa-solid fa-signal"></i></b>
        </section>

        <label class="create-studio-reference-drop" data-studio-upload-zone tabindex="0" role="button"><input type="file" hidden data-studio-upload-input><span class="create-studio-reference-icons"><i class="fa-regular fa-image"></i><i class="fa-solid fa-video"></i><i class="fa-solid fa-music"></i></span><strong data-upload-title>افزودن منبع</strong><small data-upload-help>تصویر، ویدیو یا صدا</small><em>+</em></label>
        <div class="create-studio-upload-file" data-studio-upload-file hidden></div>
        @if($experimental ?? false)
          <div class="create-studio-workflow-files" data-workflow-files hidden></div>
        @endif

        <form class="create-studio-form" data-studio-form enctype="multipart/form-data" novalidate>
          <section class="create-studio-field create-studio-prompt-field"><div class="create-studio-field-head"><label for="studio-prompt">توضیحات ساخت (پرامپت)</label><span><b data-studio-count>۰</b> / ۵۰۰۰</span></div><div class="create-studio-prompt-box"><textarea id="studio-prompt" rows="5" maxlength="5000" name="studio_prompt" data-studio-prompt placeholder="ایده‌ات را برای ساخت عکس دقیق توضیح بده..."></textarea><div class="create-studio-prompt-footer"><button type="button" class="create-studio-elements-button" data-studio-improve><i class="fa-solid fa-wand-magic-sparkles"></i> بهبود توضیحات</button></div></div></section>
          <input type="hidden" name="studio_negative_prompt" data-studio-negative>

          <div class="create-studio-settings-list">
            <div class="create-studio-setting-row create-studio-setting-row--model"><span><i class="fa-solid fa-cube"></i><b>مدل</b></span><div class="create-studio-select" data-studio-select="model"><button type="button" class="create-studio-select-toggle" data-select-toggle><span data-select-label>مدل هوش مصنوعی</span><i class="fa-solid fa-chevron-down"></i></button><div class="create-studio-select-menu create-studio-select-menu--models" data-select-menu role="listbox"></div><input type="hidden" data-select-input></div></div>
            <div class="create-studio-setting-row create-studio-setting-row--duration" data-studio-video-only><span><i class="fa-regular fa-clock"></i><b>زمان ویدیو</b></span><div class="create-studio-select" data-studio-select="duration"><button type="button" class="create-studio-select-toggle" data-select-toggle><span data-select-label>زمان ویدیو</span><i class="fa-solid fa-chevron-down"></i></button><div class="create-studio-select-menu create-studio-select-menu--timeline" data-select-menu role="listbox"></div><input type="hidden" name="video[duration]" data-select-input></div></div>
            <div class="create-studio-setting-row create-studio-setting-row--ratio"><span><i class="fa-regular fa-square"></i><b>نسبت تصویر</b></span><div class="create-studio-select" data-studio-select="ratio"><button type="button" class="create-studio-select-toggle" data-select-toggle><span data-select-label>نسبت تصویر</span><i class="fa-solid fa-chevron-down"></i></button><div class="create-studio-select-menu create-studio-select-menu--cards" data-select-menu role="listbox"></div><input type="hidden" data-select-input></div></div>
            <div class="create-studio-setting-row create-studio-setting-row--quality"><span><i class="fa-solid fa-wand-magic-sparkles"></i><b>کیفیت خروجی</b></span><div class="create-studio-select" data-studio-select="quality"><button type="button" class="create-studio-select-toggle" data-select-toggle><span data-select-label>کیفیت پیش‌فرض</span><i class="fa-solid fa-chevron-down"></i></button><div class="create-studio-select-menu create-studio-select-menu--cards" data-select-menu role="listbox"></div><input type="hidden" data-select-input></div></div>
            <div class="create-studio-setting-row create-studio-setting-row--motion" data-studio-video-only><span><i class="fa-solid fa-arrows-to-circle"></i><b>حرکت دوربین</b></span><div class="create-studio-select" data-studio-select="motion"><button type="button" class="create-studio-select-toggle" data-select-toggle><span data-select-label>حرکت دوربین</span><i class="fa-solid fa-chevron-down"></i></button><div class="create-studio-select-menu create-studio-select-menu--cards" data-select-menu role="listbox"></div><input type="hidden" name="video[motion_preset]" data-select-input></div></div>
            <div data-studio-dynamic-fields></div>
            <div class="create-studio-setting-row create-studio-setting-row--count" data-studio-image-only hidden><span><i class="fa-solid fa-layer-group"></i><b>تعداد خروجی</b></span><div class="create-studio-select" data-studio-select="count"><button type="button" class="create-studio-select-toggle" data-select-toggle><span data-select-label>تعداد خروجی: ۱ عدد</span><i class="fa-solid fa-chevron-down"></i></button><div class="create-studio-select-menu create-studio-select-menu--cards" data-select-menu role="listbox"></div><input id="studio-output-count" type="hidden" name="output[count]" data-select-input data-studio-output-count value="1"></div></div>
          </div>

        </form>
      </div>

      <div class="create-studio-generate-bar"><div class="create-studio-credit"><span>هزینه تقریبی</span><strong><i class="fa-solid fa-bolt"></i> <b data-studio-cost>۰</b> اعتبار</strong></div><button type="button" class="create-studio-generate-button" data-studio-submit><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i><span data-studio-submit-label>بساز</span></button></div>
    </aside>

    <main class="create-studio-stage" aria-label="فضای پیش‌نمایش">
      <header class="create-studio-topbar"><div class="create-studio-top-actions"><a href="{{ route('app.profile', ['tab' => 'gallery']) }}" class="create-studio-top-button"><i class="fa-regular fa-circle-user"></i> گالری من</a><button type="button" class="create-studio-top-button" data-studio-help><i class="fa-regular fa-circle-question"></i> راهنمای ساخت</button></div><div class="create-studio-top-brand"><span><i class="fa-solid fa-circle-play"></i> پلتفرم هوش مصنوعی وطن</span></div></header>

      <section class="create-studio-stage-panel"><div class="create-studio-stage-heading"><span class="create-studio-stage-kicker" data-studio-stage-kicker>استودیوی ساخت ویدیو</span><h1 data-studio-stage-title>ویدیو را با چند کلمه بساز</h1><p data-studio-stage-subtitle>ایده‌ات را بنویس، تنظیمات را انتخاب کن و ساخت واقعی را به وطن بسپار.</p></div>
        <div class="create-studio-steps" data-studio-video-content><article class="create-studio-step-card"><div class="create-studio-step-media create-studio-step-media--upload"><span><i class="fa-regular fa-image"></i></span><b>افزودن تصویر</b><small>تصویر مرجع برای شروع</small><div class="create-studio-fake-image"></div></div><h2>افزودن تصویر</h2><p>یک تصویر اضافه کن یا ساخت ویدیوی متنی را شروع کن.</p></article><article class="create-studio-step-card"><div class="create-studio-step-media"><img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="نمونه حرکت دوربین"><span class="create-studio-step-focus"><i class="fa-solid fa-crosshairs"></i></span></div><h2>انتخاب حرکت</h2><p>نسبت تصویر، مدت و حرکت دوربین را تنظیم کن.</p></article><article class="create-studio-step-card"><div class="create-studio-step-media"><video src="{{ asset('assets/videos/60ed34f8-ed85-4ae0-9b63-191dcbe11800.mp4') }}" autoplay muted loop playsinline preload="metadata" data-studio-stage-video></video><span class="create-studio-video-mark"><i class="fa-solid fa-play"></i></span></div><h2>دریافت ویدیو</h2><p>وطن درخواست را به سرویس ساخت ویدیو ارسال می‌کند.</p></article></div>
        <div class="create-studio-steps create-studio-steps--photo" data-studio-image-content hidden><article class="create-studio-step-card"><div class="create-studio-step-media create-studio-step-media--upload"><span><i class="fa-solid fa-pen-nib"></i></span><b>نوشتن توضیحات</b><small>ایده‌ات را دقیق بنویس</small><div class="create-studio-fake-image create-studio-fake-image--photo"></div></div><h2>افزودن توضیحات</h2><p>موضوع، نور و ترکیب‌بندی تصویر را توضیح بده.</p></article><article class="create-studio-step-card"><div class="create-studio-step-media"><img src="{{ asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg') }}" alt="نمونه تنظیمات عکس"><span class="create-studio-step-focus"><i class="fa-solid fa-sliders"></i></span></div><h2>انتخاب تنظیمات</h2><p>مدل، نسبت تصویر و کیفیت خروجی را انتخاب کن.</p></article><article class="create-studio-step-card"><div class="create-studio-step-media"><img src="{{ asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif') }}" alt="نمونه خروجی عکس"><span class="create-studio-photo-mark"><i class="fa-solid fa-image"></i></span></div><h2>دریافت عکس</h2><p>خروجی واقعی پس از پاسخ سرویس در گالری ذخیره می‌شود.</p></article></div>
        <div class="create-studio-progress" data-studio-progress hidden><i class="fa-solid fa-sparkles"></i><strong data-studio-progress-title>در حال ارسال درخواست ساخت</strong><span data-studio-progress-text>در حال بررسی تنظیمات و ارسال به سرویس هوش مصنوعی...</span><div><b data-studio-progress-bar></b></div></div>
        <div class="create-studio-result" data-studio-result hidden><div class="create-studio-result-media"><img alt="خروجی ساخته‌شده" data-studio-output-image hidden><video muted loop playsinline preload="metadata" data-studio-output-video hidden></video><span class="create-studio-result-badge"><i class="fa-solid fa-check"></i> خروجی آماده</span><button type="button" class="create-studio-result-play" data-studio-video-play hidden><i class="fa-solid fa-play"></i></button></div><div class="create-studio-result-actions"><button type="button" data-studio-download><i class="fa-solid fa-download"></i> دانلود</button><button type="button" data-studio-share><i class="fa-solid fa-share-nodes"></i> اشتراک‌گذاری</button><button type="button" data-studio-regenerate><i class="fa-solid fa-rotate"></i> دوباره بساز</button></div></div>
        <div class="create-studio-error" data-studio-error hidden><i class="fa-solid fa-circle-exclamation"></i><span></span><button type="button" data-studio-error-close aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></div><footer class="create-studio-stage-footer"><span><i class="fa-regular fa-lightbulb"></i> توضیحات دقیق‌تر، نتیجه‌ی دقیق‌تر.</span><span><i class="fa-solid fa-shield-halved"></i> خروجی‌ها پس از ساخت در گالری ذخیره می‌شوند.</span></footer>
        <div class="create-studio-help-backdrop" data-studio-help-dialog hidden><section class="create-studio-help-dialog" role="dialog" aria-modal="true" aria-labelledby="studio-help-title"><button type="button" class="create-studio-help-close" data-studio-help-close aria-label="بستن"><i class="fa-solid fa-xmark"></i></button><span class="create-studio-help-eyebrow">راهنمای سریع</span><h2 id="studio-help-title">چطور با وطن بسازیم؟</h2><ol><li><b>نوع خروجی را انتخاب کنید</b><span>از بالای پنل، ساخت عکس یا ساخت ویدیو را انتخاب کنید.</span></li><li><b>پرامپت را بنویسید</b><span>سوژه، فضا، نور، حرکت و جزئیات مهم را واضح توضیح دهید.</span></li><li><b>تنظیمات را بررسی کنید</b><span>مدل، نسبت تصویر، کیفیت و تعداد خروجی را از منوهای بازشونده انتخاب کنید.</span></li><li><b>ساخت را شروع کنید</b><span>اگر تصویر مرجع لازم است، ابتدا آن را بارگذاری کنید و سپس روی «بساز» بزنید.</span></li></ol><div class="create-studio-help-tip"><i class="fa-solid fa-lightbulb"></i><span>نکته: توضیح دقیق‌تر و تصویر مرجع واضح‌تر، نتیجه‌ی بهتری می‌سازد.</span></div></section></div>
      </section>
    </main>
  </div>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/create-studio.js') }}?v={{ filemtime(public_path('js/create-studio.js')) }}"></script>
  @if($experimental ?? false)
    <script src="{{ asset('js/create-studio-workflows.js') }}?v={{ filemtime(public_path('js/create-studio-workflows.js')) }}"></script>
  @endif
@endpush
