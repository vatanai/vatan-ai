@php
  $providerMeta = [
    'liara' => ['title' => 'Liara AI', 'label' => 'لیارا', 'description' => 'سرویس سازگار با API تصویر', 'icon' => 'fa-cloud', 'color' => 'success'],
    'openrouter' => ['title' => 'OpenRouter', 'label' => 'OpenRouter', 'description' => 'گیت‌وی مدل‌های عمومی', 'icon' => 'fa-bolt', 'color' => 'info'],
    'fal' => ['title' => 'Fal.ai', 'label' => 'Fal.ai', 'description' => 'صف سریع مدل‌های تصویر و ویدیو', 'icon' => 'fa-wand-magic-sparkles', 'color' => 'warning'],
    'replicate' => ['title' => 'Replicate', 'label' => 'Replicate', 'description' => 'مدل‌های community با version مستقل', 'icon' => 'fa-cubes', 'color' => 'primary'],
  ];
@endphp

<div class="provider-grid-clean">
  @foreach($providerMeta as $provider => $meta)
    @php
      $setting = $providerSettings[$provider] ?? null;
      $hasKey = $setting?->hasApiKey() || filled(config("services.{$provider}.api_key")) || filled(config("services.{$provider}.api_token"));
      $baseUrl = $setting?->base_url ?: config("services.{$provider}.base_url");
      $providerModels = $models->where('provider', $provider);
      $providerCredit = $creditAccounts[$provider] ?? null;
      $creditScale = $providerCredit && $providerCredit->currency === 'IRR' ? 10 : 1;
      $creditUnit = $providerCredit && $providerCredit->currency === 'IRR' ? 'تومان' : ($providerCredit->currency ?? '');
    @endphp
    <section class="provider-card border border-[var(--border)] rounded-xl p-4 bg-[var(--input-bg)]" data-provider-card="{{ $provider }}" data-provider-enabled="{{ $providerStatus[$provider] ? '1' : '0' }}">
      <div class="flex items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center bg-[var(--{{ $meta['color'] }}-l)] text-[var(--{{ $meta['color'] }})] border border-[var(--border)]">
            <i class="fa-solid {{ $meta['icon'] }} text-xs"></i>
          </div>
          <div>
            <div class="text-xs font-extrabold text-[var(--text-h)]">{{ $meta['title'] }}</div>
            <div class="text-[10px] text-[var(--text-soft)]">{{ $meta['description'] }}</div>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="provider-status-box {{ $providerStatus[$provider] ? 'is-on' : 'is-off' }}">{{ $providerStatus[$provider] ? 'فعال می‌باشد' : 'غیر فعال می‌باشد' }}</span>
          <form method="POST" action="{{ route('admin.ai-models.toggle-provider') }}">
            @csrf
            <input type="hidden" name="provider" value="{{ $provider }}">
            <input type="hidden" name="enabled" value="{{ $providerStatus[$provider] ? '0' : '1' }}">
            <button type="submit" class="provider-toggle-btn">{{ $providerStatus[$provider] ? 'غیرفعال‌کردن' : 'فعال‌کردن' }}</button>
          </form>
        </div>
      </div>

      <div class="provider-metrics">
        <div><span>مدل‌ها</span><strong>{{ $providerModels->count() }}</strong></div>
        <div><span>مدل فعال</span><strong>{{ $providerModels->where('is_active', true)->count() }}</strong></div>
        <div><span>موجودی</span><strong>{{ $providerCredit ? number_format((float) ($providerCredit->display_balance ?? 0) / $creditScale, 2) . ' ' . $creditUnit : '—' }}</strong></div>
        <div><span>کل مصرف</span><strong>{{ $providerCredit ? number_format((float) ($providerCredit->total_usage ?? 0) / $creditScale, 2) . ' ' . $creditUnit : '—' }}</strong></div>
      </div>
      <div class="provider-key-line">کلید: <b>{{ $hasKey ? ($setting?->maskedApiKey() ?? 'از محیط') : 'ثبت نشده' }}</b></div>

      <div class="provider-models-panel">
        <div class="provider-models-head">
          <div>
            <strong>مدل‌های این پرووایدر</strong>
            <span>{{ $providerModels->count() }} مدل ثبت شده</span>
          </div>
          <a href="{{ route('admin.ai-models.create', ['provider' => $provider]) }}" class="provider-model-add">
            <i class="fa-solid fa-plus"></i> افزودن مدل
          </a>
        </div>
        <div class="provider-model-list">
          @forelse($providerModels->sortByDesc('created_at') as $model)
            <div class="provider-model-row">
              <div class="provider-model-info">
                <span class="provider-model-media"><i class="fa-solid {{ $model->mediaIcon() }}"></i></span>
                <div class="min-w-0">
                  <strong title="{{ $model->name }}">{{ $model->name }}</strong>
                  <small dir="ltr" title="{{ $model->externalModelId() }}">{{ $model->externalModelId() }}</small>
                  <small class="provider-model-grade">گرید {{ $model->qualityGradeLabel() }}</small>
                </div>
              </div>
              <div class="provider-model-actions">
                <span class="provider-model-status {{ $model->is_active ? 'is-on' : 'is-off' }}">{{ $model->is_active ? 'فعال' : 'خاموش' }}</span>
                <form method="POST" action="{{ route('admin.ai-models.toggle-model', $model) }}">
                  @csrf
                  <input type="hidden" name="return_to" value="providers">
                  <button type="submit" class="provider-model-action" title="{{ $model->is_active ? 'خاموش‌کردن مدل' : 'روشن‌کردن مدل' }}"><i class="fa-solid fa-power-off"></i></button>
                </form>
                <a href="{{ route('admin.ai-models.edit', $model) }}" class="provider-model-action" title="ویرایش مدل"><i class="fa-regular fa-pen-to-square"></i></a>
                <form method="POST" action="{{ route('admin.ai-models.destroy', $model) }}" onsubmit="return confirm('این مدل حذف شود؟')">
                  @csrf @method('DELETE')
                  <input type="hidden" name="return_to" value="providers">
                  <button type="submit" class="provider-model-action is-danger" title="حذف مدل"><i class="fa-regular fa-trash-can"></i></button>
                </form>
              </div>
            </div>
          @empty
            <div class="provider-model-empty">برای این پرووایدر هنوز مدلی ثبت نشده است.</div>
          @endforelse
        </div>
        @if($providerModels->count() > 0)
          <a href="{{ route('admin.ai-models.index', ['provider' => $provider]) }}" class="provider-model-all">مشاهده و فیلتر همه‌ی مدل‌ها <i class="fa-solid fa-arrow-left"></i></a>
        @endif
      </div>

      @php
        $limit = $usageLimits[$provider] ?? array_replace(\App\Services\AiProviderLimitService::DEFAULTS, [
          'request_count' => 0,
          'active_count' => 0,
          'spent_usd' => 0,
          'remaining_requests' => null,
          'remaining_cost_usd' => null,
        ]);
      @endphp
      <form method="POST" action="{{ route('admin.ai-models.provider-settings') }}" class="space-y-3">
        @csrf @method('PUT')
        <input type="hidden" name="provider" value="{{ $provider }}">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <label class="text-[10px] text-[var(--text-soft)]">کلید جدید
            <input class="input-pro mt-1 w-full" type="password" name="api_key" autocomplete="new-password" placeholder="برای حفظ کلید فعلی خالی بگذارید">
          </label>
          <label class="text-[10px] text-[var(--text-soft)]">Base URL
            <input class="input-pro mt-1 w-full ltr text-left" dir="ltr" name="base_url" value="{{ $baseUrl }}" placeholder="اختیاری">
          </label>
        </div>
        @if(in_array($provider, ['fal', 'replicate'], true))
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <label class="text-[10px] text-[var(--text-soft)]">Webhook Secret برای Replicate
              <input class="input-pro mt-1 w-full" type="password" name="webhook_secret" autocomplete="new-password" placeholder="فقط برای Replicate">
            </label>
            <label class="text-[10px] text-[var(--text-soft)]">زمان انتظار (ثانیه)
              <input class="input-pro mt-1 w-full ltr text-left" dir="ltr" type="number" min="5" max="3600" name="timeout" value="{{ $setting?->timeout ?: config("services.{$provider}.timeout", 180) }}">
            </label>
          </div>
        @else
          <input type="hidden" name="timeout" value="{{ $setting?->timeout ?: config("services.{$provider}.timeout", 120) }}">
        @endif
        <div class="provider-limit-panel">
          <div class="provider-limit-head">
            <label class="inline-flex items-center gap-2 text-[10px] font-bold text-[var(--text-main)] cursor-pointer">
              <input type="checkbox" name="usage_limit_enabled" value="1" @checked($limit['enabled'])>
              سقف مصرف داخلی فعال باشد
            </label>
            <span class="provider-limit-live">این بازه: {{ number_format($limit['request_count']) }} درخواست · ${{ number_format((float) $limit['spent_usd'], 4) }} · {{ number_format($limit['active_count']) }} در حال اجرا</span>
          </div>
          <div class="provider-limit-fields">
            <label>بازه زمانی (دقیقه)
              <input class="input-pro" type="number" name="usage_limit_window_minutes" min="1" max="10080" value="{{ $limit['window_minutes'] }}">
            </label>
            <label>حداکثر درخواست در بازه
              <input class="input-pro" type="number" name="usage_limit_max_requests" min="0" max="100000" value="{{ $limit['max_requests'] }}">
            </label>
            <label>سقف هزینه تقریبی (دلار)
              <input class="input-pro ltr text-left" dir="ltr" type="number" name="usage_limit_max_cost_usd" min="0" max="100000" step="0.0001" value="{{ $limit['max_cost_usd'] > 0 ? $limit['max_cost_usd'] : '' }}" placeholder="۰ = بدون سقف">
            </label>
            <label>حداکثر درخواست هم‌زمان
              <input class="input-pro" type="number" name="usage_limit_max_concurrent" min="0" max="1000" value="{{ $limit['max_concurrent'] }}">
            </label>
            <label>حداکثر خروجی هر درخواست
              <input class="input-pro" type="number" name="usage_limit_max_outputs" min="1" max="10" value="{{ $limit['max_outputs'] }}">
            </label>
          </div>
          <div class="provider-limit-help">عدد صفر یعنی بدون سقف. برای اولین تست کم‌هزینه، فعال‌سازی سقف با «۱ درخواست، ۶۰ دقیقه، هم‌زمانی ۱ و خروجی ۱» پیشنهاد می‌شود.</div>
        </div>
        <input type="hidden" name="max_retries" value="{{ $setting?->max_retries ?? config("services.{$provider}.max_retries", 2) }}">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <label class="inline-flex items-center gap-2 text-[10px] text-[var(--text-soft)] cursor-pointer">
            <input type="checkbox" name="webhook_enabled" value="1" @checked($setting?->webhook_enabled ?? true)> وب‌هوک فعال باشد
          </label>
          <div class="flex items-center gap-2">
            <button class="btn-pro btn-pro-primary" type="submit">ذخیره امن</button>
          </div>
        </div>
      </form>
      <form method="POST" action="{{ route('admin.ai-models.test-provider') }}" class="flex justify-end mt-2">
        @csrf
        <input type="hidden" name="provider" value="{{ $provider }}">
        <button class="btn-pro btn-pro-ghost" type="submit">بررسی اتصال</button>
      </form>
    </section>
  @endforeach

  <a href="{{ route('admin.ai-models.providers.create') }}" class="provider-add-card">
    <span class="provider-add-icon"><i class="fa-solid fa-plus"></i></span>
    <strong>افزودن پروایدر</strong>
    <small>اتصال یک سرویس جدید و ثبت امن کلید آن</small>
  </a>
</div>
