<div class="finance-head">
  <div>
    <div class="finance-title">{{ $sections[$section] ?? 'سیستم مالی' }}</div>
    <div class="finance-subtitle">کنترل درآمد، هزینه و سود با اعداد ثبت‌شده در لحظه رویداد</div>
  </div>
  <div class="finance-head-actions">
    @if(!in_array($section, ['exchange-rates', 'cost-centers', 'settings'], true))
      <form class="finance-date-filter" method="get">
        <label>از <input class="finance-input compact" type="date" name="from" value="{{ request('from', $from->toDateString()) }}"></label>
        <label>تا <input class="finance-input compact" type="date" name="to" value="{{ request('to', $to->toDateString()) }}"></label>
        <button class="finance-btn secondary" type="submit"><i class="fa-solid fa-filter"></i> اعمال</button>
      </form>
      <a class="finance-btn secondary" href="{{ route('admin.finance.export', ['report' => $section === 'reports' ? ($report ?? 'daily') : 'daily', 'from' => request('from', $from->toDateString()), 'to' => request('to', $to->toDateString())]) }}">
        <i class="fa-solid fa-file-csv"></i> خروجی
      </a>
    @endif
  </div>
</div>
