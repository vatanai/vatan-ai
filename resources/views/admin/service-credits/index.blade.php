@extends('layouts.admin')
@section('title', 'مدیریت اعتبار سرویس‌ها — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/service-credits.css') }}?v={{ filemtime(public_path('admin/css/service-credits.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto credit-page" id="content">
    <div class="credit-head">
      <div>
        <h1>مدیریت اعتبار و مصرف سرویس‌ها</h1>
        <div class="credit-subtitle">نمای یکپارچه موجودی، مصرف روزانه، هزینه ماهانه و هشدار کمبود اعتبار</div>
      </div>
      <div class="credit-head-actions">
        <form method="POST" action="{{ route('admin.service-credits.refresh') }}">@csrf
          <button class="credit-btn" type="submit"><i class="fa-solid fa-rotate"></i> تازه‌سازی آنلاین</button>
        </form>
        <button class="credit-btn" type="button" data-open-modal="account-modal"><i class="fa-solid fa-plus"></i> اکانت جدید</button>
        <button class="credit-btn primary" type="button" data-open-modal="transaction-modal"><i class="fa-solid fa-receipt"></i> ثبت تراکنش</button>
      </div>
    </div>

    @if(session('success'))<div class="credit-alert success">{{ session('success') }}</div>@endif
    @if(isset($errors) && $errors->any())<div class="credit-alert error">{{ $errors->first() }}</div>@endif

    @php
      $creditBySlug = $accounts->keyBy('slug');
      $providerCards = [
        ['slug' => 'cloudiva', 'name' => 'Cloudiva', 'icon' => 'fa-cloud'],
        ['slug' => 'fal', 'name' => 'Fal.ai', 'icon' => 'fa-wand-magic-sparkles'],
        ['slug' => 'replicate', 'name' => 'Replicate', 'icon' => 'fa-cubes'],
        ['slug' => 'openrouter', 'name' => 'OpenRouter', 'icon' => 'fa-route'],
      ];
    @endphp
    <div class="credit-grid">
      <div class="credit-summary credit-rate"><div class="credit-summary-label">قیمت روز دلار</div><div class="credit-summary-value">{{ $exchange['rate'] > 0 ? number_format($exchange['rate'] / 10) : '—' }}</div><div class="credit-summary-meta">تومان · {{ $exchange['source'] }} · {{ $exchange['online'] ? 'آنلاین' : 'پشتیبان' }}</div></div>
      @foreach($providerCards as $providerCard)
        @php($creditAccount = $creditBySlug->get($providerCard['slug']))
        <a class="credit-summary credit-provider-summary" href="{{ route('admin.service-credits.index') }}">
          <div class="credit-provider-summary-head"><span>{{ $providerCard['name'] }}</span><span class="credit-provider-mark"><i class="fa-solid {{ $providerCard['icon'] }}"></i></span></div>
          <div class="credit-provider-usd">{{ $creditAccount?->balance_usd !== null ? '$'.number_format((float) $creditAccount->balance_usd, 4) : '—' }}</div>
          <div class="credit-provider-toman">{{ $creditAccount?->balance_toman !== null ? number_format((float) $creditAccount->balance_toman).' تومان' : 'موجودی ثبت نشده' }}</div>
          <div class="credit-summary-meta">{{ $creditAccount?->status_label ?? 'حساب ساخته نشده' }}</div>
        </a>
      @endforeach
    </div>

    <div class="credit-accounts">
      @forelse($accounts as $account)
        <article class="credit-card {{ $account->is_low ? 'low' : '' }} {{ $account->alert_level }}">
          <div class="credit-card-head">
            <div class="credit-service">
              <div class="credit-logo"><i class="fa-solid {{ ['openrouter' => 'fa-route', 'fal' => 'fa-wand-magic-sparkles', 'replicate' => 'fa-cubes', 'cloudiva' => 'fa-cloud'][$account->slug] ?? 'fa-wallet' }}"></i></div>
              <div><div class="credit-name">{{ $account->name }}</div><div class="credit-status {{ $account->is_online ? 'online' : '' }}"><span class="credit-dot"></span>{{ $account->status_label }}</div></div>
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
          @if(count($account->timeline_points ?? []))
            <div class="credit-timeline" aria-label="روند موجودی {{ $account->name }}">
              @foreach($account->timeline_points as $point)<span style="height:{{ $point['height'] }}%" title="{{ $point['time'] }} · {{ number_format($point['balance'], 2) }}"></span>@endforeach
            </div>
            <div class="credit-summary-meta">روند موجودی در آخرین نمونه‌های ثبت‌شده</div>
          @endif
          @if($account->forecast_hours !== null)<div class="credit-forecast"><i class="fa-solid fa-hourglass-half"></i> با روند فعلی، اعتبار حدود {{ number_format($account->forecast_hours, 1) }} ساعت دوام می‌آورد.</div>@endif
          @if($account->usage_is_estimate)<div class="credit-summary-meta">هزینه جاری ساعتی: {{ number_format($account->hourly_usage / 10) }} تومان — محاسبه آنلاین بر اساس منابع فعال سرویس</div>@endif
          @if($account->usage_source)<div class="credit-summary-meta">منبع مصرف: {{ $account->usage_source }}</div>@endif
          @if($account->alert_level !== 'normal')<div class="credit-warning"><i class="fa-solid fa-triangle-exclamation"></i> {{ $account->alert_message }}</div>@endif
          @if($account->sync_error)<div class="credit-warning">{{ $account->sync_error }}</div>@endif
        </article>

        <div class="credit-modal" id="edit-{{ $account->id }}"><div class="credit-modal-box">
          <div class="credit-panel-title">تنظیمات {{ $account->name }}</div>
          <form method="POST" action="{{ route('admin.service-credits.accounts.update', $account) }}">@csrf @method('PUT')
            <div class="credit-form-grid">
              <div class="credit-field"><label>موجودی دستی ({{ $account->currency }})</label><input type="number" step="0.000001" name="manual_balance" value="{{ $account->manual_balance }}" required></div>
              <div class="credit-field"><label>حد هشدار</label><input type="number" step="0.000001" name="low_balance_threshold" value="{{ $account->low_balance_threshold }}"></div>
              <div class="credit-field"><label>حد بحرانی</label><input type="number" step="0.000001" name="critical_balance_threshold" value="{{ $account->critical_balance_threshold }}"></div>
              <div class="credit-field"><label>یادداشت</label><input name="note" value="{{ $account->note }}"></div>
            </div>
            <label class="credit-check"><input type="checkbox" name="show_on_dashboard" value="1" {{ $account->show_on_dashboard ? 'checked' : '' }}> نمایش کارت در مرکز فرماندهی</label>
            <label class="credit-check"><input type="checkbox" name="alerts_enabled" value="1" {{ $account->alerts_enabled ? 'checked' : '' }}> فعال‌بودن هشدارهای این سرویس</label>
            <div class="credit-actions"><button class="credit-btn primary">ذخیره</button><button class="credit-btn" type="button" data-close-modal>انصراف</button></div>
          </form>
        </div></div>
      @empty
        <div class="credit-panel">پس از اجرای migration، اکانت‌های پیش‌فرض providerها ساخته می‌شوند.</div>
      @endforelse
    </div>

    <section class="credit-panel credit-transactions-panel">
      <div class="credit-panel-heading">
        <div>
          <div class="credit-panel-title">گزارش جامع مصرف و تراکنش‌ها</div>
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

      <form method="GET" action="{{ route('admin.service-credits.index') }}" class="credit-report-filters">
        <div class="credit-field credit-filter-search"><label for="credit-report-q">جست‌وجو</label><div class="credit-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input id="credit-report-q" name="q" value="{{ request('q') }}" placeholder="کاربر، محصول، سفارش، مدل یا شناسه درخواست"></div></div>
        <div class="credit-field"><label>منبع</label><select name="source"><option value="">همه منابع</option>@foreach($sourceOptions as $key => $label)<option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="credit-field"><label>پرووایدر</label><select name="provider"><option value="">همه پرووایدرها</option>@foreach($providers as $provider)<option value="{{ $provider['key'] }}" @selected(request('provider') === $provider['key'])>{{ $provider['label'] }}</option>@endforeach</select></div>
        <div class="credit-field"><label>وضعیت</label><select name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusOptions as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="credit-field"><label>از تاریخ</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="credit-field"><label>تا تاریخ</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
        <div class="credit-filter-actions"><button class="credit-btn primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button><a class="credit-btn" href="{{ route('admin.service-credits.index') }}">پاک‌کردن</a></div>
      </form>

      <div id="credit-report-table-region" data-report-url="{{ route('admin.service-credits.index') }}" aria-live="polite">
        @include('admin.service-credits.partials.usage-table')
      </div>
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
      <div class="credit-field"><label>حد هشدار</label><input type="number" step="0.000001" name="low_balance_threshold" value="0"></div><div class="credit-field"><label>حد بحرانی</label><input type="number" step="0.000001" name="critical_balance_threshold" value="0"></div><div class="credit-field" style="grid-column:span 2"><label>یادداشت</label><input name="note"></div>
    </div>
    <label class="credit-check"><input type="checkbox" name="show_on_dashboard" value="1" checked> نمایش در مرکز فرماندهی</label>
    <label class="credit-check"><input type="checkbox" name="alerts_enabled" value="1" checked> فعال‌بودن هشدارهای این سرویس</label>
    <div class="credit-actions"><button class="credit-btn primary">افزودن اکانت</button><button class="credit-btn" type="button" data-close-modal>انصراف</button></div>
  </form>
</div></div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('[data-open-modal]').forEach(function(button){button.addEventListener('click',function(){document.getElementById(button.dataset.openModal)?.classList.add('open')})});
document.querySelectorAll('[data-close-modal]').forEach(function(button){button.addEventListener('click',function(){button.closest('.credit-modal')?.classList.remove('open')})});
document.querySelectorAll('.credit-modal').forEach(function(modal){modal.addEventListener('click',function(event){if(event.target===modal)modal.classList.remove('open')})});
(function(){
  const region=document.getElementById('credit-report-table-region');
  if(!region)return;
  async function loadTable(url){
    const requestUrl=new URL(url,window.location.href);
    requestUrl.searchParams.set('usage_table','1');
    region.classList.add('is-loading');
    try{
      const response=await fetch(requestUrl,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});
      if(!response.ok)throw new Error('بارگذاری فهرست ناموفق بود.');
      region.innerHTML=await response.text();
      const cleanUrl=new URL(requestUrl);cleanUrl.searchParams.delete('usage_table');
      window.history.replaceState({},'',cleanUrl.pathname+cleanUrl.search);
      region.scrollIntoView({behavior:'smooth',block:'nearest'});
    }catch(error){
      window.location.href=url;
    }finally{region.classList.remove('is-loading')}
  }
  region.addEventListener('click',function(event){
    const link=event.target.closest('.credit-report-pagination a');
    if(!link)return;
    event.preventDefault();loadTable(link.href);
  });
  region.addEventListener('change',function(event){
    if(!event.target.matches('[data-credit-per-page]'))return;
    const url=new URL(window.location.href);url.searchParams.set('per_page',event.target.value);url.searchParams.set('page','1');loadTable(url);
  });
}());
</script>
@endsection
