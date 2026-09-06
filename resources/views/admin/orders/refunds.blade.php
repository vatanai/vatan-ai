@extends('layouts.admin')
@section('title', 'لغو و بازپرداخت — وطن استودیو')
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/orders.css') }}">@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content orders-page p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @include('admin.orders.partials.messages')
    <div class="orders-head"><div><div class="orders-title">لغو و بازپرداخت</div><div class="orders-subtitle">سابقه قابل حسابرسی لغو سفارش‌ها و بازگشت اعتبار</div></div></div>
    @include('admin.orders.partials.stats')
    <div class="order-panel"><div class="orders-table-wrap"><table class="orders-table"><thead><tr><th>سفارش</th><th>کاربر</th><th>محصول</th><th>وضعیت</th><th>اعتبار سفارش</th><th>بازپرداخت</th><th>زمان</th><th>جزئیات</th></tr></thead><tbody>
      @forelse($orders as $order)<tr><td><span class="order-number">#{{ $order->order_number }}</span></td><td><div class="order-user-name">{{ trim(($order->user?->name ?? 'مهمان').' '.($order->user?->last_name ?? '')) }}</div></td><td>{{ $order->product?->name_fa ?? 'محصول حذف‌شده' }}</td><td>@include('admin.orders.partials.status-badge',['status'=>$order->payment_status])</td><td>{{ number_format($order->final_credits) }}</td><td><strong>{{ number_format($order->refunded_credits) }}</strong></td><td>{{ ($order->refunded_at ?? $order->cancelled_at ?? $order->updated_at)->format('Y/m/d H:i') }}</td><td><a class="order-btn" href="{{ route('admin.orders.show',$order) }}"><i class="fa-solid fa-eye"></i></a></td></tr>
      @empty<tr><td colspan="8"><div class="order-empty"><i class="fa-solid fa-arrow-rotate-left"></i>هنوز لغو یا بازپرداختی ثبت نشده است.</div></td></tr>@endforelse
    </tbody></table></div>@if($orders->hasPages())<div class="order-pagination">{{ $orders->links() }}</div>@endif</div>
  </div>
</main>
@endsection
