@extends('layouts.admin')
@section('title', $product ? 'ویرایش محصول ویدیو — AIPIX Admin' : 'ثبت محصولات ویدیو — AIPIX Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/video-products-create.css') }}?v={{ filemtime(public_path('admin/css/video-products-create.css')) }}">
@endpush

@php
  $selectedModel = $product ? $models->first(fn($model) => $model->provider === $product->ai_provider && $model->openrouter_model_id === $product->primary_model) : $models->firstWhere('external_model_id', 'fal-ai/wan/v2.2-a14b/image-to-video/turbo');
  $selectedModel ??= $models->first(fn($model) => ($model->video_capabilities['task_type'] ?? '') === ($configuration['workflow'] ?? 'image_to_video'));
  $selectedCategoryIds = collect(old('category_ids', $product?->categories()->pluck('categories.id')->all() ?? array_filter([$product?->category_id])))->map(fn($id) => (int)$id)->all();
  $features = old('features_json') ? json_decode(old('features_json'), true) : (array)($product?->input_schema ?? []);
  $selectedMotionKeys = collect($configuration['motion_presets'] ?? [])->map(fn($preset) => is_array($preset) ? ($preset['key'] ?? '') : $preset)->filter()->all();
  $fallbackIds = $product ? $models->filter(fn($model) => in_array($model->openrouter_model_id, (array)$product->fallback_models, true) && in_array($model->provider, (array)$product->fallback_model_providers, true))->pluck('id')->all() : [];
  $adminClientConfig = [
      'features' => $features,
      'models' => $models->map(fn($model) => [
          'id' => $model->id,
          'name' => $model->name,
          'provider' => $model->provider,
          'model_id' => $model->openrouter_model_id,
          'capabilities' => $model->video_capabilities,
      ])->values()->all(),
  ];
@endphp

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto p-6 pb-20 max-[768px]:p-4" id="content">
    <div class="vpc-page-head">
      <div>
        <div class="vpc-breadcrumb"><a href="{{ route('admin.products') }}">محصولات</a><i class="fa-solid fa-chevron-left"></i><span>{{ $product ? 'ویرایش محصول ویدیو' : 'ثبت محصولات ویدیو' }}</span></div>
        <h1>{{ $product ? 'ویرایش محصول ویدیو' : 'ثبت محصولات ویدیو' }}</h1>
        <p>ساخت یک محصول کامل ویدیویی در ۶ گام؛ از سناریو و مدل تا ویژگی‌ها، اعتبار و انتشار</p>
      </div>
      <div class="vpc-head-actions">
        <a href="{{ route('admin.products') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right"></i> بازگشت به لیست</a>
        @if($product && $product->status === 'active')
          <a href="{{ route('app.create', ['product' => $product->route_slug]) }}" target="_blank" class="btn-pro btn-pro-primary"><i class="fa-solid fa-arrow-up-right-from-square"></i> مشاهده صفحه ساخت</a>
        @endif
      </div>
    </div>

    @if(session('success'))<div class="vpc-alert is-success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>@endif
    @if($errors->any())
      <div class="vpc-alert is-danger"><i class="fa-solid fa-triangle-exclamation"></i><div><b>موارد زیر را اصلاح کنید:</b><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif
    @if($models->isEmpty())
      <div class="vpc-alert is-warning"><i class="fa-solid fa-circle-exclamation"></i>هیچ مدل ویدیویی فعالی از سرویس‌های صف‌دار ثبت نشده است.</div>
    @endif

    <nav class="vpc-stepper" aria-label="مراحل ثبت محصول">
      @foreach([
        1 => ['هویت و رسانه','نام، دسته و نمونه'],
        2 => ['سناریوی ساخت','ورودی و پروفایل چهره'],
        3 => ['مدل و پرامپت','مدل اصلی و جایگزین'],
        4 => ['ویژگی‌های محصول','فرم قابل تنظیم کاربر'],
        5 => ['خروجی و اعتبار','حرکت، مدت و هزینه'],
        6 => ['بازبینی و انتشار','کنترل نهایی'],
      ] as $step => [$title, $description])
        <button type="button" class="vpc-step {{ $step === 1 ? 'is-active' : '' }}" data-step-tab="{{ $step }}">
          <span>{{ $step }}</span><div><b>{{ $title }}</b><small>{{ $description }}</small></div><i class="fa-solid fa-check"></i>
        </button>
      @endforeach
    </nav>

    <form id="video-product-form" method="POST" enctype="multipart/form-data" action="{{ $product ? route('admin.products.video.update', $product) : route('admin.products.video.store') }}">
      @csrf
      @if($product) @method('PUT') @endif
      <input type="hidden" name="status" id="vpc-status" value="{{ old('status', $product?->status ?? 'active') }}">
      <input type="hidden" name="features_json" id="vpc-features-json" value="{{ json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">

      <section class="vpc-panel is-active" data-step-panel="1">
        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-fingerprint"></i><span><b>هویت محصول</b><small>اطلاعاتی که در کارت، جستجو و صفحه ساخت دیده می‌شود</small></span></div><em>گام ۱</em></div>
          <div class="vpc-grid cols-2">
            <label class="vpc-field"><span>نام فارسی <b>*</b></span><input name="name_fa" value="{{ old('name_fa', $product?->name_fa) }}" required placeholder="مثلاً حرکت سینمایی چهره"></label>
            <label class="vpc-field"><span>نام انگلیسی <b>*</b></span><input name="name_en" value="{{ old('name_en', $product?->name_en) }}" required dir="ltr" placeholder="Cinematic Face Motion"></label>
            <label class="vpc-field"><span>نشانی محصول <b>*</b></span><input name="slug" id="vpc-slug" value="{{ old('slug', $product?->slug) }}" required dir="ltr" placeholder="cinematic-face-motion"><small>نشانی یکتا و انگلیسی صفحه محصول</small></label>
            <label class="vpc-field"><span>برچسب‌های جستجو</span><input name="tags" value="{{ old('tags', implode('، ', (array)$product?->tags)) }}" placeholder="چهره، سینمایی، ریلز"><small>با ویرگول از هم جدا کنید</small></label>
            <label class="vpc-field span-2"><span>توضیح فارسی</span><textarea name="description_fa" rows="3" placeholder="خروجی و کاربرد این محصول را برای کاربر توضیح دهید">{{ old('description_fa', $product?->description_fa) }}</textarea></label>
            <label class="vpc-field span-2"><span>توضیح انگلیسی</span><textarea name="description_en" rows="2" dir="ltr">{{ old('description_en', $product?->description_en) }}</textarea></label>
          </div>
        </div>

        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-layer-group"></i><span><b>دسته‌بندی</b><small>حداقل یک دسته برای انتشار انتخاب کنید</small></span></div></div>
          <div class="vpc-category-grid">
            @foreach($categories as $category)
              <label><input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $selectedCategoryIds, true))><span><i class="fa-solid fa-folder"></i>{{ $category->name_fa ?: $category->name }}</span></label>
            @endforeach
          </div>
        </div>

        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-photo-film"></i><span><b>رسانه محصول</b><small>کاور برای کارت محصول و ویدیوی کوتاه برای پیش‌نمایش</small></span></div></div>
          <div class="vpc-media-grid">
            <label class="vpc-upload">
              <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp,image/avif" data-preview-image>
              <i class="fa-solid fa-image"></i><b>تصویر کاور</b><small>عمودی یا مربع، حداکثر ۱۲ مگابایت</small><span>انتخاب تصویر</span>
              <img data-image-preview @if($product?->cover) src="{{ $product->displayImageUrl() }}" @endif alt="">
            </label>
            <label class="vpc-upload">
              <input type="file" name="preview_video" accept="video/mp4,video/webm,video/quicktime" data-preview-video>
              <i class="fa-solid fa-video"></i><b>ویدیوی پیش‌نمایش</b><small>ترجیحاً ۳ تا ۸ ثانیه، حداکثر ۱۰۰ مگابایت</small><span>انتخاب ویدیو</span>
              <video data-video-preview @if($product?->previewVideoUrl()) src="{{ $product->previewVideoUrl() }}" @endif muted loop playsinline></video>
            </label>
          </div>
          <label class="vpc-field mt-4"><span>یا نشانی ویدیوی پیش‌نمایش</span><input name="preview_video_url" value="{{ old('preview_video_url', $product?->preview_video_url) }}" dir="ltr" placeholder="https://... یا /assets/videos/example.mp4"></label>
        </div>
      </section>

      <section class="vpc-panel" data-step-panel="2">
        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-route"></i><span><b>سناریوی ساخت</b><small>ورودی اصلی کاربر را مشخص کنید</small></span></div><em>گام ۲</em></div>
          <div class="vpc-choice-grid cols-3">
            @foreach([
              'text_to_video' => ['fa-align-right','متن به ویدیو','کاربر صحنه را توصیف می‌کند و مدل ویدیو را از صفر می‌سازد.'],
              'image_to_video' => ['fa-image','عکس به ویدیو','عکس یا پروفایل چهره به یک شات متحرک تبدیل می‌شود.'],
              'video_to_video' => ['fa-film','ویدیو به ویدیو','ویدیوی ورودی با سبک یا حرکت تازه بازطراحی می‌شود.'],
            ] as $value => [$icon,$label,$copy])
              <label class="vpc-choice"><input type="radio" name="workflow" value="{{ $value }}" @checked(old('workflow', $configuration['workflow']) === $value)><span><i class="fa-solid {{ $icon }}"></i><b>{{ $label }}</b><small>{{ $copy }}</small><em><i class="fa-solid fa-check"></i></em></span></label>
            @endforeach
          </div>
        </div>

        <div class="vpc-card" data-face-profile-card>
          <div class="vpc-card-head"><div><i class="fa-solid fa-user-check"></i><span><b>اتصال به پروفایل چهره</b><small>کاربر می‌تواند تصاویر ذخیره‌شده پروفایل را به‌جای بارگذاری دوباره استفاده کند</small></span></div><span class="badge-pro badge-pro-success">متصل به پروفایل</span></div>
          <div class="vpc-choice-grid cols-3">
            @foreach([
              'disabled' => ['بدون چهره','این محصول به تصویر هویتی نیاز ندارد.'],
              'optional' => ['اختیاری','کاربر بین پروفایل ذخیره‌شده و عکس جدید انتخاب می‌کند.'],
              'required' => ['الزامی','ساخت فقط با پروفایل چهره یا عکس جدید ممکن است.'],
            ] as $value => [$label,$copy])
              <label class="vpc-choice compact"><input type="radio" name="face_profile_mode" value="{{ $value }}" @checked(old('face_profile_mode', $configuration['face_profile_mode']) === $value)><span><b>{{ $label }}</b><small>{{ $copy }}</small><em><i class="fa-solid fa-check"></i></em></span></label>
            @endforeach
          </div>
          <div class="vpc-inline-note"><i class="fa-solid fa-circle-info"></i>در صفحه ساخت، پروفایل‌های فعال همان کاربر خوانده می‌شوند و فقط تصاویر متعلق به خودش اجازه استفاده دارند.</div>
        </div>
      </section>

      <section class="vpc-panel" data-step-panel="3">
        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-microchip"></i><span><b>مدل هوش مصنوعی</b><small>فهرست فقط مدل‌های ویدیویی فعال و صف‌دار را نشان می‌دهد</small></span></div><em>گام ۳</em></div>
          <div class="vpc-grid cols-2">
            <label class="vpc-field span-2"><span>مدل اصلی <b>*</b></span>
              <select name="model_id" id="vpc-model" required>
                <option value="">انتخاب مدل سازگار...</option>
                @foreach($models as $model)
                  <option value="{{ $model->id }}" data-workflow="{{ $model->task_type === 'face_animation' ? 'image_to_video' : $model->task_type }}" @selected((int)old('model_id', $selectedModel?->id) === $model->id)>{{ $model->name }} — {{ $model->provider }} — {{ $model->taskLabel() }}</option>
                @endforeach
              </select>
            </label>
            <div class="vpc-model-summary span-2" id="vpc-model-summary"><i class="fa-solid fa-spinner fa-spin"></i><span>در حال خواندن قابلیت‌های مدل...</span></div>
            <label class="vpc-field span-2"><span>مدل‌های جایگزین</span>
              <select name="fallback_model_ids[]" id="vpc-fallback-models" multiple size="5">
                @foreach($models as $model)<option value="{{ $model->id }}" data-workflow="{{ $model->task_type === 'face_animation' ? 'image_to_video' : $model->task_type }}" @selected(in_array($model->id, old('fallback_model_ids', $fallbackIds), true))>{{ $model->name }} — {{ $model->provider }}</option>@endforeach
              </select><small>حداکثر سه مدل هم‌سناریو؛ فقط برای خطای ارسال اولیه استفاده می‌شوند</small>
            </label>
          </div>
        </div>
        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-wand-magic-sparkles"></i><span><b>راهبری پرامپت</b><small>قالب ثابت محصول با مقادیر ویژگی‌های کاربر ترکیب می‌شود</small></span></div></div>
          <div class="vpc-grid cols-2">
            <label class="vpc-field span-2"><span>قالب پرامپت <b>*</b></span><textarea name="prompt_template" rows="6" required dir="ltr">{{ old('prompt_template', $product?->prompt_template ?? 'Create a polished cinematic video based on this direction: {creative_direction}.') }}</textarea><small>از شناسه ویژگی‌ها داخل آکولاد استفاده کنید؛ مثل <code>{creative_direction}</code></small></label>
            <label class="vpc-field"><span>دستور سیستمی</span><textarea name="system_prompt" rows="4" dir="ltr">{{ old('system_prompt', $product?->system_prompt ?? 'Preserve subject identity and geometry across all frames. Prefer coherent natural motion and stable temporal detail.') }}</textarea></label>
            <label class="vpc-field"><span>موارد منفی</span><textarea name="negative_prompt" rows="4" dir="ltr">{{ old('negative_prompt', $product?->negative_prompt ?? 'flicker, jitter, warped face, unstable geometry, abrupt camera shake') }}</textarea></label>
          </div>
        </div>
      </section>

      <section class="vpc-panel" data-step-panel="4">
        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-sliders"></i><span><b>ویژگی‌های محصول</b><small>هر ویژگی به یک کنترل واقعی در صفحه ساخت و بخشی از پرامپت تبدیل می‌شود</small></span></div><em>گام ۴</em></div>
          <div class="vpc-feature-presets">
            <button type="button" data-add-feature="direction"><i class="fa-solid fa-align-right"></i> توضیح صحنه</button>
            <button type="button" data-add-feature="style"><i class="fa-solid fa-palette"></i> استایل بصری</button>
            <button type="button" data-add-feature="mood"><i class="fa-solid fa-face-smile"></i> حس‌وحال</button>
            <button type="button" data-add-feature="intensity"><i class="fa-solid fa-gauge-high"></i> شدت حرکت</button>
            <button type="button" data-add-feature="toggle"><i class="fa-solid fa-toggle-on"></i> گزینه روشن/خاموش</button>
            <button type="button" data-add-feature="custom"><i class="fa-solid fa-plus"></i> ویژگی دلخواه</button>
          </div>
          <div class="vpc-feature-list" id="vpc-feature-list"></div>
          <div class="vpc-empty" id="vpc-feature-empty"><i class="fa-solid fa-sliders"></i><b>هنوز ویژگی‌ای اضافه نشده</b><small>برای محصول استاندارد، حداقل «توضیح صحنه» را اضافه کنید.</small></div>
        </div>
      </section>

      <section class="vpc-panel" data-step-panel="5">
        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-clock"></i><span><b>مدت و مصرف اعتبار</b><small>هزینه قبل از ساخت به کاربر نمایش داده می‌شود</small></span></div><em>گام ۵</em></div>
          <div class="vpc-duration-grid">
            @foreach([2,3,4,5,6,8,10,15] as $duration)
              @php $enabled = in_array($duration, array_map('intval', (array)$configuration['durations']), true); @endphp
              <div class="vpc-duration {{ $enabled ? 'is-enabled' : '' }}">
                <label><input type="checkbox" name="durations[]" value="{{ $duration }}" @checked($enabled)><span><b>{{ $duration }} ثانیه</b><small>فعال برای کاربر</small></span></label>
                <input type="number" name="credit_costs_by_duration[{{ $duration }}]" value="{{ old('credit_costs_by_duration.'.$duration, $configuration['credit_costs_by_duration'][(string)$duration] ?? '') }}" min="0" placeholder="اعتبار" aria-label="اعتبار مدت {{ $duration }} ثانیه">
              </div>
            @endforeach
          </div>
          <label class="vpc-field mt-4"><span>مدت پیش‌فرض</span><select name="default_duration" id="vpc-default-duration">@foreach([2,3,4,5,6,8,10,15] as $duration)<option value="{{ $duration }}" @selected((int)old('default_duration', $configuration['default_duration']) === $duration)>{{ $duration }} ثانیه</option>@endforeach</select></label>
        </div>

        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-crop-simple"></i><span><b>قاب و کیفیت خروجی</b><small>گزینه‌های قابل انتخاب در صفحه ساخت</small></span></div></div>
          <div class="vpc-output-columns">
            <div><b>نسبت تصویر</b><div class="vpc-check-grid">@foreach(\App\Services\VideoProductConfigService::ASPECT_RATIOS as $ratio)<label><input type="checkbox" name="aspect_ratios[]" value="{{ $ratio }}" @checked(in_array($ratio, (array)$configuration['aspect_ratios'], true))><span dir="ltr">{{ $ratio }}</span></label>@endforeach</div><label class="vpc-field"><span>پیش‌فرض</span><select name="default_aspect_ratio">@foreach(\App\Services\VideoProductConfigService::ASPECT_RATIOS as $ratio)<option value="{{ $ratio }}" @selected($configuration['default_aspect_ratio'] === $ratio)>{{ $ratio }}</option>@endforeach</select></label></div>
            <div><b>رزولوشن</b><div class="vpc-check-grid">@foreach(\App\Services\VideoProductConfigService::RESOLUTIONS as $resolution)<label><input type="checkbox" name="resolutions[]" value="{{ $resolution }}" @checked(in_array($resolution, (array)$configuration['resolutions'], true))><span>{{ $resolution }}</span></label>@endforeach</div><label class="vpc-field"><span>پیش‌فرض</span><select name="default_resolution">@foreach(\App\Services\VideoProductConfigService::RESOLUTIONS as $resolution)<option value="{{ $resolution }}" @selected($configuration['default_resolution'] === $resolution)>{{ $resolution }}</option>@endforeach</select></label></div>
            <label class="vpc-field"><span>نرخ فریم</span><input type="number" name="fps" min="4" max="60" value="{{ old('fps', $configuration['fps']) }}"><small>در مدل‌هایی که این تنظیم را پشتیبانی کنند</small></label>
          </div>
          <div class="vpc-quality-tiers">
            <b>سطح کیفیت برای کاربر</b>
            <div class="vpc-choice-grid cols-3">
              @foreach([['standard','استاندارد','720p'],['professional','حرفه‌ای','1080p'],['best','بهترین خروجی','4K']] as [$key,$label,$resolution])
                <label class="vpc-choice compact"><input type="radio" name="quality_tier" value="{{ $key }}" @checked($key === 'standard')><span><b>{{ $label }}</b><small>{{ $resolution }} · هزینه بر اساس مدت و کیفیت محاسبه می‌شود</small><em><i class="fa-solid fa-check"></i></em></span></label>
              @endforeach
            </div>
          </div>
        </div>

        <div class="vpc-card">
          <div class="vpc-card-head"><div><i class="fa-solid fa-video"></i><span><b>حرکت‌های آماده</b><small>مانند تجربه انتخاب حرکت در ابزارهای ویدیویی حرفه‌ای</small></span></div></div>
          <div class="vpc-motion-grid">@foreach($motionCatalog as $key => $preset)<label><input type="checkbox" name="motion_presets[]" value="{{ $key }}" @checked(in_array($key, $selectedMotionKeys, true))><span><i class="fa-solid fa-camera-retro"></i><b>{{ $preset['label'] }}</b><small>{{ $preset['description'] }}</small></span></label>@endforeach</div>
          <div class="vpc-toggle-list">
            <label><input type="hidden" name="prompt_enhance" value="0"><input type="checkbox" name="prompt_enhance" value="1" @checked($configuration['prompt_enhance'])><span><b>بهبود هوشمند پرامپت</b><small>جزئیات سینمایی بدون تغییر منظور کاربر افزوده شود</small></span><i></i></label>
            <label><input type="hidden" name="allow_promotional_credits" value="0"><input type="checkbox" name="allow_promotional_credits" value="1" @checked($configuration['allow_promotional_credits'])><span><b>اجازه مصرف اعتبار هدیه</b><small>برای محصولات اقتصادی و نمونه‌های شروع</small></span><i></i></label>
            <label><input type="hidden" name="audio_allowed" value="0"><input type="checkbox" name="audio_allowed" value="1" @checked($configuration['audio_allowed'])><span><b>صدای همگام</b><small>فقط وقتی مدل اصلی ورودی یا تولید صدا را پشتیبانی کند</small></span><i></i></label>
            <label><input type="hidden" name="audio_default" value="0"><input type="checkbox" name="audio_default" value="1" @checked($configuration['audio_default'])><span><b>صدا به‌صورت پیش‌فرض روشن</b><small>کاربر همچنان می‌تواند آن را خاموش کند</small></span><i></i></label>
          </div>
        </div>
      </section>

      <section class="vpc-panel" data-step-panel="6">
        <div class="vpc-review-grid">
          <div class="vpc-card">
            <div class="vpc-card-head"><div><i class="fa-solid fa-list-check"></i><span><b>خلاصه محصول</b><small>پیش از انتشار، رفتار نهایی را کنترل کنید</small></span></div><em>گام ۶</em></div>
            <div class="vpc-review-list" id="vpc-review-list"></div>
          </div>
          <div class="vpc-card">
            <div class="vpc-card-head"><div><i class="fa-solid fa-rocket"></i><span><b>نمایش و انتشار</b><small>برچسب‌های جایگاه محصول</small></span></div></div>
            <div class="vpc-toggle-list">
              <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product?->is_featured))><span><b>محصول ویژه</b><small>نمایش در بخش‌های منتخب</small></span><i></i></label>
              <label><input type="checkbox" name="is_new" value="1" @checked(old('is_new', $product?->is_new ?? true))><span><b>نشان جدید</b><small>نمایش برچسب محصول تازه</small></span><i></i></label>
              <label><input type="checkbox" name="is_trending" value="1" @checked(old('is_trending', $product?->is_trending))><span><b>ترند</b><small>واجد نمایش در بخش ترندها</small></span><i></i></label>
            </div>
            <label class="vpc-field mt-4"><span>زمان تقریبی پردازش</span><input type="number" name="estimated_time" min="10" max="3600" value="{{ old('estimated_time', $product?->estimated_time ?? 180) }}"><small>ثانیه؛ فقط برای اطلاع کاربر</small></label>
          </div>
        </div>
      </section>
    </form>
  </div>

  <footer class="vpc-footer">
    <button type="button" class="btn-pro btn-pro-ghost" id="vpc-prev"><i class="fa-solid fa-arrow-right"></i> مرحله قبل</button>
    <div class="vpc-footer-progress"><span id="vpc-progress-label">گام ۱ از ۶</span><i><b id="vpc-progress-bar"></b></i></div>
    <div>
      <button type="button" class="btn-pro btn-pro-ghost" id="vpc-draft"><i class="fa-solid fa-floppy-disk"></i> ذخیره پیش‌نویس</button>
      <button type="button" class="btn-pro btn-pro-primary" id="vpc-next">مرحله بعد <i class="fa-solid fa-arrow-left"></i></button>
      <button type="button" class="btn-pro btn-pro-primary" id="vpc-publish" hidden><i class="fa-solid fa-rocket"></i> انتشار محصول ویدیو</button>
    </div>
  </footer>
</main>
@endsection

@section('scripts')
<script>
window.VIDEO_PRODUCT_ADMIN = @json($adminClientConfig);
</script>
<script src="{{ asset('admin/js/video-products-create.js') }}?v={{ filemtime(public_path('admin/js/video-products-create.js')) }}"></script>
@endsection
