<div class="order-panel">
  <div class="orders-table-wrap">
    <table class="orders-table">
      <thead><tr><th>سفارش</th><th>کاربر</th><th>محصول</th><th>سفارش</th><th>پرداخت</th><th>پردازش</th><th>اعتبار</th><th>زمان</th><th>عملیات</th></tr></thead>
      <tbody>
      @forelse($orders as $order)
        <tr>
          <td data-label="سفارش"><a class="order-number" href="{{ route('admin.orders.show',$order) }}">#{{ $order->order_number }}</a><div class="order-meta">{{ $order->source }}</div></td>
          <td data-label="کاربر"><div class="order-user"><div class="order-avatar">{{ mb_substr($order->user?->name ?? 'م',0,1) }}</div><div><div class="order-user-name">{{ trim(($order->user?->name ?? 'مهمان').' '.($order->user?->last_name ?? '')) }}</div><div class="order-meta">{{ $order->user?->phone ?: $order->user?->email }}</div></div></div></td>
          <td data-label="محصول"><div class="order-user-name">{{ $order->product?->name_fa ?? 'محصول حذف‌شده' }}</div><div class="order-meta">{{ $order->product?->product_code }}</div></td>
          <td data-label="وضعیت سفارش">@include('admin.orders.partials.status-badge',['status'=>$order->status])</td>
          <td data-label="پرداخت">@include('admin.orders.partials.status-badge',['status'=>$order->payment_status])</td>
          <td data-label="پردازش">@include('admin.orders.partials.status-badge',['status'=>$order->processing_status])</td>
          <td data-label="اعتبار"><strong>{{ number_format($order->final_credits) }}</strong><div class="order-meta">تخفیف: {{ number_format($order->discount_credits) }}</div></td>
          <td data-label="زمان">{{ $order->created_at->format('Y/m/d') }}<div class="order-meta">{{ $order->created_at->format('H:i') }}</div></td>
          <td data-label="عملیات"><a class="order-btn" href="{{ route('admin.orders.show',$order) }}" title="جزئیات"><i class="fa-solid fa-eye"></i></a></td>
        </tr>
      @empty
        <tr><td colspan="9"><div class="order-empty"><i class="fa-regular fa-folder-open"></i>سفارشی با این شرایط پیدا نشد.</div></td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if($orders->hasPages())<div class="order-pagination">{{ $orders->links() }}</div>@endif
</div>
