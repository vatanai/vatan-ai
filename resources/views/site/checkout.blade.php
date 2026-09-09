@extends('layouts.app')

@section('page_title', 'تکمیل خرید ' . $planModel->name . ' — وطن')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/payment-flow.css') }}?v={{ filemtime(public_path('css/payment-flow.css')) }}&rev=checkout-3">
<link rel="stylesheet" href="{{ asset('css/checkout-consent.css') }}?v={{ filemtime(public_path('css/checkout-consent.css')) }}">
@endpush

@section('content')
<section class="payment-page checkout-page" dir="rtl">
    <div class="payment-page__container">
        <div class="checkout-topline">
            <a href="{{ route('pricing.index') }}" class="payment-back"><i class="fa-solid fa-arrow-right"></i> بازگشت به پلن‌ها</a>
            <span class="checkout-secure-label"><i class="fa-solid fa-shield-halved"></i> پرداخت امن و رمزگذاری‌شده</span>
        </div>
        <header class="payment-page__head">
            <p class="vp-kicker"><span class="checkout-kicker-dot"></span> تکمیل خرید</p>
            <h1>پلنت را فعال کن و شروع به ساختن کن</h1>
            <p>اطلاعات سفارش را بررسی کن، مشخصاتت را کامل کن و برای پرداخت امن وارد درگاه شو.</p>
        </header>

        <nav class="checkout-progress" aria-label="مراحل خرید">
            <span class="checkout-progress__step is-complete"><i class="fa-solid fa-check"></i><b>انتخاب پلن</b></span>
            <span class="checkout-progress__line is-active" aria-hidden="true"></span>
            <span class="checkout-progress__step is-current"><i>۲</i><b>اطلاعات و پرداخت</b></span>
            <span class="checkout-progress__line" aria-hidden="true"></span>
            <span class="checkout-progress__step"><i>۳</i><b>افزودن اعتبار</b></span>
        </nav>

        @if(session('error'))<div class="payment-flash payment-flash--error">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="payment-flash payment-flash--error">{{ $errors->first() }}</div>@endif

        <div class="checkout-grid">
            <aside class="checkout-summary">
                <div class="checkout-summary__title"><span class="checkout-summary__icon"><i class="{{ $planModel->icon ?: 'fa-solid fa-sparkles' }}"></i></span><div><span>پلن انتخاب‌شده</span><strong>{{ $planModel->name }}</strong></div><span class="checkout-summary__badge"><i class="fa-solid fa-circle-check"></i> انتخاب شما</span></div>
                <div class="checkout-summary__intro"><strong>همه‌چیز برای شروع آماده است</strong><span>اعتبار این پلن بعد از تأیید پرداخت، مستقیم به حساب شما اضافه می‌شود.</span></div>
                <dl>
                    <div><dt>اعتبار دریافتی</dt><dd>{{ number_format((int) $offer['tokens'] + (int) $offer['bonus_tokens']) }} اعتبار</dd></div>
                    @if((int) $offer['bonus_tokens'] > 0)<div><dt>اعتبار هدیه</dt><dd>{{ number_format((int) $offer['bonus_tokens']) }} اعتبار</dd></div>@endif
                    <div><dt>تاریخ انقضا</dt><dd>ندارد</dd></div>
                    @if((int) ($offer['discount_amount'] ?? 0) > 0)
                        <div><dt>مبلغ اصلی</dt><dd><s>{{ number_format((int) $offer['original_price']) }}</s> تومان</dd></div>
                        <div><dt>تخفیف معرفی</dt><dd>{{ number_format((int) $offer['discount_amount']) }} تومان</dd></div>
                    @endif
                    <div class="checkout-summary__discount" data-checkout-discount-row hidden><dt>تخفیف کد</dt><dd data-checkout-discount>۰ تومان</dd></div>
                    <div class="checkout-summary__total"><dt>مبلغ قابل پرداخت</dt><dd><span data-checkout-total>{{ number_format((int) $offer['price']) }}</span> <small>تومان</small></dd></div>
                </dl>
                <p><i class="fa-solid fa-shield-halved"></i> پرداخت فقط در صفحه امن درگاه انجام می‌شود.</p>
            </aside>

            <form class="checkout-form" method="POST" action="{{ route('pricing.start-payment', $planModel->slug ?: $planModel->id) }}" data-checkout-form>
                @csrf
                <section class="checkout-form__section">
                    <h2>اطلاعات خریدار</h2>
                    <label><span>نام و نام خانوادگی</span><input name="name" value="{{ old('name', trim(($user->name ?? '') . ' ' . ($user->last_name ?? ''))) }}" required autocomplete="name"></label>
                    <label><span>شماره همراه</span><input name="phone" value="{{ old('phone', $user->phone) }}" dir="ltr" inputmode="tel" autocomplete="tel"></label>
                    <label><span>ایمیل برای دریافت رسید <em>اختیاری</em></span><input name="email" value="{{ old('email', $user->email) }}" dir="ltr" inputmode="email" autocomplete="email"></label>
                    <div class="checkout-discount-field">
                        <label for="checkout-discount-code"><span>کد تخفیف <em>اختیاری</em></span></label>
                        <div class="checkout-discount-field__row">
                            <input id="checkout-discount-code" name="discount_code" value="{{ old('discount_code') }}" dir="ltr" maxlength="40" autocomplete="off" placeholder="کد تخفیف را وارد کن" data-checkout-discount-input>
                            <button type="button" class="checkout-discount-field__button" data-checkout-discount-button>اعمال کد</button>
                        </div>
                        <p class="checkout-discount-field__status" data-checkout-discount-status aria-live="polite"></p>
                    </div>
                </section>
                <section class="checkout-form__section">
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
                <button class="vp-button vp-button--primary checkout-submit" type="submit" @disabled(!old('terms')) aria-disabled="{{ old('terms') ? 'false' : 'true' }}" data-checkout-submit><i class="fa-solid fa-lock" data-checkout-submit-icon aria-hidden="true"></i><span class="checkout-submit__label">پرداخت <span data-checkout-submit-amount>{{ number_format((int) $offer['price']) }}</span> تومان</span></button>
                <p class="checkout-submit-note"><i class="fa-solid fa-arrow-up-right-from-square"></i> بعد از ادامه، به درگاه رسمی زرین‌پال منتقل می‌شوی و پس از پرداخت به وطن برمی‌گردی.</p>
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
    const submitIcon = form?.querySelector('[data-checkout-submit-icon]');
    const hint = form?.querySelector('[data-checkout-terms-hint]');
    const discountInput = form?.querySelector('[data-checkout-discount-input]');
    const discountButton = form?.querySelector('[data-checkout-discount-button]');
    const discountStatus = form?.querySelector('[data-checkout-discount-status]');
    const discountRow = document.querySelector('[data-checkout-discount-row]');
    const discountValue = document.querySelector('[data-checkout-discount]');
    const total = document.querySelector('[data-checkout-total]');
    const submitAmount = form?.querySelector('[data-checkout-submit-amount]');
    if (!form || !terms || !submit || !hint) return;

    const initialPrice = Number(@json((int) $offer['price']));
    const formatPrice = function (value) {
        return new Intl.NumberFormat('en-US').format(Math.max(0, Number(value) || 0));
    };
    const setPrice = function (value) {
        const formatted = formatPrice(value);
        if (total) total.textContent = formatted;
        if (submitAmount) submitAmount.textContent = formatted;
    };
    const setDiscountMessage = function (message, type) {
        if (!discountStatus) return;
        discountStatus.textContent = message || '';
        discountStatus.dataset.state = type || '';
    };

    const applyDiscount = async function () {
        const code = discountInput?.value.trim() || '';
        if (!code) {
            setPrice(initialPrice);
            if (discountRow) discountRow.hidden = true;
            setDiscountMessage('کد تخفیف را وارد کنید.', 'error');
            discountInput?.focus();
            return;
        }
        discountButton.disabled = true;
        discountButton.classList.add('is-loading');
        setDiscountMessage('در حال بررسی کد تخفیف…', 'loading');
        try {
            const response = await fetch(@json(route('pricing.checkout.discount', $planModel->slug ?: $planModel->id)), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ discount_code: code }),
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'کد تخفیف قابل استفاده نیست.');
            }
            const offer = payload.offer || {};
            setPrice(offer.price);
            if (discountRow && discountValue) {
                discountValue.textContent = `${formatPrice(offer.discount_amount_code)} تومان`;
                discountRow.hidden = false;
            }
            setDiscountMessage(payload.message || 'کد تخفیف اعمال شد.', 'success');
        } catch (error) {
            setPrice(initialPrice);
            if (discountRow) discountRow.hidden = true;
            setDiscountMessage(error.message || 'اعمال کد تخفیف انجام نشد.', 'error');
        } finally {
            discountButton.disabled = false;
            discountButton.classList.remove('is-loading');
        }
    };

    const syncTermsState = function () {
        const accepted = terms.checked;
        submit.disabled = !accepted;
        submit.setAttribute('aria-disabled', accepted ? 'false' : 'true');
        if (submitIcon) submitIcon.hidden = accepted;
        form.classList.toggle('has-accepted-terms', accepted);
        hint.textContent = accepted
            ? 'پذیرش قوانین ثبت شد؛ اکنون می‌توانید وارد درگاه امن پرداخت شوید.'
            : 'برای فعال‌شدن پرداخت، ابتدا قوانین را مطالعه و تأیید کنید.';
    };

    terms.addEventListener('change', syncTermsState);
    discountButton?.addEventListener('click', applyDiscount);
    discountInput?.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyDiscount();
        }
    });
    discountInput?.addEventListener('input', function () {
        if (discountStatus?.dataset.state === 'success') {
            setPrice(initialPrice);
            if (discountRow) discountRow.hidden = true;
        }
        setDiscountMessage('', '');
    });
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
