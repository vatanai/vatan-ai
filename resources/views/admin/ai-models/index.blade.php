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
        'liara' => ['title' => 'Liara AI', 'label' => 'لیارا', 'description' => 'سرویس سازگار با API تصویر', 'icon' => 'fa-cloud', 'color' => 'success'],
        'openrouter' => ['title' => 'OpenRouter', 'label' => 'OpenRouter', 'description' => 'گیت‌وی مدل‌های عمومی', 'icon' => 'fa-bolt', 'color' => 'info'],
        'fal' => ['title' => 'Fal.ai', 'label' => 'Fal.ai', 'description' => 'صف سریع مدل‌های تصویر و ویدیو', 'icon' => 'fa-wand-magic-sparkles', 'color' => 'warning'],
        'replicate' => ['title' => 'Replicate', 'label' => 'Replicate', 'description' => 'مدل‌های community با version مستقل', 'icon' => 'fa-cubes', 'color' => 'primary'],
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
      <div class="ai-model-summary-card"><span class="summary-icon is-purple"><i class="fa-solid fa-cloud"></i></span><div><small>پروایدرهای ثبت‌شده</small><strong>{{ $models->pluck('provider')->filter()->unique()->count() }}</strong></div></div>
    </div>

    <section class="content-card overflow-hidden">
      <div class="flex items-center justify-between gap-3 flex-wrap p-4 border-b border-[var(--border)]">
        <div>
          <h2 class="text-sm font-extrabold text-[var(--text-h)] m-0">لیست مدل‌ها</h2>
          <p class="text-[10px] text-[var(--text-soft)] mt-1 mb-0">مدل‌های عکس و ویدیو را با وضعیت فعال‌بودن هر مدل مدیریت کنید.</p>
        </div>
        <div class="model-filters-row">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[10px] text-[var(--text-soft)]">ارائه‌دهنده:</span>
            <button class="chip-filter provider-tab active" data-provider-filter="all">همه <span>({{ $models->count() }})</span></button>
            @foreach($providerMeta as $provider => $meta)
              <button class="chip-filter provider-tab" data-provider-filter="{{ $provider }}">{{ $meta['label'] }} <span>({{ $models->where('provider', $provider)->count() }})</span></button>
            @endforeach
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[10px] text-[var(--text-soft)]">نوع خروجی:</span>
            <button class="chip-filter media-tab active" data-media-filter="all">همه</button>
            <button class="chip-filter media-tab" data-media-filter="image"><i class="fa-solid fa-image ml-1"></i> عکس <span>({{ $models->where('output_modality', 'image')->count() }})</span></button>
            <button class="chip-filter media-tab" data-media-filter="video"><i class="fa-solid fa-video ml-1"></i> ویدیو <span>({{ $models->where('output_modality', 'video')->count() }})</span></button>
          </div>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="table-pro w-full">
          <thead><tr><th>مدل</th><th>کاربرد مدل</th><th>گرید</th><th>پروایدر</th><th>شناسه / نسخه</th><th>قابلیت‌ها</th><th>هزینه به تومان</th><th>وضعیت</th><th>عملیات</th></tr></thead>
          <tbody>
            @forelse($models as $model)
              @php
                $meta = $providerMeta[$model->provider] ?? ['label' => $model->provider, 'color' => 'neutral'];
                $providerIsEnabled = $providerStatus[$model->provider] ?? false;
              @endphp
              <tr data-provider-row="{{ $model->provider }}" data-media-row="{{ $model->output_modality }}">
                <td>
                  <div class="flex items-center gap-2.5 min-w-[210px]">
                    <img src="{{ $model->image_url }}" class="w-9 h-9 rounded-lg object-cover border border-[var(--border)] bg-[var(--input-bg)]" alt="">
                    <div class="min-w-0"><div class="font-bold text-[var(--text-h)]"><span class="truncate">{{ $model->name }}</span></div><div class="text-[10px] text-[var(--text-soft)]">{{ $model->provider_name }}</div></div>
                  </div>
                </td>
                <td class="text-center align-middle">@if(in_array($model->output_modality, ['image', 'video'], true))<i class="model-purpose-icon fa-solid {{ $model->mediaIcon() }}" aria-hidden="true"></i>@endif</td>
                <td><span class="model-quality-grade">{{ $model->qualityGradeLabel() }}</span></td>
                <td>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="badge-pro badge-pro-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                    <span class="provider-inline-state {{ $providerIsEnabled ? 'is-on' : 'is-off' }}">{{ $providerIsEnabled ? 'فعال' : 'غیرفعال' }}</span>
                  </div>
                </td>
                <td dir="ltr" class="text-left font-mono text-[11px] text-[var(--text-main)]">
                  <div>{{ $model->externalModelId() }}</div>
                  @if($model->external_version)<div class="text-[10px] text-[var(--text-soft)] mt-1">version: {{ $model->external_version }}</div>@endif
                </td>
                <td class="text-[11px] text-[var(--text-soft)]">
                  <div>{{ $model->supports_image_input ? 'ورودی تصویر' : 'متن' }}</div>
                  <div>{{ $model->supports_webhook ? 'وب‌هوک' : 'Polling' }}</div>
                </td>
                <td class="font-mono text-[11px] text-[var(--text-main)]">{{ $model->cost_per_generation_usd ? (($exchange['rate'] ?? 0) > 0 ? number_format((float) $model->cost_per_generation_usd * (float) $exchange['rate'] / 10) . ' تومان' : 'نرخ ارز ثبت نشده') : 'بر اساس قیمت‌گذاری' }}</td>
                <td>
                  <div class="model-status-cell">
                    <span class="provider-status-box {{ $model->is_active ? 'is-on' : 'is-off' }}">{{ $model->is_active ? 'فعال می‌باشد' : 'غیر فعال می‌باشد' }}</span>
                    <form method="POST" action="{{ route('admin.ai-models.toggle-model', $model) }}">
                      @csrf
                      <button type="submit" class="model-toggle-btn">{{ $model->is_active ? 'خاموش' : 'روشن' }}</button>
                    </form>
                  </div>
                </td>
                <td><div class="flex items-center gap-2"><a class="icon-action-btn" href="{{ route('admin.ai-models.edit', $model) }}" title="ویرایش"><i class="fa-regular fa-pen-to-square"></i></a><form method="POST" action="{{ route('admin.ai-models.destroy', $model) }}" onsubmit="return confirm('این مدل حذف شود؟')">@csrf @method('DELETE')<button class="icon-action-btn text-[var(--danger)]" type="submit" title="حذف"><i class="fa-regular fa-trash-can"></i></button></form></div></td>
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
  .provider-tab.active, .media-tab.active { background: var(--primary-l); color: var(--primary); border-color: var(--primary); }
  .provider-tab, .media-tab { cursor: pointer; }
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
  .model-purpose-icon { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:8px; color:var(--primary); background:var(--primary-l); font-size:12px; }
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
  .model-filters-row { display:flex; align-items:center; justify-content:flex-end; gap:14px; flex-wrap:wrap; }
  .model-status-cell { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
  @media (max-width: 1100px) { .ai-model-summary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
  @media (max-width: 900px) { .ai-model-summary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .provider-metrics { grid-template-columns:repeat(2, minmax(0, 1fr)); } .model-filters-row { justify-content:flex-start; } }
  @media (max-width: 520px) { .ai-model-summary-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('scripts')
<script>
  let selectedProvider = @json($initialProvider ?? 'all');
  let selectedMedia = 'all';

  function applyModelFilters() {
    document.querySelectorAll('[data-provider-row]').forEach(row => {
      const providerOk = selectedProvider === 'all' || row.dataset.providerRow === selectedProvider;
      const mediaOk = selectedMedia === 'all' || row.dataset.mediaRow === selectedMedia;
      row.hidden = !(providerOk && mediaOk);
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

  document.querySelectorAll('[data-provider-filter]').forEach(button => {
    button.classList.toggle('active', button.dataset.providerFilter === selectedProvider);
  });
  applyModelFilters();
</script>
@endsection
