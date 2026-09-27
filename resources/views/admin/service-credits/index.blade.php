@extends('layouts.admin')
@section('title', 'مرکز اعتبار سرویس‌ها — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/service-credits.css') }}?v={{ filemtime(public_path('admin/css/service-credits.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto credit-page credit-center" id="content">
    @php
      $providerIcons = [
        'openrouter' => 'fa-route', 'fal' => 'fa-wand-magic-sparkles',
        'replicate' => 'fa-cubes', 'cloudiva' => 'fa-cloud', 'melipayamak' => 'fa-comment-sms',
        'netafraz' => 'fa-server',
      ];
      $connectedCount = $accounts->where('is_online', true)->count();
      $liveBalanceCount = $accounts->where('balance_is_live', true)->count();
      $accountCount = $accounts->count();
      $connectionPercent = $accountCount > 0 ? min(100, ($connectedCount / $accountCount) * 100) : 0;
      $monthUsageToman = (float) ($totals['month_irr'] ?? 0) / 10;
      $availableToman = (float) ($totals['balance_toman'] ?? 0);
      $usagePercent = ($availableToman + $monthUsageToman) > 0 ? min(100, ($monthUsageToman / ($availableToman + $monthUsageToman)) * 100) : 0;
      $chartAccounts = $accounts->sortByDesc(fn ($account) => (float) ($account->balance_toman ?? 0))->values();
      $maxChartBalance = max(1, (float) ($chartAccounts->max('balance_toman') ?? 0));
    @endphp

    <section class="credit-center-hero">
      <div class="credit-center-hero-copy">
        <span class="credit-center-badge"><span class="credit-live-pulse"></span> پایش زنده اعتبار</span>
        <h1>مرکز اعتبار سرویس‌ها</h1>
        <p>موجودی، مصرف، سلامت اتصال و رخدادهای ساخت همهٔ پرووایدرها در یک نمای یکپارچه.</p>
        <div class="credit-center-meta">
          <span><i class="fa-solid fa-plug-circle-check"></i> {{ number_format($connectedCount) }} اتصال فعال از {{ number_format($accountCount) }}</span>
          <span><i class="fa-solid fa-satellite-dish"></i> {{ number_format($liveBalanceCount) }} موجودی مستقیم</span>
          <span><i class="fa-solid fa-clock"></i> دریافت اطلاعات فقط هنگام ورود به این صفحه</span>
        </div>
      </div>
      <div class="credit-center-actions">
        <form method="POST" action="{{ route('admin.service-credits.refresh') }}">@csrf
          <button class="credit-btn primary" type="submit"><i class="fa-solid fa-rotate"></i> تازه‌سازی آنلاین</button>
        </form>
        <a class="credit-btn" href="{{ route('admin.service-credits.transactions') }}"><i class="fa-solid fa-table-list"></i> گزارش کامل ساخت‌ها</a>
        <button class="credit-btn" type="button" data-open-modal="account-modal"><i class="fa-solid fa-plus"></i> اکانت جدید</button>
        <button class="credit-btn" type="button" data-open-modal="transaction-modal"><i class="fa-solid fa-receipt"></i> ثبت تراکنش</button>
      </div>
    </section>

    @if(session('success'))<div class="credit-alert success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="credit-alert error"><i class="fa-solid fa-triangle-exclamation"></i>{{ $errors->first() }}</div>@endif

    @if($alerts->isNotEmpty())
      <section class="credit-center-alerts" aria-label="هشدارهای اعتبار سرویس‌ها">
        <div class="credit-center-alert-heading"><span><i class="fa-solid fa-bell"></i> نیازمند توجه</span><strong>{{ number_format($alerts->count()) }} هشدار فعال</strong></div>
        <div class="credit-center-alert-list">
          @foreach($alerts as $alert)
            <a href="#credit-account-{{ $alert->id }}" class="credit-center-alert {{ $alert->is_critical ? 'critical' : 'low' }}">
              <i class="fa-solid {{ $alert->is_critical ? 'fa-triangle-exclamation' : 'fa-circle-exclamation' }}"></i>
              <span><strong>{{ $alert->name }}</strong><small>{{ $alert->alert_label }} · موجودی {{ $alert->currency === 'USD' ? '$'.number_format($alert->display_balance, 4) : number_format($alert->display_balance / 10).' تومان' }}</small></span>
              <i class="fa-solid fa-angle-left"></i>
            </a>
          @endforeach
        </div>
      </section>
    @endif

    <section class="credit-center-kpis" aria-label="خلاصه وضعیت اعتبار">
      <article class="credit-center-kpi primary"><span class="credit-center-kpi-icon"><i class="fa-solid fa-wallet"></i></span><div><small>موجودی کل پرووایدرها</small><strong>{{ number_format($availableToman) }} <em>تومان</em></strong><span>${{ number_format((float) ($totals['balance_usd'] ?? 0), 4) }} معادل دلاری</span></div></article>
      <article class="credit-center-kpi"><span class="credit-center-kpi-icon"><i class="fa-solid fa-chart-line"></i></span><div><small>مصرف ماه جاری</small><strong>{{ number_format($monthUsageToman) }} <em>تومان</em></strong><span>{{ number_format($usagePercent, 1) }}٪ از اعتبار این دوره</span></div></article>
      <article class="credit-center-kpi"><span class="credit-center-kpi-icon"><i class="fa-solid fa-signal"></i></span><div><small>سلامت اتصال‌ها</small><strong>{{ number_format($connectionPercent, 0) }}<em>٪</em></strong><span>{{ number_format($connectedCount) }} سرویس پاسخ‌گو</span></div></article>
      <article class="credit-center-kpi"><span class="credit-center-kpi-icon"><i class="fa-solid fa-dollar-sign"></i></span><div><small>نرخ تبدیل فعلی</small><strong>{{ ($exchange['rate'] ?? 0) > 0 ? number_format($exchange['rate'] / 10) : '—' }} <em>تومان</em></strong><span>{{ $exchange['source'] ?? 'نرخ پشتیبان' }} · {{ ($exchange['online'] ?? false) ? 'آنلاین' : 'پشتیبان' }}</span></div></article>
    </section>

    <section class="credit-center-analytics">
      <article class="credit-center-panel credit-balance-chart-panel">
        <div class="credit-center-section-head"><div><span>توزیع موجودی</span><h2>مقایسه اعتبار پرووایدرها</h2><p>مقایسه بر اساس معادل تومانی موجودی قابل استفاده.</p></div><i class="fa-solid fa-chart-simple"></i></div>
        <div class="credit-balance-chart">
          @forelse($chartAccounts as $account)
            @php
              $barPercent = max(2, min(100, ((float) ($account->balance_toman ?? 0) / $maxChartBalance) * 100));
            @endphp
            <div class="credit-balance-row">
              <div class="credit-balance-row-head"><span><i class="fa-solid {{ $providerIcons[$account->slug] ?? 'fa-wallet' }}"></i>{{ $account->name }}</span><strong>{{ number_format((float) ($account->balance_toman ?? 0)) }} تومان</strong></div>
              <div class="credit-balance-track"><span class="{{ $account->is_critical ? 'critical' : ($account->is_low ? 'low' : '') }}" style="width:{{ $barPercent }}%"></span></div>
            </div>
          @empty
            <div class="credit-empty-state"><i class="fa-solid fa-chart-bar"></i><strong>اطلاعات موجودی ثبت نشده است.</strong></div>
          @endforelse
        </div>
      </article>

      <article class="credit-center-panel credit-health-panel">
        <div class="credit-center-section-head"><div><span>سلامت لحظه‌ای</span><h2>وضعیت اتصال‌ها</h2><p>تفکیک اتصال مستقیم و موجودی دستی.</p></div><i class="fa-solid fa-heart-pulse"></i></div>
        <div class="credit-health-visual">
          <div class="credit-health-ring" style="--credit-ring:{{ $connectionPercent }}%"><span><strong>{{ number_format($connectionPercent, 0) }}٪</strong><small>پاسخ‌گو</small></span></div>
          <div class="credit-health-legend">
            <div><span class="success"></span><p><strong>{{ number_format($liveBalanceCount) }}</strong><small>موجودی زنده</small></p></div>
            <div><span class="primary"></span><p><strong>{{ number_format(max(0, $connectedCount - $liveBalanceCount)) }}</strong><small>اتصال سالم، موجودی دستی</small></p></div>
            <div><span class="muted"></span><p><strong>{{ number_format(max(0, $accountCount - $connectedCount)) }}</strong><small>دستی یا بدون اتصال</small></p></div>
          </div>
        </div>
      </article>
    </section>

    <section class="credit-center-providers">
      <div class="credit-center-title-row"><div><span class="credit-center-kicker">پرووایدرها</span><h2>موجودی و مصرف سرویس‌ها</h2><p>هر کارت، آخرین وضعیت همین بازدید و مصرف ثبت‌شده را نشان می‌دهد.</p></div><span class="credit-center-snapshot"><i class="fa-solid fa-circle-info"></i> اطلاعات کش‌شده حداکثر سه دقیقه اعتبار دارد</span></div>
      <div class="credit-center-provider-grid">
        @forelse($accounts as $account)
          @php
            $accountUsagePercent = ($account->display_balance + $account->month_usage) > 0 ? min(100, ($account->month_usage / ($account->display_balance + $account->month_usage)) * 100) : 0;
            $providerStat = $providerStats->first(function (array $stat) use ($account): bool {
              $key = strtolower((string) ($stat['key'] ?? ''));
              return $key !== '' && (str_contains($key, strtolower($account->slug)) || str_contains(strtolower($account->slug), $key));
            });
          @endphp
          <article class="credit-center-provider {{ $account->is_critical ? 'critical' : ($account->is_low ? 'low' : '') }}" id="credit-account-{{ $account->id }}">
            <header><span class="credit-center-provider-icon"><i class="fa-solid {{ $providerIcons[$account->slug] ?? 'fa-wallet' }}"></i></span><div><h3>{{ $account->name }}</h3><span class="credit-center-provider-status {{ $account->is_online ? 'online' : '' }}"><i></i>{{ $account->health_label }}</span></div><button type="button" data-open-modal="edit-{{ $account->id }}" aria-label="تنظیمات {{ $account->name }}"><i class="fa-solid fa-sliders"></i></button></header>
            <div class="credit-center-provider-balance"><small>موجودی قابل استفاده</small><strong>{{ $account->currency === 'USD' ? '$'.number_format($account->display_balance, 4) : number_format($account->display_balance / 10).' تومان' }}</strong><span>{{ number_format((float) $account->balance_toman) }} تومان · {{ $account->balance_is_live ? 'دریافت مستقیم' : 'موجودی دستی' }}</span></div>
            <div class="credit-center-provider-metrics"><div><small>{{ $account->usage_is_estimate ? 'برآورد امروز' : 'مصرف امروز' }}</small><strong>{{ number_format($account->today_usage_irr / 10) }} تومان</strong></div><div><small>{{ $account->usage_is_estimate ? 'برآورد ماه' : 'مصرف ماه' }}</small><strong>{{ number_format($account->month_usage_irr / 10) }} تومان</strong></div></div>
            <div class="credit-center-provider-progress"><span style="width:{{ $accountUsagePercent }}%"></span></div>
            <footer><span>{{ number_format($accountUsagePercent, 1) }}٪ مصرف این دوره</span><a href="{{ route('admin.service-credits.transactions', ['provider' => $account->slug]) }}">{{ $providerStat ? number_format($providerStat['count']).' رخداد' : 'مشاهده گزارش' }} <i class="fa-solid fa-angle-left"></i></a></footer>
            @if($account->alert_level)<p class="credit-center-provider-warning"><i class="fa-solid fa-triangle-exclamation"></i>{{ $account->alert_label }}؛ موجودی از آستانه تنظیم‌شده کمتر است.</p>@endif
            @if($account->sync_error)<p class="credit-center-provider-note"><i class="fa-solid fa-circle-info"></i>{{ $account->sync_error }}</p>@endif
          </article>

          <div class="credit-modal" id="edit-{{ $account->id }}"><div class="credit-modal-box">
            <div class="credit-panel-title">تنظیمات {{ $account->name }}</div>
            <form method="POST" action="{{ route('admin.service-credits.accounts.update', $account) }}">@csrf @method('PUT')
              <div class="credit-form-grid">
                <div class="credit-field"><label>موجودی دستی ({{ $account->currency }})</label><input type="number" step="0.000001" name="manual_balance" value="{{ $account->manual_balance }}" required></div>
                <div class="credit-field"><label>آستانه هشدار</label><input type="number" step="0.000001" name="low_balance_threshold" value="{{ $account->low_balance_threshold }}"></div>
                <div class="credit-field"><label>آستانه بحرانی</label><input type="number" step="0.000001" name="critical_balance_threshold" value="{{ $account->critical_balance_threshold }}"></div>
                <div class="credit-field"><label>یادداشت</label><input name="note" value="{{ $account->note }}"></div>
              </div>
              <label class="credit-check"><input type="checkbox" name="show_on_dashboard" value="1" {{ $account->show_on_dashboard ? 'checked' : '' }}> نمایش در گزارش‌های مدیریتی</label>
              <label class="credit-check"><input type="checkbox" name="alerts_enabled" value="1" {{ $account->alerts_enabled ? 'checked' : '' }}> فعال‌سازی هشدار کمبود موجودی</label>
              <div class="credit-actions"><button class="credit-btn primary">ذخیره</button><button class="credit-btn" type="button" data-close-modal>انصراف</button></div>
            </form>
          </div></div>
        @empty
          <div class="credit-center-panel credit-empty-state"><i class="fa-solid fa-wallet"></i><strong>هنوز اکانت سرویسی ثبت نشده است.</strong></div>
        @endforelse
      </div>
    </section>

    <section class="credit-center-panel credit-center-activity">
      <div class="credit-center-title-row"><div><span class="credit-center-kicker">عملیات و هزینه</span><h2>رخدادهای اخیر ساخت</h2><p>خلاصه‌ای از آخرین اجراها و هزینه ثبت‌شده برای هر پرووایدر.</p></div><div class="credit-activity-summary"><span>{{ number_format($activitySummary['count'] ?? 0) }} رخداد</span><span class="success">{{ number_format($activitySummary['success'] ?? 0) }} موفق</span><span class="danger">{{ number_format($activitySummary['failed'] ?? 0) }} ناموفق</span></div></div>
      <div class="credit-center-activity-grid">
        <div class="credit-center-timeline">
          @forelse($timeline as $event)
            @php($eventClass = $event['is_success'] ? 'success' : (in_array($event['status_key'], ['failed', 'usage'], true) ? 'danger' : 'warning'))
            <a class="credit-center-event {{ $eventClass }}" href="{{ $event['detail_url'] ?: route('admin.service-credits.transactions') }}">
              <span class="credit-center-event-icon"><i class="fa-solid {{ $event['source_key'] === 'user' ? 'fa-wand-magic-sparkles' : ($event['source_key'] === 'lab' ? 'fa-flask' : 'fa-wallet') }}"></i></span>
              <span class="credit-center-event-copy"><strong>{{ $event['provider'] }} <em>{{ $event['status_label'] }}</em></strong><small>{{ $event['product_name'] !== '—' ? $event['product_name'] : $event['source_label'] }} · {{ $event['date_jalali'] }}</small></span>
              <span class="credit-center-event-cost">@if($event['amount_usd'] !== null)<strong>${{ number_format($event['amount_usd'], 6) }}</strong><small>{{ number_format($event['amount_toman']) }} تومان</small>@else<strong>—</strong><small>هزینه ثبت نشده</small>@endif</span>
              <i class="fa-solid fa-angle-left"></i>
            </a>
          @empty
            <div class="credit-empty-state"><i class="fa-solid fa-timeline"></i><strong>هنوز رخدادی ثبت نشده است.</strong><span>پس از اولین مصرف، اطلاعات این بخش نمایش داده می‌شود.</span></div>
          @endforelse
        </div>
        <aside class="credit-center-ledger"><div class="credit-center-ledger-head"><strong>هزینه به تفکیک پرووایدر</strong><span>تمام رخدادهای ثبت‌شده</span></div>@forelse($providerStats as $provider)<a href="{{ route('admin.service-credits.transactions', ['provider' => $provider['key']]) }}"><span><strong>{{ $provider['label'] }}</strong><small>{{ number_format($provider['count']) }} رخداد · آخرین {{ $provider['latest_at'] }}</small></span><b>${{ number_format($provider['usd'], 4) }}<small>{{ number_format($provider['toman']) }} تومان</small></b></a>@empty<span class="credit-muted">داده‌ای ثبت نشده است.</span>@endforelse</aside>
      </div>
      <div class="credit-center-activity-footer"><a class="credit-btn primary" href="{{ route('admin.service-credits.transactions') }}"><i class="fa-solid fa-arrow-left"></i> ورود به گزارش کامل ساخت و تراکنش‌ها</a></div>
    </section>
  </div>
</main>

<div class="credit-modal" id="transaction-modal"><div class="credit-modal-box">
  <div class="credit-panel-title">ثبت شارژ یا مصرف</div>
  <form method="POST" action="{{ route('admin.service-credits.transactions.store') }}">@csrf
    <div class="credit-form-grid">
      <div class="credit-field"><label>سرویس</label><select name="service_credit_account_id" required>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }} ({{ $account->currency }})</option>@endforeach</select></div>
      <div class="credit-field"><label>نوع تراکنش</label><select name="type"><option value="usage">مصرف</option><option value="charge">شارژ</option><option value="refund">بازگشت وجه</option><option value="adjustment">اصلاح افزایشی</option></select></div>
      <div class="credit-field"><label>مبلغ</label><input type="number" step="0.000001" min="0.000001" name="amount" required></div>
      <div class="credit-field"><label>زمان</label><input type="datetime-local" name="occurred_at" value="{{ now()->format('Y-m-d\\TH:i') }}" required></div>
      <div class="credit-field"><label>شماره مرجع</label><input name="reference"></div>
      <div class="credit-field" style="grid-column:span 3"><label>توضیح</label><input name="note"></div>
    </div>
    <div class="credit-actions"><button class="credit-btn primary">ثبت تراکنش</button><button class="credit-btn" type="button" data-close-modal>انصراف</button></div>
  </form>
</div></div>

<div class="credit-modal" id="account-modal"><div class="credit-modal-box">
  <div class="credit-panel-title">افزودن اکانت سرویس</div>
  <form method="POST" action="{{ route('admin.service-credits.accounts.store') }}">@csrf
    <div class="credit-form-grid">
      <div class="credit-field"><label>نام سرویس</label><input name="name" required></div><div class="credit-field"><label>شناسه انگلیسی</label><input name="slug" required></div>
      <div class="credit-field"><label>واحد پول</label><select name="currency"><option value="USD">دلار</option><option value="IRR">ریال</option></select></div>
      <div class="credit-field"><label>موجودی اولیه</label><input type="number" step="0.000001" name="manual_balance" value="0" required></div>
      <div class="credit-field"><label>آستانه هشدار</label><input type="number" step="0.000001" name="low_balance_threshold" value="0"></div><div class="credit-field"><label>آستانه بحرانی</label><input type="number" step="0.000001" name="critical_balance_threshold" value="0"></div><div class="credit-field" style="grid-column:span 2"><label>یادداشت</label><input name="note"></div>
    </div>
    <label class="credit-check"><input type="checkbox" name="show_on_dashboard" value="1" checked> نمایش در گزارش‌های مدیریتی</label>
    <label class="credit-check"><input type="checkbox" name="alerts_enabled" value="1" checked> فعال‌سازی هشدار کمبود موجودی</label>
    <div class="credit-actions"><button class="credit-btn primary">افزودن اکانت</button><button class="credit-btn" type="button" data-close-modal>انصراف</button></div>
  </form>
</div></div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('[data-open-modal]').forEach(function(button){button.addEventListener('click',function(){document.getElementById(button.dataset.openModal)?.classList.add('open')})});
document.querySelectorAll('[data-close-modal]').forEach(function(button){button.addEventListener('click',function(){button.closest('.credit-modal')?.classList.remove('open')})});
document.querySelectorAll('.credit-modal').forEach(function(modal){modal.addEventListener('click',function(event){if(event.target===modal)modal.classList.remove('open')})});
</script>
@endsection
