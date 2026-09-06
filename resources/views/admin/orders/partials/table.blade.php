<div class="order-panel">
  <div class="orders-table-wrap">
    <table class="orders-table">
      <thead><tr><th>سفارش</th><th>کاربر</th><th>محصول</th><th>پلن / گرید</th><th>سفارش</th><th>پرداخت</th><th>پردازش</th><th>اعتبار</th><th>زمان</th><th>عملیات</th></tr></thead>
      <tbody>
      @forelse($orders as $order)
        <tr>
          <td><a class="order-number" href="{{ route('admin.orders.show',$order) }}">#{{ $order->order_number }}</a><div class="order-meta">{{ $order->source }}</div></td>
          <td>
            <div class="order-user"><div class="order-avatar">{{ mb_substr($order->user?->name ?? 'م',0,1) }}</div><div><div class="order-user-name">{{ trim(($order->user?->name ?? 'مهمان').' '.($order->user?->last_name ?? '')) }}</div><div class="order-meta" dir="ltr">ID: {{ $order->user?->id ?: '—' }}</div></div></div>
            @include('admin.users.partials.operational-snapshot', ['snapshotUser' => $order->user])
          </td>
          <td><div class="order-user-name">{{ $order->product?->name_fa ?? 'محصول حذف‌شده' }}</div><div class="order-meta">{{ $order->product?->product_code }}</div></td>
          <td><div class="order-user-name">{{ $order->plan_name ?? $order->user?->plan?->name ?? 'رایگان' }}</div><div class="order-meta">{{ $order->model_tier_name ?? 'رایگان — گرید ۴' }}</div></td>
          <td>@include('admin.orders.partials.status-badge',['status'=>$order->status])</td>
          <td>@include('admin.orders.partials.status-badge',['status'=>$order->payment_status])</td>
          <td>@include('admin.orders.partials.status-badge',['status'=>$order->processing_status])</td>
          <td><strong>{{ number_format($order->final_credits) }}</strong><div class="order-meta">تخفیف: {{ number_format($order->discount_credits) }}</div></td>
          <td>{{ \App\Support\Jalali::formatNumeric($order->created_at) }}</td>
          <td>
            <div class="orders-row-actions">
              <a class="order-btn" href="{{ route('admin.orders.show',$order) }}" title="جزئیات"><i class="fa-solid fa-eye"></i></a>
              @php($orderFinanceCase = $order->creditAllocations->first()?->financeCase)
              @if($order->user)
                <a class="order-btn" href="{{ route('admin.users.index', ['show_user' => $order->user_id]) }}" title="خروجی‌های ساخته‌شده"><i class="fa-solid fa-images"></i></a>
                <a class="order-btn" href="{{ route('admin.users.gallery.show', $order->user_id) }}" title="گالری شخصی کاربر"><i class="fa-solid fa-photo-film"></i></a>
              @else
                <button class="order-btn" type="button" disabled title="کاربر این سفارش در دسترس نیست"><i class="fa-solid fa-images"></i></button>
              @endif
              @if($orderFinanceCase || (int) ($order->user?->finance_cases_count ?? 0) > 0)
                <a class="order-btn success" href="{{ $orderFinanceCase ? route('admin.finance.cases.show', $orderFinanceCase) : route('admin.finance.cases.index', ['user_id' => $order->user_id]) }}" title="پرونده مالی"><i class="fa-solid fa-chart-pie"></i></a>
              @else
                <button class="order-btn" type="button" disabled title="برای این کاربر پرونده مالی ثبت نشده است"><i class="fa-solid fa-chart-pie"></i></button>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="10"><div class="order-empty"><i class="fa-regular fa-folder-open"></i>سفارشی با این شرایط پیدا نشد.</div></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($orders->hasPages())<div class="order-pagination">{{ $orders->links() }}</div>@endif
</div>
