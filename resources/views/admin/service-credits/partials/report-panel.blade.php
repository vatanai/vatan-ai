<section class="credit-panel credit-transactions-panel" id="credit-report">
  <div class="credit-panel-heading">
    <div>
      <div class="credit-panel-title">گزارش کامل مصرف و تراکنش‌ها</div>
      <div class="credit-panel-caption">هر ساخت کاربر یک ردیف؛ هزینه‌ها بر اساس مصرف ثبت‌شده نمایش داده می‌شوند.</div>
    </div>
    <span class="credit-live-label"><span class="credit-dot"></span> داده زنده</span>
  </div>

  <div class="credit-report-summary">
    <div class="credit-report-stat"><span>کل رخدادها</span><strong>{{ number_format($summary['count']) }}</strong></div>
    <div class="credit-report-stat success"><span>موفق</span><strong>{{ number_format($summary['success']) }}</strong></div>
    <div class="credit-report-stat danger"><span>ناموفق</span><strong>{{ number_format($summary['failed']) }}</strong></div>
    <div class="credit-report-stat"><span>هزینه دلاری ثبت‌شده</span><strong>${{ number_format($summary['usd'], 6) }}</strong></div>
    <div class="credit-report-stat"><span>هزینه تومانی ثبت‌شده/محاسبه‌شده</span><strong>{{ number_format($summary['toman']) }} تومان</strong></div>
  </div>

  <form method="GET" action="{{ route('admin.service-credits.transactions') }}" class="credit-report-filters" data-credit-filters>
    <input type="hidden" name="per_page" value="{{ $transactions->perPage() }}">
    <div class="credit-field credit-filter-search"><label for="credit-report-q">جست‌وجو</label><div class="credit-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input id="credit-report-q" name="q" value="{{ request('q') }}" placeholder="کاربر، محصول، سفارش، مدل یا شناسه درخواست"></div></div>
    <div class="credit-field"><label>منبع</label><select name="source"><option value="">همه منابع</option>@foreach($sourceOptions as $key => $label)<option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>@endforeach</select></div>
    <div class="credit-field"><label>پرووایدر</label><select name="provider"><option value="">همه پرووایدرها</option>@foreach($providers as $provider)<option value="{{ $provider['key'] }}" @selected(request('provider') === $provider['key'])>{{ $provider['label'] }}</option>@endforeach</select></div>
    <div class="credit-field"><label>مدل ساخته‌شده</label><select name="model"><option value="">همه مدل‌ها</option>@foreach($models as $model)<option value="{{ $model['key'] }}" @selected(request('model') === $model['key'])>{{ $model['label'] }}</option>@endforeach</select></div>
    <div class="credit-field"><label>وضعیت</label><select name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusOptions as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
    <div class="credit-field"><label>از تاریخ</label><input type="date" name="date_from" value="{{ request('date_from') }}"></div>
    <div class="credit-field"><label>تا تاریخ</label><input type="date" name="date_to" value="{{ request('date_to') }}"></div>
    <div class="credit-filter-actions"><button class="credit-btn primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button><a class="credit-btn" href="{{ route('admin.service-credits.transactions') }}">پاک‌کردن</a></div>
  </form>

  @include('admin.service-credits.partials.usage-table')
</section>
