@extends('layouts.app')

@section('page_title', 'نتیجه پرداخت — وطن')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-flow.css') }}?v={{ filemtime(public_path('css/payment-flow.css')) }}">
<link rel="stylesheet" href="{{ asset('css/payment-result-demo.css') }}?v={{ filemtime(public_path('css/payment-result-demo.css')) }}">
@endpush

@section('content')
@php
    $isSuccess = $planPurchase->isCompleted();
    $isWaiting = in_array($planPurchase->status, [\App\Models\PlanPurchase::PENDING, \App\Models\PlanPurchase::REDIRECTED, \App\Models\PlanPurchase::VERIFYING], true);
    $isDemo = $isDemo ?? false;
@endphp
<section class="payment-page payment-result-page" dir="rtl">
    @if($isDemo)
        <aside class="payment-result-demo" aria-label="نمونه نمایشی نتیجه پرداخت">
            <div><i class="fa-solid fa-flask"></i><span>نمونه نمایشی است؛ هیچ سفارش، پرداخت یا اعتباری ثبت نمی‌شود.</span></div>
            <nav aria-label="انتخاب حالت نمونه">
                <a class="{{ $isSuccess ? 'is-active' : '' }}" href="{{ route('payments.demo', ['state' => 'success']) }}">موفق</a>
                <a class="{{ $isWaiting ? 'is-active' : '' }}" href="{{ route('payments.demo', ['state' => 'pending']) }}">در انتظار</a>
                <a class="{{ ! $isSuccess && ! $isWaiting ? 'is-active' : '' }}" href="{{ route('payments.demo', ['state' => 'failed']) }}">ناموفق</a>
            </nav>
        </aside>
    @endif
    <div class="payment-result-card {{ $isSuccess ? 'is-success' : ($isWaiting ? 'is-pending' : 'is-failed') }}">
        <span class="payment-result-card__icon"><i class="fa-solid {{ $isSuccess ? 'fa-check' : ($isWaiting ? 'fa-clock' : 'fa-xmark') }}"></i></span>
        <p class="vp-kicker">نتیجه پرداخت</p>
        <h1>{{ $isSuccess ? 'خرید شما با موفقیت انجام شد' : ($isWaiting ? 'پرداخت شما در انتظار بررسی است' : 'پرداخت تکمیل نشد') }}</h1>
        <p>{{ $isSuccess ? number_format($planPurchase->granted_tokens) . ' اعتبار پلن «' . $planPurchase->plan_name . '» به حساب شما افزوده شد.' : ($planPurchase->failure_reason ?: 'پرداختی برای این سفارش تأیید نشده است. می‌توانید دوباره تلاش کنید.') }}</p>
        <dl>
            <div><dt>شماره سفارش</dt><dd dir="ltr">{{ $planPurchase->order_number }}</dd></div>
            <div><dt>پلن</dt><dd>{{ $planPurchase->plan_name }}</dd></div>
            <div><dt>مبلغ</dt><dd>{{ number_format($planPurchase->paid_amount) }} تومان</dd></div>
            <div><dt>وضعیت</dt><dd>{{ \App\Models\PlanPurchase::statusLabel($planPurchase->status) }}</dd></div>
        </dl>
        <div class="payment-result-card__actions">
            @if($isDemo)<a class="vp-button vp-button--primary" href="{{ route('pricing.index') }}"><i class="fa-solid fa-layer-group"></i> مشاهده پلن‌ها</a>@elseif($isSuccess)<a class="vp-button vp-button--primary" href="{{ route('payments.receipt', $planPurchase->order_number) }}"><i class="fa-solid fa-receipt"></i> مشاهده رسید</a>@elseif(! $isWaiting)<a class="vp-button vp-button--primary" href="{{ route('pricing.checkout', $planPurchase->plan?->slug ?: $planPurchase->plan_id) }}">تلاش دوباره</a>@endif
            <a class="vp-button vp-button--secondary" href="{{ route('app.profile', ['tab' => 'files', 'file_tab' => 'account']) }}">حساب و پرداخت‌ها</a>
        </div>
    </div>
</section>
@endsection
