@extends('layouts.admin')
@section('title', $product ? 'ویرایش محصول ویدیو — نسخه جدید' : 'ثبت محصول ویدیو — نسخه جدید')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/video-products-create-v2.css') }}?v={{ filemtime(public_path('admin/css/video-products-create-v2.css')) }}">
@endpush

@php
  $configuration = (array) ($configuration ?? []);
  $productFamily = old('product_family', $configuration['product_family'] ?? 'shop');
  $videoStructure = old('video_structure', ($productFamily === 'music_ready' || ($configuration['multi_shot_enabled'] ?? false)) ? 'multi_shot' : 'single_shot');
  $musicMode = old('music_mode', $configuration['music_mode'] ?? 'disabled');
  $showPromptToUser = old('show_prompt_to_user', array_key_exists('show_prompt_to_user', $configuration) ? $configuration['show_prompt_to_user'] : false);
  $inputContract = (array) ($configuration['input_contract'] ?? []);
  $timeline = old('timeline_json') ? json_decode(old('timeline_json'), true) : (array) ($configuration['timeline'] ?? []);
  $timeline = is_array($timeline) ? $timeline : [];
  $selectedCategoryIds = collect(old('category_ids', $product?->categories()->pluck('categories.id')->all() ?? array_filter([$product?->category_id])))->map(fn ($id) => (int) $id)->all();
  $selectedMotionKeys = collect($configuration['motion_presets'] ?? [])->map(fn ($preset) => is_array($preset) ? ($preset['key'] ?? '') : $preset)->filter()->all();
  $fallbackIds = $product ? $models->filter(fn ($model) => in_array($model->openrouter_model_id, (array) $product->fallback_models, true) && in_array($model->provider, (array) $product->fallback_model_providers, true))->pluck('id')->all() : [];
  $selectedModel = $product ? $models->first(fn ($model) => $model->provider === $product->ai_provider && $model->openrouter_model_id === $product->primary_model) : null;
  $selectedModel ??= $models->first(fn ($model) => $model->provider === 'openrouter');
  $selectedModel ??= $models->first();
  $promptTemplate = old('prompt_template', $product?->prompt_template ?? 'Animate the first frame image with natural, restrained motion. Preserve identity, composition, lighting, texture, and the exact source aspect ratio. No cropping, stretching, reframing, or changes to the original aspect ratio.');
  $adminClientConfig = [
      'models' => $models->map(fn ($model) => [
          'id' => $model->id,
          'name' => $model->name,
          'provider' => $model->provider,
          'model_id' => $model->openrouter_model_id,
          'task_type' => $model->task_type,
          'capabilities' => $model->video_capabilities,
      ])->values()->all(),
      'timeline' => $timeline,
      'families' => $familyCatalog,
      'motion_catalog' => $motionCatalog,
      'cover' => $product?->displayImageUrl(),
  ];
  $familyIcons = ['shop' => 'fa-bag-shopping', 'face' => 'fa-user-astronaut', 'hybrid' => 'fa-people-arrows', 'music_ready' => 'fa-music'];
  $durationOptions = [2, 3, 4, 5, 6, 8, 10, 15];
@endphp

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content vpc2-content flex-1 overflow-y-auto p-6 pb-24 max-[768px]:p-4" id="content">
    <div class="vpc2-page-head">
      <div>
        <div class="vpc2-breadcrumb"><a href="{{ route('admin.products.videos') }}">لیست محصولات ویدیو</a><i class="fa-solid fa-chevron-left"></i><span>{{ $product ? 'ویرایش محصول ویدیو' : 'ثبت محصول ویدیو — نسخه جدید' }}</span></div>
        <h1>{{ $product ? 'ویرایش محصول ویدیو' : 'ثبت محصول ویدیو — نسخه جدید' }}</h1>
        <p>نوع ورودی، مدل، خروجی و سناریوی چندپلان را در یک مسیر روشن برای کاربر تعریف کنید.</p>
      </div>
      <div class="vpc2-head-actions">
        <span class="vpc2-version-badge"><i class="fa-solid fa-shield-halved"></i> معماری شش‌گامه</span>
        <a href="{{ route('admin.products.video.create') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-clock-rotate-left"></i> نسخه پشتیبان</a>
        <a href="{{ route('admin.products.videos') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>
      </div>
    </div>

    @if(session('success'))<div class="vpc2-alert is-success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>@endif
    @if($errors->any())
      <div class="vpc2-alert is-danger"><i class="fa-solid fa-triangle-exclamation"></i><div><b>موارد زیر را اصلاح کنید:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif
    @if($models->isEmpty())<div class="vpc2-alert is-warning"><i class="fa-solid fa-circle-exclamation"></i>هیچ مدل ویدیویی فعال برای انتخاب وجود ندارد.</div>@endif

    <nav class="vpc2-stepper" aria-label="مراحل ثبت محصول ویدیو">
      @foreach([
        1 => ['هویت و قرارداد', 'نام، دسته، رسانه و ورودی'],
        2 => ['پرامپت و منطق ساخت', 'ساختار، پرامپت و موسیقی'],
        3 => ['مدل و مسیر', 'اصلی و جایگزین'],
        4 => ['تنظیمات خروجی', 'مدت، قاب و کیفیت'],
        5 => ['پلان و موزیک', 'سناریوی چندپلان'],
        6 => ['بازبینی و انتشار', 'کنترل نهایی'],
      ] as $step => [$title, $description])
        <button type="button" class="vpc2-step {{ $step === 1 ? 'is-active' : '' }}" data-vpc2-step-tab="{{ $step }}" @if($step === 5) data-vpc2-multi-shot-only @endif><span>{{ $step }}</span><div><b>{{ $title }}</b><small>{{ $description }}</small></div><i class="fa-solid fa-check"></i></button>
      @endforeach
    </nav>

    <form id="video-product-v2-form" method="POST" enctype="multipart/form-data" action="{{ $product ? route('admin.products.video.v2.update', $product) : route('admin.products.video.v2.store') }}">
      @csrf
      @if($product) @method('PUT') @endif
      <input type="hidden" name="status" id="vpc2-status" value="{{ old('status', $product?->status ?? 'active') }}">
      <input type="hidden" name="features_json" value="[]">
      <input type="hidden" name="timeline_json" id="vpc2-timeline-json" value="{{ json_encode($timeline, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">

      <section class="vpc2-panel is-active" data-vpc2-step-panel="1">
        {{-- گام ۱ عمداً از همان پارشیال کامل گام ۱ ثبت محصول عکس استفاده می‌کند؛
             فقط قرارداد ویدیویی در انتهای همین گام به آن اضافه می‌شود. --}}
        @php
          $duplicateFrom = $product;
          $fixedMediaType = 'video';
          $suggestedLikesCount = (int) ($product?->base_likes_count ?? 120);
        @endphp
        @include('admin.products.partials.step-1')

        {{-- خروجی‌های گام ۱ ثبت عکس نام‌های عمومی خودشان را دارند؛ این مقادیر پنهان
             قرارداد ویدیویی را بدون تغییر در UI ثبت محصول عکس، به نام‌های v2 نگاشت می‌کنند. --}}
        <div id="vpc2-photo-category-fields" aria-hidden="true"></div>
        <input type="hidden" name="tags" id="vpc2-photo-tags" value="">
        <div id="vpc2-canonical-output-fields" aria-hidden="true">
          @foreach((array) ($configuration['aspect_ratios'] ?? ['9:16', '16:9', '1:1']) as $ratio)<input type="hidden" name="aspect_ratios[]" value="{{ $ratio }}" data-vpc2-canonical-value="1">@endforeach
          <input type="hidden" name="default_aspect_ratio" value="{{ old('default_aspect_ratio', $configuration['default_aspect_ratio'] ?? '9:16') }}">
          @foreach((array) ($configuration['resolutions'] ?? ['480p', '720p', '1080p']) as $resolution)<input type="hidden" name="resolutions[]" value="{{ $resolution }}" data-vpc2-canonical-value="1">@endforeach
          <input type="hidden" name="default_resolution" value="{{ old('default_resolution', $configuration['default_resolution'] ?? '720p') }}">
        </div>

        {{-- این دو بخش تنها بخش‌های مخصوص ویدیو در گام ۱ هستند و باید آخر گام بمانند. --}}
        <div class="vpc2-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-shapes"></i><span><b>نوع محصول ویدیویی</b><small>این انتخاب رفتار صفحه‌ی ساخت کاربر را تعیین می‌کند.</small></span></div><em>قرارداد محصول</em></div>
          <div class="vpc2-family-grid">@foreach($familyCatalog as $value => $family)<label class="vpc2-choice"><input type="radio" name="product_family" value="{{ $value }}" @checked($productFamily === $value)><span><i class="fa-solid {{ $familyIcons[$value] ?? 'fa-video' }}"></i><b>{{ $family['label'] }}</b><small>{{ $family['description'] }}</small><em><i class="fa-solid fa-check"></i></em></span></label>@endforeach</div>
          <div class="vpc2-info"><i class="fa-solid fa-circle-info"></i>در فاز اول همه‌ی این خانواده‌ها با ورودی عکس به ویدیو ساخته می‌شوند. خانواده‌ی «آماده با موزیک» علاوه بر آن، چند پلان و مونتاژ نهایی دارد.</div>
        </div>
        <div class="vpc2-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-images"></i><span><b>منابعی که کاربر وارد می‌کند</b><small>با روشن و خاموش کردن این موارد، باکس‌های صفحه‌ی ساخت تغییر می‌کنند.</small></span></div></div>
          <div class="vpc2-toggle-grid">
            <label><input type="hidden" name="input_product_image" value="0"><input type="checkbox" name="input_product_image" value="1" @checked(old('input_product_image', $inputContract['product_image'] ?? true))><span><b>عکس محصول</b><small>برای کفش، کیف، شامپو و سایر کالاها</small></span><i></i></label>
            <label><input type="hidden" name="input_face_image" value="0"><input type="checkbox" name="input_face_image" value="1" @checked(old('input_face_image', $inputContract['face_image'] ?? false))><span><b>عکس چهره</b><small>برای محصولات چهره‌محور</small></span><i></i></label>
            <label><input type="hidden" name="allow_face_profile" value="0"><input type="checkbox" name="allow_face_profile" value="1" @checked(old('allow_face_profile', $inputContract['face_profile'] ?? false))><span><b>پروفایل چهره</b><small>انتخاب شیت چهره‌ی ذخیره‌شده‌ی کاربر</small></span><i></i></label>
          </div>
          <div class="vpc2-profile-mode"><b>وضعیت پروفایل چهره</b><div class="vpc2-radio-row">@foreach(['disabled' => 'غیرفعال', 'optional' => 'اختیاری', 'required' => 'الزامی'] as $value => $label)<label><input type="radio" name="face_profile_mode" value="{{ $value }}" @checked(old('face_profile_mode', $configuration['face_profile_mode'] ?? 'disabled') === $value)><span>{{ $label }}</span></label>@endforeach</div></div>
        </div>
        <input type="hidden" name="workflow" value="image_to_video">
      </section>

      <section class="vpc2-panel" data-vpc2-step-panel="2">
        <div class="vpc2-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-wand-magic-sparkles"></i><span><b>پرامپت و منطق ساخت</b><small>ساختار تولید، پرامپت و موسیقی را برای همین محصول مشخص کنید.</small></span></div><em>گام ۲</em></div>
          <div class="vpc2-structure-layout">
            <div class="vpc2-structure-main">
              <div class="vpc2-card-head vpc2-sub-card-head"><div><i class="fa-solid fa-film"></i><span><b>فرمت ساخت</b><small>مدت کل و تعداد پلان از همین انتخاب و سناریوی محصول تعیین می‌شود.</small></span></div></div>
              <div class="vpc2-choice-row vpc2-format-row">@foreach(['single_shot' => ['fa-clapperboard', 'تک‌پلان'], 'multi_shot' => ['fa-scissors', 'چندپلان']] as $value => [$icon, $label])<label class="vpc2-choice horizontal"><input type="radio" name="video_structure" value="{{ $value }}" @checked($videoStructure === $value)><span><i class="fa-solid {{ $icon }}"></i><b>{{ $label }}</b><em><i class="fa-solid fa-check"></i></em></span></label>@endforeach</div>
              <div class="vpc2-plan-range">
                <div class="vpc2-plan-range-head"><span><i class="fa-solid fa-timeline"></i> حداقل و حداکثر تعداد پلان</span><output id="vpc2-max-shots-value">{{ old('max_shots', $configuration['max_shots'] ?? 1) }} پلان</output></div>
                <input type="range" name="max_shots" id="vpc2-max-shots" min="1" max="10" step="1" value="{{ old('max_shots', $configuration['max_shots'] ?? 1) }}" aria-label="حداکثر تعداد پلان">
                <div class="vpc2-plan-range-scale"><span>حداقل ۱ پلان</span><span>حداکثر ۱۰ پلان</span></div>
                <small>این عدد سقف پلان‌های قابل تعریف در سناریو است؛ در حالت تک‌پلان فقط یک پلان به کاربر نمایش داده می‌شود.</small>
              </div>
            </div>
            <div class="vpc2-music-box">
              <div class="vpc2-card-head vpc2-sub-card-head"><div><i class="fa-solid fa-music"></i><span><b>موسیقی محصول</b><small>نوع استفاده از موسیقی را مشخص کنید.</small></span></div></div>
              <div class="vpc2-music-options">@foreach(['disabled' => ['fa-volume-xmark', 'بدون موسیقی'], 'optional' => ['fa-music', 'موسیقی اختیاری'], 'required' => ['fa-compact-disc', 'موسیقی ثابت محصول']] as $value => [$icon, $label])<label><input type="radio" name="music_mode" value="{{ $value }}" @checked($musicMode === $value)><span><i class="fa-solid {{ $icon }}"></i><b>{{ $label }}</b><em><i class="fa-solid fa-check"></i></em></span></label>@endforeach</div>
              <small class="vpc2-field-hint">محصول کسب‌وکار می‌تواند چندپلان و بدون موسیقی باشد.</small>
            </div>
          </div>
          <input type="hidden" name="multi_shot_enabled" id="vpc2-multi-shot-value" value="{{ $videoStructure === 'multi_shot' ? '1' : '0' }}">
          <div class="vpc2-prompt-settings vpc2-mt">
            <div class="vpc2-prompt-mode-row">@foreach(['locked' => ['fa-lock', 'پرامپت ثابت محصول'], 'custom' => ['fa-pen-to-square', 'پرامپت قابل ویرایش']] as $value => [$icon, $label])<label class="vpc2-prompt-toggle"><input type="radio" name="prompt_mode" value="{{ $value }}" @checked(old('prompt_mode', $configuration['prompt_mode'] ?? 'locked') === $value)><span><i class="fa-solid {{ $icon }}"></i><b>{{ $label }}</b><em><i class="fa-solid fa-check"></i></em></span></label>@endforeach</div>
            <label class="vpc2-toggle-wide vpc2-prompt-visibility"><input type="hidden" name="show_prompt_to_user" value="0"><input type="checkbox" name="show_prompt_to_user" value="1" @checked($showPromptToUser)><span><b>نمایش پرامپت به کاربر</b><small>در حالت خاموش، کاربر فقط ورودی‌ها و تنظیمات مجاز محصول را می‌بیند.</small></span><i></i></label>
          </div>
          <label class="vpc2-field vpc2-mt"><span>قالب پرامپت بک‌اند <b>*</b></span><textarea name="prompt_template" required rows="8" dir="ltr">{{ $promptTemplate }}</textarea><small>برای حفظ نسبت تصویر، متن مربوط به حفظ دقیق نسبت منبع در قالب محصول ثبت شده است.</small></label>
          <div class="vpc2-grid cols-2 vpc2-mt"><label class="vpc2-field"><span>دستور سیستمی</span><textarea name="system_prompt" rows="4" dir="ltr">{{ old('system_prompt', $product?->system_prompt ?? 'Preserve identity, geometry, temporal consistency, and the exact source aspect ratio. Never crop, stretch, reframe, or alter the aspect ratio.') }}</textarea></label><label class="vpc2-field"><span>موارد ممنوع</span><textarea name="negative_prompt" rows="4" dir="ltr">{{ old('negative_prompt', $product?->negative_prompt ?? 'cropping, stretching, aspect ratio change, warped face, flicker, jitter, unstable geometry, abrupt motion') }}</textarea></label></div>
          <div class="vpc2-info"><i class="fa-solid fa-lock"></i>وقتی حالت ثابت باشد، مقدار پرامپت به کاربر نمایش داده نمی‌شود و فقط همین قرارداد کنترل‌شده به مدل ارسال می‌شود.</div>
        </div>
      </section>

      <section class="vpc2-panel" data-vpc2-step-panel="3">
        <div class="vpc2-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-microchip"></i><span><b>مسیر ساخت و مدل‌ها</b><small>مدل اصلی اولویت اول است؛ مدل‌های جایگزین فقط در خطای سازگاری یا ارسال استفاده می‌شوند.</small></span></div><em>اولویت `OpenRouter`</em></div>
          <label class="vpc2-field"><span>مدل اصلی <b>*</b></span><select name="model_id" id="vpc2-model" required><option value="">انتخاب مدل ویدیویی...</option>@foreach($models as $model)<option value="{{ $model->id }}" data-provider="{{ $model->provider }}" data-workflow="{{ $model->task_type === 'face_animation' ? 'image_to_video' : $model->task_type }}" @selected((int) old('model_id', $selectedModel?->id) === $model->id)>{{ $model->name }} — {{ $model->provider }} — {{ $model->taskLabel() }}</option>@endforeach</select></label>
          <div class="vpc2-model-summary" id="vpc2-model-summary"><i class="fa-solid fa-spinner fa-spin"></i><span>در حال خواندن قابلیت‌های مدل...</span></div>
          <label class="vpc2-field vpc2-mt"><span>مدل‌های جایگزین، حداکثر ۳ مورد</span><select name="fallback_model_ids[]" id="vpc2-fallback-models" multiple size="5">@foreach($models as $model)<option value="{{ $model->id }}" data-provider="{{ $model->provider }}" data-workflow="{{ $model->task_type === 'face_animation' ? 'image_to_video' : $model->task_type }}" @selected(in_array($model->id, old('fallback_model_ids', $fallbackIds), true))>{{ $model->name }} — {{ $model->provider }}</option>@endforeach</select><small>مدل اصلی باید از `OpenRouter` باشد؛ مدل جایگزین می‌تواند از مسیر پشتیبان معتبر انتخاب شود.</small></label>
          <div class="vpc2-model-policy"><i class="fa-solid fa-route"></i><div><b>مسیر ارسال</b><span>اولویت: `OpenRouter` → پشتیبان سازگار</span><small>قابلیت‌های مدت، نسبت تصویر و رزولوشن قبل از انتشار و دوباره هنگام ساخت بررسی می‌شوند.</small></div></div>
        </div>
      </section>

      <section class="vpc2-panel" data-vpc2-step-panel="4">
        <div class="vpc2-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-clock"></i><span><b>مدت و هزینه</b><small>فقط مدت‌هایی که مدل اصلی پشتیبانی می‌کند قابل انتشار هستند.</small></span></div><em>گام ۴</em></div>
          <div class="vpc2-duration-grid">@foreach($durationOptions as $duration)<div class="vpc2-duration"><label><input type="checkbox" name="durations[]" value="{{ $duration }}" @checked(in_array($duration, array_map('intval', (array) ($configuration['durations'] ?? [])), true))><span><b>{{ $duration }} ثانیه</b><small>قابل انتخاب برای کاربر</small></span></label><input type="number" name="credit_costs_by_duration[{{ $duration }}]" min="0" value="{{ old('credit_costs_by_duration.'.$duration, $configuration['credit_costs_by_duration'][(string) $duration] ?? '') }}" placeholder="اعتبار" aria-label="هزینه {{ $duration }} ثانیه"></div>@endforeach</div>
          <label class="vpc2-field vpc2-mt"><span>مدت پیش‌فرض</span><select name="default_duration" id="vpc2-default-duration">@foreach($durationOptions as $duration)<option value="{{ $duration }}" @selected((int) old('default_duration', $configuration['default_duration'] ?? 4) === $duration)>{{ $duration }} ثانیه</option>@endforeach</select></label>
        </div>
        <div class="vpc2-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-crop-simple"></i><span><b>تنظیمات تکمیلی خروجی</b><small>نسبت تصویر و کیفیت در همان کارت‌های استاندارد گام ۱ ثبت محصول عکس تنظیم می‌شوند.</small></span></div></div>
          <div class="vpc2-grid cols-2">
            <label class="vpc2-field"><span>نرخ فریم</span><input type="number" name="fps" min="4" max="60" value="{{ old('fps', $configuration['fps'] ?? 24) }}"><small>در مسیرهایی که پشتیبانی کنند.</small></label>
            <label class="vpc2-field"><span>سطح کیفیت پیش‌فرض</span><select name="quality_tier">@foreach([['standard','استاندارد','720p'], ['professional','حرفه‌ای','1080p'], ['best','بهترین خروجی','4K']] as [$key, $label, $resolution])<option value="{{ $key }}" @selected(old('quality_tier', $configuration['quality_tier'] ?? 'standard') === $key)>{{ $label }} — {{ $resolution }}</option>@endforeach</select></label>
          </div>
          <div class="vpc2-motion-grid">@foreach($motionCatalog as $key => $preset)<label><input type="checkbox" name="motion_presets[]" value="{{ $key }}" @checked(in_array($key, $selectedMotionKeys, true))><span><i class="fa-solid fa-camera-retro"></i><b>{{ $preset['label'] }}</b><small>{{ $preset['description'] }}</small></span></label>@endforeach</div>
          <div class="vpc2-toggle-grid vpc2-mt"><label><input type="hidden" name="audio_allowed" value="0"><input type="checkbox" name="audio_allowed" value="1" @checked($configuration['audio_allowed'] ?? false)><span><b>صدای خروجی</b><small>فقط در صورت پشتیبانی مسیر ساخت</small></span><i></i></label><label><input type="hidden" name="audio_default" value="0"><input type="checkbox" name="audio_default" value="1" @checked($configuration['audio_default'] ?? false)><span><b>روشن به‌صورت پیش‌فرض</b><small>کاربر امکان تغییر دارد</small></span><i></i></label><label><input type="hidden" name="prompt_enhance" value="0"><input type="checkbox" name="prompt_enhance" value="1" @checked($configuration['prompt_enhance'] ?? true)><span><b>بهبود کنترل‌شده‌ی پرامپت</b><small>بدون تغییر هدف اصلی محصول</small></span><i></i></label><label><input type="hidden" name="allow_promotional_credits" value="0"><input type="checkbox" name="allow_promotional_credits" value="1" @checked($configuration['allow_promotional_credits'] ?? true)><span><b>اعتبار هدیه</b><small>برای نمونه یا شروع کاربر</small></span><i></i></label></div>
        </div>
      </section>

      <section class="vpc2-panel" data-vpc2-step-panel="5" data-vpc2-multi-shot-only>
        <div class="vpc2-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-music"></i><span><b>سناریوی پلان‌ها</b><small>هر پلان جداگانه ساخته می‌شود و در پایان به یک خروجی متصل تبدیل می‌شود.</small></span></div><em>حداکثر ۱۰ پلان</em></div>
          <div class="vpc2-plan-summary"><i class="fa-solid fa-timeline"></i><span><b>سقف تعریف پلان</b><small id="vpc2-timeline-max-label">{{ old('max_shots', $configuration['max_shots'] ?? 1) }} پلان طبق تنظیم گام ۲</small></span></div>
          <div class="vpc2-timeline-list" id="vpc2-timeline-list"></div><button type="button" class="vpc2-add-shot" id="vpc2-add-shot"><i class="fa-solid fa-plus"></i> افزودن پلان</button>
          <div class="vpc2-info"><i class="fa-solid fa-shield-halved"></i>هر پلان زمان، پرامپت، ورودی و نوع انتقال خودش را دارد؛ تکمیل مجدد یک پلان نباید کل پروژه را دوباره بسازد.</div>
        </div>
      </section>

      <section class="vpc2-panel" data-vpc2-step-panel="6">
        <div class="vpc2-review-grid"><div class="vpc2-card"><div class="vpc2-card-head"><div><i class="fa-solid fa-list-check"></i><span><b>بازبینی قرارداد محصول</b><small>این خلاصه رفتار صفحه‌ی کاربر را نشان می‌دهد.</small></span></div><em>گام ۶</em></div><div class="vpc2-review-list" id="vpc2-review-list"></div></div><div class="vpc2-card"><div class="vpc2-card-head"><div><i class="fa-solid fa-rocket"></i><span><b>نمایش و انتشار</b><small>برچسب‌های نمایش در گام ۱ ثبت محصول عکس تنظیم شده‌اند.</small></span></div></div><label class="vpc2-field"><span>زمان تقریبی پردازش</span><input type="number" name="estimated_time" min="10" max="3600" value="{{ old('estimated_time', $product?->estimated_time ?? 180) }}"><small>ثانیه؛ برای اطلاع کاربر</small></label></div></div>
        <div class="vpc2-card vpc2-user-preview-card">
          <div class="vpc2-card-head"><div><i class="fa-solid fa-display"></i><span><b>پیش‌نمایش صفحه بساز محصول</b><small>نمایش شبیه‌سازی‌شده‌ی همان چیزی که کاربر برای این محصول می‌بیند.</small></span></div><em>زنده</em></div>
          <div class="vpc2-preview-note"><i class="fa-solid fa-circle-info"></i>این پیش‌نمایش با تغییر ورودی‌ها، حرکت‌ها، مدت، نسبت تصویر و ساختار پلان به‌صورت خودکار به‌روزرسانی می‌شود.</div>
          <div id="vpc2-user-preview" class="vpc2-user-preview" aria-live="polite"></div>
        </div>
      </section>
    </form>
  </div>
  <footer class="vpc2-footer"><button type="button" class="btn-pro btn-pro-ghost" id="vpc2-prev"><i class="fa-solid fa-arrow-right"></i> مرحله قبل</button><div class="vpc2-progress"><span id="vpc2-progress-label">گام ۱ از ۶</span><i><b id="vpc2-progress-bar"></b></i></div><div><button type="button" class="btn-pro btn-pro-ghost" id="vpc2-draft"><i class="fa-solid fa-floppy-disk"></i> ذخیره پیش‌نویس</button><button type="button" class="btn-pro btn-pro-primary" id="vpc2-next">مرحله بعد <i class="fa-solid fa-arrow-left"></i></button><button type="button" class="btn-pro btn-pro-primary" id="vpc2-publish" hidden><i class="fa-solid fa-rocket"></i> انتشار محصول ویدیو</button></div></footer>
</main>
@endsection

@section('scripts')
<script>window.VIDEO_PRODUCT_ADMIN_V2 = @json($adminClientConfig);</script>
<script src="{{ asset('admin/js/video-products-create-v2.js') }}?v={{ filemtime(public_path('admin/js/video-products-create-v2.js')) }}"></script>
@endsection
