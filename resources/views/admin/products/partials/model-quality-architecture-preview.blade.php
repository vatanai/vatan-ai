@php
  // $aiModels از scope واحد selectableForProduct می‌آید؛ این بخش نباید دوباره
  // فهرست متفاوتی بسازد، چون همین کاتالوگ در تنظیم سریع لیست محصولات هم استفاده می‌شود.
  $architectureModels = collect($aiModels)->values();
  $modelCostLabel = function ($model) use ($exchange) {
    $usd = data_get($model?->lab_pricing ?? [], 'usd');
    if (!is_numeric($usd) || (float) $usd <= 0) return 'قیمت زنده';
    $toman = (int) round(((float) $usd * (float) data_get($exchange, 'rate', 0)) / 10);
    $unit = strtolower((string) data_get($model?->lab_pricing ?? [], 'unit', ''));
    $unitLabel = in_array($unit, ['megapixel', 'per_megapixel'], true) ? ' / مگاپیکسل' : '';
    return '$' . number_format((float) $usd, 3) . $unitLabel . ' · ' . number_format($toman) . ' تومان';
  };
  // هر سه سطح باید تمام مدل‌های تیک‌خورده را نشان دهند؛ سطح فقط برای
  // انتخاب کیفیت محصول و اعتبار آن است، نه برای حذف مدل‌های پرووایدر.
  $modelChoices = fn (int $grade) => $architectureModels;
  $qualityCards = [
    ['key' => 'standard', 'title' => 'مدل استاندارد', 'description' => 'مسیر متعادل برای ساخت روزمره', 'grade' => 3, 'icon' => 'fa-solid fa-wand-magic-sparkles'],
    ['key' => 'professional', 'title' => 'مدل حرفه‌ای', 'description' => 'جزئیات و پایداری بیشتر برای خروجی حرفه‌ای', 'grade' => 2, 'icon' => 'fa-solid fa-gem'],
    ['key' => 'best', 'title' => 'مدل بهترین خروجی', 'description' => 'بالاترین سطح کیفیت برای نتیجه‌های کلیدی', 'grade' => 1, 'icon' => 'fa-solid fa-crown'],
  ];
  $freeUserCards = [
    ['key' => 'standard', 'title' => 'مدل استاندارد', 'description' => 'مسیر پیش‌فرض ساخت با اعتبار هدیه', 'grade' => 4, 'icon' => 'fa-solid fa-gift'],
    ['key' => 'best', 'title' => 'حالت بهترین خروجی', 'description' => 'مدل آماده برای فعال‌شدن دسترسی بهترین خروجی', 'grade' => 1, 'icon' => 'fa-solid fa-star'],
  ];
  $sourceProduct = $duplicateFrom ?? $product ?? null;
  $savedConfiguration = old('model_configuration', (array) ($sourceProduct?->model_configuration ?? []));
  $architectureEnabled = (bool) old(
    'model_configuration.quality_architecture_enabled',
    data_get($savedConfiguration, 'quality_architecture_enabled', true)
  );
  $presetPayloads = collect($modelQualityPresets ?? [])
    ->mapWithKeys(fn ($preset) => [$preset->preset_key => [
      'name' => $preset->name,
      'configuration' => $preset->configuration ?: [],
      'is_default_for_product_creation' => (bool) $preset->is_default_for_product_creation,
    ]])
    ->all();
  $presetUrls = collect($modelQualityPresets ?? [])
    ->mapWithKeys(fn ($preset) => [$preset->preset_key => route('admin.model-quality-presets.update', $preset)])
    ->all();
  $presetDeleteUrls = collect($modelQualityPresets ?? [])
    ->mapWithKeys(fn ($preset) => [$preset->preset_key => route('admin.model-quality-presets.destroy', $preset)])
    ->all();
  $defaultPresetKey = collect($modelQualityPresets ?? [])->first(fn ($preset) => (bool) $preset->is_default_for_product_creation)?->preset_key
    ?: collect($modelQualityPresets ?? [])->first()?->preset_key
    ?: 'preset_1';
  $currentPresetKey = data_get($savedConfiguration, 'quality_preset_key') ?: $defaultPresetKey;
  $selectedPresetConfiguration = data_get($presetPayloads, "{$currentPresetKey}.configuration", data_get($presetPayloads, "{$defaultPresetKey}.configuration", []));
  $selectionFor = function (string $group, string $quality, string $role) use ($savedConfiguration, $selectedPresetConfiguration) {
    $selected = data_get($savedConfiguration, "{$group}.{$quality}.{$role}", data_get($selectedPresetConfiguration, "{$group}.{$quality}.{$role}", []));
    return is_array($selected) ? $selected : [];
  };
@endphp

<section class="step2-disclosure" id="model-quality-architecture"
  data-model-quality-architecture
  data-preset-configurations='@json($presetPayloads)'
  data-preset-urls='@json($presetUrls)'
  data-preset-delete-urls='@json($presetDeleteUrls)'
  data-preset-create-url="{{ route('admin.model-quality-presets.store') }}">
  <div class="step2-disclosure__header">
    <div class="step2-disclosure__heading">
      <span class="step2-disclosure__icon"><i class="fa-solid fa-layer-group"></i></span>
      <div>
        <h3>معماری کیفیت خروجی مدل</h3>
        <p>کیفیت انتخاب‌شده در صفحه ساخت، مدل اصلی و مدل جایگزین همین محصول را تعیین می‌کند.</p>
      </div>
    </div>
    <div class="step2-disclosure__actions">
      <label class="sr-only" for="model-quality-preset">پیش‌فرض‌ها</label>
      <select id="model-quality-preset" name="model_configuration[quality_preset_key]" class="h-9 px-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[11px] font-bold text-[var(--text)]" data-quality-preset>
        @foreach($presetPayloads as $key => $preset)
          <option value="{{ $key }}" @selected($currentPresetKey === $key)>{{ $preset['name'] }}</option>
        @endforeach
        <option value="custom" @selected($currentPresetKey === 'custom')>تنظیم سفارشی</option>
      </select>
      <button type="button" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-[10.5px] font-bold bg-[var(--s1)] text-[var(--text2)] border border-[var(--b1)]" data-manage-quality-presets>
        <i class="fa-solid fa-gear"></i> مدیریت پیش‌فرض‌ها
      </button>
      <button type="button" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-[10.5px] font-bold bg-[var(--primary-l)] text-[var(--primary)] border border-[var(--primary-m)]" data-fix-quality-preset>
        <i class="fa-solid fa-thumbtack"></i> فیکس کردن تنظیمات
      </button>
      <span class="text-[10px] text-[var(--text3)]" data-preset-save-status></span>
      <input type="hidden" name="model_configuration[quality_architecture_enabled]" value="{{ $architectureEnabled ? 1 : 0 }}" data-quality-architecture-enabled>
      <button type="button" class="step2-disclosure__toggle" data-quality-architecture-toggle aria-expanded="{{ $architectureEnabled ? 'true' : 'false' }}" aria-controls="quality-architecture-drawer">
        <span data-quality-architecture-toggle-label>{{ $architectureEnabled ? 'خاموش کردن معماری' : 'روشن کردن معماری' }}</span>
        <span class="step2-disclosure__switch"><span></span></span>
        <i class="fa-solid fa-chevron-down"></i>
      </button>
    </div>
  </div>

  <div id="quality-architecture-drawer" data-quality-architecture-content class="step2-disclosure__drawer {{ $architectureEnabled ? '' : 'hidden' }}">
  <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-4 mb-4">
    <div class="flex items-center gap-2 mb-4"><span class="w-7 h-7 grid place-items-center rounded-lg bg-[var(--primary-l)] text-[var(--primary)]"><i class="fa-solid fa-sliders"></i></span><div><b class="text-xs text-[var(--text)]">مدل‌های انتخابی برای کاربر:</b><p class="text-[10px] text-[var(--text3)] mt-0.5">برای هر کیفیت، مدل اصلی و جایگزین را مشخص کنید.</p></div></div>
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
      @foreach($qualityCards as $card)
        @php $choices = $modelChoices($card['grade']); @endphp
        <article class="border border-[var(--b1)] rounded-xl p-3.5 bg-[var(--s2)]" data-architecture-card="{{ $card['key'] }}">
          <div class="flex items-start gap-2.5 mb-3"><span class="w-8 h-8 grid place-items-center rounded-lg bg-[var(--primary-l)] text-[var(--primary)] shrink-0"><i class="{{ $card['icon'] }}"></i></span><div><b class="text-[12px] text-[var(--text)]">{{ $card['title'] }}</b><p class="text-[9.5px] text-[var(--text3)] leading-5 mt-0.5">{{ $card['description'] }}</p></div></div>
          <div class="grid gap-2">
            @foreach(['primary' => 'مدل اصلی', 'fallback' => 'مدل جایگزین'] as $role => $roleTitle)
              @php
                $selected = $selectionFor('quality_models', $card['key'], $role);
                $roleChoices = $role === 'fallback' ? $architectureModels : $choices;
                $roleProviders = $roleChoices->pluck('provider')->filter()->unique()->values();
              @endphp
              <label class="block">
                <span class="text-[10px] font-bold text-[var(--text2)]">{{ $roleTitle }} @if($role === 'fallback')<small class="font-normal text-[var(--text3)]">(مسیر مستقل)</small>@endif</span>
                <span class="block mt-1 text-[9px] text-[var(--text3)]">ابتدا پرووایدر</span>
                <select class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[10px] text-[var(--text)]" data-quality-provider-select data-group="quality_models" data-quality="{{ $card['key'] }}" data-role="{{ $role }}">
                  <option value="">انتخاب پرووایدر</option>
                  @foreach($roleProviders as $provider)
                    <option value="{{ $provider }}" @selected(($selected['provider'] ?? null) === $provider)>{{ $provider }}</option>
                  @endforeach
                </select>
                <span class="block mt-2 text-[9px] text-[var(--text3)]">سپس مدل</span>
                <input type="search" class="mt-1 w-full h-8 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[10px] text-[var(--text)]" data-quality-model-search placeholder="جستجوی نام یا شناسه مدل..." autocomplete="off" aria-label="جستجوی مدل {{ $roleTitle }} {{ $card['title'] }}">
                <select class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[10px] text-[var(--text)]" data-quality-model-search-select data-quality-model data-group="quality_models" data-quality="{{ $card['key'] }}" data-role="{{ $role }}" data-cost-target="quality_models-{{ $card['key'] }}-{{ $role }}" name="model_configuration[quality_models][{{ $card['key'] }}][{{ $role }}][model_id]">
                  <option value="">ابتدا پرووایدر را انتخاب کنید</option>
                  @foreach($roleChoices as $model)
                    <option value="{{ $model->openrouter_model_id }}" data-provider="{{ $model->provider }}" data-search="{{ strtolower($model->openrouter_model_id.' '.$model->name.' '.$model->provider) }}" data-cost="{{ $modelCostLabel($model) }}" @selected(($selected['model_id'] ?? null) === $model->openrouter_model_id && ($selected['provider'] ?? null) === $model->provider)>{{ $model->shortDisplayName() }} · {{ $model->provider }} · {{ $modelCostLabel($model) }}</option>
                  @endforeach
                </select>
                <input type="hidden" data-quality-provider name="model_configuration[quality_models][{{ $card['key'] }}][{{ $role }}][provider]" value="{{ $selected['provider'] ?? '' }}">
                <small class="block min-h-4 mt-1 text-[9px] text-[var(--text3)]" data-model-cost="quality_models-{{ $card['key'] }}-{{ $role }}">هزینه تقریبی هر ساخت: —</small>
              </label>
            @endforeach
          </div>
        </article>
      @endforeach
    </div>
  </div>

  <div class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-4">
    <div class="flex items-center gap-2 mb-4"><span class="w-7 h-7 grid place-items-center rounded-lg bg-[var(--orange)]/10 text-[var(--orange)]"><i class="fa-solid fa-user-clock"></i></span><div><b class="text-xs text-[var(--text)]">کاربران بدون پلن خریداری‌شده</b><p class="text-[10px] text-[var(--text3)] mt-0.5">مسیر اعتبار هدیه جداست تا هزینه مدل پیشرفته ناخواسته مصرف نشود.</p></div></div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      @foreach($freeUserCards as $card)
        @php $choices = $modelChoices($card['grade']); @endphp
        <article class="border border-dashed border-[var(--b2)] rounded-xl p-3.5 bg-[var(--s2)]" data-architecture-card="free-{{ $card['key'] }}">
          <div class="flex items-start gap-2.5 mb-3"><span class="w-8 h-8 grid place-items-center rounded-lg bg-[var(--primary-l)] text-[var(--primary)] shrink-0"><i class="{{ $card['icon'] }}"></i></span><div><b class="text-[12px] text-[var(--text)]">{{ $card['title'] }}</b><p class="text-[9.5px] text-[var(--text3)] leading-5 mt-0.5">{{ $card['description'] }}</p></div></div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            @foreach(['primary' => 'مدل اصلی', 'fallback' => 'مدل جایگزین'] as $role => $roleTitle)
              @php
                $selected = $selectionFor('free_quality_models', $card['key'], $role);
                $roleChoices = $role === 'fallback' ? $architectureModels : $choices;
                $roleProviders = $roleChoices->pluck('provider')->filter()->unique()->values();
              @endphp
              <label class="block">
                <span class="text-[10px] font-bold text-[var(--text2)]">{{ $roleTitle }} @if($role === 'fallback')<small class="font-normal text-[var(--text3)]">(مسیر مستقل)</small>@endif</span>
                <span class="block mt-1 text-[9px] text-[var(--text3)]">ابتدا پرووایدر</span>
                <select class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[10px] text-[var(--text)]" data-quality-provider-select data-group="free_quality_models" data-quality="{{ $card['key'] }}" data-role="{{ $role }}">
                  <option value="">انتخاب پرووایدر</option>
                  @foreach($roleProviders as $provider)
                    <option value="{{ $provider }}" @selected(($selected['provider'] ?? null) === $provider)>{{ $provider }}</option>
                  @endforeach
                </select>
                <span class="block mt-2 text-[9px] text-[var(--text3)]">سپس مدل</span>
                <input type="search" class="mt-1 w-full h-8 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[10px] text-[var(--text)]" data-quality-model-search placeholder="جستجوی نام یا شناسه مدل..." autocomplete="off" aria-label="جستجوی مدل {{ $roleTitle }} {{ $card['title'] }} کاربران بدون پلن">
                <select class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[10px] text-[var(--text)]" data-quality-model-search-select data-quality-model data-group="free_quality_models" data-quality="{{ $card['key'] }}" data-role="{{ $role }}" data-cost-target="free_quality_models-{{ $card['key'] }}-{{ $role }}" name="model_configuration[free_quality_models][{{ $card['key'] }}][{{ $role }}][model_id]">
                  <option value="">ابتدا پرووایدر را انتخاب کنید</option>
                  @foreach($roleChoices as $model)
                    <option value="{{ $model->openrouter_model_id }}" data-provider="{{ $model->provider }}" data-search="{{ strtolower($model->openrouter_model_id.' '.$model->name.' '.$model->provider) }}" data-cost="{{ $modelCostLabel($model) }}" @selected(($selected['model_id'] ?? null) === $model->openrouter_model_id && ($selected['provider'] ?? null) === $model->provider)>{{ $model->shortDisplayName() }} · {{ $model->provider }} · {{ $modelCostLabel($model) }}</option>
                  @endforeach
                </select>
                <input type="hidden" data-quality-provider name="model_configuration[free_quality_models][{{ $card['key'] }}][{{ $role }}][provider]" value="{{ $selected['provider'] ?? '' }}">
                <small class="block min-h-4 mt-1 text-[9px] text-[var(--text3)]" data-model-cost="free_quality_models-{{ $card['key'] }}-{{ $role }}">هزینه تقریبی هر ساخت: —</small>
              </label>
            @endforeach
          </div>
        </article>
      @endforeach
    </div>
  </div>
  </div>
</section>

<dialog id="model-quality-preset-dialog" class="bg-[var(--s2)] text-[var(--text)] border border-[var(--b1)] rounded-2xl p-0 w-[min(94vw,700px)] backdrop:bg-[var(--text)]/50">
  <div class="p-5" dir="rtl">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div><b class="text-sm">مدیریت پیش‌فرض‌های مدل</b><p class="text-[10px] text-[var(--text3)] mt-1">نام پیش‌فرض‌ها را تغییر دهید، مورد جدید اضافه کنید یا یکی را انتخاب نخست ثبت محصول کنید.</p></div>
      <button type="button" class="text-[var(--text3)]" data-close-quality-presets aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="flex items-center gap-2 mb-4">
      <input type="text" class="flex-1 h-9 px-3 bg-[var(--s1)] border border-[var(--b1)] rounded-lg text-[11px] text-[var(--text)]" placeholder="نام پیش‌فرض جدید" data-new-quality-preset-name>
      <button type="button" class="inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-[10.5px] font-bold bg-[var(--primary-l)] text-[var(--primary)] border border-[var(--primary-m)]" data-add-quality-preset><i class="fa-solid fa-plus"></i> افزودن</button>
    </div>
    <div class="space-y-2" data-quality-preset-list></div>
    <div class="min-h-5 mt-3 text-[10.5px] text-[var(--text3)]" data-quality-preset-management-status></div>
  </div>
</dialog>

<style>
  #model-quality-preset-dialog {
    position: fixed;
    inset: 0;
    margin: auto;
    max-width: min(94vw, 700px);
    max-height: 90vh;
  }
  #model-quality-preset-dialog::backdrop {
    background: color-mix(in srgb, var(--text) 50%, transparent);
  }
</style>

<script>
(() => {
  const root = document.querySelector('[data-model-quality-architecture]');
  if (!root) return;
  const presets = JSON.parse(root.dataset.presetConfigurations || '{}');
  const presetUrls = JSON.parse(root.dataset.presetUrls || '{}');
  const presetDeleteUrls = JSON.parse(root.dataset.presetDeleteUrls || '{}');
  const presetCreateUrl = root.dataset.presetCreateUrl || '';
  const presetSelect = root.querySelector('[data-quality-preset]');
  const status = root.querySelector('[data-preset-save-status]');
  const selectors = Array.from(root.querySelectorAll('[data-quality-model]'));
  const providerSelectors = Array.from(root.querySelectorAll('[data-quality-provider-select]'));
  const presetDialog = document.getElementById('model-quality-preset-dialog');
  const presetList = presetDialog?.querySelector('[data-quality-preset-list]');
  const presetManagementStatus = presetDialog?.querySelector('[data-quality-preset-management-status]');

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
  const setPresetManagementStatus = (message, error = false) => {
    if (!presetManagementStatus) return;
    presetManagementStatus.textContent = message || '';
    presetManagementStatus.style.color = error ? 'var(--danger)' : 'var(--text3)';
  };
  const announcePresetChange = (message) => {
    setPresetManagementStatus(message);
    if (status) {
      status.textContent = message;
      status.style.color = 'var(--success)';
    }
  };
  const refreshPresetSelect = (selectedKey = presetSelect?.value) => {
    if (!presetSelect) return;
    presetSelect.innerHTML = Object.entries(presets).map(([key, preset]) =>
      '<option value="' + escapeHtml(key) + '"' + (key === selectedKey ? ' selected' : '') + '>' + escapeHtml(preset.name || key) + '</option>'
    ).join('') + '<option value="custom"' + (selectedKey === 'custom' ? ' selected' : '') + '>تنظیم سفارشی</option>';
  };
  const renderPresetManager = () => {
    if (!presetList) return;
    presetList.innerHTML = Object.entries(presets).map(([key, preset]) => `
      <div class="flex items-center gap-2 p-2.5 rounded-xl bg-[var(--s1)] border border-[var(--b1)]" data-quality-preset-row="${escapeHtml(key)}">
        <input type="text" value="${escapeHtml(preset.name || key)}" class="flex-1 min-w-0 h-8 px-2 bg-[var(--s2)] border border-[var(--b1)] rounded-lg text-[10.5px] text-[var(--text)]" data-quality-preset-name>
        ${preset.is_default_for_product_creation ? '<span class="text-[9px] font-bold text-[var(--success)] whitespace-nowrap">انتخاب نخست ثبت محصول</span>' : ''}
        <button type="button" class="h-8 px-2 rounded-lg text-[9.5px] font-bold text-[var(--primary)] border border-[var(--primary-m)]" data-preset-action="rename">ذخیره نام</button>
        ${preset.is_default_for_product_creation ? '' : '<button type="button" class="h-8 px-2 rounded-lg text-[9.5px] font-bold text-[var(--text2)] border border-[var(--b1)]" data-preset-action="default">انتخاب نخست</button>'}
        <button type="button" class="h-8 w-8 rounded-lg text-[var(--danger)] border border-[var(--b1)]" data-preset-action="delete" aria-label="حذف"><i class="fa-solid fa-trash text-[10px]"></i></button>
      </div>`).join('');
  };
  const requestPreset = async (url, method, payload = null) => {
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: payload ? JSON.stringify(payload) : undefined,
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) {
      const validationMessage = Object.values(result.errors || {})[0]?.[0];
      throw new Error(validationMessage || result.message || 'عملیات پیش‌فرض انجام نشد.');
    }
    return result;
  };
  root.querySelector('[data-manage-quality-presets]')?.addEventListener('click', () => {
    renderPresetManager();
    setPresetManagementStatus('');
    presetDialog?.showModal();
  });
  presetDialog?.querySelector('[data-close-quality-presets]')?.addEventListener('click', () => presetDialog.close());
  presetDialog?.addEventListener('cancel', (event) => event.preventDefault());
  presetDialog?.querySelector('[data-add-quality-preset]')?.addEventListener('click', async () => {
    const input = presetDialog.querySelector('[data-new-quality-preset-name]');
    const name = input?.value.trim();
    const source = presets[presetSelect?.value] || Object.values(presets)[0];
    if (!name) { setPresetManagementStatus('نام پیش‌فرض جدید را وارد کنید.', true); return; }
    if (!source?.configuration) { setPresetManagementStatus('برای ساخت پیش‌فرض جدید، ابتدا یک پیش‌فرض کامل لازم است.', true); return; }
    try {
      setPresetManagementStatus('در حال افزودن…');
      const result = await requestPreset(presetCreateUrl, 'POST', { name, configuration: source.configuration });
      const created = result.preset;
      presets[created.preset_key] = {
        name: created.name,
        configuration: created.configuration || source.configuration,
        is_default_for_product_creation: Boolean(created.is_default_for_product_creation),
      };
      presetUrls[created.preset_key] = result.update_url;
      presetDeleteUrls[created.preset_key] = result.delete_url;
      refreshPresetSelect(created.preset_key);
      applyPreset(presets[created.preset_key].configuration);
      renderPresetManager();
      if (input) input.value = '';
      announcePresetChange('پیش‌فرض جدید اضافه شد و همین‌جا باقی ماند.');
    } catch (error) { setPresetManagementStatus(error.message, true); }
  });
  presetList?.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-preset-action]');
    const row = event.target.closest('[data-quality-preset-row]');
    if (!button || !row) return;
    const key = row.dataset.qualityPresetRow;
    const action = button.dataset.presetAction;
    try {
      setPresetManagementStatus('در حال ذخیره…');
      if (action === 'rename') {
        const name = row.querySelector('[data-quality-preset-name]')?.value.trim();
        if (!name) throw new Error('نام پیش‌فرض نمی‌تواند خالی باشد.');
        const result = await requestPreset(presetUrls[key], 'PATCH', { name });
        presets[key].name = result.preset?.name || name;
      } else if (action === 'default') {
        const result = await requestPreset(presetUrls[key], 'PATCH', { is_default_for_product_creation: true });
        Object.keys(presets).forEach((presetKey) => {
          presets[presetKey].is_default_for_product_creation = presetKey === key;
        });
        if (result.preset) presets[key].name = result.preset.name || presets[key].name;
      } else if (action === 'delete') {
        if (!window.confirm('این پیش‌فرض حذف شود؟ محصولات استفاده‌کننده به پیش‌فرض دیگری منتقل می‌شوند.')) return;
        await requestPreset(presetDeleteUrls[key], 'DELETE');
        delete presets[key];
        delete presetUrls[key];
        delete presetDeleteUrls[key];
        const nextKey = presetSelect?.value === key ? Object.keys(presets)[0] : presetSelect?.value;
        refreshPresetSelect(nextKey);
        if (nextKey && presets[nextKey]) applyPreset(presets[nextKey].configuration);
      }
      renderPresetManager();
      announcePresetChange(action === 'rename' ? 'نام پیش‌فرض ذخیره شد.' : action === 'default' ? 'انتخاب نخست ثبت محصول تغییر کرد.' : 'پیش‌فرض حذف شد.');
    } catch (error) { setPresetManagementStatus(error.message, true); }
  });

  const pairSelector = (select, role = select.dataset.role) => '[data-quality-provider-select][data-group="' + select.dataset.group + '"][data-quality="' + select.dataset.quality + '"][data-role="' + role + '"]';
  const pairedProviderSelect = (select) => root.querySelector(pairSelector(select));
  const pairedModelSelect = (providerSelect) => root.querySelector('[data-quality-model][data-group="' + providerSelect.dataset.group + '"][data-quality="' + providerSelect.dataset.quality + '"][data-role="' + providerSelect.dataset.role + '"]');
  const primaryProvider = (select) => root.querySelector('[data-quality-provider-select][data-group="' + select.dataset.group + '"][data-quality="' + select.dataset.quality + '"][data-role="primary"]')?.value || '';

  const filterQualityModelOptions = (input) => {
    const query = String(input?.value || '').trim().toLowerCase();
    const select = input?.closest('label')?.querySelector('[data-quality-model-search-select]');
    if (!select) return;
    Array.from(select.options).forEach((option) => {
      if (!option.value) return;
      const haystack = String(option.dataset.search || option.textContent || '').toLowerCase();
      const allowed = option.dataset.qualityAllowed !== '0';
      option.hidden = !allowed || Boolean(query && !haystack.includes(query));
    });
  };

  // پیش‌فرض فقط نقطه‌ی شروع است. به‌محض تغییر دستی یک provider یا مدل،
  // انتخاب محصول سفارشی می‌شود و نام پیش‌فرض قبلی دیگر به‌اشتباه حفظ نمی‌شود.
  const markPresetAsCustom = () => {
    if (!presetSelect) return;
    if (!presetSelect.querySelector('option[value="custom"]')) {
      const option = document.createElement('option');
      option.value = 'custom';
      option.textContent = 'تنظیم سفارشی';
      presetSelect.appendChild(option);
    }
    if (presetSelect.value !== 'custom') {
      presetSelect.value = 'custom';
      if (status) {
        status.textContent = 'تغییر دستی ذخیره شد؛ این محصول اکنون تنظیم سفارشی دارد.';
        status.style.color = 'var(--warning)';
      }
    }
  };

  const refreshSelect = (select, requestedProvider = null) => {
    const wrapper = select.closest('label');
    const providerSelect = pairedProviderSelect(select);
    const hiddenProvider = wrapper?.querySelector('[data-quality-provider]');
    const provider = requestedProvider === null ? (providerSelect?.value || hiddenProvider?.value || '') : requestedProvider;

    if (providerSelect) {
      Array.from(providerSelect.options).forEach((option) => {
        if (!option.value) return;
        // جایگزین می‌تواند از همان provider مدل اصلی باشد؛ فقط خودِ همان
        // مدل (ترکیب provider + شناسه) نباید دوباره انتخاب شود.
        option.hidden = false;
        option.disabled = false;
      });
      providerSelect.value = provider || '';
    }

    const activeProvider = providerSelect?.value || '';
    const primaryModel = select.dataset.role === 'fallback'
      ? root.querySelector('[data-quality-model][data-group="' + select.dataset.group + '"][data-quality="' + select.dataset.quality + '"][data-role="primary"]')
      : null;
    const primaryModelId = primaryModel?.value || '';
    const primaryModelProvider = primaryProvider(select);
    let selectedIsVisible = false;
    Array.from(select.options).forEach((option) => {
      if (!option.value) return;
      const isSameAsPrimary = select.dataset.role === 'fallback'
        && option.value === primaryModelId
        && option.dataset.provider === primaryModelProvider;
      const allowed = Boolean(activeProvider) && option.dataset.provider === activeProvider && !isSameAsPrimary;
      option.dataset.qualityAllowed = allowed ? '1' : '0';
      option.hidden = !allowed;
      option.disabled = !allowed;
      if (option.selected && allowed) selectedIsVisible = true;
    });
    select.disabled = !activeProvider;
    if (!selectedIsVisible) select.value = '';
    if (hiddenProvider) hiddenProvider.value = activeProvider;
    filterQualityModelOptions(wrapper?.querySelector('[data-quality-model-search]'));
    sync(select);
  };

  const sync = (select) => {
    const selected = select.options[select.selectedIndex];
    const wrapper = select.closest('label');
    const provider = wrapper?.querySelector('[data-quality-provider]');
    const cost = wrapper?.querySelector('[data-model-cost]');
    if (provider) provider.value = selected?.dataset.provider || pairedProviderSelect(select)?.value || '';
    if (cost) cost.textContent = selected?.dataset.cost ? 'هزینه تقریبی هر ساخت: ' + selected.dataset.cost : 'هزینه تقریبی هر ساخت: —';
  };
  const selectionFor = (configuration, select) => configuration?.[select.dataset.group]?.[select.dataset.quality]?.[select.dataset.role] || {};
  const applyPreset = (configuration) => {
    selectors.forEach((select) => {
      const selection = selectionFor(configuration, select);
      const providerSelect = pairedProviderSelect(select);
      const target = Array.from(select.options).find((option) => option.value === selection.model_id && option.dataset.provider === selection.provider);
      if (providerSelect) providerSelect.value = selection.provider || '';
      select.value = target ? target.value : '';
      refreshSelect(select, selection.provider || '');
    });
  };
  const currentConfiguration = () => {
    const configuration = { quality_models: {}, free_quality_models: {} };
    selectors.forEach((select) => {
      const group = select.dataset.group;
      const quality = select.dataset.quality;
      const role = select.dataset.role;
      configuration[group][quality] ||= {};
      configuration[group][quality][role] = {
        model_id: select.value || null,
        provider: select.closest('label')?.querySelector('[data-quality-provider]')?.value || null,
      };
    });
    return configuration;
  };

  selectors.forEach((select) => {
    refreshSelect(select);
    select.addEventListener('change', () => {
      sync(select);
      markPresetAsCustom();
      if (select.dataset.role === 'primary') {
        const fallback = root.querySelector('[data-quality-model][data-group="' + select.dataset.group + '"][data-quality="' + select.dataset.quality + '"][data-role="fallback"]');
        if (fallback) refreshSelect(fallback);
      }
    });
  });
  providerSelectors.forEach((providerSelect) => {
    providerSelect.addEventListener('change', () => {
      const select = pairedModelSelect(providerSelect);
      if (!select) return;
      select.value = '';
      refreshSelect(select, providerSelect.value);
      markPresetAsCustom();
      if (providerSelect.dataset.role === 'primary') {
        const fallback = root.querySelector('[data-quality-model][data-group="' + providerSelect.dataset.group + '"][data-quality="' + providerSelect.dataset.quality + '"][data-role="fallback"]');
        if (fallback) refreshSelect(fallback);
      }
    });
  });
  root.querySelectorAll('[data-quality-model-search]').forEach((input) => {
    input.addEventListener('input', () => filterQualityModelOptions(input));
  });
  presetSelect?.addEventListener('change', () => {
    const preset = presets[presetSelect.value];
    if (presetSelect.value === 'custom') {
      if (status) {
        status.textContent = 'تنظیم سفارشی فعال است؛ تغییرات شما روی همین محصول ذخیره می‌شود.';
        status.style.color = 'var(--warning)';
      }
      return;
    }
    if (preset?.configuration) applyPreset(preset.configuration);
    if (status) status.textContent = '';
  });
  const architectureToggle = root.querySelector('[data-quality-architecture-toggle]');
  const architectureContent = root.querySelector('[data-quality-architecture-content]');
  const architectureToggleLabel = root.querySelector('[data-quality-architecture-toggle-label]');
  const architectureEnabledInput = root.querySelector('[data-quality-architecture-enabled]');
  const setArchitectureState = (enabled) => {
    architectureContent?.classList.toggle('hidden', !enabled);
    architectureToggle?.setAttribute('aria-expanded', enabled ? 'true' : 'false');
    if (architectureEnabledInput) architectureEnabledInput.value = enabled ? '1' : '0';
    if (architectureToggleLabel) architectureToggleLabel.textContent = enabled ? 'خاموش کردن معماری' : 'روشن کردن معماری';
  };
  architectureToggle?.addEventListener('click', () => {
    setArchitectureState(architectureToggle.getAttribute('aria-expanded') !== 'true');
  });
  root.querySelector('[data-fix-quality-preset]')?.addEventListener('click', async () => {
    const key = presetSelect?.value;
    const url = presetUrls[key];
    if (!url) {
      if (status) status.textContent = 'پیش‌فرض انتخاب‌شده در دسترس نیست.';
      return;
    }
    if (status) status.textContent = 'در حال ذخیره…';
    try {
      const response = await fetch(url, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        },
        body: JSON.stringify({ configuration: currentConfiguration() }),
      });
      const result = await response.json();
      if (!response.ok) throw new Error(result.message || 'ذخیره تنظیمات انجام نشد.');
      presets[key] = { ...(presets[key] || {}), configuration: result.preset.configuration };
      if (status) status.textContent = 'تنظیمات این پیش‌فرض ذخیره شد.';
    } catch (error) {
      if (status) status.textContent = error.message || 'ذخیره تنظیمات انجام نشد.';
    }
  });
})();
</script>
