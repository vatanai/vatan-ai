@extends('layouts.app')

@section('page_title', 'رسید پرداخت ' . $planPurchase->order_number . ' — وطن')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-flow.css') }}?v={{ filemtime(public_path('css/payment-flow.css')) }}">
@endpush

@section('content')
<section class="payment-page receipt-page" dir="rtl">
    <article class="payment-receipt">
        <header><a href="{{ route('app.profile', ['tab' => 'account']) }}"><img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن"></a><div><span>رسید پرداخت</span><strong>وطن</strong></div><button type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> چاپ رسید</button></header>
        <div class="payment-receipt__success"><i class="fa-solid fa-circle-check"></i><span>پرداخت با موفقیت تأیید شد</span></div>
        <dl>
            <div><dt>شماره سفارش</dt><dd dir="ltr">{{ $planPurchase->order_number }}</dd></div>
            <div><dt>کد پیگیری درگاه</dt><dd dir="ltr">{{ $planPurchase->gateway_reference ?: $planPurchase->gateway_track_id }}</dd></div>
            <div><dt>نام خریدار</dt><dd>{{ $planPurchase->billing_name ?: trim(($planPurchase->user?->name ?? '') . ' ' . ($planPurchase->user?->last_name ?? '')) }}</dd></div>
            <div><dt>تاریخ پرداخت</dt><dd>{{ \App\Support\Jalali::format($planPurchase->verified_at ?: $planPurchase->purchased_at) }}</dd></div>
            <div><dt>پلن</dt><dd>{{ $planPurchase->plan_name }}</dd></div>
            <div><dt>اعتبار افزوده‌شده</dt><dd>{{ number_format($planPurchase->granted_tokens) }} اعتبار</dd></div>
            <div class="payment-receipt__amount"><dt>مبلغ پرداخت‌شده</dt><dd>{{ number_format($planPurchase->paid_amount) }} تومان</dd></div>
        </dl>
    </article>
</section>
@endsection
