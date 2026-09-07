<section class="finance-card">
  <div class="finance-card-head">
    <div><div class="finance-card-title">سود محصولات</div><div class="finance-card-subtitle">درآمد تخصیص‌یافته بر اساس ارزش هر اعتبار در خرید پلن کاربر</div></div>
    <span class="finance-count">{{ number_format($rows->count()) }} محصول</span>
  </div>
  <div class="finance-table-wrap">
    <table class="finance-table">
      <thead><tr><th>محصول</th><th>سفارش</th><th>اعتبار مصرفی</th><th>درآمد تخصیصی</th><th>هزینه تخمینی</th><th>هزینه واقعی</th><th>سود خالص</th><th>حاشیه</th></tr></thead>
      <tbody>
        @forelse($rows as $row)
          <tr>
            <td><strong>{{ $row['label'] }}</strong></td>
            <td>{{ number_format($row['count']) }}</td>
            <td>{{ number_format($row['credits']) }}</td>
            <td>{{ number_format($row['revenue']) }}</td>
            <td>{{ number_format($row['estimated_cost']) }}</td>
            <td>{{ number_format($row['direct_cost']) }}</td>
            <td class="{{ $row['profit'] < 0 ? 'finance-negative' : 'finance-positive' }}"><strong>{{ number_format($row['profit']) }}</strong></td>
            <td><span class="finance-margin {{ $row['margin'] < 0 ? 'negative' : 'positive' }}">{{ number_format($row['margin'], 1) }}٪</span></td>
          </tr>
        @empty
          <tr><td colspan="8" class="finance-empty">هنوز سفارش مالی‌شده‌ای در این بازه وجود ندارد.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
