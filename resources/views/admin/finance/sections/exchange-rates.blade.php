<div class="finance-two-column finance-reference-layout">
  <section class="finance-card">
    <div class="finance-card-head"><div><div class="finance-card-title">نرخ روزانه دلار</div><div class="finance-card-subtitle">هر تراکنش دلاری یک نسخه از نرخ همان لحظه نگه می‌دارد.</div></div></div>
    @if($canWrite)
      <form class="finance-form" method="post" action="{{ route('admin.finance.exchange-rates.store') }}">
        @csrf
        <div class="finance-form-grid">
          <label class="finance-field"><span>تاریخ نرخ</span><input class="finance-input" type="date" name="rate_date" value="{{ old('rate_date', now()->toDateString()) }}" required></label>
          <label class="finance-field"><span>هر دلار به تومان</span><input class="finance-input" type="number" min="1" step="0.0001" name="rate_to_toman" value="{{ old('rate_to_toman', $latest?->rate_to_toman) }}" required></label>
          <label class="finance-field finance-span-2"><span>منبع</span><input class="finance-input" name="source" value="{{ old('source', 'ثبت دستی مدیر مالی') }}"></label>
        </div>
        <div class="finance-form-actions"><button class="finance-btn primary"><i class="fa-solid fa-dollar-sign"></i> ذخیره نرخ روز</button></div>
      </form>
    @else
      <div class="finance-readonly-note">حساب شما فقط دسترسی مشاهده نرخ‌ها را دارد.</div>
    @endif
  </section>
  <section class="finance-card finance-rate-summary">
    <div class="finance-card-head"><div class="finance-card-title">آخرین وضعیت</div></div>
    <div class="finance-rate-value">{{ $latest ? number_format((float) $latest->rate_to_toman) : '—' }} <small>تومان</small></div>
    <div class="finance-rate-meta">{{ $latest?->rate_date?->format('Y/m/d') ?: 'بدون نرخ ثبت‌شده' }} · {{ $latest?->source }}</div>
    <div class="finance-rate-jump {{ $jump < 0 ? 'down' : 'up' }}">{{ $jump >= 0 ? '+' : '' }}{{ number_format($jump, 2) }}٪ نسبت به نرخ قبل</div>
  </section>
</div>

<section class="finance-card">
  <div class="finance-card-head"><div class="finance-card-title">تاریخچه نرخ‌ها</div><span class="finance-count">{{ number_format($rates->total()) }} روز</span></div>
  <div class="finance-table-wrap">
    <table class="finance-table"><thead><tr><th>تاریخ</th><th>ارز</th><th>نرخ به تومان</th><th>منبع</th><th>نوع</th><th>ثبت‌کننده</th></tr></thead><tbody>
      @forelse($rates as $rate)<tr><td>{{ $rate->rate_date->format('Y/m/d') }}</td><td>{{ $rate->currency }}</td><td><strong>{{ number_format((float) $rate->rate_to_toman) }}</strong></td><td>{{ $rate->source ?: '—' }}</td><td><span class="finance-source {{ $rate->is_manual ? 'manual' : 'automatic' }}">{{ $rate->is_manual ? 'دستی' : 'خودکار' }}</span></td><td>{{ $rate->creator?->name ?: 'سیستم' }}</td></tr>
      @empty<tr><td colspan="6" class="finance-empty">نرخی ثبت نشده است.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="finance-pagination">{{ $rates->links() }}</div>
</section>
