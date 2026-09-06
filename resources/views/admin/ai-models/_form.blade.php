@php
  $editing = isset($model);
  $currentProvider = old('provider', $model->provider ?? ($selectedProvider ?? 'replicate'));
  $selectedCategoryIds = collect(old('category_ids', $model->recommended_category_ids ?? []))->map(fn ($id) => (int) $id)->all();
  $externalId = old('external_model_id', $model->external_model_id ?? $model->openrouter_model_id ?? '');
  $inputSchema = old('input_schema', json_encode($model->input_schema ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  $capabilities = old('capability_config', json_encode($model->capability_config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  $pricing = old('pricing_config', json_encode($model->pricing_config ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
@endphp

<input type="hidden" name="openrouter_model_id" id="legacy-model-id" value="{{ old('openrouter_model_id', $model->openrouter_model_id ?? $externalId) }}">
<input type="hidden" name="is_active" id="model-active" value="{{ old('is_active', ($model->is_active ?? true) ? '1' : '0') }}">

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 items-start">
  <div class="xl:col-span-2 space-y-5">
    <section class="content-card">
      <div class="flex items-center gap-2 border-b border-[var(--border)] pb-3 mb-4"><i class="fa-solid fa-circle-nodes text-[var(--primary)]"></i><h2 class="text-sm font-extrabold text-[var(--text-h)] m-0">هویت و مسیر اتصال مدل</h2></div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <label class="form-label">نام نمایشی مدل<input class="input-pro mt-1 w-full" name="name" value="{{ old('name', $model->name ?? '') }}" required></label>
        <label class="form-label">نام شرکت سازنده<input class="input-pro mt-1 w-full" name="provider_name" value="{{ old('provider_name', $model->provider_name ?? '') }}" required></label>
        <label class="form-label md:col-span-2">شناسه‌ی خارجی مدل
          <input class="input-pro mt-1 w-full ltr text-left font-mono" dir="ltr" id="external-model-id" name="external_model_id" value="{{ $externalId }}" placeholder="مثلاً fal-ai/flux/schnell یا black-forest-labs/flux-schnell" required>
          <span class="form-help">در `Replicate` این مقدار `owner/name` است؛ در `Fal.ai` همان endpoint مدل است.</span>
        </label>
        <label class="form-label">نسخه‌ی مدل (`Replicate`)<input class="input-pro mt-1 w-full ltr text-left font-mono" dir="ltr" name="external_version" value="{{ old('external_version', $model->external_version ?? '') }}" placeholder="اختیاری برای مدل‌های رسمی"></label>
        <label class="form-label">provider
          <select class="input-pro mt-1 w-full" name="provider" id="provider-select" required>
            @foreach(['fal' => 'Fal.ai', 'replicate' => 'Replicate', 'liara' => 'Liara AI', 'openrouter' => 'OpenRouter'] as $key => $label)
              <option value="{{ $key }}" @selected($currentProvider === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label class="form-label">نوع خروجی
          <select class="input-pro mt-1 w-full" name="output_modality" required>
            @foreach(['image' => 'عکس', 'video' => 'ویدیو', 'text' => 'متن', 'audio' => 'صدا'] as $key => $label)<option value="{{ $key }}" @selected(old('output_modality', $model->output_modality ?? 'image') === $key)>{{ $label }}</option>@endforeach
          </select>
        </label>
        <label class="form-label">نوع کاربرد مدل
          <select class="input-pro mt-1 w-full" name="task_type">
            @foreach(['text_to_image' => 'متن به عکس', 'image_to_image' => 'عکس به عکس', 'text_to_video' => 'متن به ویدیو', 'image_to_video' => 'عکس به ویدیو', 'video_to_video' => 'ویدیو به ویدیو', 'face_consistency' => 'حفظ هویت چهره', 'face_animation' => 'متحرک‌سازی چهره', 'upscaling' => 'افزایش کیفیت'] as $key => $label)<option value="{{ $key }}" @selected(old('task_type', $model->task_type ?? '') === $key)>{{ $label }}</option>@endforeach
          </select>
        </label>
        <label class="form-label">حداکثر رزولوشن<input class="input-pro mt-1 w-full ltr text-left" dir="ltr" name="max_resolution" value="{{ old('max_resolution', $model->max_resolution ?? '') }}" placeholder="مثلاً 2K یا 2048x2048"></label>
        <label class="form-label">حداکثر مدت ویدیو (ثانیه)<input class="input-pro mt-1 w-full ltr text-left" dir="ltr" type="number" min="1" max="3600" name="max_duration" value="{{ old('max_duration', $model->max_duration ?? '') }}"></label>
        <label class="form-label md:col-span-2">توضیحات<textarea class="input-pro mt-1 w-full min-h-[78px]" name="description">{{ old('description', $model->description ?? '') }}</textarea></label>
      </div>
    </section>

    <section class="content-card">
      <div class="flex items-center gap-2 border-b border-[var(--border)] pb-3 mb-4"><i class="fa-solid fa-code text-[var(--info)]"></i><h2 class="text-sm font-extrabold text-[var(--text-h)] m-0">schema و پارامترهای قابل‌ارسال</h2></div>
      <p class="text-[11px] text-[var(--text-soft)] leading-6 mt-0">فقط کلیدهایی که در `allowed_inputs` یا `input_schema.properties` قرار می‌دهی به provider ارسال می‌شوند. اگر schema مدل متفاوت است، آن را همین‌جا ثبت کن.</p>
      <div class="space-y-3">
        <label class="form-label">پارامترهای پیش‌فرض<textarea class="input-pro mt-1 w-full min-h-[100px] ltr text-left font-mono" dir="ltr" name="default_parameters">{{ old('default_parameters', $model->default_parameters ?? '{}') }}</textarea></label>
        <label class="form-label">Input Schema به شکل JSON<textarea class="input-pro mt-1 w-full min-h-[150px] ltr text-left font-mono" dir="ltr" name="input_schema" required>{{ $inputSchema }}</textarea></label>
        <label class="form-label">Capability Config به شکل JSON<textarea class="input-pro mt-1 w-full min-h-[150px] ltr text-left font-mono" dir="ltr" name="capability_config" required>{{ $capabilities }}</textarea></label>
      </div>
    </section>
  </div>

  <div class="space-y-5">
    <section class="content-card">
      <div class="flex items-center justify-between gap-3 mb-4"><div><h2 class="text-sm font-extrabold text-[var(--text-h)] m-0">وضعیت و هزینه</h2><p class="form-help mt-1">مدل غیرفعال در ثبت محصول نمایش داده نمی‌شود.</p></div><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" class="sr-only peer" id="active-toggle" @checked(old('is_active', $model->is_active ?? true) == 1)><span class="w-10 h-6 rounded-full bg-[var(--border)] peer-checked:bg-[var(--primary)] after:content-[''] after:absolute after:top-1 after:right-1 after:w-4 after:h-4 after:rounded-full after:bg-[var(--text-soft)] peer-checked:after:bg-[var(--accent)] peer-checked:after:-translate-x-4 after:transition"></span></label></div>
      <div class="space-y-3">
        <label class="form-label">هزینه به توکن<input class="input-pro mt-1 w-full ltr text-left" dir="ltr" type="number" min="0" name="cost_per_generation" value="{{ old('cost_per_generation', $model->cost_per_generation ?? 0) }}" required></label>
        <label class="form-label">قیمت تخمینی provider به دلار<input class="input-pro mt-1 w-full ltr text-left" dir="ltr" type="number" min="0" step="0.000001" name="cost_per_generation_usd" value="{{ old('cost_per_generation_usd', $model->cost_per_generation_usd ?? '') }}"></label>
        <label class="form-label">Pricing Config به شکل JSON<textarea class="input-pro mt-1 w-full min-h-[90px] ltr text-left font-mono" dir="ltr" name="pricing_config" required>{{ $pricing }}</textarea></label>
        <div class="grid grid-cols-2 gap-2"><label class="form-label">عرض<input class="input-pro mt-1 w-full ltr text-left" dir="ltr" type="number" min="1" name="default_width" value="{{ old('default_width', $model->default_width ?? 1024) }}"></label><label class="form-label">ارتفاع<input class="input-pro mt-1 w-full ltr text-left" dir="ltr" type="number" min="1" name="default_height" value="{{ old('default_height', $model->default_height ?? 1024) }}"></label></div>
        <input type="hidden" name="supports_image_input" value="0"><label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-main)]"><input type="checkbox" name="supports_image_input" value="1" @checked(old('supports_image_input', $model->supports_image_input ?? false) == 1)> پشتیبانی از تصویر ورودی</label>
        <input type="hidden" name="supports_face_identity" value="0"><label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-main)]"><input type="checkbox" name="supports_face_identity" value="1" @checked(old('supports_face_identity', $model->supports_face_identity ?? false) == 1)> حفظ هویت چهره</label>
        <input type="hidden" name="supports_multiple_faces" value="0"><label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-main)]"><input type="checkbox" name="supports_multiple_faces" value="1" @checked(old('supports_multiple_faces', $model->supports_multiple_faces ?? false) == 1)> چند چهره هم‌زمان</label>
        <input type="hidden" name="supports_audio" value="0"><label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-main)]"><input type="checkbox" name="supports_audio" value="1" @checked(old('supports_audio', $model->supports_audio ?? false) == 1)> پشتیبانی از صدا</label>
        <input type="hidden" name="supports_video_input" value="0"><label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-main)]"><input type="checkbox" name="supports_video_input" value="1" @checked(old('supports_video_input', $model->supports_video_input ?? false) == 1)> پشتیبانی از ویدیوی ورودی</label>
        <label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-main)]"><input type="checkbox" name="supports_webhook" value="1" @checked(old('supports_webhook', $model->supports_webhook ?? false) == 1)> پشتیبانی از وب‌هوک</label>
        <label class="form-label">روش قیمت‌گذاری
          <select class="input-pro mt-1 w-full" name="pricing_type"><option value="">نامشخص</option>@foreach(['per_generation' => 'برای هر خروجی', 'per_second' => 'برای هر ثانیه', 'per_megapixel' => 'برای هر مگاپیکسل', 'per_gpu_second' => 'برای هر ثانیه GPU', 'unknown' => 'نامشخص'] as $key => $label)<option value="{{ $key }}" @selected(old('pricing_type', $model->pricing_type ?? '') === $key)>{{ $label }}</option>@endforeach</select>
        </label>
        <input type="hidden" name="commercial_use" value="0"><label class="inline-flex items-center gap-2 text-[11px] text-[var(--text-main)]"><input type="checkbox" name="commercial_use" value="1" @checked(old('commercial_use', $model->commercial_use ?? false) == 1)> استفاده تجاری مجاز است</label>
      </div>
    </section>
    <section class="content-card">
      <div class="flex items-center gap-2 border-b border-[var(--border)] pb-3 mb-3"><i class="fa-solid fa-layer-group text-[var(--primary)]"></i><div><h2 class="text-sm font-extrabold text-[var(--text-h)] m-0">دسته‌های پیشنهادی آزمایشگاه</h2><p class="form-help mt-1">این مدل برای محصولات کدام دسته‌ها مناسب‌تر است؟</p></div></div>
      <div class="grid grid-cols-1 gap-2">
        @forelse($categories as $category)
          <label class="category-model-choice flex items-center gap-2 p-2.5 rounded-lg border border-[var(--border)] bg-[var(--input-bg)] cursor-pointer transition-colors hover:border-[var(--primary)]">
            <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $selectedCategoryIds, true))>
            <span class="text-[11px] text-[var(--text-main)]">{{ $category->name_fa }}</span>
            <small class="mr-auto text-[9px] text-[var(--text-soft)]" dir="ltr">{{ $category->slug }}</small>
          </label>
        @empty
          <p class="form-help">هنوز دسته‌ی ریشه‌ای فعالی ثبت نشده است.</p>
        @endforelse
      </div>
    </section>
    <section class="content-card">
      <div class="space-y-3"><label class="form-label">Terms URL<input class="input-pro mt-1 w-full ltr text-left" dir="ltr" name="terms_url" value="{{ old('terms_url', $model->terms_url ?? '') }}"></label><label class="form-label">یادداشت ماندگاری داده<textarea class="input-pro mt-1 w-full min-h-[75px]" name="data_retention_notes">{{ old('data_retention_notes', $model->data_retention_notes ?? '') }}</textarea></label></div>
    </section>
  </div>
</div>

@push('styles')
<style>
  .form-label { display:block; font-size:11px; font-weight:700; color:var(--text-main); }
  .form-help { display:block; font-size:10px; color:var(--text-soft); line-height:1.8; }
</style>
@endpush

<script>
  const externalId = document.getElementById('external-model-id');
  const legacyId = document.getElementById('legacy-model-id');
  const activeToggle = document.getElementById('active-toggle');
  externalId?.addEventListener('input', () => { if (legacyId) legacyId.value = externalId.value; });
  activeToggle?.addEventListener('change', () => { document.getElementById('model-active').value = activeToggle.checked ? '1' : '0'; });
</script>
