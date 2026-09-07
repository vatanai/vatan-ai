@extends('layouts.admin')
@section('title', 'جزئیات پرونده خرید — وطن استودیو')
@push('styles')
  <link rel="stylesheet" href="{{ asset('admin/css/finance.css') }}?v={{ filemtime(public_path('admin/css/finance.css')) }}">
  <link rel="stylesheet" href="{{ asset('admin/css/finance-cases.css') }}?v={{ filemtime(public_path('admin/css/finance-cases.css')) }}">
  <link rel="stylesheet" href="{{ asset('admin/css/user-operational-snapshot.css') }}?v={{ filemtime(public_path('admin/css/user-operational-snapshot.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content finance-page finance-case-show flex-1 overflow-y-auto" id="content" dir="rtl">
    @include('admin.finance.partials.messages')

    <section class="finance-case-hero">
      <div class="finance-case-hero-main">
        <div class="finance-case-eyebrow"><span>{{ $case->case_number }}</span>@if($case->is_test)<span class="finance-source manual"><i class="fa-solid fa-flask"></i> آزمایش کنترل‌شده</span>@endif<span class="finance-status {{ $case->status === 'open' ? 'status-paid' : 'status-draft' }}">{{ $case->status === 'open' ? 'پرونده باز' : 'پرونده بسته' }}</span></div>
        <h1>{{ $purchase?->plan_name ?: $case->title }}</h1>
        <p>{{ trim(($case->user?->name ?? 'کاربر') . ' ' . ($case->user?->last_name ?? '')) }} · <span dir="ltr">{{ $case->user?->phone ?: $case->user?->email ?: '—' }}</span> · شروع {{ \App\Support\Jalali::formatNumeric($case->started_at) }}</p>
        @include('admin.users.partials.operational-snapshot', ['snapshotUser' => $case->user])
      </div>
      <div class="finance-case-hero-actions">
        <a class="finance-btn secondary" href="{{ route('admin.finance.cases.index') }}"><i class="fa-solid fa-arrow-right"></i> همه پرونده‌ها</a>
        @if($case->user_id)<a class="finance-btn secondary" href="{{ route('admin.users.index', ['q' => $case->user?->phone ?: $case->user?->email]) }}"><i class="fa-solid fa-user"></i> کاربر</a>@endif
        @if($case->user_id)<a class="finance-btn secondary" href="{{ route('admin.users.index', ['show_user' => $case->user_id]) }}"><i class="fa-solid fa-images"></i> خروجی‌ها</a>@endif
        @if($case->user_id)<a class="finance-btn secondary" href="{{ route('admin.orders.index', ['q' => $case->user?->phone ?: $case->user?->email]) }}"><i class="fa-solid fa-receipt"></i> سفارش‌ها</a>@endif
      </div>
    </section>

    <div class="finance-case-kpis">
      @foreach([
        ['مبلغ خرید', $metrics['revenue'], 'تومان', 'fa-credit-card', 'primary'],
        ['هزینه مستقیم دلاری', $metrics['direct_cost_usd'], 'دلار', 'fa-dollar-sign', 'info'],
        ['هزینه مستقیم وطن', $metrics['direct_cost_toman'], 'تومان', 'fa-microchip', 'danger'],
        ['سود محقق‌شده', $metrics['realized_profit'], 'تومان', 'fa-chart-line', $metrics['realized_profit'] < 0 ? 'danger' : 'success'],
        ['سود نهایی پیش‌بینی', $metrics['projected_profit'], 'تومان', 'fa-sack-dollar', $metrics['projected_profit'] < 0 ? 'danger' : 'success'],
        ['اعتبار مصرف‌شده', $metrics['credits_used'], 'اعتبار', 'fa-bolt', 'warning'],
        ['اعتبار هدیه', $metrics['credits_gift'], 'اعتبار', 'fa-gift', 'info'],
        ['پوشش هزینه قطعی', $quality['coverage'], '٪', 'fa-shield-halved', $quality['key'] === 'actual' ? 'success' : 'warning'],
      ] as [$label, $value, $unit, $icon, $tone])
        <article class="finance-case-kpi tone-{{ $tone }}"><div class="finance-case-kpi-top"><span><i class="fa-solid {{ $icon }}"></i></span><small>{{ $label }}</small></div><strong>{{ number_format((float) $value, $unit === 'دلار' ? 4 : ($unit === '٪' ? 1 : 0)) }}</strong><footer>{{ $unit }}</footer></article>
      @endforeach
    </div>

    <section class="finance-case-quick-grid">
      <a href="#economy" class="finance-case-quick"><i class="fa-solid fa-scale-balanced"></i><span><strong>اقتصاد خرید</strong><small>سود، ضرر و نقطه سربه‌سر</small></span></a>
      <a href="#products" class="finance-case-quick"><i class="fa-solid fa-wand-magic-sparkles"></i><span><strong>محصولات</strong><small>{{ number_format($products->count()) }} محصول استفاده‌شده</small></span></a>
      <a href="#providers" class="finance-case-quick"><i class="fa-solid fa-diagram-project"></i><span><strong>مدل‌ها و سرویس‌ها</strong><small>{{ number_format($providers->sum('attempts')) }} تلاش اجرایی</small></span></a>
      <a href="#timeline" class="finance-case-quick"><i class="fa-solid fa-timeline"></i><span><strong>خط زمانی کامل</strong><small>{{ number_format($events->count()) }} رویداد ثبت‌شده</small></span></a>
    </section>

    <div class="finance-case-chart-grid">
      <section class="finance-card finance-case-chart-card">
        <div class="finance-card-head"><div><div class="finance-card-title">روند درآمد، هزینه و سود</div><div class="finance-card-subtitle">مبالغ به تومان و بر اساس تاریخ اجرای سفارش‌ها</div></div></div>
        <div class="finance-case-chart"><canvas id="caseTrendChart"></canvas></div>
      </section>
      <section class="finance-card finance-case-chart-card">
        <div class="finance-card-head"><div><div class="finance-card-title">ترکیب منابع اعتبار</div><div class="finance-card-subtitle">خرید، هدیه، ارتقا و اصلاحات دستی</div></div></div>
        <div class="finance-case-donut"><canvas id="caseCreditChart"></canvas><div><strong>{{ number_format($metrics['credits_granted']) }}</strong><small>کل اعتبار</small></div></div>
        <div class="finance-case-chart-legend">@foreach($sources as $source)<span><i></i>{{ $source['label'] }}: {{ number_format($source['credits']) }}</span>@endforeach</div>
      </section>
    </div>

    <section class="finance-card" id="economy">
      <div class="finance-card-head"><div><div class="finance-card-title">تصویر اقتصادی خرید</div><div class="finance-card-subtitle">اعداد تصمیم‌ساز برای قیمت‌گذاری پکیج و کنترل ریسک نرخ ارز</div></div><span class="finance-quality quality-{{ $quality['key'] }}">داده {{ $quality['label'] }}</span></div>
      <div class="finance-case-economy">
        <div class="finance-case-economy-main">
          <div class="finance-case-profit {{ $metrics['projected_profit'] < 0 ? 'is-loss' : 'is-profit' }}"><span>{{ $metrics['projected_profit'] < 0 ? 'ضرر پیش‌بینی‌شده' : 'سود پیش‌بینی‌شده' }}</span><strong>{{ number_format(abs($metrics['projected_profit'])) }} <small>تومان</small></strong><em>{{ number_format($metrics['projected_margin'], 1) }}٪ حاشیه</em></div>
          <div class="finance-case-usage"><div><span>مصرف کل اعتبار</span><strong>{{ number_format($metrics['utilization'], 1) }}٪</strong></div><div class="finance-case-usage-track"><span style="width:{{ min(100, $metrics['utilization']) }}%"></span></div><small>{{ number_format($metrics['credits_used']) }} مصرف · {{ number_format($metrics['credits_remaining']) }} باقی‌مانده · {{ number_format($metrics['credits_refunded']) }} بازگشتی</small></div>
        </div>
        <div class="finance-case-economy-list">
          <div><span>درآمد تخصیص‌یافته به خروجی‌ها</span><strong>{{ number_format($metrics['recognized_revenue']) }} تومان</strong></div>
          <div><span>ارزش اعتبار مصرف‌نشده</span><strong>{{ number_format($metrics['unallocated_revenue']) }} تومان</strong></div>
          <div><span>هزینه درگاه</span><strong>{{ number_format($metrics['gateway_cost']) }} تومان</strong></div>
          <div><span>زیرساخت و نیروی انسانی</span><strong>{{ number_format($metrics['infrastructure_cost'] + $metrics['workforce_cost']) }} تومان</strong></div>
          <div><span>هزینه هر اعتبار برای کاربر</span><strong>{{ number_format($metrics['user_cost_per_credit']) }} تومان</strong></div>
          <div><span>هزینه هر اعتبار برای وطن</span><strong>{{ number_format($metrics['vatan_cost_per_credit']) }} تومان</strong></div>
          <div><span>قیمت پیشنهادی برای حاشیه ۳۰٪</span><strong class="finance-emphasis">{{ number_format($metrics['recommended_price']) }} تومان</strong></div>
          <div><span>نرخ دلار سربه‌سر</span><strong class="finance-emphasis">{{ number_format($metrics['break_even_usd_rate']) }} تومان</strong></div>
        </div>
      </div>
    </section>

    <section class="finance-card" id="products">
      <div class="finance-card-head"><div><div class="finance-card-title">بهای تمام‌شده محصولات</div><div class="finance-card-subtitle">هزینه برای کاربر، هزینه برای وطن و سود واقعی هر محصول</div></div><span class="finance-count">{{ number_format($products->count()) }} محصول</span></div>
      <div class="finance-table-wrap"><table class="finance-table finance-products-economy"><thead><tr><th>محصول</th><th>سفارش / خروجی</th><th>اعتبار</th><th>هزینه کاربر</th><th>هزینه وطن</th><th>هزینه دلار</th><th>سود</th><th>حاشیه</th><th>کیفیت داده</th></tr></thead><tbody>
        @forelse($products as $product)
          <tr><td><strong>{{ $product['name'] }}</strong><div class="finance-row-sub">موفقیت {{ number_format($product['success_rate'], 1) }}٪ · میانگین {{ number_format($product['avg_duration'] / 1000, 1) }} ثانیه</div></td><td>{{ number_format($product['orders']) }} / {{ number_format($product['outputs']) }}</td><td>{{ number_format($product['credits']) }}</td><td>{{ number_format($product['revenue']) }} تومان</td><td><strong>{{ number_format($product['vatan_cost']) }} تومان</strong><div class="finance-row-sub">مدل {{ number_format($product['direct_cost']) }} · سربار {{ number_format($product['allocated_cost']) }}</div></td><td dir="ltr">${{ number_format($product['cost_usd'], 4) }}</td><td class="{{ $product['profit'] < 0 ? 'finance-negative' : 'finance-positive' }}"><strong>{{ number_format($product['profit']) }}</strong></td><td>{{ number_format($product['margin'], 1) }}٪</td><td><span class="finance-quality quality-{{ $product['quality']['key'] }}">{{ $product['quality']['label'] }}</span></td></tr>
        @empty<tr><td colspan="9" class="finance-empty">هنوز سفارش منتسبی برای این پرونده وجود ندارد.</td></tr>@endforelse
      </tbody></table></div>
    </section>

    <div class="finance-case-chart-grid" id="providers">
      <section class="finance-card finance-case-chart-card">
        <div class="finance-card-head"><div><div class="finance-card-title">سهم هزینه سرویس‌دهنده‌ها</div><div class="finance-card-subtitle">تمام تلاش‌های موفق، ناموفق و جایگزین</div></div></div>
        <div class="finance-case-chart"><canvas id="caseProviderChart"></canvas></div>
      </section>
      <section class="finance-card">
        <div class="finance-card-head"><div><div class="finance-card-title">ریز سرویس‌دهنده‌ها</div><div class="finance-card-subtitle">تفکیک هزینه قطعی، تخمینی و فاقد قیمت</div></div></div>
        <div class="finance-provider-list">@forelse($providers as $provider)<article><div><strong>{{ $provider['provider'] }}</strong><small>{{ number_format($provider['attempts']) }} تلاش · {{ number_format($provider['actual']) }} قطعی · {{ number_format($provider['estimated']) }} تخمینی</small></div><div><strong>${{ number_format($provider['cost_usd'], 4) }}</strong><small>{{ number_format($provider['cost_toman']) }} تومان</small></div></article>@empty<div class="finance-empty">هنوز اجرای مدلی ثبت نشده است.</div>@endforelse</div>
      </section>
    </div>

    <section class="finance-card" id="timeline">
      <div class="finance-card-head"><div><div class="finance-card-title">خط زمانی صفر تا صد پرونده</div><div class="finance-card-subtitle">خرید، هدیه، ارتقای پلن، سفارش، بازپرداخت و هزینه مدل‌ها</div></div><span class="finance-count">{{ number_format($events->count()) }} رویداد</span></div>
      <div class="finance-case-timeline">
        @forelse($events as $event)
          @php
            $eventTone = in_array($event->event_type, ['credit_refunded', 'credit_granted', 'plan_purchase'], true) ? 'success' : (in_array($event->event_type, ['credit_deducted'], true) ? 'danger' : ($event->data_quality === 'estimated' ? 'warning' : 'primary'));
          @endphp
          <article class="tone-{{ $eventTone }}"><div class="finance-case-event-dot"><i class="fa-solid {{ match($event->event_type) { 'plan_purchase'=>'fa-credit-card', 'credit_granted'=>'fa-gift', 'credit_deducted'=>'fa-minus', 'credit_consumed'=>'fa-bolt', 'credit_refunded'=>'fa-rotate-left', 'plan_changed'=>'fa-arrow-right-arrow-left', 'provider_request'=>'fa-microchip', default=>'fa-circle' } }}"></i></div><div class="finance-case-event-body"><header><strong>{{ $event->title }}</strong><time>{{ \App\Support\Jalali::formatNumeric($event->occurred_at) }}</time></header>@if($event->description)<p>{{ $event->description }}</p>@endif<div class="finance-case-event-meta">@if($event->credits_delta)<span class="{{ $event->credits_delta < 0 ? 'finance-negative' : 'finance-positive' }}">{{ $event->credits_delta > 0 ? '+' : '' }}{{ number_format($event->credits_delta) }} اعتبار</span>@endif @if($event->amount_usd !== null)<span dir="ltr">${{ number_format((float) $event->amount_usd, 4) }}</span>@endif @if($event->amount_toman !== null)<span>{{ number_format((float) $event->amount_toman) }} تومان</span>@endif @if($event->data_quality !== 'actual')<span class="finance-quality quality-{{ $event->data_quality }}">{{ $event->data_quality === 'estimated' ? 'تخمینی' : 'ناقص' }}</span>@endif</div></div></article>
        @empty<div class="finance-empty">هنوز رویدادی در این پرونده ثبت نشده است.</div>@endforelse
      </div>
    </section>

    <section class="finance-card">
      <div class="finance-card-head"><div><div class="finance-card-title">تنظیمات پرونده</div><div class="finance-card-subtitle">برای تست قیمت‌گذاری، پرونده را آزمایشی علامت بزنید و پس از اتمام اعتبار ببندید.</div></div></div>
      <form class="finance-form finance-case-settings" method="post" action="{{ route('admin.finance.cases.update', $case) }}">@csrf @method('PATCH')
        <label class="finance-case-switch"><input type="hidden" name="is_test" value="0"><input type="checkbox" name="is_test" value="1" @checked($case->is_test)><span><strong>آزمایش کنترل‌شده</strong><small>برای سنجش واقعی قیمت و هزینه یک پکیج</small></span></label>
        <label class="finance-field"><span>وضعیت پرونده</span><select class="finance-input" name="status"><option value="open" @selected($case->status === 'open')>باز</option><option value="closed" @selected($case->status === 'closed')>بسته</option></select></label>
        <label class="finance-field finance-case-note"><span>یادداشت</span><textarea class="finance-input finance-textarea" name="notes" placeholder="نتیجه تست یا توضیح تصمیم قیمت‌گذاری...">{{ $case->notes }}</textarea></label>
        <button class="finance-btn primary"><i class="fa-solid fa-floppy-disk"></i> ذخیره پرونده</button>
      </form>
    </section>
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
  const initCaseCharts = function () {
    if (!window.Chart) return;
  const readVar = function (name) { return getComputedStyle(document.body).getPropertyValue(name).trim(); };
  const text = readVar('--text-soft');
  const grid = readVar('--border');
  const daily = @json($daily);
  const providers = @json($providers);
  const sources = @json($sources);
  const common = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: text, font: { family: 'YekanBakh', size: 10 }, usePointStyle: true } } }, scales: { x: { ticks: { color: text, font: { family: 'YekanBakh', size: 9 } }, grid: { color: grid } }, y: { ticks: { color: text, font: { family: 'YekanBakh', size: 9 } }, grid: { color: grid } } } };
  if (window.Chart && document.getElementById('caseTrendChart')) new Chart(document.getElementById('caseTrendChart'), { type: 'line', data: { labels: daily.map(x => x.label), datasets: [
    { label: 'درآمد', data: daily.map(x => x.revenue), borderColor: readVar('--primary'), backgroundColor: readVar('--primary-l'), fill: true, tension: .42, pointRadius: 3 },
    { label: 'هزینه', data: daily.map(x => x.cost), borderColor: readVar('--danger'), backgroundColor: readVar('--danger-l'), tension: .42, pointRadius: 3 },
    { label: 'سود', data: daily.map(x => x.profit), borderColor: readVar('--success'), backgroundColor: readVar('--success-l'), tension: .42, pointRadius: 3 }
  ] }, options: common });
  if (window.Chart && document.getElementById('caseCreditChart')) new Chart(document.getElementById('caseCreditChart'), { type: 'doughnut', data: { labels: sources.map(x => x.label), datasets: [{ data: sources.map(x => x.credits), backgroundColor: [readVar('--primary'), readVar('--info'), readVar('--warning'), readVar('--success'), readVar('--danger')], borderColor: readVar('--card-bg'), borderWidth: 4, hoverOffset: 5 }] }, options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { legend: { display: false } } } });
  if (window.Chart && document.getElementById('caseProviderChart')) new Chart(document.getElementById('caseProviderChart'), { type: 'bar', data: { labels: providers.map(x => x.provider), datasets: [{ label: 'هزینه تومان', data: providers.map(x => x.cost_toman), backgroundColor: readVar('--primary'), borderRadius: 8, maxBarThickness: 42 }] }, options: common });
  };
  if (document.readyState === 'complete') initCaseCharts();
  else window.addEventListener('load', initCaseCharts, { once: true });
})();
</script>
@endsection
