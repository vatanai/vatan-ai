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
        <input type="hidden" name="max_retries" value="{{ $setting?->max_retries ?? config("services.{$provider}.max_retries", 2) }}">
        <div class="flex items-center justify-between gap-3 flex-wrap">
          <label class="inline-flex items-center gap-2 text-[10px] text-[var(--text-soft)] cursor-pointer">
            <input type="checkbox" name="webhook_enabled" value="1" @checked($setting?->webhook_enabled ?? true)> وب‌هوک فعال باشد
          </label>
          <div class="flex items-center gap-2">
            <button class="btn-pro btn-pro-primary" type="submit">ذخیره امن</button>
            <button class="btn-pro btn-pro-ghost" type="submit" formaction="{{ route('admin.ai-models.test-provider') }}" formmethod="POST">بررسی اتصال</button>
          </div>
        </div>
      </form>
    </section>
  @endforeach

  <a href="{{ route('admin.ai-models.providers.create') }}" class="provider-add-card">
    <span class="provider-add-icon"><i class="fa-solid fa-plus"></i></span>
    <strong>افزودن پروایدر</strong>
    <small>اتصال یک سرویس جدید و ثبت امن کلید آن</small>
  </a>
</div>
