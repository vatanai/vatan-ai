@extends('layouts.admin')
@section('title', 'مدیریت اعتبار سرویس‌ها — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/service-credits.css') }}?v={{ filemtime(public_path('admin/css/service-credits.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto credit-page" id="content">
    @php $mode = $mode ?? 'providers'; @endphp
    <div class="credit-head">
      <div>
        <div class="credit-eyebrow">زیرساخت مالی و مصرف هوش مصنوعی</div>
        <h1>{{ $mode === 'transactions' ? 'بررسی تراکنش‌ها' : 'میزان اعتبار پرووایدرها' }}</h1>
        <div class="credit-subtitle">{{ $mode === 'transactions' ? 'جست‌وجو، فیلتر و بررسی کامل تمام رخدادهای مصرف و هزینه' : 'نمایش کامل موجودی، سلامت اتصال و هشدارهای تمام پرووایدرها' }}</div>
      </div>
      <div class="credit-head-actions">
        <form method="POST" action="{{ route('admin.service-credits.refresh') }}">@csrf
          <button class="credit-btn" type="submit"><i class="fa-solid fa-rotate"></i> تازه‌سازی آنلاین</button>
        </form>
        @if($mode === 'providers')<button class="credit-btn" type="button" data-open-modal="account-modal"><i class="fa-solid fa-plus"></i> اکانت جدید</button>@endif
        <button class="credit-btn primary" type="button" data-open-modal="transaction-modal"><i class="fa-solid fa-receipt"></i> ثبت تراکنش</button>
      </div>
    </div>

    <nav class="credit-subnav" aria-label="بخش‌های اعتبار سرویس‌ها">
      <a class="{{ $mode === 'providers' ? 'active' : '' }}" href="{{ route('admin.service-credits.providers') }}"><i class="fa-solid fa-chart-pie"></i><span><strong>میزان اعتبار پرووایدرها</strong><small>موجودی و سلامت اتصال</small></span></a>
      <a class="{{ $mode === 'transactions' ? 'active' : '' }}" href="{{ route('admin.service-credits.transactions') }}"><i class="fa-solid fa-receipt"></i><span><strong>بررسی تراکنش‌ها</strong><small>گزارش کامل و فیلترپذیر</small></span></a>
    </nav>

    @if(session('success'))<div class="credit-alert success"><i class="fa-solid fa-circle-check"></i>{{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="credit-alert error"><i class="fa-solid fa-triangle-exclamation"></i>{{ $errors->first() }}</div>@endif

    @php
      $creditBySlug = $accounts->keyBy('slug');
      $providerCards = [
        ['slug' => 'openrouter', 'name' => 'OpenRouter', 'icon' => 'fa-route'],
        ['slug' => 'fal', 'name' => 'Fal.ai', 'icon' => 'fa-wand-magic-sparkles'],
        ['slug' => 'replicate', 'name' => 'Replicate', 'icon' => 'fa-cubes'],
        ['slug' => 'cloudiva', 'name' => 'Cloudiva', 'icon' => 'fa-cloud'],
        ['slug' => 'melipayamak', 'name' => 'پنل پیامک', 'icon' => 'fa-comment-sms'],
        ['slug' => 'netafraz', 'name' => 'Netafraz', 'icon' => 'fa-server'],
      ];
    @endphp
    @if($alerts->isNotEmpty())
      <section class="credit-alert-rail" aria-label="هشدارهای اعتبار سرویس‌ها">
        <div class="credit-alert-rail-head"><span class="credit-section-kicker"><i class="fa-solid fa-bell"></i> مرکز هشدار</span><strong>{{ number_format($alerts->count()) }} مورد نیازمند توجه</strong></div>
        <div class="credit-alert-list">
          @foreach($alerts as $alert)
            <a class="credit-alert-item {{ $alert->is_critical ? 'critical' : 'low' }}" href="#credit-account-{{ $alert->id }}">
              <span class="credit-alert-icon"><i class="fa-solid {{ $alert->is_critical ? 'fa-triangle-exclamation' : 'fa-circle-exclamation' }}"></i></span>
              <span><strong>{{ $alert->name }} · {{ $alert->alert_label }}</strong><small>موجودی فعلی {{ $alert->currency === 'USD' ? '$'.number_format($alert->display_balance, 4) : number_format($alert->display_balance / 10).' تومان' }}</small></span>
              <i class="fa-solid fa-chevron-left"></i>
            </a>
          @endforeach
        </div>
      </section>
    @else
      <div class="credit-health-banner"><span class="credit-health-icon"><i class="fa-solid fa-shield-check"></i></span><div><strong>همهٔ آستانه‌های اعتبار در محدودهٔ امن هستند</strong><span>هشدارها بر اساس موجودی ثبت‌شده و آستانه‌های هر سرویس محاسبه می‌شوند.</span></div></div>
    @endif

    <div class="credit-overview-bar">
      <div class="credit-rate-card"><span class="credit-rate-icon"><i class="fa-solid fa-chart-line"></i></span><div><span class="credit-summary-label">نرخ تبدیل فعلی</span><strong>{{ $exchange['rate'] > 0 ? number_format($exchange['rate'] / 10) : '—' }} <small>تومان / دلار</small></strong><em>{{ $exchange['source'] }} · {{ $exchange['online'] ? 'آنلاین' : 'پشتیبان' }}</em></div></div>
      <div class="credit-overview-stats"><div><span>موجودی دلاری کل</span><strong>${{ number_format($totals['balance_usd'], 4) }}</strong></div><div><span>معادل تومانی کل</span><strong>{{ number_format($totals['balance_toman']) }} تومان</strong></div><div><span>هزینهٔ ماه جاری</span><strong>{{ number_format($totals['month_irr'] / 10) }} تومان</strong></div></div>
    </div>

    <section class="credit-provider-section">
      <div class="credit-section-head"><div><span class="credit-section-kicker">نمای لحظه‌ای اتصال‌ها</span><h2>سلامت سرویس‌ها</h2><p>برای دیدن ریز رخدادها و هزینه‌های هر ارائه‌دهنده، کارت آن را انتخاب کنید.</p></div><span class="credit-refresh-note"><i class="fa-solid fa-clock-rotate-left"></i> آخرین بررسی هنگام باز شدن صفحه</span></div>
      <div class="credit-provider-grid">
      @foreach($providerCards as $providerCard)
        @php($creditAccount = $creditBySlug->get($providerCard['slug']))
        @php($providerStat = $providerStats->firstWhere('key', $providerCard['slug']))
        <a class="credit-provider-card {{ $creditAccount?->is_critical ? 'is-critical' : ($creditAccount?->is_low ? 'is-low' : '') }}" href="{{ route('admin.service-credits.providers', ['provider' => $providerCard['slug']]) }}">
          <div class="credit-provider-card-head"><span class="credit-provider-mark"><i class="fa-solid {{ $providerCard['icon'] }}"></i></span><div><strong>{{ $providerCard['name'] }}</strong><small class="{{ $creditAccount?->is_online ? 'is-online' : '' }}"><span></span>{{ $creditAccount?->health_label ?? 'حساب ساخته نشده' }}</small></div><i class="fa-solid fa-arrow-up-left-from-circle credit-provider-open"></i></div>
          <div class="credit-provider-balance">{{ $creditAccount?->balance_usd !== null ? '$'.number_format((float) $creditAccount->balance_usd, 4) : '—' }}</div>
          <div class="credit-provider-toman">{{ $creditAccount?->balance_toman !== null ? number_format((float) $creditAccount->balance_toman).' تومان' : 'موجودی دستی ثبت نشده' }}</div>
          <div class="credit-provider-footer"><span>{{ $creditAccount?->status_label ?? 'حساب ساخته نشده' }}</span><b>{{ $providerStat ? number_format((int) $providerStat['count']) : '۰' }} رخداد</b></div>
        </a>
      @endforeach
      </div>
    </section>

    @if($mode === 'transactions')
    <section class="credit-timeline-panel credit-panel">
      <div class="credit-section-head"><div><span class="credit-section-kicker">ردیابی هزینه و عملیات</span><h2>تایم‌لاین رخدادهای اخیر</h2><p>تمام اجراها، هزینه‌ها و تغییرات موجودی با تبدیل هم‌زمان دلار و تومان.</p></div><a class="credit-btn" href="#credit-report"><i class="fa-solid fa-list"></i> مشاهدهٔ گزارش کامل</a></div>
      <div class="credit-timeline-layout">
        <div class="credit-timeline">
          @forelse($timeline as $event)
            @php($eventClass = $event['is_success'] ? 'success' : (in_array($event['status_key'], ['failed', 'usage'], true) ? 'danger' : 'warning'))
            <a class="credit-timeline-item {{ $eventClass }}" href="{{ $event['detail_url'] ?: '#credit-report' }}">
              <span class="credit-timeline-marker"><i class="fa-solid {{ $event['source_key'] === 'user' ? 'fa-user' : ($event['source_key'] === 'lab' ? 'fa-flask' : 'fa-wallet') }}"></i></span>
              <span class="credit-timeline-content"><strong>{{ $event['provider'] }} <small>· {{ $event['status_label'] }}</small></strong><span>{{ $event['source_label'] }} @if($event['product_name'] !== '—') · {{ $event['product_name'] }} @endif</span><time>{{ $event['date_jalali'] }} · {{ $event['date_gregorian'] }}</time></span>
              <span class="credit-timeline-cost">@if($event['amount_usd'] !== null)<strong>${{ number_format($event['amount_usd'], 6) }}</strong><small>{{ number_format($event['amount_toman']) }} تومان</small>@else<span>—</span>@endif</span>
            </a>
          @empty
            <div class="credit-empty-state"><i class="fa-solid fa-timeline"></i><strong>هنوز رخدادی ثبت نشده است.</strong><span>با اولین مصرف یا ثبت تراکنش، تایم‌لاین اینجا پر می‌شود.</span></div>
          @endforelse
        </div>
        <aside class="credit-provider-ledger"><div class="credit-ledger-title"><strong>دفتر هر ارائه‌دهنده</strong><span>بر اساس فیلتر فعلی</span></div>@forelse($providerStats as $provider)<a href="{{ route('admin.service-credits.transactions', ['provider' => $provider['key']]) }}" class="credit-ledger-row"><span><strong>{{ $provider['label'] }}</strong><small>{{ number_format($provider['count']) }} رخداد · آخرین {{ $provider['latest_at'] }}</small></span><b>${{ number_format($provider['usd'], 4) }}<small>{{ number_format($provider['toman']) }} تومان</small></b><i class="fa-solid fa-chevron-left"></i></a>@empty<span class="credit-muted">داده‌ای برای ارائه‌دهنده‌ها وجود ندارد.</span>@endforelse</aside>
      </div>
    </section>
    @endif

    @if($mode === 'providers')
    <div class="credit-accounts">
      @forelse($accounts as $account)
        <article class="credit-card {{ $account->is_critical ? 'critical' : ($account->is_low ? 'low' : '') }}" id="credit-account-{{ $account->id }}">
          <div class="credit-card-head">
            <div class="credit-service">
              <div class="credit-logo"><i class="fa-solid {{ ['openrouter' => 'fa-route', 'liara' => 'fa-cloud-arrow-up', 'fal' => 'fa-wand-magic-sparkles', 'replicate' => 'fa-cubes', 'cloudiva' => 'fa-cloud', 'melipayamak' => 'fa-comment-sms', 'netafraz' => 'fa-server'][$account->slug] ?? 'fa-wallet' }}"></i></div>
              <div><div class="credit-name">{{ $account->name }}</div><div class="credit-status {{ $account->is_online ? 'online' : '' }}"><span class="credit-dot"></span>{{ $account->health_label }}</div></div>
            </div>
            <button class="credit-btn" type="button" data-open-modal="edit-{{ $account->id }}"><i class="fa-solid fa-sliders"></i></button>
          </div>
          <div class="credit-balance-label">موجودی قابل استفاده</div>
          <div class="credit-balance">{{ $account->currency === 'USD' ? '$'.number_format($account->display_balance, 2) : number_format($account->display_balance / 10).' تومان' }}</div>
          <div class="credit-balance-irr">{{ $account->balance_usd !== null ? '$'.number_format((float) $account->balance_usd, 4) : '—' }} · {{ number_format((float) $account->balance_toman) }} تومان با نرخ روز</div>
          <div class="credit-metrics">
            <div class="credit-metric"><span>{{ $account->usage_is_estimate ? 'برآورد امروز' : 'مصرف امروز' }}</span><strong>{{ $account->currency === 'USD' ? '$'.number_format($account->today_usage, 4) : number_format($account->today_usage / 10).' ت' }}</strong></div>
            <div class="credit-metric"><span>{{ $account->usage_is_estimate ? 'برآورد ماهانه' : 'مصرف ماه' }}</span><strong>{{ $account->currency === 'USD' ? '$'.number_format($account->month_usage, 4) : number_format($account->month_usage / 10).' ت' }}</strong></div>
            <div class="credit-metric"><span>معادل امروز</span><strong>{{ number_format($account->today_usage_irr / 10) }} ت</strong></div>
          </div>
          @php($usagePercent = ($account->display_balance + $account->month_usage) > 0 ? min(100, ($account->month_usage / ($account->display_balance + $account->month_usage)) * 100) : 0)
          <div class="credit-progress"><span style="width:{{ $usagePercent }}%"></span></div>
          <div class="credit-summary-meta">{{ number_format($usagePercent, 1) }}٪ از اعتبار در دسترس این دوره مصرف شده</div>
          @if($account->usage_is_estimate)<div class="credit-summary-meta">هزینه جاری ساعتی: {{ number_format($account->hourly_usage / 10) }} تومان — محاسبه آنلاین براساس منابع فعال Liara</div>@endif
          @if($account->usage_source)<div class="credit-summary-meta">منبع مصرف: {{ $account->usage_source }}</div>@endif
          @if($account->alert_level)<div class="credit-warning {{ $account->is_critical ? 'critical' : '' }}"><i class="fa-solid fa-triangle-exclamation"></i> {{ $account->alert_label }} · موجودی از آستانهٔ تنظیم‌شده کمتر است</div>@endif
          @if($account->sync_error)<div class="credit-warning">{{ $account->sync_error }}</div>@endif
        </article>

        <div class="credit-modal" id="edit-{{ $account->id }}"><div class="credit-modal-box">
          <div class="credit-panel-title">تنظیمات {{ $account->name }}</div>
          <form method="POST" action="{{ route('admin.service-credits.accounts.update', $account) }}">@csrf @method('PUT')
            <div class="credit-form-grid">
              <div class="credit-field"><label>موجودی دستی ({{ $account->currency }})</label><input type="number" step="0.000001" name="manual_balance" value="{{ $account->manual_balance }}" required></div>
              <div class="credit-field"><label>آستانهٔ هشدار</label><input type="number" step="0.000001" name="low_balance_threshold" value="{{ $account->low_balance_threshold }}"></div>
              <div class="credit-field"><label>آستانهٔ بحرانی</label><input type="number" step="0.000001" name="critical_balance_threshold" value="{{ $account->critical_balance_threshold }}"></div>
              <div class="credit-field" style="grid-column:span 2"><label>یادداشت</label><input name="note" value="{{ $account->note }}"></div>
            </div>
            <label class="credit-check"><input type="checkbox" name="show_on_dashboard" value="1" {{ $account->show_on_dashboard ? 'checked' : '' }}> نمایش کارت در مرکز فرماندهی</label>
            <label class="credit-check"><input type="checkbox" name="alerts_enabled" value="1" {{ $account->alerts_enabled ? 'checked' : '' }}> فعال‌سازی هشدار کمبود موجودی</label>
            <div class="credit-actions"><button class="credit-btn primary">ذخیره</button><button class="credit-btn" type="button" data-close-modal>انصراف</button></div>
          </form>
        </div></div>
      @empty
        <div class="credit-panel">پس از اجرای migration، اکانت‌های پیش‌فرض OpenRouter و Liara ساخته می‌شوند.</div>
      @endforelse
    </div>
    @endif

    @if($mode === 'transactions')
    <section class="credit-panel credit-transactions-panel" id="credit-report">
      <div class="credit-panel-heading">
        <div>
          <div class="credit-panel-title">گزارش کامل مصرف و تراکنش‌ها</div>
          <div class="credit-panel-caption">اجرای واقعی کاربر، آزمایشگاه، سفارش و تغییرات موجودی سرویس‌ها در یک گزارش قابل پیگیری</div>
        </div>
        <span class="credit-live-label"><span class="credit-dot"></span> داده زنده</span>
      </div>

      <div class="credit-report-summary">
        <div class="credit-report-stat"><span>کل رخدادها</span><strong>{{ number_format($summary['count']) }}</strong></div>
        <div class="credit-report-stat success"><span>موفق</span><strong>{{ number_format($summary['success']) }}</strong></div>
        <div class="credit-report-stat danger"><span>ناموفق</span><strong>{{ number_format($summary['failed']) }}</strong></div>
        <div class="credit-report-stat"><span>هزینه دلاری</span><strong>${{ number_format($summary['usd'], 6) }}</strong></div>
        <div class="credit-report-stat"><span>هزینه تومانی</span><strong>{{ number_format($summary['toman']) }} تومان</strong></div>
      </div>

      <form method="GET" action="{{ route('admin.service-credits.transactions') }}" class="credit-report-filters">
        <div class="credit-field credit-filter-search"><label for="credit-report-q">جست‌وجو</label><div class="credit-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input id="credit-report-q" name="q" value="{{ request('q') }}" placeholder="کاربر، محصول، سفارش، مدل یا شناسه درخواست"></div></div>
        <div class="credit-field"><label>منبع</label><select name="source"><option value="">همه منابع</option>@foreach($sourceOptions as $key => $label)<option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="credit-field"><label>پرووایدر</label><select name="provider"><option value="">همه پرووایدرها</option>@foreach($providers as $provider)<option value="{{ $provider['key'] }}" @selected(request('provider') === $provider['key'])>{{ $provider['label'] }}</option>@endforeach</select></div>
        <div class="credit-field"><label>وضعیت</label><select name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusOptions as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="credit-field"><label>از تاریخ</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="credit-field"><label>تا تاریخ</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="credit-filter-actions"><button class="credit-btn primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button><a class="credit-btn" href="{{ route('admin.service-credits.transactions') }}">پاک‌کردن</a></div>
      </form>

      <div class="credit-table-wrap"><table class="credit-table credit-report-table"><thead><tr>
        <th>زمان / منبع</th><th>اجراکننده</th><th>محصول</th><th>پرووایدر و مدل</th><th>وضعیت</th><th>عکس ورودی</th><th>خروجی</th><th>هزینه</th><th>جزئیات</th>
      </tr></thead><tbody>
        @forelse($transactions as $transaction)
          @php($statusClass = in_array($transaction['status_key'], ['completed','charge','refund'], true) ? 'success' : (in_array($transaction['status_key'], ['failed','usage'], true) ? 'danger' : 'warning'))
          <tr class="credit-report-row">
            <td><div class="credit-source-cell"><span class="credit-source-icon {{ $transaction['source_key'] }}"><i class="fa-solid {{ $transaction['source_key'] === 'lab' ? 'fa-flask' : ($transaction['source_key'] === 'user' ? 'fa-user' : ($transaction['source_key'] === 'ledger' ? 'fa-wallet' : 'fa-receipt')) }}"></i></span><div><strong>{{ $transaction['source_label'] }}</strong><small>{{ $transaction['date_jalali'] }}</small><small>{{ $transaction['date_gregorian'] }}</small></div></div></td>
            <td><div class="credit-entity-cell"><strong>{{ $transaction['actor_label'] }}</strong><small>{{ $transaction['user_name'] }}</small><small>{{ $transaction['user_contact'] }}</small></div></td>
            <td><div class="credit-entity-cell"><strong>{{ $transaction['product_name'] }}</strong>@if($transaction['order_number'])<small>{{ $transaction['order_number'] }}</small>@elseif($transaction['reference'] !== '—')<small>{{ $transaction['reference'] }}</small>@endif</div></td>
            <td><div class="credit-entity-cell"><strong>{{ $transaction['provider'] }}</strong><small>{{ $transaction['model'] }}</small>@if($transaction['latency_seconds'] !== null)<small>{{ number_format($transaction['latency_seconds'], 1) }} ثانیه · {{ $transaction['retries'] ?? 0 }} تلاش</small>@endif</div></td>
            <td><span class="credit-status-badge {{ $statusClass }}"><span></span>{{ $transaction['status_label'] }}</span>@if($transaction['error'])<small class="credit-error-text" title="{{ $transaction['error'] }}"><i class="fa-solid fa-circle-exclamation"></i> خطا</small>@endif</td>
            <td>
              @if(!empty($transaction['input_media']))
                <div class="credit-input-cell">
                  @foreach($transaction['input_media'] as $input)
                    @if(($input['type'] ?? 'image') === 'video')
                      <a class="credit-input-thumb" href="{{ $input['url'] }}" target="_blank" rel="noopener" title="{{ $input['label'] }}"><video src="{{ $input['url'] }}" preload="metadata" muted playsinline></video><span class="credit-input-type"><i class="fa-solid fa-play"></i></span></a>
                    @elseif(($input['type'] ?? 'image') === 'text')
                      <a class="credit-input-text" href="{{ $input['url'] }}" target="_blank" rel="noopener" title="{{ $input['text'] ?: $input['label'] }}"><i class="fa-solid fa-align-right"></i><span>{{ $input['text'] ?: $input['label'] }}</span></a>
                    @else
                      <a class="credit-input-thumb" href="{{ $input['url'] }}" target="_blank" rel="noopener" title="{{ $input['label'] }}"><img src="{{ $input['url'] }}" alt="{{ $input['label'] }}" loading="lazy"></a>
                    @endif
                  @endforeach
                </div>
              @else
                <span class="credit-muted">ثبت نشده</span>
              @endif
            </td>
            <td>@if(count($transaction['output_urls']))<div class="credit-output-cell"><a href="{{ $transaction['output_urls'][0] }}" target="_blank" rel="noopener"><img src="{{ $transaction['output_urls'][0] }}" alt="خروجی"></a><span>{{ count($transaction['output_urls']) }} فایل</span></div>@else<span class="credit-muted">بدون خروجی</span>@endif</td>
            <td><div class="credit-cost-cell">@if($transaction['amount_usd'] !== null)<strong>${{ number_format($transaction['amount_usd'], 6) }}</strong><small>{{ number_format($transaction['amount_toman']) }} تومان</small>@elseif($transaction['credits'] !== null)<strong>{{ number_format($transaction['credits']) }} اعتبار</strong><small>هزینه provider ثبت نشده</small>@else<span class="credit-muted">—</span>@endif</div></td>
            <td>@if($transaction['detail_url'])<a class="credit-detail-link" href="{{ $transaction['detail_url'] }}" target="_blank">مشاهده <i class="fa-solid fa-arrow-up-left-from-circle"></i></a>@else<span class="credit-muted">—</span>@endif</td>
          </tr>
          @if($transaction['note'])<tr class="credit-report-note"><td colspan="9"><i class="fa-solid fa-circle-info"></i> {{ $transaction['note'] }}</td></tr>@endif
        @empty
          <tr><td colspan="9" class="credit-empty-state"><i class="fa-solid fa-receipt"></i><strong>رکوردی با این فیلتر پیدا نشد.</strong><span>با پاک‌کردن فیلترها یا اجرای یک تولید جدید، گزارش اینجا نمایش داده می‌شود.</span></td></tr>
        @endforelse
      </tbody></table></div>
      @if($transactions->hasPages())<div class="credit-report-pagination">{{ $transactions->onEachSide(1)->links() }}</div>@endif
    </section>
    @endif
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
      <div class="credit-field"><label>آستانهٔ هشدار</label><input type="number" step="0.000001" name="low_balance_threshold" value="0"></div><div class="credit-field"><label>آستانهٔ بحرانی</label><input type="number" step="0.000001" name="critical_balance_threshold" value="0"></div><div class="credit-field" style="grid-column:span 2"><label>یادداشت</label><input name="note"></div>
    </div>
    <label class="credit-check"><input type="checkbox" name="show_on_dashboard" value="1" checked> نمایش در مرکز فرماندهی</label>
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
