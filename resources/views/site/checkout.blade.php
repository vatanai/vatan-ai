@extends('layouts.app')

@section('page_title', 'تکمیل خرید ' . $planModel->name . ' — وطن')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-flow.css') }}?v={{ filemtime(public_path('css/payment-flow.css')) }}">
<link rel="stylesheet" href="{{ asset('css/checkout-consent.css') }}?v={{ filemtime(public_path('css/checkout-consent.css')) }}">
@endpush

@section('content')
<section class="payment-page" dir="rtl">
    <div class="payment-page__container">
        <a href="{{ route('pricing.index') }}" class="payment-back"><i class="fa-solid fa-arrow-right"></i> بازگشت به پلن‌ها</a>
        <header class="payment-page__head"><p class="vp-kicker">تکمیل خرید</p><h1>جزئیات سفارش و پرداخت</h1><p>پس از تأیید درگاه، اعتبار پلن بلافاصله به حساب شما افزوده می‌شود.</p></header>

        @if(session('error'))<div class="payment-flash payment-flash--error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="payment-flash payment-flash--error">{{ $errors->first() }}</div>@endif

        <div class="checkout-grid">
            <aside class="checkout-summary">
                <div class="checkout-summary__title"><span class="checkout-summary__icon"><i class="{{ $planModel->icon ?: 'fa-solid fa-sparkles' }}"></i></span><div><span>پلن انتخاب‌شده</span><strong>{{ $planModel->name }}</strong></div></div>
                <dl>
                    <div><dt>اعتبار دریافتی</dt><dd>{{ number_format((int) $offer['tokens'] + (int) $offer['bonus_tokens']) }} اعتبار</dd></div>
                    @if((int) $offer['bonus_tokens'] > 0)<div><dt>اعتبار هدیه</dt><dd>{{ number_format((int) $offer['bonus_tokens']) }} اعتبار</dd></div>@endif
                    <div><dt>تاریخ انقضا</dt><dd>ندارد</dd></div>
                    <div class="checkout-summary__total"><dt>مبلغ قابل پرداخت</dt><dd>{{ number_format((int) $offer['price']) }} <small>تومان</small></dd></div>
                </dl>
                <p><i class="fa-solid fa-shield-halved"></i> پرداخت فقط در صفحه امن درگاه انجام می‌شود.</p>
            </aside>

            <form class="checkout-form" method="POST" action="{{ route('pricing.start-payment', $planModel->slug ?: $planModel->id) }}" data-checkout-form>
                @csrf
                <section>
                    <h2>اطلاعات خریدار</h2>
                    <label><span>نام و نام خانوادگی</span><input name="name" value="{{ old('name', trim(($user->name ?? '') . ' ' . ($user->last_name ?? ''))) }}" required autocomplete="name"></label>
                    <label><span>شماره همراه</span><input name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr" inputmode="tel" autocomplete="tel"></label>
                    <label><span>ایمیل برای دریافت رسید <em>اختیاری</em></span><input name="email" value="{{ old('email', $user->email) }}" dir="ltr" inputmode="email" autocomplete="email"></label>
                </section>
                <section>
                    <h2>روش پرداخت</h2>
                    <label class="gateway-option"><input type="radio" name="gateway" value="zarinpal" checked><span class="gateway-option__mark"><i class="fa-solid fa-credit-card"></i></span><span><b>پرداخت آنلاین زرین‌پال</b><small>قابل پرداخت با تمام کارت‌های بانکی شتاب</small></span><i class="fa-solid fa-circle-check"></i></label>
                </section>
                <div class="checkout-consent">
                    <label class="checkout-terms" for="checkout-terms">
                        <input id="checkout-terms" type="checkbox" name="terms" value="1" required @checked(old('terms')) aria-describedby="checkout-terms-hint" data-checkout-terms>
                        <span>با مطالعه و پذیرش <a href="{{ route('privacy') }}" target="_blank" rel="noopener noreferrer">قوانین، شرایط استفاده و حریم خصوصی وطن</a> موافقم.</span>
                    </label>
                    <p class="checkout-terms__hint" id="checkout-terms-hint" data-checkout-terms-hint>برای فعال‌شدن پرداخت، ابتدا قوانین را مطالعه و تأیید کنید.</p>
                </div>
                <button class="vp-button vp-button--primary checkout-submit" type="submit" @disabled(!old('terms')) aria-disabled="{{ old('terms') ? 'false' : 'true' }}" data-checkout-submit><i class="fa-solid fa-lock"></i> پرداخت {{ number_format((int) $offer['price']) }} تومان</button>
            </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.querySelector('[data-checkout-form]');
    const terms = form?.querySelector('[data-checkout-terms]');
    const submit = form?.querySelector('[data-checkout-submit]');
    const hint = form?.querySelector('[data-checkout-terms-hint]');
    if (!form || !terms || !submit || !hint) return;

    const syncTermsState = function () {
        const accepted = terms.checked;
        submit.disabled = !accepted;
        submit.setAttribute('aria-disabled', accepted ? 'false' : 'true');
        form.classList.toggle('has-accepted-terms', accepted);
        hint.textContent = accepted
            ? 'پذیرش قوانین ثبت شد؛ اکنون می‌توانید وارد درگاه امن پرداخت شوید.'
            : 'برای فعال‌شدن پرداخت، ابتدا قوانین را مطالعه و تأیید کنید.';
    };

    terms.addEventListener('change', syncTermsState);
    form.addEventListener('submit', function (event) {
        if (terms.checked) return;
        event.preventDefault();
        syncTermsState();
        terms.focus();
    });
    syncTermsState();
}());
</script>
@endpush
