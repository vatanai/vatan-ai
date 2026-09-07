@extends('layouts.admin')
@section('title', 'پرونده خریدها — وطن استودیو')
@push('styles')
  <link rel="stylesheet" href="{{ asset('admin/css/finance.css') }}?v={{ filemtime(public_path('admin/css/finance.css')) }}">
  <link rel="stylesheet" href="{{ asset('admin/css/finance-cases.css') }}?v={{ filemtime(public_path('admin/css/finance-cases.css')) }}">
  <link rel="stylesheet" href="{{ asset('admin/css/user-operational-snapshot.css') }}?v={{ filemtime(public_path('admin/css/user-operational-snapshot.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content finance-page finance-cases-page flex-1 overflow-y-auto" id="content" dir="rtl">
    @include('admin.finance.partials.messages')

    <div class="finance-head">
      <div>
        <div class="finance-title">پرونده خریدها</div>
        <div class="finance-subtitle">ردیابی کامل هر خرید از پرداخت و اعتبار هدیه تا سفارش، هزینه واقعی و سود نهایی</div>
      </div>
      <div class="finance-head-actions">
        <a class="finance-btn secondary" href="{{ route('admin.finance.show', ['section' => 'overview']) }}"><i class="fa-solid fa-chart-pie"></i> نمای مالی</a>
        <form method="post" action="{{ route('admin.finance.cases.sync') }}">@csrf<button class="finance-btn primary"><i class="fa-solid fa-rotate"></i> همگام‌سازی پرونده‌ها</button></form>
      </div>
    </div>

    <div class="finance-stat-grid finance-case-index-stats">
      @foreach([
        ['کل پرونده‌ها', $stats['total'], 'fa-folder-tree', 'primary'],
        ['پرونده باز', $stats['open'], 'fa-folder-open', 'info'],
        ['آزمایش کنترل‌شده', $stats['test'], 'fa-flask', 'warning'],
        ['کاربر دارای پرونده', $stats['users'], 'fa-users', 'success'],
      ] as [$label, $value, $icon, $tone])
        <div class="finance-stat-card tone-{{ $tone }}"><span class="finance-stat-icon"><i class="fa-solid {{ $icon }}"></i></span><div><div class="finance-stat-label">{{ $label }}</div><div class="finance-stat-value">{{ number_format($value) }}</div></div></div>
      @endforeach
    </div>

    <section class="finance-card finance-filter-card">
      <form class="finance-case-filters" method="get">
        <label class="finance-field"><span>جستجو</span><input class="finance-input" name="q" value="{{ request('q') }}" placeholder="کاربر، شماره پرونده یا خرید"></label>
        <label class="finance-field"><span>وضعیت</span><select class="finance-input" name="status"><option value="">همه</option><option value="open" @selected(request('status') === 'open')>باز</option><option value="closed" @selected(request('status') === 'closed')>بسته</option></select></label>
        <label class="finance-field"><span>نوع پرونده</span><select class="finance-input" name="is_test"><option value="">همه</option><option value="1" @selected(request('is_test') === '1')>آزمایش کنترل‌شده</option><option value="0" @selected(request('is_test') === '0')>عادی</option></select></label>
        @if($selectedUser)<input type="hidden" name="user_id" value="{{ $selectedUser->id }}">@endif
        <button class="finance-btn primary"><i class="fa-solid fa-filter"></i> اعمال</button>
        @if(request()->query())<a class="finance-btn secondary" href="{{ route('admin.finance.cases.index') }}"><i class="fa-solid fa-xmark"></i> پاک‌کردن</a>@endif
      </form>
      @if($selectedUser)<div class="finance-selected-user"><i class="fa-solid fa-user-check"></i><span>پرونده‌های {{ trim(($selectedUser->name ?? 'کاربر') . ' ' . ($selectedUser->last_name ?? '')) }}</span></div>@endif
    </section>

    <section class="finance-card">
      <div class="finance-card-head"><div><div class="finance-card-title">فهرست پرونده‌های مالی</div><div class="finance-card-subtitle">هر ردیف یک خرید مبنا و تمام رویدادهای مالی پس از آن را نگه می‌دارد.</div></div><span class="finance-count">{{ number_format($cases->total()) }} پرونده</span></div>
      <div class="finance-table-wrap">
        <table class="finance-table finance-case-table">
          <thead><tr><th>پرونده</th><th>کاربر</th><th>خرید مبنا</th><th>اعتبار</th><th>مبلغ خرید</th><th>درآمد تخصیصی</th><th>وضعیت</th><th>عملیات</th></tr></thead>
          <tbody>
            @forelse($cases as $case)
              @php
                $granted = (int) ($case->credits_granted ?? 0);
                $remaining = (int) ($case->credits_remaining ?? 0);
                $used = max(0, $granted - $remaining);
                $usage = $granted > 0 ? min(100, round(($used / $granted) * 100, 1)) : 0;
              @endphp
              <tr>
                <td><a class="finance-case-number" href="{{ route('admin.finance.cases.show', $case) }}">{{ $case->case_number }}</a><div class="finance-row-sub">{{ \App\Support\Jalali::formatNumeric($case->started_at) }}</div></td>
                <td><strong>{{ trim(($case->user?->name ?? 'کاربر حذف‌شده') . ' ' . ($case->user?->last_name ?? '')) }}</strong>@include('admin.users.partials.operational-snapshot', ['snapshotUser' => $case->user])</td>
                <td><strong>{{ $case->purchase?->plan_name ?: 'بدون خرید مبنا' }}</strong><div class="finance-row-sub" dir="ltr">{{ $case->purchase?->order_number ?: '—' }}</div></td>
                <td><div class="finance-case-credit"><strong>{{ number_format($used) }} / {{ number_format($granted) }}</strong><div class="finance-case-progress"><span style="width:{{ $usage }}%"></span></div><small>{{ number_format($usage, 1) }}٪ مصرف</small></div></td>
                <td><strong>{{ number_format((float) ($case->purchase?->paid_amount ?? 0)) }}</strong><div class="finance-row-sub">تومان</div></td>
                <td><strong>{{ number_format((float) ($case->recognized_revenue ?? 0)) }}</strong><div class="finance-row-sub">تومان</div></td>
                <td><div class="finance-case-badges">@if($case->is_test)<span class="finance-source manual">آزمایشی</span>@endif<span class="finance-status {{ $case->status === 'open' ? 'status-paid' : 'status-draft' }}">{{ $case->status === 'open' ? 'باز' : 'بسته' }}</span></div></td>
                <td><div class="finance-case-row-actions"><a class="finance-icon-btn" href="{{ route('admin.finance.cases.show', $case) }}" title="بازکردن پرونده"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>@if($case->user)<a class="finance-icon-btn" href="{{ route('admin.users.index', ['show_user' => $case->user_id]) }}" title="خروجی‌های ساخته‌شده"><i class="fa-solid fa-images"></i></a>@endif</div></td>
              </tr>
            @empty
              <tr><td colspan="8" class="finance-empty">هنوز پرونده‌ای ساخته نشده است؛ همگام‌سازی را اجرا کنید.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($cases->hasPages())<div class="finance-pagination">{{ $cases->links() }}</div>@endif
    </section>
  </div>
</main>
@endsection
