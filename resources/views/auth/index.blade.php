<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>ورود به پلتفرم هوش مصنوعی وطن</title>
  @include('partials.site-icons')
  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  @vite(['resources/css/app.css'])
  @include('auth.partials.auth-styles')
</head>
<body class="auth-page">
  <div class="auth-glow auth-glow-one"></div><div class="auth-glow auth-glow-two"></div>
  <main class="auth-shell">
    <div class="auth-loading" id="auth-loading" aria-live="polite" aria-hidden="true">
      <span class="auth-loading-spinner"></span><span id="auth-loading-text">در حال انجام...</span>
    </div>

    <section class="auth-form-col">
      <a href="{{ route('site.home.root') }}" class="auth-logo" aria-label="پلتفرم هوش مصنوعی وطن">
        <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt=""><img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="پلتفرم هوش مصنوعی وطن">
      </a>

      <div id="step-stage" class="step-stage">
        <div class="auth-step active" id="step-phone">
          <h1>ورود به پلتفرم هوش مصنوعی وطن</h1>
          <p class="auth-subtitle">شماره موبایلت را وارد کن؛ اگر حساب نداشته باشی همین‌جا ساخته می‌شود.</p>
          <label class="field-label" for="phone-input">شماره موبایل</label>
          <div class="field-wrap" id="phone-wrap">
            <i class="fa-solid fa-mobile-screen-button"></i>
            <input type="tel" id="phone-input" name="phone" autocomplete="tel" inputmode="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr">
          </div>
          <div class="field-error hidden" id="phone-error">شماره موبایل معتبر نیست</div>
          <button type="button" class="primary-action" id="send-code-button" onclick="sendCode()"><span>ادامه</span><i class="fa-solid fa-arrow-left"></i></button>

          <div class="social-separator"><span>یا با این روش‌ها</span></div>
          <div class="social-methods">
            <button type="button" class="social-method"><span class="social-icon telegram"><img src="{{ asset('assets/img/icon-telegram.svg') }}" alt=""></span><span>تلگرام</span><em>به زودی</em></button>
            <button type="button" class="social-method"><span class="social-icon bale"><img src="{{ asset('assets/img/Bale-icon.svg') }}" alt=""></span><span>بله</span><em>به زودی</em></button>
            <button type="button" class="social-method"><span class="social-icon google"><i class="fa-brands fa-google"></i></span><span>گوگل</span><em>به زودی</em></button>
          </div>
        </div>

        <div class="auth-step" id="step-otp">
          <button type="button" class="back-action" onclick="backToPhone()"><i class="fa-solid fa-arrow-right"></i> اصلاح شماره</button>
          <h1>کد تأیید را وارد کن</h1>
          <p class="auth-subtitle">کد ۵ رقمی به <strong id="otp-phone-display" dir="ltr"></strong> ارسال شد.</p>
          <div class="otp-boxes" id="otp-boxes" dir="ltr">
            @for($i = 1; $i <= 5; $i++)
              <input class="otp-box" type="text" maxlength="{{ $i === 1 ? 5 : 1 }}" inputmode="numeric" pattern="[0-9۰-۹٠-٩]*" @if($i === 1) autocomplete="one-time-code" name="one-time-code" @endif aria-label="رقم {{ $i }} کد تأیید">
            @endfor
          </div>
          <div class="field-error center hidden" id="otp-error">کد واردشده اشتباه است</div>
          <button type="button" class="primary-action" id="verify-button" onclick="verifyCode()"><span>تأیید و ادامه</span><i class="fa-solid fa-arrow-left"></i></button>
          <div class="resend-row">کد نرسید؟ <button type="button" id="resend-link" onclick="resendCode()" disabled>ارسال مجدد</button><span id="resend-timer"></span></div>
        </div>

        <div class="auth-step" id="step-profile">
          <h1>حسابت را کامل کن</h1>
          <p class="auth-subtitle">فقط یک قدم تا شروع ساختن فاصله داری.</p>
          <div class="name-grid">
            <div><label class="field-label" for="name-input">نام <b>*</b></label><div class="field-wrap" id="name-wrap"><i class="fa-solid fa-user"></i><input type="text" id="name-input" autocomplete="given-name" placeholder="نام"></div><div class="field-error hidden" id="name-error">نام را وارد کنید</div></div>
            <div><label class="field-label" for="lastname-input">نام خانوادگی <b>*</b></label><div class="field-wrap" id="lastname-wrap"><i class="fa-solid fa-user"></i><input type="text" id="lastname-input" autocomplete="family-name" placeholder="نام خانوادگی"></div><div class="field-error hidden" id="lastname-error">نام خانوادگی را وارد کنید</div></div>
          </div>
          @php
            [$currentJalaliYear] = \App\Support\Jalali::toJalaliYmd((int) now()->format('Y'), (int) now()->format('n'), (int) now()->format('j'));
            $jalaliMonths = [1=>'فروردین',2=>'اردیبهشت',3=>'خرداد',4=>'تیر',5=>'مرداد',6=>'شهریور',7=>'مهر',8=>'آبان',9=>'آذر',10=>'دی',11=>'بهمن',12=>'اسفند'];
          @endphp
          <label class="field-label">تاریخ تولد شمسی <b>*</b></label>
          <div class="birth-grid" id="birthdate-wrap">
            <input type="text" id="birth-day-input" inputmode="numeric" maxlength="2" placeholder="۲۲" aria-label="روز تولد" autocomplete="bday-day">
            <select id="birth-month-input"><option value="">ماه</option>@foreach($jalaliMonths as $number=>$month)<option value="{{ $number }}">{{ $month }}</option>@endforeach</select>
            <input type="text" id="birth-year-input" inputmode="numeric" maxlength="4" placeholder="۱۳۸۱" aria-label="سال تولد" autocomplete="bday-year" data-current-year="{{ $currentJalaliYear }}">
          </div>
          <div class="field-error hidden" id="birthdate-error">تاریخ تولد شمسی را کامل و صحیح وارد کنید</div>
          <label class="field-label" for="email-input">ایمیل <span>(اختیاری)</span></label>
          <div class="field-wrap" id="email-wrap"><i class="fa-solid fa-envelope"></i><input type="email" id="email-input" autocomplete="email" placeholder="example@gmail.com" dir="ltr"></div>
          <div class="field-error hidden" id="email-error">ایمیل معتبر نیست</div>
          <label class="terms-consent" for="terms-consent">
            <input type="checkbox" id="terms-consent" name="terms" value="1">
            <span class="terms-consent-box" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
            <span class="terms-consent-copy">با <a href="{{ route('privacy') }}" target="_blank" rel="noopener">قوانین و شرایط استفاده</a> و سیاست حفظ حریم خصوصی وطن موافقم.</span>
          </label>
          <div class="field-error hidden" id="terms-error">برای ورود، پذیرش قوانین و شرایط استفاده لازم است.</div>
          <button type="button" class="primary-action profile-submit" id="profile-submit-button" onclick="completeProfile()" disabled aria-disabled="true"><span>ورود به پلتفرم وطن</span><i id="profile-submit-icon" class="fa-solid fa-lock"></i></button>
        </div>
      </div>
    </section>

    <aside class="auth-brand-col">
      <div class="brand-orbit"></div><img class="brand-icon" src="{{ asset('assets/img/icon_vatan.svg') }}" alt=""><img class="brand-logo" src="{{ asset('assets/img/vatan-logo.svg') }}" alt="پلتفرم هوش مصنوعی وطن"><p>وطن، ابزاری برای خلق بی‌نهایت</p>
    </aside>
  </main>
  @include('auth.partials.auth-scripts')
</body>
</html>
