@extends('layouts.admin')
@section('title', 'جزئیات سفارش — وطن استودیو')
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/orders.css') }}">@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content orders-page p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @include('admin.orders.partials.messages')
    <div class="orders-head"><div><div class="orders-title">سفارش #{{ $order->order_number }}</div><div class="orders-subtitle">ثبت‌شده در {{ $order->created_at->format('Y/m/d H:i') }}</div></div><div class="orders-actions"><a href="{{ route('admin.orders.index') }}" class="order-btn"><i class="fa-solid fa-arrow-right"></i> بازگشت</a>@include('admin.orders.partials.status-badge',['status'=>$order->status])</div></div>
    <div class="order-detail-grid">
      <div>
        <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">اطلاعات سفارش</div></div><div class="order-info-grid">
          @foreach([
            'کاربر'=>trim(($order->user?->name ?? 'مهمان').' '.($order->user?->last_name ?? '')),
            'راه ارتباطی'=>$order->user?->phone ?: $order->user?->email,
            'محصول'=>$order->product?->name_fa ?? 'محصول حذف‌شده',
            'مدل AI'=>$order->ai_model ?: 'ثبت نشده',
            'ارائه‌دهنده'=>$order->ai_provider ?: 'ثبت نشده',
            'منبع سفارش'=>$order->source,
            'اعتبار اولیه'=>number_format($order->original_credits),
            'تخفیف'=>number_format($order->discount_credits),
            'اعتبار نهایی'=>number_format($order->final_credits),
            'بازپرداخت‌شده'=>number_format($order->refunded_credits),
            'مدت صف'=>$order->queue_duration_ms ? number_format($order->queue_duration_ms).' ms' : '—',
            'مدت پردازش'=>$order->processing_duration_ms ? number_format($order->processing_duration_ms).' ms' : '—',
          ] as $key=>$value)<div class="order-info-row"><span class="order-info-key">{{ $key }}</span><span class="order-info-value">{{ $value ?: '—' }}</span></div>@endforeach
        </div></section>
        <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">وضعیت‌ها و خطای پردازش</div></div><div class="order-info-grid">
          <div class="order-info-row"><span class="order-info-key">سفارش</span><span>@include('admin.orders.partials.status-badge',['status'=>$order->status])</span></div>
          <div class="order-info-row"><span class="order-info-key">پرداخت</span><span>@include('admin.orders.partials.status-badge',['status'=>$order->payment_status])</span></div>
          <div class="order-info-row"><span class="order-info-key">پردازش</span><span>@include('admin.orders.partials.status-badge',['status'=>$order->processing_status])</span></div>
          <div class="order-info-row"><span class="order-info-key">تعداد تلاش</span><span class="order-info-value">{{ $order->attempts }}</span></div>
          @if($order->error_message)<div class="order-info-row" style="grid-column:1/-1"><span class="order-info-key">شرح خطا</span><span class="order-info-value" style="color:var(--danger)">{{ $order->error_message }}</span></div>@endif
        </div></section>
        <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">تاریخچه سفارش</div></div><div class="order-timeline">
          @forelse($order->events as $event)<div class="order-event"><div class="order-event-title">{{ $event->title }}</div>@if($event->description)<div class="order-event-desc">{{ $event->description }}</div>@endif<div class="order-event-time">{{ $event->created_at->format('Y/m/d H:i') }} · {{ $event->admin?->name ?? 'سیستم' }}</div></div>@empty<div class="order-empty">هنوز رویدادی ثبت نشده است.</div>@endforelse
        </div></section>
      </div>
      <aside>
        <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">عملیات سفارش</div></div><div class="order-form">
          @if(in_array($order->processing_status,['failed','expired','stopped']))<form method="POST" action="{{ route('admin.orders.retry',$order) }}">@csrf @method('PATCH')<button class="order-btn success" style="width:100%"><i class="fa-solid fa-rotate"></i> اجرای مجدد</button></form>@endif
          @if(!in_array($order->status,['completed','cancelled']))<form method="POST" action="{{ route('admin.orders.cancel',$order) }}" style="margin-top:10px" onsubmit="return confirm('سفارش لغو شود؟')">@csrf @method('PATCH')<div class="order-field"><label>دلیل لغو</label><textarea class="order-textarea" name="reason" required></textarea></div><button class="order-btn danger" style="width:100%"><i class="fa-solid fa-ban"></i> لغو سفارش</button></form>@endif
        </div></section>
        @if($order->user_id && $order->final_credits > $order->refunded_credits)<section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">بازپرداخت اعتبار</div></div><form class="order-form" method="POST" action="{{ route('admin.orders.refund',$order) }}" onsubmit="return confirm('اعتبار به کیف پول کاربر بازگردد؟')">@csrf @method('PATCH')<div class="order-field"><label>تعداد اعتبار (حداکثر {{ number_format($order->final_credits-$order->refunded_credits) }})</label><input class="order-input" type="number" name="credits" min="1" max="{{ $order->final_credits-$order->refunded_credits }}" required></div><div class="order-field" style="margin-top:10px"><label>دلیل بازپرداخت</label><textarea class="order-textarea" name="reason" required></textarea></div><button class="order-btn primary" style="width:100%;margin-top:10px"><i class="fa-solid fa-coins"></i> بازگرداندن اعتبار</button></form></section>@endif
        <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">یادداشت داخلی</div></div><form class="order-form" method="POST" action="{{ route('admin.orders.note',$order) }}">@csrf @method('PATCH')<textarea class="order-textarea" name="admin_note" placeholder="فقط مدیران می‌بینند...">{{ $order->admin_note }}</textarea><button class="order-btn" style="width:100%;margin-top:10px"><i class="fa-solid fa-floppy-disk"></i> ذخیره یادداشت</button></form></section>
      </aside>
    </div>
  </div>
</main>
@endsection
