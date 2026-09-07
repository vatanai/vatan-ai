@isset($planDetail)
  <section class="finance-card">
    <div class="finance-card-head">
      <div>
        <div class="finance-card-title">جزئیات سود {{ $planDetail->name }}</div>
        <div class="finance-card-subtitle">کد {{ $planDetail->plan_code }} · قیمت فعلی {{ number_format($planDetail->price) }} تومان · {{ number_format($planDetail->tokens) }} اعتبار</div>
      </div>
      <a class="finance-btn secondary" href="{{ route('admin.finance.show', ['section' => 'plans', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">بازگشت به همه پلن‌ها</a>
    </div>
    <div class="finance-stat-grid finance-detail-stats">
      @foreach([
        ['تعداد خرید', $planMetrics['count'], ''],
        ['مبلغ فروش', $planMetrics['revenue'], 'تومان'],
        ['اعتبار اعطاشده', $planMetrics['credits'], ''],
        ['هزینه تقریبی مدل', $planMetrics['estimated_cost'], 'تومان'],
        ['هزینه مستقیم', $planMetrics['direct_cost'], 'تومان'],
        ['هزینه تخصیصی', $planMetrics['allocated_cost'], 'تومان'],
        ['سود', $planMetrics['profit'], 'تومان'],
        ['حاشیه سود', $planMetrics['margin'], '٪'],
      ] as [$label, $value, $unit])
        <div class="finance-stat-card {{ in_array($label, ['سود', 'حاشیه سود'], true) ? ($value < 0 ? 'tone-danger' : 'tone-success') : '' }}">
          <div><div class="finance-stat-label">{{ $label }}</div><div class="finance-stat-value">{{ number_format($value, $unit === '٪' ? 1 : 0) }} <small>{{ $unit }}</small></div></div>
        </div>
      @endforeach
    </div>
  </section>

  <div class="finance-two-column">
    <section class="finance-card">
      <div class="finance-card-head"><div><div class="finance-card-title">آخرین خریدها</div><div class="finance-card-subtitle">مقادیر ثبت‌شده در لحظه خرید</div></div></div>
      <div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>تاریخ</th><th>فروش</th><th>اعتبار</th><th>سود ثبتی</th><th>حاشیه</th></tr></thead><tbody>
        @forelse($planPurchases as $purchase)
          <tr><td>{{ $purchase->purchased_at?->format('Y/m/d H:i') }}</td><td>{{ number_format($purchase->gross_sales_toman) }}</td><td>{{ number_format($purchase->granted_credits) }}</td><td>{{ number_format($purchase->net_profit_toman) }}</td><td>{{ number_format($purchase->margin_percent, 1) }}٪</td></tr>
        @empty<tr><td colspan="5" class="finance-empty">خریدی در این بازه نیست.</td></tr>@endforelse
      </tbody></table></div>
    </section>
    <section class="finance-card">
      <div class="finance-card-head"><div><div class="finance-card-title">آخرین سفارش‌های منتسب</div><div class="finance-card-subtitle">مدل و هزینه واقعی اجرا</div></div></div>
      <div class="finance-table-wrap"><table class="finance-table"><thead><tr><th>سفارش</th><th>محصول</th><th>مدل واقعی</th><th>هزینه مستقیم</th><th>سود</th></tr></thead><tbody>
        @forelse($planOrders as $order)
          <tr><td>{{ $order->order_number ?: $order->order_id }}</td><td>{{ $order->product_name ?: 'نامشخص' }}</td><td>{{ $order->actual_model ?: $order->primary_model ?: 'نامشخص' }}</td><td>{{ number_format($order->direct_cost_toman) }}</td><td class="{{ $order->net_profit_toman < 0 ? 'finance-negative' : 'finance-positive' }}">{{ number_format($order->net_profit_toman) }}</td></tr>
        @empty<tr><td colspan="5" class="finance-empty">سفارش منتسبی در این بازه نیست.</td></tr>@endforelse
      </tbody></table></div>
    </section>
  </div>
@endisset

<section class="finance-card">
  <div class="finance-card-head">
    <div><div class="finance-card-title">اقتصاد پلن‌ها</div><div class="finance-card-subtitle">فروش و اعتبار از خریدها؛ هزینه مستقیم از اجرای سفارش‌های منتسب به همان پلن</div></div>
    <span class="finance-count">{{ number_format($rows->count()) }} پلن</span>
  </div>
  <div class="finance-table-wrap">
    <table class="finance-table">
      <thead><tr><th>پلن</th><th>تعداد خرید</th><th>فروش</th><th>اعتبار اعطاشده</th><th>هزینه تقریبی</th><th>هزینه مستقیم</th><th>هزینه تخصیصی</th><th>سود</th><th>حاشیه</th></tr></thead>
      <tbody>
        @forelse($rows as $row)
          <tr>
            <td><strong>@if($row['plan_id'])<a href="{{ route('admin.finance.plans.show', ['plan' => $row['plan_id'], 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}">{{ $row['label'] }}</a>@else{{ $row['label'] }}@endif</strong></td>
            <td>{{ number_format($row['count']) }}</td>
            <td>{{ number_format($row['revenue']) }}</td>
            <td>{{ number_format($row['credits']) }}</td>
            <td>{{ number_format($row['estimated_cost']) }}</td>
            <td>{{ number_format($row['direct_cost']) }}</td>
            <td>{{ number_format($row['allocated_cost']) }}</td>
            <td class="{{ $row['profit'] < 0 ? 'finance-negative' : 'finance-positive' }}"><strong>{{ number_format($row['profit']) }}</strong></td>
            <td><span class="finance-margin {{ $row['margin'] < 0 ? 'negative' : 'positive' }}">{{ number_format($row['margin'], 1) }}٪</span></td>
          </tr>
        @empty
          <tr><td colspan="9" class="finance-empty">هنوز خرید پلنی برای این بازه همگام نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
