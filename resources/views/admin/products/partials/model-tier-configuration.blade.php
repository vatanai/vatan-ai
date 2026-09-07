@php
  $tierDefinitions = \App\Services\ModelTierService::DEFINITIONS;
  $savedConfiguration = old('model_configuration', (array) ($product?->model_configuration ?? $duplicateFrom?->model_configuration ?? []));
  $tierModels = $aiModels->filter(fn ($model) => $model->supportsProductImageWorkflow() && $model->featured_in_lab);
  $modelCost = function ($model) use ($exchange) {
    $usd = data_get($model?->lab_pricing ?? [], 'usd');
    return [
      'usd' => is_numeric($usd) ? (float) $usd : null,
      'toman' => is_numeric($usd) ? (int) round(((float) $usd * (float) data_get($exchange, 'rate', 0)) / 10) : null,
    ];
  };
  $tierDefaultRecords = $modelTierDefaults->mapWithKeys(function ($item) {
    return [$item->tier_key => [
      'id' => $item->id,
      'name' => $item->name,
      'description' => $item->description,
      'is_active' => $item->is_active,
      'primary_model_id' => $item->primary_model_id,
      'primary_provider' => $item->primary_provider,
      'fallback_model_id' => $item->fallback_model_id,
      'fallback_provider' => $item->fallback_provider,
    ]];
  })->all();
@endphp

<section class="bg-[var(--s2)] border border-[var(--b1)] rounded-xl p-5 mb-5" id="model-tier-configuration">
  <div class="flex items-start justify-between gap-4 pb-4 mb-4 border-b border-[var(--b1)] flex-wrap">
    <div>
      <div class="text-sm font-bold text-[var(--text)] flex items-center gap-2"><i class="fa-solid fa-layer-group text-[var(--accent)]"></i> معماری چهار سطح مدل</div>
      <p class="text-[11px] text-[var(--text3)] mt-1 leading-6">هر محصول برای هر سطح کاربر یک مدل اصلی و یک مسیر جایگزین مستقل دارد. مسیر جایگزین می‌تواند از مدل دیگری در همان provider یا provider دیگری باشد.</p>
    </div>
    <button type="button" onclick="openTierDefaultDialog()" class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg text-[11px] font-semibold bg-[var(--primary-l)] text-[var(--primary)] border border-[var(--primary-m)]"><i class="fa-solid fa-sliders"></i> ویرایش پیش‌فرض‌ها</button>
  </div>

  <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
    @foreach($tierDefinitions as $tierKey => $tierMeta)
      @php
        $default = $modelTierDefaults->get($tierKey);
        $savedTier = (array) data_get($savedConfiguration, "tiers.{$tierKey}", []);
        $primarySaved = (array) ($savedTier['primary'] ?? []);
        $fallbackSaved = (array) ($savedTier['fallback'] ?? []);
        $primaryModelId = old("model_configuration.tiers.{$tierKey}.primary.model_id", $primarySaved['model_id'] ?? $default?->primary_model_id);
        $primaryProvider = old("model_configuration.tiers.{$tierKey}.primary.provider", $primarySaved['provider'] ?? $default?->primary_provider);
        $fallbackModelId = old("model_configuration.tiers.{$tierKey}.fallback.model_id", $fallbackSaved['model_id'] ?? $default?->fallback_model_id);
        $fallbackProvider = old("model_configuration.tiers.{$tierKey}.fallback.provider", $fallbackSaved['provider'] ?? $default?->fallback_provider);
        $modelsForTier = $tierModels->filter(fn ($model) => $model->pricingGrade() === $tierMeta['grade']);
        $modelsForTier = $modelsForTier->isNotEmpty() ? $modelsForTier : $tierModels;
        $tierDefaultPayload = [
          'primary_model_id' => $default?->primary_model_id,
          'primary_provider' => $default?->primary_provider,
          'fallback_model_id' => $default?->fallback_model_id,
          'fallback_provider' => $default?->fallback_provider,
        ];
      @endphp
      <article class="bg-[var(--s1)] border border-[var(--b1)] rounded-xl p-4" data-tier-card="{{ $tierKey }}">
        <div class="flex items-center justify-between gap-3 mb-3">
          <div><b class="text-xs text-[var(--text)]">پلن {{ $tierMeta['name'] }} <span class="text-[var(--text3)] font-normal">· گرید {{ $tierMeta['grade'] }}</span></b><p class="text-[10px] text-[var(--text3)] mt-1">{{ $tierMeta['description'] }}</p></div>
          <div class="flex items-center gap-2"><button type="button" class="text-[10px] text-[var(--primary)] hover:text-[var(--accent)]" onclick="applyTierDefault('{{ $tierKey }}')">اعمال پیش‌فرض</button><button type="button" class="text-[10px] text-[var(--text3)] hover:text-[var(--text)]" onclick="openTierDefaultDialog('{{ $tierKey }}')">ویرایش</button></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
          <label class="block">
            <span class="text-[10px] font-bold text-[var(--text2)]">مدل اصلی</span>
            <select name="model_configuration[tiers][{{ $tierKey }}][primary][model_id]" data-tier="{{ $tierKey }}" data-role="primary" onchange="syncTierModel(this)" class="mt-1 w-full h-9 px-2 bg-[var(--s2)] border border-[var(--b1)] rounded-lg text-[11px] text-[var(--text)]">
              <option value="">انتخاب مدل اصلی</option>
              @foreach($modelsForTier as $model)
                @php($cost = $modelCost($model))
                <option value="{{ $model->openrouter_model_id }}" data-provider="{{ $model->provider }}" data-usd="{{ $cost['usd'] }}" data-toman="{{ $cost['toman'] }}" @selected($primaryModelId === $model->openrouter_model_id && $primaryProvider === $model->provider)>{{ $model->shortDisplayName() }} · {{ $model->provider }}</option>
              @endforeach
            </select>
            <input type="hidden" name="model_configuration[tiers][{{ $tierKey }}][primary][provider]" data-tier-provider="{{ $tierKey }}" data-role="primary" value="{{ $primaryProvider }}">
            <small class="block mt-1 text-[10px] text-[var(--text3)]" data-tier-cost="{{ $tierKey }}-primary">هزینه تقریبی هر ساخت: —</small>
          </label>
          <label class="block">
            <span class="text-[10px] font-bold text-[var(--text2)]">مدل جایگزین <span class="text-[var(--text3)] font-normal">(مسیر مستقل)</span></span>
            <select name="model_configuration[tiers][{{ $tierKey }}][fallback][model_id]" data-tier="{{ $tierKey }}" data-role="fallback" onchange="syncTierModel(this)" class="mt-1 w-full h-9 px-2 bg-[var(--s2)] border border-[var(--b1)] rounded-lg text-[11px] text-[var(--text)]">
              <option value="">انتخاب مدل جایگزین</option>
              @foreach($tierModels as $model)
                @php($cost = $modelCost($model))
                <option value="{{ $model->openrouter_model_id }}" data-provider="{{ $model->provider }}" data-usd="{{ $cost['usd'] }}" data-toman="{{ $cost['toman'] }}" @selected($fallbackModelId === $model->openrouter_model_id && $fallbackProvider === $model->provider)>{{ $model->shortDisplayName() }} · {{ $model->provider }}</option>
              @endforeach
            </select>
            <input type="hidden" name="model_configuration[tiers][{{ $tierKey }}][fallback][provider]" data-tier-provider="{{ $tierKey }}" data-role="fallback" value="{{ $fallbackProvider }}">
            <small class="block mt-1 text-[10px] text-[var(--text3)]" data-tier-cost="{{ $tierKey }}-fallback">هزینه تقریبی هر ساخت: —</small>
          </label>
        </div>
        <script type="application/json" id="tier-default-{{ $tierKey }}">@json($tierDefaultPayload)</script>
      </article>
    @endforeach
  </div>
  <p class="text-[10px] text-[var(--text3)] mt-3">اعداد هزینه تخمینی‌اند و با نرخ روز دلار و تنظیمات رزولوشن/پرامپت تغییر می‌کنند؛ قیمت فروش از این صفحه به کاربر نمایش داده نمی‌شود.</p>
</section>

<dialog id="tier-default-dialog" class="bg-[var(--s2)] text-[var(--text)] border border-[var(--b1)] rounded-2xl p-0 w-[min(94vw,620px)] backdrop:bg-[var(--text)]/50">
  <form id="tier-default-form" method="POST" class="p-5" onsubmit="saveTierDefault(event)">
    @csrf @method('PATCH')
    <div class="flex items-center justify-between gap-3 mb-4"><div><b class="text-sm">ویرایش پیش‌فرض سطح مدل</b><p class="text-[10px] text-[var(--text3)] mt-1">این تنظیم برای اعمال سریع روی محصولات جدید یا انتخاب‌شده است.</p></div><button type="button" onclick="document.getElementById('tier-default-dialog').close()" class="text-[var(--text3)]"><i class="fa-solid fa-xmark"></i></button></div>
    <input type="hidden" name="is_active" value="0"><input id="tier-default-active" type="hidden" name="is_active" value="1">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <label class="text-[11px]">نام سطح<input id="tier-default-name" name="name" class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg"></label>
      <label class="text-[11px]">توضیح<input id="tier-default-description" name="description" class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg"></label>
      <label class="text-[11px]">مدل اصلی<select id="tier-default-primary-model" name="primary_model_id" onchange="syncDefaultProvider('primary')" class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg">@foreach($tierModels as $model)<option value="{{ $model->openrouter_model_id }}" data-provider="{{ $model->provider }}">{{ $model->shortDisplayName() }} · {{ $model->provider }}</option>@endforeach</select><input id="tier-default-primary-provider" type="hidden" name="primary_provider"></label>
      <label class="text-[11px]">مدل جایگزین<select id="tier-default-fallback-model" name="fallback_model_id" onchange="syncDefaultProvider('fallback')" class="mt-1 w-full h-9 px-2 bg-[var(--s1)] border border-[var(--b1)] rounded-lg">@foreach($tierModels as $model)<option value="{{ $model->openrouter_model_id }}" data-provider="{{ $model->provider }}">{{ $model->shortDisplayName() }} · {{ $model->provider }}</option>@endforeach</select><input id="tier-default-fallback-provider" type="hidden" name="fallback_provider"></label>
    </div>
    <div class="flex justify-end gap-2 mt-5"><button type="button" onclick="document.getElementById('tier-default-dialog').close()" class="px-3 h-9 rounded-lg text-xs border border-[var(--b1)] text-[var(--text2)]">انصراف</button><button class="px-3 h-9 rounded-lg text-xs bg-[var(--primary)] text-white">ذخیره پیش‌فرض</button></div>
  </form>
</dialog>

<script>
const tierDefaultRecords = @json($tierDefaultRecords);
const tierDefaultUpdateUrl = '{{ route('admin.model-tier-defaults.update', ['modelTierDefault' => '__id__']) }}';
function tierNumber(value) { return value ? new Intl.NumberFormat('fa-IR').format(Number(value)) : '—'; }
function syncTierModel(select) {
  const tier = select.dataset.tier, role = select.dataset.role, option = select.options[select.selectedIndex];
  const provider = document.querySelector('[data-tier-provider="' + tier + '"][data-role="' + role + '"]');
  if (provider) provider.value = option?.dataset.provider || '';
  const cost = document.querySelector('[data-tier-cost="' + tier + '-' + role + '"]');
  if (cost) cost.textContent = option?.dataset.usd ? 'هزینه تقریبی هر ساخت: $' + Number(option.dataset.usd).toFixed(3) + ' · ' + tierNumber(option.dataset.toman) + ' تومان' : 'هزینه تقریبی هر ساخت: —';
}
function applyTierDefault(tier) {
  const value = JSON.parse(document.getElementById('tier-default-' + tier).textContent || '{}');
  ['primary', 'fallback'].forEach(function(role) {
    const select = document.querySelector('[data-tier="' + tier + '"][data-role="' + role + '"]');
    if (!select) return;
    const id = value[role + '_model_id'], provider = value[role + '_provider'];
    const option = Array.from(select.options).find(item => item.value === id && item.dataset.provider === provider);
    if (option) { select.value = option.value; syncTierModel(select); }
  });
}
function syncDefaultProvider(role) {
  const select = document.getElementById('tier-default-' + role + '-model');
  document.getElementById('tier-default-' + role + '-provider').value = select?.options[select.selectedIndex]?.dataset.provider || '';
}
function openTierDefaultDialog(tier = 'free') {
  const item = tierDefaultRecords[tier] || Object.values(tierDefaultRecords)[0]; if (!item) return;
  const form = document.getElementById('tier-default-form'); form.action = tierDefaultUpdateUrl.replace('__id__', item.id);
  document.getElementById('tier-default-name').value = item.name || '';
  document.getElementById('tier-default-description').value = item.description || '';
  document.getElementById('tier-default-active').value = item.is_active ? '1' : '0';
  ['primary', 'fallback'].forEach(function(role) {
    const select = document.getElementById('tier-default-' + role + '-model');
    const opt = Array.from(select.options).find(option => option.value === item[role + '_model_id'] && option.dataset.provider === item[role + '_provider']);
    if (opt) select.value = opt.value; syncDefaultProvider(role);
  });
  document.getElementById('tier-default-dialog').showModal();
}
async function saveTierDefault(event) {
  event.preventDefault(); const form = event.currentTarget;
  const response = await fetch(form.action, {method:'POST', headers:{'Accept':'application/json'}, body:new FormData(form)});
  if (!response.ok) { alert('ذخیره پیش‌فرض انجام نشد. مدل اصلی و جایگزین باید فعال و متفاوت باشند.'); return; }
  window.location.reload();
}
document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-tier][data-role]').forEach(syncTierModel));
</script>
