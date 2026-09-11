@extends('layouts.admin')
@section('title', 'خرید پلن‌ها — وطن استودیو')
@push('styles')
  <link rel="stylesheet" href="{{ asset('admin/css/orders.css') }}">
  <link rel="stylesheet" href="{{ asset('admin/css/user-operational-snapshot.css') }}?v={{ filemtime(public_path('admin/css/user-operational-snapshot.css')) }}">
  <style>
    /* چیدمان اختصاصی همین لیست؛ محتوا و ستون‌های جدول دست‌نخورده می‌مانند. */
    .orders-plan-purchases-table {
      width: 100%;
      min-width: 0;
      table-layout: fixed;
    }
    .orders-plan-purchases-table th {
      text-align: center;
      white-space: normal;
      line-height: 1.55;
    }
    .orders-plan-purchases-table td {
      overflow: hidden;
    }
    .orders-plan-purchases-table th:nth-child(1),
    .orders-plan-purchases-table td:nth-child(1) { width: 42px; }
    .orders-plan-purchases-table th:nth-child(2),
    .orders-plan-purchases-table td:nth-child(2) { width: 118px; }
    .orders-plan-purchases-table th:nth-child(3),
    .orders-plan-purchases-table td:nth-child(3) { width: 31%; }
    .orders-plan-purchases-table th:nth-child(4),
    .orders-plan-purchases-table td:nth-child(4) { width: 148px; }
    .orders-plan-purchases-table th:nth-child(5),
    .orders-plan-purchases-table td:nth-child(5) { width: 112px; }
    .orders-plan-purchases-table th:nth-child(6),
    .orders-plan-purchases-table td:nth-child(6) { width: 122px; }
    .orders-plan-purchases-table th:nth-child(7),
    .orders-plan-purchases-table td:nth-child(7) { width: 116px; }
    .orders-plan-purchases-table th:nth-child(8),
    .orders-plan-purchases-table td:nth-child(8) { width: 104px; }
    .orders-plan-purchases-table th:nth-child(9),
    .orders-plan-purchases-table td:nth-child(9) { width: 104px; }

    .orders-plan-purchases-table .order-user,
    .orders-plan-purchases-table .order-user > div {
      min-width: 0;
    }
    .orders-plan-purchases-table .order-user-name,
    .orders-plan-purchases-table .order-meta {
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .orders-plan-purchases-table td:nth-child(6) .order-meta {
      max-width: 100px;
      margin-inline: auto;
      white-space: normal;
      overflow-wrap: anywhere;
      word-break: break-word;
      line-height: 1.55;
    }
    .orders-plan-purchases-table .orders-row-actions {
      display: grid;
      grid-template-columns: repeat(2, 40px);
      grid-auto-rows: 40px;
      align-items: center;
      justify-content: center;
      gap: 6px;
      width: max-content;
      margin-inline: auto;
    }
    .orders-plan-purchases-table .orders-row-actions .order-btn {
      width: 40px;
      min-width: 40px;
      height: 40px;
      padding: 0;
    }
    @media (max-width: 900px) {
      .orders-plan-purchases-table {
        min-width: 1050px;
      }
    }
  </style>
@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content orders-page p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    <div class="orders-head">
      <div>
        <div class="orders-title">خرید پلن‌ها و پرداخت‌ها</div>
        <div class="orders-subtitle">مرکز پیگیری رفتار مالی کاربر؛ از ورود به درگاه تا تأیید، شکست یا رهاشدن پرداخت</div>
      </div>
      <div class="orders-actions">
        <a class="order-btn" href="{{ route('admin.orders.index') }}"><i class="fa-solid fa-receipt"></i> سفارش‌های ساخت</a>
        <a class="order-btn" href="{{ route('admin.orders.plan-purchases.export', request()->except(['page', 'ids'])) }}"><i class="fa-solid fa-file-export"></i> خروجی فیلترشده</a>
        <button type="button" class="order-btn" id="export-selected-purchases" disabled><i class="fa-solid fa-download"></i> خروجی انتخاب‌شده</button>
        <a class="order-btn" href="{{ url()->current() }}"><i class="fa-solid fa-rotate-right"></i> بروزرسانی</a>
      </div>
    </div>

    <div class="orders-stats">
      @foreach([
        ['کل تلاش‌ها', $stats['total'], 'fa-credit-card'],
        ['پرداخت موفق', $stats['completed'], 'fa-circle-check'],
        ['در انتظار پرداخت', $stats['active'], 'fa-clock'],
        ['ناموفق یا منقضی', $stats['failed'], 'fa-circle-xmark'],
        ['درآمد موفق', number_format($stats['revenue']) . ' تومان', 'fa-wallet'],
      ] as [$label, $value, $icon])
        <div class="order-stat"><div class="order-stat-icon"><i class="fa-solid {{ $icon }}"></i></div><div class="order-stat-label">{{ $label }}</div><div class="order-stat-value">{{ is_numeric($value) ? number_format($value) : $value }}</div></div>
      @endforeach
    </div>

    <section class="order-panel order-attention-panel">
      <div class="order-panel-head">
        <div><div class="order-panel-title"><i class="fa-solid fa-bell" style="color:var(--warning);margin-left:6px"></i> نیازمند پیگیری مالی</div><div class="order-meta">کاربرانی که به درگاه رفته‌اند اما هنوز خرید موفق برایشان ثبت نشده است.</div></div>
        <span class="order-badge warning"><i class="order-dot"></i>{{ number_format($stats['gateway_attempts']) }} مورد</span>
      </div>
      @if($gatewayAttempts->isEmpty())
        <div class="order-empty order-empty-compact"><i class="fa-solid fa-circle-check"></i>مورد باز یا رهاشده‌ای برای پیگیری وجود ندارد.</div>
      @else
        <div class="order-attention-list">
          @foreach($gatewayAttempts as $attempt)
            @php
              $attemptClass = in_array($attempt->status, [\App\Models\PlanPurchase::EXPIRED, \App\Models\PlanPurchase::FAILED], true) ? 'danger' : 'warning';
              $attemptFollowedUp = $attempt->followUps->whereNotNull('completed_at')->isNotEmpty();
            @endphp
            <a class="order-attention-item" href="{{ route('admin.orders.plan-purchases.show', $attempt) }}">
              <span class="order-attention-icon {{ $attemptClass }}"><i class="fa-solid {{ $attempt->status === \App\Models\PlanPurchase::EXPIRED ? 'fa-hourglass-end' : 'fa-arrow-up-right-from-square' }}"></i></span>
              <span class="order-attention-main"><strong>{{ trim(($attempt->user?->name ?: 'کاربر') . ' ' . ($attempt->user?->last_name ?: '')) }}</strong><small>{{ $attempt->plan_name }} · {{ number_format((int) $attempt->paid_amount) }} تومان · {{ \App\Models\PlanPurchase::statusLabel($attempt->status) }}</small><em class="order-follow-up-mini {{ $attemptFollowedUp ? 'is-done' : '' }}"><i class="fa-solid {{ $attemptFollowedUp ? 'fa-check' : 'fa-circle-exclamation' }}"></i>{{ $attemptFollowedUp ? 'پیگیری ثبت شده' : 'بدون پیگیری' }}</em></span>
              <span class="order-attention-time">{{ \App\Support\Jalali::formatNumeric($attempt->updated_at ?: $attempt->created_at) }} <i class="fa-solid fa-angle-left"></i></span>
            </a>
          @endforeach
        </div>
      @endif
    </section>

    <form class="order-panel order-filters" method="GET" action="{{ route('admin.orders.plan-purchases') }}">
      <div class="order-field"><label>جستجو</label><input class="order-input" name="q" value="{{ request('q') }}" placeholder="شماره سفارش، کد پیگیری یا کاربر"></div>
      <div class="order-field"><label>وضعیت پرداخت</label><select class="order-select" name="status"><option value="">همه وضعیت‌ها</option>@foreach(['pending'=>'آماده پرداخت','redirected'=>'در انتظار بازگشت از درگاه','verifying'=>'در حال بررسی پرداخت','completed'=>'موفق','failed'=>'ناموفق','expired'=>'منقضی'] as $key => $label)<option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>@endforeach</select></div>
      <button type="submit" class="order-btn primary"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button>
    </form>

    <div class="order-panel">
      <div class="orders-table-wrap">
        <table class="orders-table orders-plan-purchases-table">
          <thead>
            <tr>
              <th class="order-select-column"><input type="checkbox" id="select-all-purchases" aria-label="انتخاب همه خریدهای نمایش‌داده‌شده"></th>
              <th>شماره سفارش</th>
              <th>اطلاعات کامل کاربر</th>
              <th>پلن خریداری‌شده</th>
              <th>مبلغ و اعتبار</th>
              <th>درگاه و پیگیری</th>
              <th>وضعیت</th>
              <th>زمان</th>
              <th>عملیات</th>
            </tr>
          </thead>
          <tbody>
            @forelse($purchases as $purchase)
              @php
                $statusClass = $purchase->isCompleted() ? 'success' : (in_array($purchase->status, ['failed', 'expired', 'cancelled'], true) ? 'danger' : 'warning');
                $user = $purchase->user;
                $referrer = $user?->referrer;
                $joinedAt = $user?->registered_at ?: $user?->created_at;
                $birthDate = '—';
                if ($user?->birth_date) {
                  [$birthYear, $birthMonth, $birthDay] = \App\Support\Jalali::toJalaliYmd((int) $user->birth_date->format('Y'), (int) $user->birth_date->format('n'), (int) $user->birth_date->format('j'));
                  $birthDate = \App\Support\Jalali::toPersianDigits(sprintf('%04d/%02d/%02d', $birthYear, $birthMonth, $birthDay));
                }
                $userStatus = match ($user?->status) { 'suspended' => 'معلق', 'deleted' => 'حذف شده', default => 'فعال' };
              @endphp
              <tr>
                <td class="order-select-column"><input type="checkbox" class="purchase-select" value="{{ $purchase->id }}" aria-label="انتخاب خرید {{ $purchase->order_number }}"></td>
                <td><span class="order-number">{{ $purchase->order_number }}</span></td>
                <td>
                  <div class="order-user">
                    @if($user?->avatar)
                      <img class="order-avatar order-avatar-image" src="{{ asset('storage/' . $user->avatar) }}" alt="">
                    @else
                      <span class="order-avatar">{{ mb_substr($user?->name ?: 'ک', 0, 1) }}</span>
                    @endif
                    <div>
                      <div class="order-user-name">{{ trim(($user?->name ?: 'کاربر') . ' ' . ($user?->last_name ?: '')) }}</div>
                      <div class="order-meta"><span dir="ltr">ID: {{ $user?->id ?: '—' }}</span></div>
                      <div class="order-meta" dir="ltr">{{ $user?->email ?: '—' }}</div>
                    </div>
                  </div>
                  @include('admin.users.partials.operational-snapshot', ['snapshotUser' => $user])
                  <div class="order-user-details">
                    <span><b>تولد:</b> {{ $birthDate }}</span>
                    <span><b>وضعیت:</b> {{ $userStatus }}</span>
                    <span><b>پلن فعال:</b> {{ $user?->plan_display_name ?: '—' }}</span>
                    <span><b>گروه:</b> {{ ($user?->customer_segment ?: 'regular') === 'loyal' ? 'مشتری ثابت' : 'کاربر عادی' }}</span>
                    <span><b>اعتبار:</b> {{ number_format($user?->tokens ?? 0) }} <small>خرید: {{ number_format($user?->tokens_purchased ?? 0) }} · مصرف: {{ number_format($user?->tokens_used ?? 0) }}</small></span>
                    <span><b>دعوت از:</b> {{ $referrer ? trim(($referrer->name ?? '') . ' ' . ($referrer->last_name ?? '')) : 'ثبت‌نام مستقیم' }}</span>
                    <span><b>تاریخ عضویت:</b> {{ \App\Support\Jalali::formatNumeric($joinedAt) }}</span>
                  </div>
                  @if($user)
                    <div class="order-user-links">
                      <a href="{{ route('admin.users.logs', $user) }}"><i class="fa-solid fa-history"></i> لاگ‌های کاربر</a>
                      <a href="{{ route('admin.users.tokens', ['user_id' => $user->id]) }}"><i class="fa-solid fa-coins"></i> مدیریت اعتبار</a>
                      <a href="{{ route('admin.users.index', ['show_user' => $user->id]) }}"><i class="fa-solid fa-images"></i> خروجی‌ها</a>
                    </div>
                  @endif
                </td>
                <td><div class="order-user-name">{{ $purchase->plan_name }}</div><div class="order-meta" dir="ltr">{{ $purchase->plan?->slug ?: $purchase->plan_code }}</div></td>
                <td><strong>{{ number_format($purchase->paid_amount) }} تومان</strong><div class="order-meta">{{ number_format($purchase->granted_tokens) }} اعتبار</div></td>
                <td><div class="order-user-name">{{ $purchase->gateway === 'zarinpal' ? 'زرین‌پال' : ($purchase->gateway === 'zibal' ? 'زیبال' : ($purchase->gateway ?: '—')) }}</div><div class="order-meta" dir="ltr">{{ $purchase->gateway_reference ?: $purchase->gateway_track_id ?: '—' }}</div></td>
                <td><span class="order-badge {{ $statusClass }}"><i class="order-dot"></i>{{ \App\Models\PlanPurchase::statusLabel($purchase->status) }}</span>@if($purchase->failure_reason)<div class="order-meta" title="{{ $purchase->failure_reason }}">{{ \Illuminate\Support\Str::limit($purchase->failure_reason, 42) }}</div>@endif</td>
                <td>{{ \App\Support\Jalali::formatNumeric($purchase->verified_at ?: $purchase->initiated_at ?: $purchase->created_at) }}</td>
                <td>
                  <div class="orders-row-actions">
                    <a class="order-btn primary" href="{{ route('admin.orders.plan-purchases.show', $purchase) }}" title="مسیر مالی و جزئیات پرداخت"><i class="fa-solid fa-route"></i></a>
                    @if($user)<a class="order-btn" href="{{ route('admin.users.index', ['show_user' => $user->id]) }}" title="خروجی‌های ساخته‌شده"><i class="fa-solid fa-images"></i></a>@endif
                    @if($purchase->financeCase)
                      <a class="order-btn success" href="{{ route('admin.finance.cases.show', $purchase->financeCase) }}" title="پرونده مالی این خرید"><i class="fa-solid fa-chart-pie"></i></a>
                    @elseif($purchase->isCompleted() && (int) ($user?->finance_cases_count ?? 0) > 0)
                      <a class="order-btn" href="{{ route('admin.finance.cases.index', ['user_id' => $purchase->user_id, 'q' => $purchase->order_number]) }}" title="یافتن پرونده مالی"><i class="fa-solid fa-magnifying-glass-chart"></i></a>
                    @else
                      <button class="order-btn" type="button" disabled title="برای این خرید پرونده مالی ثبت نشده است"><i class="fa-solid fa-chart-pie"></i></button>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="9"><div class="order-empty"><i class="fa-regular fa-folder-open"></i>هنوز خرید پلنی ثبت نشده است.</div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if($purchases->hasPages())<div class="order-pagination">{{ $purchases->links() }}</div>@endif
    </div>
  </div>
</main>
@endsection

@section('scripts')
<script>
  (function () {
    const selectAll = document.getElementById('select-all-purchases');
    const exportButton = document.getElementById('export-selected-purchases');
    const exportUrl = @json(route('admin.orders.plan-purchases.export'));

    const selectedPurchases = function () {
      return Array.from(document.querySelectorAll('.purchase-select:checked')).map(function (input) { return input.value; });
    };
    const syncSelection = function () {
      const rows = Array.from(document.querySelectorAll('.purchase-select'));
      const selected = selectedPurchases();
      exportButton.disabled = selected.length === 0;
      selectAll.checked = rows.length > 0 && rows.every(function (input) { return input.checked; });
      selectAll.indeterminate = selected.length > 0 && !selectAll.checked;
    };

    selectAll?.addEventListener('change', function () {
      document.querySelectorAll('.purchase-select').forEach(function (input) { input.checked = selectAll.checked; });
      syncSelection();
    });
    document.querySelectorAll('.purchase-select').forEach(function (input) { input.addEventListener('change', syncSelection); });
    exportButton?.addEventListener('click', function () {
      const selected = selectedPurchases();
      if (!selected.length) return;
      const params = new URLSearchParams(window.location.search);
      params.delete('page');
      params.delete('ids[]');
      selected.forEach(function (purchaseId) { params.append('ids[]', purchaseId); });
      window.location.assign(exportUrl + '?' + params.toString());
    });
  }());
</script>
@endsection
