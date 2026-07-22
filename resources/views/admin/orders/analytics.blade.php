@extends('layouts.admin')
@section('title', 'آنالیتیکس سفارشات — وطن استودیو')
@push('styles')<link rel="stylesheet" href="{{ asset('admin/css/orders.css') }}">@endpush
@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content orders-page p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl">
    <div class="orders-head"><div><div class="orders-title">آنالیتیکس سفارشات</div><div class="orders-subtitle">گزارش سبک و قابل اتکا براساس داده واقعی سفارش‌ها</div></div><form method="GET" class="orders-actions"><select class="order-select" name="days" onchange="this.form.submit()">@foreach([7=>'۷ روز',30=>'۳۰ روز',90=>'۹۰ روز',365=>'یک سال'] as $value=>$label)<option value="{{ $value }}" @selected($days===$value)>{{ $label }}</option>@endforeach</select></form></div>
    <div class="orders-stats">
      @foreach([['کل سفارش',$stats['total'],'fa-receipt'],['اعتبار فروش',number_format($stats['credits']),'fa-coins'],['نرخ موفقیت',$stats['success_rate'].'٪','fa-circle-check'],['میانگین پردازش',number_format($stats['avg_duration']/1000,1).' ثانیه','fa-stopwatch'],['نرخ بازپرداخت',$stats['refund_rate'].'٪','fa-arrow-rotate-left']] as [$label,$value,$icon])<div class="order-stat"><div class="order-stat-icon"><i class="fa-solid {{ $icon }}"></i></div><div class="order-stat-label">{{ $label }}</div><div class="order-stat-value">{{ $value }}</div></div>@endforeach
    </div>
    <div class="analytics-grid">
      <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">روند تعداد سفارش</div></div><div class="chart-area">@php($max=max(1,$chart->max('orders')))@foreach($chart as $point)<div class="chart-col" title="{{ $point['orders'] }} سفارش · {{ number_format($point['credits']) }} اعتبار"><div class="chart-bar" style="height:{{ max(2,round($point['orders']*100/$max)) }}%"></div>@if($days<=30)<div class="chart-label">{{ $point['date'] }}</div>@endif</div>@endforeach</div></section>
      <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">وضعیت پردازش</div></div><div class="order-form">@foreach(['completed'=>'موفق','processing'=>'در حال پردازش','queued'=>'در صف','failed'=>'ناموفق','retrying'=>'اجرای مجدد'] as $key=>$label)@php($count=$statuses[$key]??0)<div class="order-info-row"><span class="order-info-key">{{ $label }}</span><span class="order-info-value">{{ number_format($count) }}</span></div>@endforeach</div></section>
    </div>
    <section class="order-panel"><div class="order-panel-head"><div class="order-panel-title">محصولات برتر</div></div><div class="orders-table-wrap"><table class="orders-table" style="min-width:600px"><thead><tr><th>محصول</th><th>تعداد سفارش</th><th>اعتبار فروش</th><th>سهم از سفارش‌ها</th></tr></thead><tbody>@forelse($products as $product)<tr><td><strong>{{ $product['name'] }}</strong></td><td>{{ number_format($product['count']) }}</td><td>{{ number_format($product['credits']) }}</td><td>{{ $stats['total'] ? round($product['count']*100/$stats['total'],1) : 0 }}٪</td></tr>@empty<tr><td colspan="4"><div class="order-empty">داده‌ای در این بازه وجود ندارد.</div></td></tr>@endforelse</tbody></table></div></section>
  </div>
</main>
@endsection
