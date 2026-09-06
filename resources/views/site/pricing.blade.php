@extends('layouts.app')

@section('page_title', 'خرید اشتراک — وطن')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-flow.css') }}?v={{ filemtime(public_path('css/payment-flow.css')) }}">
<link rel="stylesheet" href="{{ asset('css/pricing.css') }}?v={{ filemtime(public_path('css/pricing.css')) }}">
@endpush

@section('content')
@php
    $pricingSettings = is_array($homePricing ?? null) ? $homePricing : [];
    $pricingNotes = collect($pricingSettings['notes'] ?? [])->filter()->values();
@endphp
<section class="vp-section vp-pricing vp-pricing--proposal vp-pricing-page" aria-labelledby="pricing-page-title">
    <div class="vp-container">
        @if(session('success'))<div class="payment-flash payment-flash--success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="payment-flash payment-flash--error">{{ session('error') }}</div>@endif

        <header class="vp-section-head vp-section-head--center">
            <p class="vp-kicker">خرید اشتراک</p>
            <h1 id="pricing-page-title">{{ $pricingSettings['title'] ?? 'پلن‌ها، بر پایه اعتبار دائمی' }}</h1>
            <p>پلن مناسب خودت را انتخاب کن؛ اعتبار خریداری‌شده بدون تاریخ انقضا در حسابت می‌ماند.</p>
        </header>

        @if($pricingNotes->isNotEmpty())
            <div class="vp-proposal-note">
                @foreach($pricingNotes as $note)<span><i class="fa-solid fa-bolt vp-proposal-note__token" aria-hidden="true"></i>{{ $note }}</span>@endforeach
            </div>
        @endif

        <div class="vp-plans vp-proposal-plans">
            @forelse($plans as $plan)
                <div>@include('site.partials.pricing-proposal-card', ['plan' => $plan, 'offer' => $plan->offer, 'pricingPage' => true])</div>
            @empty
                <p class="vp-pricing__empty">در حال حاضر پلن فعالی برای نمایش وجود ندارد.</p>
            @endforelse
        </div>

        @if($plans->isNotEmpty())
            @php
                $featureNames = $plans->flatMap(fn ($plan) => collect($plan->features ?? [])->map(fn ($feature) => is_array($feature) ? ($feature['title'] ?? '') : $feature))->filter()->reject(fn ($feature) => $feature === 'پروفایل چهره')->unique()->values();
            @endphp
            <section class="pricing-comparison" aria-labelledby="pricing-comparison-title">
                <header><p class="vp-kicker">مقایسه کامل</p><h2 id="pricing-comparison-title">تفاوت پلن‌ها در یک نگاه</h2></header>
                <div class="pricing-comparison__scroll">
                    <table>
                        <thead><tr><th>قابلیت</th>@foreach($plans as $plan)<th>{{ $plan->name }}</th>@endforeach</tr></thead>
                        <tbody>
                            <tr><th>اعتبار دریافتی</th>@foreach($plans as $plan)<td>{{ number_format((int) $plan->offer['tokens'] + (int) $plan->offer['bonus_tokens']) }} اعتبار</td>@endforeach</tr>
                            <tr><th>تاریخ انقضا</th>@foreach($plans as $plan)<td>بدون تاریخ انقضا</td>@endforeach</tr>
                            <tr><th>پروفایل چهره</th>@foreach($plans as $plan)<td>{{ number_format((int) ($plan->face_profile_limit ?? 0)) }} پروفایل</td>@endforeach</tr>
                            @foreach($featureNames as $featureName)
                                <tr>
                                    <th>{{ $featureName }}</th>
                                    @foreach($plans as $plan)
                                        @php $feature = collect($plan->features ?? [])->first(fn ($item) => (is_array($item) ? ($item['title'] ?? '') : $item) === $featureName); $included = ! is_array($feature) || ($feature['included'] ?? true) !== 'no' && ($feature['included'] ?? true) !== false; @endphp
                                        <td>@if($feature && $included)<i class="fa-solid fa-check" aria-label="دارد"></i>@else<span>—</span>@endif</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <section class="pricing-policy" aria-labelledby="pricing-policy-title">
            <header class="pricing-policy__head">
                <p class="vp-kicker">قبل از خرید</p>
                <h2 id="pricing-policy-title">اعتبار وطن چطور مصرف می‌شود؟</h2>
                <p>برای انتخاب آگاهانه، این چند نکته را قبل از خرید اعتبار در نظر بگیر.</p>
            </header>
            <div class="pricing-policy__grid">
                <article>
                    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                    <h3>اعتبار، واحد مصرف سرویس‌هاست</h3>
                    <p>هر محصول و مدل، بر اساس پیچیدگی ساخت، مقدار مشخصی اعتبار مصرف می‌کند. مقدار دقیق مصرف قبل از ساخت در همان صفحه نمایش داده می‌شود.</p>
                </article>
                <article>
                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                    <h3>هر ساخت هزینه پردازشی دارد</h3>
                    <p>اعتبار هنگام شروع پردازش مصرف می‌شود؛ حتی اگر نتیجه دقیقاً مطابق انتظار نباشد یا لازم باشد نسخه دیگری بسازی. قبل از تأیید، هزینه را بررسی کن.</p>
                </article>
                <article>
                    <i class="fa-solid fa-infinity" aria-hidden="true"></i>
                    <h3>اعتبار خریداری‌شده منقضی نمی‌شود</h3>
                    <p>اعتبارهای خریداری‌شده در حساب تو بدون تاریخ انقضا باقی می‌مانند. موجودی و سابقه مصرف را همیشه از پروفایل ببین.</p>
                </article>
                <article>
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    <h3>نتیجه به مدل انتخابی وابسته است</h3>
                    <p>کیفیت، سرعت و سبک خروجی به محصول و مدل انتخابی بستگی دارد. وطن زیرساخت ساخت را فراهم می‌کند و کنترل نهایی خروجی با انتخاب توست.</p>
                </article>
            </div>
            <p class="pricing-policy__notice"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> قوانین مصرف ممکن است با اضافه‌شدن محصولات یا مدل‌های جدید به‌روزرسانی شود؛ هزینه نهایی هر ساخت را قبل از شروع مشاهده می‌کنی.</p>
        </section>
    </div>
</section>
@endsection
