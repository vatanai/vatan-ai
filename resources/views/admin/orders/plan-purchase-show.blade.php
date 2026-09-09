@extends('layouts.admin')
@section('title', 'مسیر مالی خرید — وطن استودیو')
@push('styles')
  <link rel="stylesheet" href="{{ asset('admin/css/orders.css') }}">
@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content orders-page p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @php
      $statusClass = $planPurchase->isCompleted() ? 'success' : (in_array($planPurchase->status, [\App\Models\PlanPurchase::FAILED, \App\Models\PlanPurchase::EXPIRED, 'cancelled'], true) ? 'danger' : 'warning');
      $referral = $planPurchase->referralConversion;
    @endphp
    <div class="orders-head">
      <div>
        <div class="orders-title">مسیر مالی خرید</div>
        <div class="orders-subtitle">جزئیات یکپارچهٔ کاربر، منبع ورود، تلاش پرداخت، درگاه و پرونده مالی</div>
      </div>
      <div class="orders-actions">
        <a class="order-btn" href="{{ route('admin.orders.plan-purchases') }}"><i class="fa-solid fa-arrow-right"></i> همه خریدهای پلن</a>
        @if($planPurchase->financeCase)<a class="order-btn success" href="{{ route('admin.finance.cases.show', $planPurchase->financeCase) }}"><i class="fa-solid fa-chart-pie"></i> پرونده مالی</a>@endif
        @if($user)<a class="order-btn" href="{{ route('admin.users.index', ['show_user' => $user->id]) }}"><i class="fa-solid fa-user"></i> پرونده کاربر</a>@endif
      </div>
    </div>

    <div class="order-detail-grid">
      <div>
        <section class="order-panel">
          <div class="order-panel-head"><div class="order-panel-title"><i class="fa-solid fa-route" style="color:var(--primary);margin-left:6px"></i> مسیر زمانی</div><span class="order-badge {{ $statusClass }}"><i class="order-dot"></i>{{ \App\Models\PlanPurchase::statusLabel($planPurchase->status) }}</span></div>
          <div class="order-timeline">
            @forelse($timeline as $event)
              <div class="order-event"><div class="order-event-title">{{ $event['title'] }}</div><div class="order-event-desc">{{ $event['description'] }}</div><div class="order-event-time">{{ \App\Support\Jalali::formatNumeric($event['time']) }}</div></div>
            @empty
              <div class="order-empty">برای این خرید هنوز رویداد زمانی ثبت نشده است.</div>
            @endforelse
          </div>
        </section>

        <section class="order-panel">
          <div class="order-panel-head"><div class="order-panel-title"><i class="fa-solid fa-receipt" style="color:var(--primary);margin-left:6px"></i> اطلاعات تراکنش</div></div>
          <div class="order-info-grid">
            <div class="order-info-row"><span class="order-info-key">شماره سفارش</span><span class="order-info-value" dir="ltr">{{ $planPurchase->order_number }}</span></div>
            <div class="order-info-row"><span class="order-info-key">درگاه</span><span class="order-info-value">{{ $planPurchase->gateway ?: '—' }}</span></div>
            <div class="order-info-row"><span class="order-info-key">مبلغ نهایی</span><span class="order-info-value">{{ number_format((int) $planPurchase->paid_amount) }} تومان</span></div>
            <div class="order-info-row"><span class="order-info-key">اعتبار اعطاشده</span><span class="order-info-value">{{ number_format((int) $planPurchase->granted_tokens) }}</span></div>
            <div class="order-info-row"><span class="order-info-key">کد پیگیری درگاه</span><span class="order-info-value" dir="ltr">{{ $planPurchase->gateway_reference ?: $planPurchase->gateway_track_id ?: '—' }}</span></div>
            <div class="order-info-row"><span class="order-info-key">شناسه پرداخت</span><span class="order-info-value" dir="ltr">{{ $planPurchase->payment_reference ?: '—' }}</span></div>
            @if($planPurchase->failure_reason)<div class="order-info-row"><span class="order-info-key">علت تکمیل‌نشدن</span><span class="order-info-value">{{ $planPurchase->failure_reason }}</span></div>@endif
          </div>
        </section>
      </div>

      <aside>
        <section class="order-panel">
          <div class="order-panel-head"><div class="order-panel-title">کاربر</div></div>
          <div class="order-form">
            <div class="order-user"><span class="order-avatar">{{ mb_substr($user?->name ?: 'ک', 0, 1) }}</span><div><div class="order-user-name">{{ trim(($user?->name ?: 'کاربر') . ' ' . ($user?->last_name ?: '')) }}</div><div class="order-meta">شناسه: {{ $user?->id ?: '—' }}</div></div></div>
            <div class="order-user-details" style="margin-top:12px"><span><b>شماره:</b> {{ $user?->phone ?: '—' }}</span><span><b>ایمیل:</b> {{ $user?->email ?: '—' }}</span><span><b>پلن:</b> {{ $planPurchase->plan_name }}</span><span><b>ورود از:</b> {{ $user?->referrer ? trim(($user->referrer->name ?? '').' '.($user->referrer->last_name ?? '')) : 'ثبت‌نام مستقیم' }}</span></div>
          </div>
        </section>

        <section class="order-panel">
          <div class="order-panel-head"><div class="order-panel-title">منبع جذب و انتساب</div></div>
          <div class="order-form">
            @if($growthAttribution)
              <div class="order-source-card"><strong>{{ $growthAttribution->title ?: 'لینک رشد' }}</strong><span>{{ $growthAttribution->channel ?: 'کانال نامشخص' }}{{ $growthAttribution->campaign ? ' · '.$growthAttribution->campaign : '' }}</span><small>مرحله: {{ $growthAttribution->stage ?: 'خرید' }}</small></div>
            @elseif($referral)
              <div class="order-source-card"><strong>دعوت توسط {{ trim(($referral->inviter?->name ?: '').' '.($referral->inviter?->last_name ?: '')) ?: 'کاربر دیگر' }}</strong><span>{{ $referral->link?->slug ?: 'لینک دعوت' }}</span><small>ثبت‌نام از مسیر دعوت کاربری</small></div>
            @else
              <div class="order-empty" style="padding:18px 6px">منبع قابل انتسابی برای این خرید ثبت نشده است.</div>
            @endif
          </div>
        </section>

        <section class="order-panel order-follow-up-panel">
          <div class="order-panel-head"><div><div class="order-panel-title"><i class="fa-solid fa-list-check" style="color:var(--primary);margin-left:6px"></i> پیگیری فروش</div><div class="order-meta">اقدام‌ها را برای تیم فروش ثبت کنید؛ هیچ پیام خارجی خودکار ارسال نمی‌شود.</div></div></div>
          <div class="order-form">
            <label class="order-field-label" for="follow-up-notes">یادداشت داخلی</label>
            <textarea class="order-textarea" id="follow-up-notes" placeholder="در صورت نیاز، نتیجه تماس یا متن پیشنهاد را بنویسید..."></textarea>
            <div class="order-follow-up-list">
              @foreach($followUpTasks as $taskKey => $taskLabel)
                @php($followUp = $planPurchase->followUps->firstWhere('task_key', $taskKey))
                <form method="POST" action="{{ route('admin.orders.plan-purchases.follow-up.store', $planPurchase) }}" class="order-follow-up-item {{ $followUp?->completed_at ? 'is-done' : '' }}" id="follow-up-{{ $taskKey }}">
                  @csrf
                  <input type="hidden" name="task" value="{{ $taskKey }}">
                  <input type="hidden" name="notes" class="follow-up-notes-target">
                  <span class="order-follow-up-check"><i class="fa-solid {{ $followUp?->completed_at ? 'fa-check' : 'fa-circle' }}"></i></span>
                  <span class="order-follow-up-copy"><strong>{{ $taskLabel }}</strong>@if($followUp?->completed_at)<small>{{ $followUp->completed_at->format('Y/m/d H:i') }} · {{ $followUp->admin?->name ?: 'مدیر' }}</small>@endif</span>
                  <button type="submit" class="order-btn {{ $followUp?->completed_at ? 'success' : '' }}" @disabled($followUp?->completed_at)>{{ $followUp?->completed_at ? 'ثبت شد' : 'ثبت اقدام' }}</button>
                </form>
              @endforeach
            </div>
          </div>
        </section>

        <section class="order-panel">
          <div class="order-panel-head"><div class="order-panel-title">وضعیت پرونده مالی</div></div>
          <div class="order-form">
            @if($planPurchase->financeCase)
              <div class="order-source-card"><strong>{{ $planPurchase->financeCase->case_number }}</strong><span>{{ $planPurchase->financeCase->status ?: 'باز' }}</span><small>{{ number_format($planPurchase->financeCase->events->count()) }} رویداد مالی ثبت شده</small></div>
              <a class="order-btn success" style="width:100%;margin-top:12px" href="{{ route('admin.finance.cases.show', $planPurchase->financeCase) }}"><i class="fa-solid fa-arrow-up-right-from-square"></i> بازکردن پرونده مالی</a>
            @else
              <div class="order-empty" style="padding:18px 6px">برای این تلاش پرداخت هنوز پرونده مالی ساخته نشده است.</div>
            @endif
          </div>
        </section>
      </aside>
    </div>
  </div>
</main>
@endsection
@section('scripts')
<script>
  document.querySelectorAll('.order-follow-up-item').forEach(function (form) {
    form.addEventListener('submit', function () {
      const source = document.getElementById('follow-up-notes');
      const target = form.querySelector('.follow-up-notes-target');
      if (source && target) target.value = source.value;
    });
  });
</script>
@endsection
