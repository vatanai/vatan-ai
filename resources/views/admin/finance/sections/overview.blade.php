@php
  $cards = [
    ['key' => 'gross_sales', 'label' => 'فروش ناخالص', 'icon' => 'fa-cart-shopping', 'tone' => 'primary', 'suffix' => 'تومان'],
    ['key' => 'received', 'label' => 'دریافتی واقعی', 'icon' => 'fa-wallet', 'tone' => 'success', 'suffix' => 'تومان'],
    ['key' => 'direct_model_cost', 'label' => 'هزینه مستقیم مدل‌ها', 'icon' => 'fa-microchip', 'tone' => 'danger', 'suffix' => 'تومان'],
    ['key' => 'infrastructure_cost', 'label' => 'هزینه زیرساخت', 'icon' => 'fa-server', 'tone' => 'warning', 'suffix' => 'تومان'],
    ['key' => 'workforce_cost', 'label' => 'هزینه نیرو', 'icon' => 'fa-people-group', 'tone' => 'info', 'suffix' => 'تومان'],
    ['key' => 'gateway_cost', 'label' => 'هزینه درگاه', 'icon' => 'fa-credit-card', 'tone' => 'warning', 'suffix' => 'تومان'],
    ['key' => 'gross_profit', 'label' => 'سود ناخالص', 'icon' => 'fa-chart-line', 'tone' => ($metrics['gross_profit'] < 0 ? 'danger' : 'success'), 'suffix' => 'تومان'],
    ['key' => 'net_profit', 'label' => 'سود خالص', 'icon' => 'fa-sack-dollar', 'tone' => ($metrics['net_profit'] < 0 ? 'danger' : 'success'), 'suffix' => 'تومان'],
    ['key' => 'margin', 'label' => 'حاشیه سود', 'icon' => 'fa-percent', 'tone' => ($metrics['margin'] < 0 ? 'danger' : 'primary'), 'suffix' => '٪'],
    ['key' => 'unpaid', 'label' => 'پرداخت‌نشده', 'icon' => 'fa-clock', 'tone' => 'danger', 'suffix' => 'تومان'],
    ['key' => 'conversion', 'label' => 'نرخ تبدیل خرید پلن', 'icon' => 'fa-arrow-trend-up', 'tone' => 'info', 'suffix' => '٪'],
  ];
@endphp

<section class="finance-stat-grid">
  @foreach($cards as $card)
    <article class="finance-stat-card tone-{{ $card['tone'] }}">
      <div class="finance-stat-icon"><i class="fa-solid {{ $card['icon'] }}"></i></div>
      <div>
        <div class="finance-stat-label">{{ $card['label'] }}</div>
        <div class="finance-stat-value">{{ number_format((float) $metrics[$card['key']], str_contains((string) $metrics[$card['key']], '.') ? 2 : 0) }} <small>{{ $card['suffix'] }}</small></div>
      </div>
    </article>
  @endforeach
</section>

<div class="finance-two-column">
  <section class="finance-card">
    <div class="finance-card-head">
      <div><div class="finance-card-title">روند سود روزانه</div><div class="finance-card-subtitle">درآمد وصول‌شده منهای هزینه‌های ثبت‌شده</div></div>
      <a class="finance-link" href="{{ route('admin.finance.show', ['section' => 'reports']) }}">گزارش کامل</a>
    </div>
    <div class="finance-table-wrap">
      <table class="finance-table">
        <thead><tr><th>روز</th><th>درآمد</th><th>هزینه مستقیم</th><th>سود خالص</th><th>حاشیه</th></tr></thead>
        <tbody>
          @forelse($dailyRows as $row)
            <tr>
              <td>{{ $row['label'] }}</td>
              <td>{{ number_format($row['revenue']) }}</td>
              <td>{{ number_format($row['direct_cost']) }}</td>
              <td class="{{ $row['profit'] < 0 ? 'finance-negative' : 'finance-positive' }}">{{ number_format($row['profit']) }}</td>
              <td>{{ number_format($row['margin'], 1) }}٪</td>
            </tr>
          @empty
            <tr><td colspan="5" class="finance-empty">هنوز داده مالی در این بازه ثبت نشده است.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="finance-card">
    <div class="finance-card-head"><div><div class="finance-card-title">هشدارهای مالی</div><div class="finance-card-subtitle">موارد نیازمند بررسی مدیر مالی یا مالک</div></div></div>
    <div class="finance-alert-list">
      @forelse($alerts as $alert)
        <div class="finance-alert-row {{ $alert['level'] }}">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>{{ $alert['title'] }}</span>
          <strong>{{ number_format($alert['count']) }}</strong>
        </div>
      @empty
        <div class="finance-empty"><i class="fa-solid fa-circle-check"></i> هشدار فعالی وجود ندارد.</div>
      @endforelse
    </div>
  </section>
</div>
