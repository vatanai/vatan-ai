@extends('layouts.admin')
@section('title', 'مدیریت مدل‌های هوش مصنوعی — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')

  <div class="admin-content flex-1 overflow-y-auto p-6 max-[768px]:p-[18px]" id="content">
    @if(session('success'))
      <div class="content-card mb-4 flex items-center gap-2 border-[var(--success)]/30 text-[var(--success)]" role="status">
        <i class="fa-solid fa-circle-check"></i><span class="text-xs font-bold">{{ session('success') }}</span>
      </div>
    @endif
    @if($errors->any())
      <div class="content-card mb-4 border-[var(--danger)]/30 text-[var(--danger)]">
        <div class="text-xs font-bold mb-2">خطا در ذخیره یا بررسی تنظیمات</div>
        <ul class="m-0 pr-5 text-xs leading-7 list-disc">
          @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
      </div>
    @endif

    @php
      $providerMeta = [
        'openrouter' => ['title' => 'OpenRouter', 'label' => 'OpenRouter', 'description' => 'گیت‌وی مدل‌های عمومی', 'icon' => 'fa-bolt', 'color' => 'info'],
        'fal' => ['title' => 'Fal.ai', 'label' => 'Fal.ai', 'description' => 'صف سریع مدل‌های تصویر و ویدیو', 'icon' => 'fa-wand-magic-sparkles', 'color' => 'warning'],
        'replicate' => ['title' => 'Replicate', 'label' => 'Replicate', 'description' => 'مدل‌های community با version مستقل', 'icon' => 'fa-cubes', 'color' => 'primary'],
      ];
      $taskTypeLabels = [
        'text_to_image' => 'متن به عکس', 'image_to_image' => 'عکس به عکس',
        'text_to_video' => 'متن به ویدیو', 'image_to_video' => 'عکس به ویدیو',
        'video_to_video' => 'ویدیو به ویدیو', 'face_consistency' => 'حفظ هویت چهره',
        'face_animation' => 'متحرک‌سازی چهره', 'upscaling' => 'افزایش کیفیت',
      ];
      $useCaseLabels = [
        'portrait' => 'چهره و پرتره', 'identity' => 'حفظ هویت چهره', 'business' => 'محصول و کسب‌وکار',
        'design' => 'طراحی و متن', 'creative' => 'تصویرسازی خلاق', 'video' => 'ویدیو و موشن',
      ];
    @endphp

    <div class="flex items-start justify-between gap-4 flex-wrap mb-5">
      <div>
        <h1 class="text-xl font-extrabold text-[var(--text-h)] mb-1">مدل‌های هوش مصنوعی</h1>
        <p class="text-xs text-[var(--text-soft)] m-0">مدیریت مدل‌های عکس و ویدیو، کاربرد مدل و وضعیت فعال‌بودن</p>
      </div>
      <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('admin.ai-models.providers') }}" class="btn-pro btn-pro-ghost no-underline inline-flex items-center gap-2">
          <i class="fa-solid fa-plug"></i> مدیریت پرووایدرها
        </a>
        <a href="{{ route('admin.ai-models.create') }}" class="btn-pro btn-pro-primary no-underline inline-flex items-center gap-2">
          <i class="fa-solid fa-plus"></i> ثبت مدل دستی
        </a>
      </div>
    </div>

    <div class="ai-model-summary-grid mb-5">
      <div class="ai-model-summary-card"><span class="summary-icon is-primary"><i class="fa-solid fa-microchip"></i></span><div><small>تعداد کل مدل‌ها</small><strong>{{ $models->count() }}</strong></div></div>
      <div class="ai-model-summary-card"><span class="summary-icon is-success"><i class="fa-solid fa-toggle-on"></i></span><div><small>مدل‌های فعال</small><strong>{{ $models->where('is_active', true)->count() }}</strong></div></div>
      <div class="ai-model-summary-card"><span class="summary-icon is-info"><i class="fa-solid fa-image"></i></span><div><small>مدل‌های عکس</small><strong>{{ $models->where('output_modality', 'image')->count() }}</strong></div></div>
      <div class="ai-model-summary-card"><span class="summary-icon is-warning"><i class="fa-solid fa-video"></i></span><div><small>مدل‌های ویدیو</small><strong>{{ $models->where('output_modality', 'video')->count() }}</strong></div></div>
      <div class="ai-model-summary-card"><span class="summary-icon is-success"><i class="fa-solid fa-user-check"></i></span><div><small>مدل‌های هویت‌محور</small><strong>{{ $models->where('supports_face_identity', true)->count() }}</strong></div></div>
    </div>

    @php
      $studioDefaultSelection = $studioImageModels
        ->filter(function ($model) use ($studioSelectionReady) {
          return $studioSelectionReady
            ? (bool) $model->featured_in_image_studio
            : in_array($model->openrouter_model_id, \App\Models\AiModel::STUDIO_IMAGE_MODEL_PRIORITY, true);
        })
        ->pluck('id')
        ->map(fn ($id) => (string) $id)
        ->all();
      $studioSelectedIds = collect(old('studio_models', $studioDefaultSelection))->map(fn ($id) => (string) $id)->all();
    @endphp

    <section class="content-card studio-image-models-panel mb-5" aria-labelledby="studio-image-models-title">
      <div class="studio-image-models-head">
        <div class="flex items-center gap-3 min-w-0">
          <span class="studio-panel-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
          <div class="min-w-0">
            <h2 id="studio-image-models-title" class="text-sm font-extrabold text-[var(--text-h)] m-0">استودیو ساخت — ساخت عکس</h2>
            <p class="text-[10px] text-[var(--text-soft)] mt-1 mb-0">مدل‌هایی را انتخاب کنید که در هر دو حالت «متن به عکس» و «عکس به عکس» به کاربر نمایش داده شوند.</p>
          </div>
        </div>
        <div class="studio-selection-counter" aria-live="polite">
          <strong data-studio-selected-count>{{ count($studioSelectedIds) }}</strong>
          <span>مدل انتخاب‌شده</span>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.ai-models.image-studio-selection') }}" class="studio-model-selection-form" data-studio-selection-form data-selection-ready="{{ $studioSelectionReady ? '1' : '0' }}">
        @csrf
        @method('PUT')

        <div class="studio-selection-note">
          <span><i class="fa-solid fa-circle-info"></i> حداقل ۱۰ مدل فعال نگه دارید تا تنوع، قیمت و مسیر جایگزین مناسب باقی بماند.</span>
          <span>فقط نمایش در استودیو تغییر می‌کند؛ تنظیمات محصول و آزمایشگاه دست‌نخورده می‌ماند.</span>
        </div>

        @if(! $studioSelectionReady)
          <div class="studio-selection-warning">تنظیمات دیتابیس این بخش هنوز آماده نیست؛ پس از اجرای migration قابل ذخیره خواهد بود.</div>
        @endif

        <div class="studio-model-checkbox-grid">
          @forelse($studioImageModels as $studioModel)
            @php
              $studioModelId = (string) $studioModel->openrouter_model_id;
              $studioFamily = match (true) {
                str_starts_with($studioModelId, 'openai/') => 'OpenAI',
                str_starts_with($studioModelId, 'google/') => 'Nano Banana',
                str_starts_with($studioModelId, 'black-forest-labs/') => 'FLUX',
                str_starts_with($studioModelId, 'qwen/') => 'Qwen',
                str_starts_with($studioModelId, 'bytedance-seed/') => 'Seedream',
                str_starts_with($studioModelId, 'sourceful/') => 'Riverflow',
                default => 'OpenRouter',
              };
              $studioChecked = in_array((string) $studioModel->id, $studioSelectedIds, true);
            @endphp
            <label class="studio-model-checkbox-card">
              <input type="checkbox" name="studio_models[]" value="{{ $studioModel->id }}" @checked($studioChecked) data-studio-model-checkbox>
              <span class="studio-model-check"><i class="fa-solid fa-check"></i></span>
              <img src="{{ $studioModel->image_url }}" alt="" class="studio-model-thumb">
              <span class="studio-model-copy">
                <strong>{{ $studioModel->name }}</strong>
                <small dir="ltr">{{ $studioModelId }}</small>
                <span class="studio-model-meta">
                  <b>{{ $studioFamily }}</b>
                  <b>{{ $studioModel->cost_per_generation_usd ? '$' . number_format((float) $studioModel->cost_per_generation_usd, 3) : 'قیمت متغیر' }}</b>
                  <b>عکس مرجع</b>
                </span>
              </span>
            </label>
          @empty
            <div class="empty-state">مدل فعال و سازگار برای ساخت عکس پیدا نشد.</div>
          @endforelse
        </div>

        <div class="studio-selection-actions">
          <p data-studio-selection-message>حداقل ۱۰ مدل را انتخاب کنید.</p>
          <button type="submit" class="btn-pro btn-pro-primary inline-flex items-center gap-2" @disabled(! $studioSelectionReady || $studioImageModels->count() < 10) data-studio-save-button>
            <i class="fa-solid fa-floppy-disk"></i> ذخیره مدل‌های استودیو
          </button>
        </div>
      </form>
    </section>

    <section class="content-card overflow-hidden">
      <div class="ai-model-list-head p-4 border-b border-[var(--border)]">
        <div>
          <h2 class="text-sm font-extrabold text-[var(--text-h)] m-0">لیست مدل‌ها</h2>
          <p class="text-[10px] text-[var(--text-soft)] mt-1 mb-0">مدل‌های عکس و ویدیو را با وضعیت فعال‌بودن هر مدل مدیریت کنید.</p>
        </div>
        <div class="model-filters-row">
          <div class="model-filter-group">
            <span class="text-[10px] text-[var(--text-soft)]">ارائه‌دهنده:</span>
            <button class="chip-filter provider-tab active" data-provider-filter="all">همه <span>({{ $models->count() }})</span></button>
            @foreach($providerMeta as $provider => $meta)
              <button class="chip-filter provider-tab" data-provider-filter="{{ $provider }}">{{ $meta['label'] }} <span>({{ $models->where('provider', $provider)->count() }})</span></button>
            @endforeach
          </div>
          <div class="model-filter-group">
            <span class="text-[10px] text-[var(--text-soft)]">نوع خروجی:</span>
            <button class="chip-filter media-tab active" data-media-filter="all">همه</button>
            <button class="chip-filter media-tab" data-media-filter="image"><i class="fa-solid fa-image ml-1"></i> عکس <span>({{ $models->where('output_modality', 'image')->count() }})</span></button>
            <button class="chip-filter media-tab" data-media-filter="video"><i class="fa-solid fa-video ml-1"></i> ویدیو <span>({{ $models->where('output_modality', 'video')->count() }})</span></button>
          </div>
          <div class="model-filter-group">
            <span class="text-[10px] text-[var(--text-soft)]">نوع تبدیل:</span>
            <button class="chip-filter task-tab active" data-task-filter="all">همه</button>
            @foreach($taskTypeLabels as $taskType => $taskLabel)
              @php $taskCount = $models->where('task_type', $taskType)->count(); @endphp
              @if($taskCount)
                <button class="chip-filter task-tab" data-task-filter="{{ $taskType }}">{{ $taskLabel }} <span>({{ $taskCount }})</span></button>
              @endif
            @endforeach
          </div>
          <div class="model-filter-group">
            <span class="text-[10px] text-[var(--text-soft)]">بهترین گزینه برای:</span>
            <button class="chip-filter use-case-tab active" data-use-case-filter="all">همه</button>
            @foreach($useCaseLabels as $useCase => $useCaseLabel)
              @php $useCaseCount = $models->filter(fn ($model) => in_array($useCase, $model->recommendedUseCaseKeys(), true))->count(); @endphp
              @if($useCaseCount)
                <button class="chip-filter use-case-tab" data-use-case-filter="{{ $useCase }}">{{ $useCaseLabel }} <span>({{ $useCaseCount }})</span></button>
              @endif
            @endforeach
          </div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="table-pro ai-models-table w-full">
          <colgroup>
            <col class="col-model"><col class="col-purpose"><col class="col-grade"><col class="col-provider"><col class="col-identifier"><col class="col-capabilities"><col class="col-price"><col class="col-status"><col class="col-actions">
          </colgroup>
          <thead><tr><th>مدل</th><th>کاربرد مدل</th><th>گرید</th><th>پروایدر</th><th>شناسه / نسخه</th><th>قابلیت‌ها</th><th>هزینه به تومان</th><th>وضعیت</th><th>عملیات</th></tr></thead>
          <tbody>
            @forelse($models as $model)
              @php
                $meta = $providerMeta[$model->provider] ?? ['label' => $model->provider, 'color' => 'neutral'];
                $providerIsEnabled = $providerStatus[$model->provider] ?? false;
              @endphp
              <tr data-provider-row="{{ $model->provider }}" data-media-row="{{ $model->output_modality }}" data-task-row="{{ $model->task_type }}" data-use-cases-row="{{ implode(' ', $model->recommendedUseCaseKeys()) }}">
                <td data-label="مدل">
                  <div class="ai-model-name-cell">
                    <img src="{{ $model->image_url }}" class="w-8 h-8 rounded-lg object-cover border border-[var(--border)] bg-[var(--input-bg)]" alt="">
                    <div class="min-w-0"><div class="font-bold text-[var(--text-h)]"><span class="truncate">{{ $model->name }}</span></div><div class="text-[10px] text-[var(--text-soft)]">{{ $model->provider_name }}</div></div>
                  </div>
                </td>
                <td class="align-middle" data-label="کاربرد مدل">
                  <div class="model-use-case-cell">
                    <span class="model-purpose-icon fa-solid {{ $model->mediaIcon() }}" aria-hidden="true"></span>
                    <span>{{ $model->taskLabel() }}</span>
                    <small>{{ $model->primaryUseCaseLabel() }}</small>
                  </div>
                </td>
                <td data-label="گرید"><span class="model-quality-grade">{{ $model->qualityGradeLabel() }}</span></td>
                <td data-label="پروایدر">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="badge-pro badge-pro-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                    <span class="provider-inline-state {{ $providerIsEnabled ? 'is-on' : 'is-off' }}">{{ $providerIsEnabled ? 'فعال' : 'غیرفعال' }}</span>
                  </div>
                </td>
                <td dir="ltr" class="ai-model-identifier text-left font-mono text-[10px] text-[var(--text-main)]" data-label="شناسه / نسخه">
                  <div>{{ $model->externalModelId() }}</div>
                  @if($model->external_version)<div class="text-[10px] text-[var(--text-soft)] mt-1">version: {{ $model->external_version }}</div>@endif
                </td>
                <td class="text-[10px] text-[var(--text-soft)]" data-label="قابلیت‌ها">
                  <div class="model-capabilities-cell">
                    @foreach(array_slice($model->capabilityLabels(), 0, 4) as $capability)
                      <span>{{ $capability }}</span>
                    @endforeach
                  </div>
                </td>
                <td class="ai-model-price font-mono text-[10px] text-[var(--text-main)]" data-label="هزینه به تومان">{{ $model->cost_per_generation_usd ? (($exchange['rate'] ?? 0) > 0 ? number_format((float) $model->cost_per_generation_usd * (float) $exchange['rate'] / 10) . ' تومان' : 'نرخ ارز ثبت نشده') : 'بر اساس قیمت‌گذاری' }}</td>
                <td data-label="وضعیت">
                  <div class="model-status-cell">
                    <span class="provider-status-box {{ $model->is_active ? 'is-on' : 'is-off' }}">{{ $model->is_active ? 'فعال' : 'غیرفعال' }}</span>
                    <form method="POST" action="{{ route('admin.ai-models.toggle-model', $model) }}">
                      @csrf
                      <button type="submit" class="model-toggle-btn">{{ $model->is_active ? 'خاموش' : 'روشن' }}</button>
                    </form>
                  </div>
                </td>
                <td data-label="عملیات"><div class="ai-model-actions"><a class="icon-action-btn" href="{{ route('admin.ai-models.edit', $model) }}" title="ویرایش"><i class="fa-regular fa-pen-to-square"></i></a><form method="POST" action="{{ route('admin.ai-models.destroy', $model) }}" onsubmit="return confirm('این مدل حذف شود؟')">@csrf @method('DELETE')<button class="icon-action-btn text-[var(--danger)]" type="submit" title="حذف"><i class="fa-regular fa-trash-can"></i></button></form></div></td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="empty-state">هنوز مدلی ثبت نشده است. از دکمه‌ی «ثبت مدل دستی» شروع کنید.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>
</main>
@endsection

@push('styles')
<style>
  .provider-tab.active, .media-tab.active, .task-tab.active, .use-case-tab.active { background: var(--primary-l); color: var(--primary); border-color: var(--primary); }
  .provider-tab, .media-tab, .task-tab, .use-case-tab { cursor: pointer; }
  .ai-model-summary-grid { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; }
  .ai-model-summary-card { display:flex; align-items:center; gap:9px; min-width:0; padding:12px; border:1px solid var(--border); border-radius:11px; background:var(--card-bg); box-shadow:var(--shadow-card); }
  .ai-model-summary-card small,.ai-model-summary-card strong { display:block; }
  .ai-model-summary-card small { color:var(--text-soft); font-size:9px; }
  .ai-model-summary-card strong { margin-top:4px; color:var(--text-h); font-size:17px; line-height:1; }
  .summary-icon { width:31px; height:31px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:9px; font-size:12px; }
  .summary-icon.is-primary { color:var(--primary); background:var(--primary-l); }
  .summary-icon.is-success { color:var(--success); background:color-mix(in srgb,var(--success) 10%,transparent); }
  .summary-icon.is-info { color:var(--info); background:color-mix(in srgb,var(--info) 10%,transparent); }
  .summary-icon.is-warning { color:var(--warning); background:color-mix(in srgb,var(--warning) 10%,transparent); }
  .summary-icon.is-purple { color:var(--primary); background:var(--primary-l); }
  .studio-image-models-panel { padding:0; overflow:hidden; }
  .studio-image-models-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:16px; border-bottom:1px solid var(--border); }
  .studio-panel-icon { width:38px; height:38px; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; border-radius:11px; color:var(--primary); background:var(--primary-l); }
  .studio-selection-counter { display:flex; align-items:baseline; gap:5px; flex-shrink:0; padding:8px 11px; border:1px solid var(--border); border-radius:9px; background:var(--input-bg); color:var(--text-soft); font-size:9px; }
  .studio-selection-counter strong { color:var(--primary); font-size:17px; line-height:1; }
  .studio-model-selection-form { padding:16px; }
  .studio-selection-note { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; padding:9px 11px; border:1px solid color-mix(in srgb,var(--info) 25%,var(--border)); border-radius:9px; background:color-mix(in srgb,var(--info) 6%,var(--card-bg)); color:var(--text-soft); font-size:9px; line-height:1.8; }
  .studio-selection-note i { margin-left:4px; color:var(--info); }
  .studio-selection-warning { margin-bottom:12px; padding:9px 11px; border:1px solid color-mix(in srgb,var(--warning) 35%,var(--border)); border-radius:9px; background:color-mix(in srgb,var(--warning) 7%,var(--card-bg)); color:var(--warning); font-size:10px; font-weight:700; }
  .studio-model-checkbox-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; }
  .studio-model-checkbox-card { position:relative; display:flex; align-items:center; gap:9px; min-width:0; min-height:78px; padding:10px 38px 10px 10px; border:1px solid var(--border); border-radius:10px; background:var(--card-bg); cursor:pointer; transition:border-color .18s ease,background .18s ease,transform .18s ease; }
  .studio-model-checkbox-card:hover { border-color:color-mix(in srgb,var(--primary) 45%,var(--border)); transform:translateY(-1px); }
  .studio-model-checkbox-card:has(input:checked) { border-color:var(--primary); background:color-mix(in srgb,var(--primary) 6%,var(--card-bg)); }
  .studio-model-checkbox-card > input { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
  .studio-model-check { position:absolute; right:10px; top:10px; width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:5px; background:var(--input-bg); color:transparent; font-size:9px; }
  .studio-model-checkbox-card:has(input:checked) .studio-model-check { border-color:var(--primary); background:var(--primary); color:var(--card-bg); }
  .studio-model-checkbox-card:focus-within { outline:2px solid color-mix(in srgb,var(--primary) 30%,transparent); outline-offset:2px; }
  .studio-model-thumb { width:38px; height:38px; flex-shrink:0; object-fit:cover; border:1px solid var(--border); border-radius:9px; background:var(--input-bg); }
  .studio-model-copy { display:block; min-width:0; }
  .studio-model-copy > strong,.studio-model-copy > small { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .studio-model-copy > strong { color:var(--text-h); font-size:10px; }
  .studio-model-copy > small { margin-top:3px; color:var(--text-soft); font-family:monospace; font-size:8px; text-align:left; }
  .studio-model-meta { display:flex; align-items:center; gap:4px; flex-wrap:wrap; margin-top:6px; }
  .studio-model-meta b { padding:2px 5px; border:1px solid var(--border); border-radius:5px; background:var(--input-bg); color:var(--text-soft); font-size:7.5px; font-weight:700; }
  .studio-selection-actions { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:14px; padding-top:12px; border-top:1px solid var(--border); }
  .studio-selection-actions p { margin:0; color:var(--text-soft); font-size:9px; }
  .studio-selection-actions p.is-valid { color:var(--success); }
  .studio-selection-actions p.is-invalid { color:var(--danger); }
  .studio-selection-actions button:disabled { opacity:.55; cursor:not-allowed; }
  .model-purpose-icon { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:8px; color:var(--primary); background:var(--primary-l); font-size:12px; }
  .model-use-case-cell { display:grid; grid-template-columns:28px minmax(0,1fr); align-items:center; gap:3px 6px; min-width:0; }
  .model-use-case-cell small { grid-column:2; color:var(--text-soft); font-size:9px; }
  .model-capabilities-cell { display:flex; flex-wrap:wrap; gap:3px; min-width:0; }
  .model-capabilities-cell span { display:inline-flex; align-items:center; min-height:20px; padding:2px 5px; border:1px solid var(--border); border-radius:6px; color:var(--text-soft); background:var(--input-bg); font-size:8px; white-space:nowrap; }
  .model-quality-grade { color:var(--warning); font-size:10px; font-weight:800; white-space:nowrap; }
  .provider-inline-state { display:inline-flex; align-items:center; min-height:19px; padding:2px 5px; border:1px solid; border-radius:6px; font-size:8px; font-weight:800; }
  .provider-inline-state.is-on { color:var(--success); border-color:color-mix(in srgb,var(--success) 28%,transparent); background:color-mix(in srgb,var(--success) 8%,transparent); }
  .provider-inline-state.is-off { color:var(--danger); border-color:color-mix(in srgb,var(--danger) 28%,transparent); background:color-mix(in srgb,var(--danger) 8%,transparent); }
  .provider-card[data-provider-enabled="1"] { border-color: color-mix(in srgb, var(--success) 42%, var(--border)); }
  .provider-card[data-provider-enabled="0"] { border-color: color-mix(in srgb, var(--danger) 42%, var(--border)); }
  .provider-status-box { display:inline-flex; align-items:center; justify-content:center; min-height:25px; padding:4px 8px; border:1px solid; border-radius:7px; font-size:9px; font-weight:800; white-space:nowrap; }
  .provider-status-box.is-on { color:var(--success); border-color:color-mix(in srgb, var(--success) 32%, transparent); background:color-mix(in srgb, var(--success) 10%, transparent); }
  .provider-status-box.is-off { color:var(--danger); border-color:color-mix(in srgb, var(--danger) 32%, transparent); background:color-mix(in srgb, var(--danger) 10%, transparent); }
  .provider-toggle-btn, .model-toggle-btn { border:1px solid var(--border); border-radius:7px; padding:5px 8px; background:var(--card-bg); color:var(--text-soft); font-size:9px; cursor:pointer; }
  .provider-toggle-btn:hover, .model-toggle-btn:hover { color:var(--primary); border-color:var(--primary); }
  .provider-metrics { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:6px; margin:0 0 8px; }
  .provider-metrics > div { min-width:0; padding:7px; border:1px solid var(--border); border-radius:8px; background:var(--card-bg); }
  .provider-metrics span, .provider-metrics strong { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .provider-metrics span { color:var(--text-soft); font-size:8px; }
  .provider-metrics strong { margin-top:3px; color:var(--text-h); font-size:10px; }
  .provider-key-line { margin-bottom:10px; color:var(--text-soft); font-size:9px; }
  .provider-key-line b { color:var(--text-main); }
  .ai-model-list-head > div:first-child { margin-bottom:12px; }
  .model-filters-row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); width:100%; gap:8px 12px; }
  .model-filter-group { display:flex; align-items:center; align-content:flex-start; gap:6px; min-width:0; padding:7px 8px; border:1px solid var(--border); border-radius:9px; background:var(--input-bg); flex-wrap:wrap; }
  .model-filter-group .chip-filter { height:27px; padding:0 8px; font-size:9.5px; }
  .ai-models-table { width:100%; table-layout:fixed; }
  .ai-models-table .col-model { width:18%; }
  .ai-models-table .col-purpose { width:13%; }
  .ai-models-table .col-grade { width:7%; }
  .ai-models-table .col-provider { width:10%; }
  .ai-models-table .col-identifier { width:15%; }
  .ai-models-table .col-capabilities { width:14%; }
  .ai-models-table .col-price { width:9%; }
  .ai-models-table .col-status { width:9%; }
  .ai-models-table .col-actions { width:5%; }
  .ai-models-table thead th, .ai-models-table tbody td { padding:8px 6px; font-size:10px; overflow-wrap:anywhere; }
  .ai-models-table thead th { white-space:normal; line-height:1.5; }
  .ai-model-name-cell { display:flex; align-items:center; gap:7px; min-width:0; }
  .ai-model-name-cell > div { min-width:0; }
  .ai-model-name-cell .truncate { display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .ai-model-identifier { overflow-wrap:anywhere; word-break:break-word; }
  .ai-model-price { line-height:1.6; }
  .model-status-cell { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }
  .provider-status-box { min-height:22px; padding:3px 5px; }
  .model-toggle-btn { padding:4px 5px; }
  .ai-model-actions { display:flex; align-items:center; justify-content:flex-end; gap:3px; }
  .ai-model-actions .icon-action-btn { width:28px; height:28px; }
  @media (max-width: 1100px) { .ai-model-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .studio-model-checkbox-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
  @media (max-width: 1100px) { .model-filters-row { grid-template-columns:1fr; } }
  @media (max-width: 900px) { .ai-model-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .provider-metrics { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
  @media (max-width: 620px) { .studio-image-models-head,.studio-selection-note,.studio-selection-actions { align-items:stretch; flex-direction:column; } .studio-selection-counter { align-self:flex-start; } .studio-model-checkbox-grid { grid-template-columns:1fr; } }
  @media (max-width: 520px) { .ai-model-summary-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('scripts')
<script>
  const studioSelectionForm = document.querySelector('[data-studio-selection-form]');
  if (studioSelectionForm) {
    const studioCheckboxes = [...studioSelectionForm.querySelectorAll('[data-studio-model-checkbox]')];
    const studioCount = document.querySelector('[data-studio-selected-count]');
    const studioMessage = studioSelectionForm.querySelector('[data-studio-selection-message]');
    const studioSaveButton = studioSelectionForm.querySelector('[data-studio-save-button]');
    const studioSelectionReady = studioSelectionForm.dataset.selectionReady === '1';

    const refreshStudioSelection = () => {
      const selectedCount = studioCheckboxes.filter(checkbox => checkbox.checked).length;
      studioCount.textContent = selectedCount;
      studioMessage.classList.toggle('is-valid', selectedCount >= 10);
      studioMessage.classList.toggle('is-invalid', selectedCount < 10);
      studioMessage.textContent = selectedCount >= 10
        ? `${selectedCount} مدل در صفحه ساخت عکس نمایش داده می‌شود.`
        : `${10 - selectedCount} مدل دیگر انتخاب کنید؛ حداقل انتخاب ۱۰ مدل است.`;
      studioSaveButton.disabled = !studioSelectionReady || selectedCount < 10;
    };

    studioCheckboxes.forEach(checkbox => checkbox.addEventListener('change', refreshStudioSelection));
    refreshStudioSelection();
  }

  let selectedProvider = @json($initialProvider ?? 'all');
  let selectedMedia = 'all';
  let selectedTask = 'all';
  let selectedUseCase = 'all';

  function applyModelFilters() {
    document.querySelectorAll('[data-provider-row]').forEach(row => {
      const providerOk = selectedProvider === 'all' || row.dataset.providerRow === selectedProvider;
      const mediaOk = selectedMedia === 'all' || row.dataset.mediaRow === selectedMedia;
      const taskOk = selectedTask === 'all' || row.dataset.taskRow === selectedTask;
      const useCaseOk = selectedUseCase === 'all' || (row.dataset.useCasesRow || '').split(' ').includes(selectedUseCase);
      row.hidden = !(providerOk && mediaOk && taskOk && useCaseOk);
    });
  }

  document.querySelectorAll('[data-provider-filter]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-provider-filter]').forEach(item => item.classList.remove('active'));
      button.classList.add('active');
      selectedProvider = button.dataset.providerFilter;
      applyModelFilters();
    });
  });
  document.querySelectorAll('[data-media-filter]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-media-filter]').forEach(item => item.classList.remove('active'));
      button.classList.add('active');
      selectedMedia = button.dataset.mediaFilter;
      applyModelFilters();
    });
  });
  document.querySelectorAll('[data-task-filter]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-task-filter]').forEach(item => item.classList.remove('active'));
      button.classList.add('active');
      selectedTask = button.dataset.taskFilter;
      applyModelFilters();
    });
  });
  document.querySelectorAll('[data-use-case-filter]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-use-case-filter]').forEach(item => item.classList.remove('active'));
      button.classList.add('active');
      selectedUseCase = button.dataset.useCaseFilter;
      applyModelFilters();
    });
  });

  document.querySelectorAll('[data-provider-filter]').forEach(button => {
    button.classList.toggle('active', button.dataset.providerFilter === selectedProvider);
  });
  applyModelFilters();
</script>
@endsection
