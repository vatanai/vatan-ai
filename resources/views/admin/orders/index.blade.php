@extends('layouts.admin')
@section('title', 'مدیریت سفارشات — وطن استودیو')
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/orders.css') }}">@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content orders-page p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    @include('admin.orders.partials.messages')
    <div class="orders-head">
      <div><div class="orders-title">{{ $view === 'processing' ? 'سفارش‌های در حال پردازش' : ($view === 'failed' ? 'ناموفق و نیازمند بررسی' : 'همه سفارشات') }}</div><div class="orders-subtitle">پیگیری یکپارچه سفارش، پرداخت و اجرای هوش مصنوعی</div></div>
      <div class="orders-actions"><a class="order-btn" href="{{ url()->current() }}"><i class="fa-solid fa-rotate-right"></i> بروزرسانی</a><a class="order-btn" href="{{ route('admin.orders.analytics') }}"><i class="fa-solid fa-chart-line"></i> گزارش‌ها</a></div>
    </div>
    @include('admin.orders.partials.stats')
    @include('admin.orders.partials.filters')
    @include('admin.orders.partials.table')
  </div>
</main>
@endsection
