<div class="files-account-content" id="files-account">
  @if($isGuest ?? false)
    <section class="account-guest-card">
      <span class="account-guest-card__icon"><i class="fa-solid fa-lock"></i></span>
      <h2>حساب و پرداخت‌های تو</h2>
      <p>برای دیدن اعتبار، پلن فعال، سفارش‌ها و رسیدهای پرداخت وارد حسابت شو.</p>
      <a href="{{ route('login', ['redirect' => route('app.profile', ['tab' => 'account'])]) }}">ورود به حساب</a>
    </section>
  @else
    @php
      $currentUser = auth()->user();
      $isFreePlan = $currentUser->hasFreePlan();
      $statusClasses = [
        'completed' => 'is-success',
        'failed' => 'is-failed',
        'expired' => 'is-failed',
        'cancelled' => 'is-failed',
      ];
      $tokenSourceLabels = [
        'plan_purchase' => 'خرید اشتراک',
        'admin' => 'اصلاح توسط پشتیبانی',
        'referral_reward' => 'پاداش دعوت',
        'registration_gift' => 'هدیه ثبت‌نام',
      ];
    @endphp

    <section class="account-heading">
      <div>
        <span>حساب کاربری</span>
        <h2>پلن، اعتبار و پرداخت‌ها</h2>
      </div>
      <a href="{{ route('pricing.index') }}" class="account-heading__action">{{ $isFreePlan ? 'خرید اشتراک' : 'خرید اعتبار' }}</a>
    </section>

    <section class="account-current-plan">
      <div class="account-current-plan__title">
        <span class="account-current-plan__icon"><i class="fa-solid fa-sparkles"></i></span>
        <div><small>پلن فعال</small><strong>پلن {{ $planName }}</strong></div>
      </div>
      <div class="account-current-plan__credits"><strong>{{ number_format($tokenBalance) }}</strong><span>اعتبار قابل استفاده</span></div>
      <a href="{{ route('pricing.index') }}">مشاهده پلن‌ها <i class="fa-solid fa-arrow-left"></i></a>
    </section>

    <section class="account-stats">
      <article><strong>{{ number_format($accountSummary['successful_purchases']) }}</strong><span>خرید موفق</span></article>
      <article><strong>{{ number_format($accountSummary['purchased_tokens']) }}</strong><span>اعتبار خریداری‌شده</span></article>
      <article><strong>{{ number_format($accountSummary['promotional_tokens']) }}</strong><span>اعتبار هدیه</span></article>
      <article><strong>{{ number_format($accountSummary['total_paid']) }}</strong><span>تومان پرداخت‌شده</span></article>
    </section>

    <section class="account-section">
      <div class="account-section__heading"><div><h3>سفارش‌ها و پرداخت‌ها</h3><p>وضعیت همه تلاش‌های پرداخت و رسید خریدهای موفق</p></div><a href="{{ route('pricing.index') }}">خرید جدید</a></div>
      <div class="account-history-list">
        @forelse($planPurchases as $purchase)
          @php
            $purchaseStatusClass = $statusClasses[$purchase->status] ?? 'is-pending';
            $purchaseUrl = $purchase->isCompleted()
              ? route('payments.receipt', $purchase->order_number)
              : route('payments.result', $purchase->order_number);
          @endphp
          <a href="{{ $purchaseUrl }}" class="account-history-row">
            <span class="account-history-row__icon {{ $purchaseStatusClass }}"><i class="fa-solid {{ $purchase->isCompleted() ? 'fa-receipt' : ($purchaseStatusClass === 'is-pending' ? 'fa-clock' : 'fa-circle-xmark') }}"></i></span>
            <span class="account-history-row__copy"><strong>{{ $purchase->plan_name }}</strong><small>{{ \App\Support\Jalali::format($purchase->verified_at ?: $purchase->initiated_at ?: $purchase->created_at) }} · <b dir="ltr">{{ $purchase->order_number }}</b></small></span>
            <span class="account-history-row__meta"><b>{{ number_format($purchase->paid_amount) }} تومان</b><small class="account-status {{ $purchaseStatusClass }}">{{ \App\Models\PlanPurchase::statusLabel($purchase->status) }}</small></span>
            <i class="fa-solid fa-chevron-left account-history-row__arrow"></i>
          </a>
        @empty
          <div class="account-empty"><i class="fa-solid fa-receipt"></i><strong>هنوز خریدی ثبت نشده است</strong><p>با انتخاب پلن مناسب، سفارش و رسید پرداختت در همین بخش ثبت می‌شود.</p><a href="{{ route('pricing.index') }}">مشاهده پلن‌ها</a></div>
        @endforelse
      </div>
    </section>

    <section class="account-section">
      <div class="account-section__heading"><div><h3>گردش اعتبار</h3><p>تغییرات ثبت‌شده در موجودی حساب</p></div></div>
      <div class="account-history-list account-history-list--credits">
        @forelse($tokenLogs as $tokenLog)
          @php
            $isCredit = $tokenLog->amount >= 0;
            $sourceLabel = $tokenSourceLabels[$tokenLog->source] ?? ($tokenLog->note ?: 'تغییر اعتبار');
          @endphp
          <div class="account-history-row account-history-row--static">
            <span class="account-history-row__icon {{ $isCredit ? 'is-success' : 'is-failed' }}"><i class="fa-solid {{ $isCredit ? 'fa-plus' : 'fa-minus' }}"></i></span>
            <span class="account-history-row__copy"><strong>{{ $sourceLabel }}</strong><small>{{ \App\Support\Jalali::format($tokenLog->created_at) }}</small></span>
            <span class="account-history-row__meta"><b class="{{ $isCredit ? 'account-credit-plus' : 'account-credit-minus' }}">{{ $isCredit ? '+' : '' }}{{ number_format($tokenLog->amount) }} اعتبار</b><small>مانده: {{ number_format($tokenLog->balance_after) }}</small></span>
          </div>
        @empty
          <div class="account-empty account-empty--compact"><i class="fa-solid fa-coins"></i><strong>هنوز گردش اعتباری ثبت نشده است</strong></div>
        @endforelse
      </div>
    </section>
  @endif
</div>
