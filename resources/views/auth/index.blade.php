<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>ورود | وطن استودیو</title>
@include('partials.site-icons')
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@vite(['resources/css/app.css'])
<style>
  /* افکت انیمیشن لرزش کارت در صورت خطای کد OTP */
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
  }
  .shake-effect { animation: shake 0.4s ease-in-out; }

  /* اسکرول‌بار ستون فرم لاگین هرگز و تحت هیچ شرایطی نمایش داده نشود (حتی هنگام نمایش خطا) */
  .auth-form-col {
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
  }
  .auth-form-col::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none !important;
  }

  /* حذف باکس سفید/زرد autofill مرورگر روی همه فیلدهای فرم (رمز، ایمیل، موبایل، نام و ...) */
  input:-webkit-autofill,
  input:-webkit-autofill:hover,
  input:-webkit-autofill:focus,
  input:-webkit-autofill:active {
    -webkit-box-shadow: 0 0 0px 1000px #16161c inset !important;
    box-shadow: 0 0 0px 1000px #16161c inset !important;
    -webkit-text-fill-color: #ffffff !important;
    caret-color: #ffffff !important;
    background-color: transparent !important;
    background-clip: content-box !important;
    border: 0 !important;
    outline: 0 !important;
    border-radius: 9px !important;
    transition: background-color 5000s ease-in-out 0s !important;
  }

  /* ═══ لایوت دو ستونه صفحه ورود/ثبت‌نام — با CSS خام، مستقل از کامپایل Tailwind ═══ */
  .auth-shell {
    width: 100%;
    max-width: 420px;
  }
  .auth-form-col {
    width: 100%;
  }
  .auth-brand-col {
    display: none;
  }
  @media (min-width: 768px) {
    .auth-shell {
      width: 840px;
      max-width: 840px;
      display: grid;
      grid-template-columns: 420px 420px;
      align-items: stretch;
    }
    .auth-form-col {
      width: 420px;
      height: 684px;
      min-width: 0;
    }
    .auth-brand-col {
      display: flex;
      width: 420px;
      height: 684px;
    }
  }

  /* ═══ حالت هاور/انتخاب تب‌های ثبت‌نام و ورود — CSS خام ═══ */
  .auth-tab {
    transition: background-color 0.2s ease, color 0.2s ease;
  }
  .auth-tab:hover {
    background-color: rgba(207, 254, 0, 0.12);
    color: #cffe00;
  }
  .auth-tab.active {
    background-color: rgba(207, 254, 0, 0.16);
    color: #cffe00;
  }
</style>
</head>
<body class="m-0 min-h-screen min-h-[100dvh] flex items-center justify-center p-6 max-[480px]:p-4 bg-[#0c0c10] text-white font-[IRANSansXFaNum,_sans-serif] overflow-x-hidden">

{{-- هاله درخشان سبز پس‌زمینه --}}
<div class="fixed inset-0 -z-10 bg-[#0c0c10] overflow-hidden before:content-[''] before:absolute before:rounded-full before:blur-[90px] before:opacity-[0.14] before:w-[420px] before:h-[420px] before:bg-[#cffe00] before:-top-[120px] before:-right-[100px] after:content-[''] after:absolute after:rounded-full after:blur-[90px] after:opacity-[0.14] after:w-[380px] after:h-[380px] after:bg-[#cffe00] after:-bottom-[140px] after:-left-[100px]"></div>

{{-- باکس اصلی فرم --}}
<div class="auth-shell bg-[#111116] border border-[#222230] rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.4)]">

  {{-- ستون اطلاعات / فرم (سمت راست) --}}
  <div class="auth-form-col overflow-y-auto py-7 px-7 max-[480px]:py-[18px] max-[480px]:px-4">

    <div class="flex flex-row items-center justify-center gap-3 mb-6 max-[480px]:mb-[14px]">
      <img src="{{ asset('assets/img/icon vatan.svg') }}" alt="وطن استودیو" class="h-14 w-auto max-[480px]:h-11">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن استودیو" class="h-9 w-auto max-[480px]:h-7">
    </div>

    <div class="flex bg-[#16161c] border border-[#222230] rounded-[10px] p-1 mb-6 max-[480px]:mb-[14px]">
      <div class="auth-tab active flex-1 text-center py-[9px] text-[13px] font-bold text-[#4d7a56] rounded-lg cursor-pointer select-none" id="tab-register" onclick="switchTab('register')">ثبت‌نام</div>
      <div class="auth-tab flex-1 text-center py-[9px] text-[13px] font-bold text-[#4d7a56] rounded-lg cursor-pointer select-none" id="tab-login" onclick="switchTab('login')">ورود</div>
    </div>

    <div class="relative w-full overflow-hidden transition-[height] duration-300 ease-out" id="step-stage">

      {{-- مرحله اول ثبت‌نام --}}
      <div class="auth-step active absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="reg-step-1">
        <div class="text-[17px] font-extrabold text-white mb-1 text-center">بیا شروع کنیم 👋</div>
        <div class="text-xs text-[#4d7a56] text-center mb-[22px]">با شماره موبایلت ثبت‌نام کن</div>
        <div class="flex flex-col gap-[6px] mb-4 max-[480px]:mb-[10px]">
          <label class="text-[11px] font-semibold text-[#a8c4a8]">شماره موبایل</label>
          <div class="relative flex items-center bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="reg-phone-wrap">
            <i class="fa-solid fa-mobile-screen-button text-[#4d7a56] text-[13px] absolute left-[14px] top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input class="w-full min-w-0 bg-transparent border-0 outline-none text-base text-white text-right pr-[14px] pl-[40px] placeholder:text-[#4d7a56] autofill:shadow-[0_0_0px_1000px_#16161c_inset] autofill:[-webkit-text-fill-color:#fff] autofill:[caret-color:#fff] autofill:[transition:background-color_5000s_ease-in-out_0s]" type="tel" id="reg-phone-input" name="phone" autocomplete="tel" inputmode="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" />
          </div>
          <div class="hidden text-[10.5px] text-[#f05c5c]" id="reg-phone-error">شماره موبایل معتبر نیست</div>
        </div>

        <button type="button" class="flex w-full py-3 border-0 rounded-[10px] bg-[#cffe00] text-[#04170c] text-[13.5px] font-black cursor-pointer items-center justify-center gap-2 transition-all hover:bg-[#b8e600] active:scale-[0.99]" onmousedown="event.preventDefault()" onclick="goToOtp('register')">
          <span>ادامه</span><i class="fa-solid fa-arrow-left"></i>
        </button>

        <div class="flex items-center gap-[10px] mt-[22px] mb-0 text-[#4d7a56] text-[11px] max-[480px]:mt-[18px] before:content-[''] before:flex-1 before:h-px before:bg-[#222230] after:content-[''] after:flex-1 after:h-px after:bg-[#222230]">یا با این روش‌ها</div>

        <div class="flex flex-col gap-2 mt-[18px] max-[480px]:mt-[16px] max-[480px]:flex-row max-[480px]:gap-2">
          <div class="flex items-center gap-3 w-full py-[10px] px-4 rounded-[10px] border border-[#222230] bg-[#16161c] cursor-pointer transition-all hover:border-[#2e2e3e] hover:-translate-y-px active:translate-y-0 max-[480px]:flex-1 max-[480px]:w-auto max-[480px]:flex-col max-[480px]:items-center max-[480px]:justify-center max-[480px]:gap-1 max-[480px]:py-2 max-[480px]:px-1">
            <div class="w-[30px] h-[30px] rounded-lg flex items-center justify-center text-[15px] flex-shrink-0 bg-[#29a9eb]/[0.12] max-[480px]:w-7 max-[480px]:h-7 max-[480px]:text-xs"><img src="{{ asset('assets/img/icon-telegram.svg') }}" alt="تلگرام" class="w-[18px] h-[18px] object-contain max-[480px]:w-[15px] max-[480px]:h-[15px]"></div>
            <div class="flex-1 text-right max-[480px]:flex-none max-[480px]:text-center"><div class="text-[13px] font-bold text-white flex items-center gap-2 max-[480px]:text-[10px] max-[480px]:justify-center max-[480px]:gap-0">ورود با تلگرام</div></div>
            <span class="text-[9px] font-bold py-[2px] px-[7px] rounded-md bg-[#f5923a]/[0.1] text-[#f5923a] border border-[#f5923a]/[0.25] flex-shrink-0 max-[480px]:hidden">به زودی</span>
          </div>
          <div class="flex items-center gap-3 w-full py-[10px] px-4 rounded-[10px] border border-[#222230] bg-[#16161c] cursor-pointer transition-all hover:border-[#2e2e3e] hover:-translate-y-px active:translate-y-0 max-[480px]:flex-1 max-[480px]:w-auto max-[480px]:flex-col max-[480px]:items-center max-[480px]:justify-center max-[480px]:gap-1 max-[480px]:py-2 max-[480px]:px-1">
            <div class="w-[30px] h-[30px] rounded-lg flex items-center justify-center text-[15px] flex-shrink-0 bg-[#f5923a]/[0.12] max-[480px]:w-7 max-[480px]:h-7 max-[480px]:text-xs"><img src="{{ asset('assets/img/Bale-icon.svg') }}" alt="بله" class="w-[18px] h-[18px] object-contain max-[480px]:w-[15px] max-[480px]:h-[15px]"></div>
            <div class="flex-1 text-right max-[480px]:flex-none max-[480px]:text-center"><div class="text-[13px] font-bold text-white flex items-center gap-2 max-[480px]:text-[10px] max-[480px]:justify-center max-[480px]:gap-0">ورود با بله</div></div>
            <span class="text-[9px] font-bold py-[2px] px-[7px] rounded-md bg-[#f5923a]/[0.1] text-[#f5923a] border border-[#f5923a]/[0.25] flex-shrink-0 max-[480px]:hidden">به زودی</span>
          </div>
          <div class="flex items-center gap-3 w-full py-[10px] px-4 rounded-[10px] border border-[#222230] bg-[#16161c] cursor-pointer transition-all hover:border-[#2e2e3e] hover:-translate-y-px active:translate-y-0 max-[480px]:flex-1 max-[480px]:w-auto max-[480px]:flex-col max-[480px]:items-center max-[480px]:justify-center max-[480px]:gap-1 max-[480px]:py-2 max-[480px]:px-1">
            <div class="w-[30px] h-[30px] rounded-lg flex items-center justify-center text-[15px] text-[#a8c4a8] flex-shrink-0 bg-white/5 max-[480px]:w-7 max-[480px]:h-7 max-[480px]:text-xs"><i class="fa-brands fa-google"></i></div>
            <div class="flex-1 text-right max-[480px]:flex-none max-[480px]:text-center"><div class="text-[13px] font-bold text-white flex items-center gap-2 max-[480px]:text-[10px] max-[480px]:justify-center max-[480px]:gap-0">ورود با گوگل</div></div>
            <span class="text-[9px] font-bold py-[2px] px-[7px] rounded-md bg-[#f5923a]/[0.1] text-[#f5923a] border border-[#f5923a]/[0.25] flex-shrink-0 max-[480px]:hidden">به زودی</span>
          </div>
        </div>

        <div class="text-center text-[11.5px] text-[#4d7a56] mt-[24px] max-[480px]:mt-[16px]">قبلاً ثبت‌نام کردی؟ <span class="text-[#cffe00] cursor-pointer font-bold" onclick="switchTab('login')">ورود</span></div>
      </div>

      {{-- مرحله ورود با رمز یک‌بارمصرف پیامکی --}}
      <div class="auth-step absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="login-step-1">
        <div class="text-[17px] font-extrabold text-white mb-1 text-center">خوش آمدید مجدد</div>
        <div class="text-xs text-[#4d7a56] text-center mb-[22px]">شماره موبایلت را وارد کن تا کد ۵ رقمی بفرستیم</div>

        <div class="flex flex-col gap-[6px] mb-4 max-[480px]:mb-[10px]">
          <label class="text-[11px] font-semibold text-[#a8c4a8]">شماره موبایل</label>
          <div class="relative flex items-center bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="login-phone-wrap">
            <i class="fa-solid fa-mobile-screen-button text-[#4d7a56] text-[13px] absolute left-[14px] top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input class="w-full min-w-0 bg-transparent border-0 outline-none text-base text-white text-right pr-[14px] pl-[40px] placeholder:text-[#4d7a56] autofill:shadow-[0_0_0px_1000px_#16161c_inset] autofill:[-webkit-text-fill-color:#fff] autofill:[caret-color:#fff] autofill:[transition:background-color_5000s_ease-in-out_0s]" type="tel" id="login-phone-input" name="phone" autocomplete="tel" inputmode="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" />
          </div>
          <div class="hidden text-[10.5px] text-[#f05c5c]" id="login-phone-error">شماره موبایل معتبر نیست</div>
        </div>

        <button type="button" class="flex w-full py-3 border-0 rounded-[10px] bg-[#cffe00] text-[#04170c] text-[13.5px] font-black cursor-pointer items-center justify-center gap-2 transition-all hover:bg-[#b8e600] active:scale-[0.99]" onmousedown="event.preventDefault()" onclick="goToOtp('login')">
          <span>ارسال کد ورود</span><i class="fa-solid fa-arrow-left"></i>
        </button>
        <div class="text-center text-[11.5px] text-[#4d7a56] mt-[24px] max-[480px]:mt-[16px]">حساب نداری؟ <span class="text-[#cffe00] cursor-pointer font-bold" onclick="switchTab('register')">ثبت‌نام</span></div>
      </div>

      {{-- مرحله تایید کد OTP ورود و ثبت‌نام --}}
      <div class="auth-step absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="step-otp">
        <div class="flex items-center gap-[6px] text-xs text-[#4d7a56] cursor-pointer mb-[14px] w-fit transition-colors duration-150 hover:text-white" id="otp-back"><i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت</div>
        <div class="text-[17px] font-extrabold text-white mb-1 text-center">کد تأیید رو وارد کن</div>
        <div class="text-xs text-[#4d7a56] text-center mb-[22px]">کد ۵ رقمی به <span id="otp-phone-display" dir="ltr" class="inline-block text-[#cffe00] font-bold">۰۹۱۲۳۴۵۶۷۸۹</span> ارسال شد</div>
        <div class="min-h-[70px] mb-4 max-[480px]:min-h-0 max-[480px]:mb-[10px]">
          <div class="flex gap-2 [direction:ltr] justify-center mb-3" id="otp-boxes">
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" autocomplete="one-time-code" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
          </div>
          <div class="hidden text-center text-[11.5px] text-[#f05c5c] mb-3" id="otp-error">کد وارد شده اشتباه است</div>
        </div>

        <button class="flex w-full py-3 border-0 rounded-[10px] bg-[#cffe00] text-[#04170c] text-[13.5px] font-black cursor-pointer items-center justify-center gap-2 transition-all hover:bg-[#b8e600] active:scale-[0.99]" onclick="confirmOtp()">
          <span>تأیید و ادامه</span><i class="fa-solid fa-arrow-left"></i>
        </button>

        <div class="text-center text-[11.5px] text-[#4d7a56] mt-[20px]">
          کد نرسید؟ <span class="text-[#cffe00] cursor-pointer font-bold" id="resend-link" onclick="resendOtp()">ارسال مجدد</span>
          <span id="resend-timer">(۶۰ ثانیه)</span>
        </div>
      </div>

      {{-- مرحله تعیین رمز عبور (فقط ثبت‌نام) --}}
      <div class="auth-step absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="step-password">
        <div class="flex items-center gap-[6px] text-xs text-[#4d7a56] cursor-pointer mb-[14px] w-fit transition-colors duration-150 hover:text-white" onclick="goBackFromPassword()"><i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت</div>

        <div class="text-[17px] font-extrabold text-white mb-1 text-center" id="pwd-title">تعیین رمز عبور</div>
        <div class="text-xs text-[#4d7a56] text-center mb-[22px]" id="pwd-desc">یک رمز عبور برای حساب خود انتخاب کنید</div>

        <div class="flex flex-col gap-[6px] mb-3 max-[480px]:mb-[10px]">
          <label class="text-[11px] font-semibold text-[#a8c4a8]">رمز عبور</label>
          <div class="relative flex items-center bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="password-wrap">
            <i class="fa-solid fa-lock" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#4d7a56; font-size:13px; pointer-events:none;"></i>
            <input class="w-full bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]" style="padding-right:40px; padding-left:40px;" type="password" id="password-input" placeholder="رمز عبور ۶ رقمی خود را وارد کنید" />
            <i class="fa-solid fa-eye-slash cursor-pointer transition-colors hover:text-white" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#4d7a56; font-size:13px;" id="toggle-password" onclick="togglePasswordVisibility('password-input','toggle-password')"></i>
          </div>
          <div class="hidden text-[10.5px] text-[#f05c5c]" id="password-error">رمز عبور باید حداقل ۶ کاراکتر باشد</div>
        </div>

        <div class="flex flex-col gap-[6px] mb-2 max-[480px]:mb-[8px]">
          <label class="text-[11px] font-semibold text-[#a8c4a8]">تکرار رمز عبور</label>
          <div class="relative flex items-center bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="password-confirm-wrap">
            <i class="fa-solid fa-lock" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#4d7a56; font-size:13px; pointer-events:none;"></i>
            <input class="w-full bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]" style="padding-right:40px; padding-left:40px;" type="password" id="password-confirm-input" placeholder="رمز عبور را دوباره وارد کنید" />
            <i class="fa-solid fa-eye-slash cursor-pointer transition-colors hover:text-white" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#4d7a56; font-size:13px;" id="toggle-password-confirm" onclick="togglePasswordVisibility('password-confirm-input','toggle-password-confirm')"></i>
          </div>
          <div class="hidden text-[10.5px] text-[#f05c5c]" id="password-confirm-error">رمز عبور و تکرار آن یکسان نیست</div>
        </div>

        <button class="flex w-full py-3 border-0 rounded-[10px] bg-[#cffe00] text-[#04170c] text-[13.5px] font-black cursor-pointer items-center justify-center gap-2 transition-all hover:bg-[#b8e600] active:scale-[0.99] mt-3" onclick="submitPassword()">
          <span>تأیید و ادامه</span><i class="fa-solid fa-arrow-left"></i>
        </button>
      </div>

      {{-- مرحله نهایی تکمیل پروفایل ثبت‌نام --}}
      <div class="auth-step absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="step-3">
        <div class="text-[17px] font-extrabold text-white mb-1 text-center">یه قدم مونده 🚀</div>
        <div class="text-xs text-[#4d7a56] text-center mb-[22px]">اطلاعاتت رو کامل کن</div>

        <div class="flex flex-col gap-3 mb-4 max-[480px]:mb-[10px]">
          <div class="flex flex-col gap-[6px]">
            <label class="text-[11px] font-semibold text-[#a8c4a8]">نام</label>
            <div class="w-full flex items-center gap-2 bg-[#16161c] border border-[#222230] rounded-[10px] px-[14px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="name-wrap">
              <i class="fa-solid fa-user text-[#4d7a56] text-[13px]"></i>
              <input class="w-full bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]" type="text" id="name-input" placeholder="مثلاً علی" />
            </div>
            <div class="hidden text-[10.5px] text-[#f05c5c]" id="name-error">لطفاً نام خود را وارد کنید</div>
          </div>

          <div class="flex flex-col gap-[6px]">
            <label class="text-[11px] font-semibold text-[#a8c4a8]">نام خانوادگی</label>
            <div class="w-full flex items-center gap-2 bg-[#16161c] border border-[#222230] rounded-[10px] px-[14px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="lastname-wrap">
              <i class="fa-solid fa-user text-[#4d7a56] text-[13px]"></i>
              <input class="w-full bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]" type="text" id="lastname-input" placeholder="مثلاً محمدی" />
            </div>
            <div class="hidden text-[10.5px] text-[#f05c5c]" id="lastname-error">لطفاً نام خانوادگی خود را وارد کنید</div>
          </div>

          <div class="flex flex-col gap-[6px]">
            <label class="text-[11px] font-semibold text-[#a8c4a8] flex items-center gap-1">ایمیل <span class="text-[10px] font-normal text-[#4d7a56]">(اختیاری)</span></label>
            <div class="w-full flex items-center gap-2 bg-[#16161c] border border-[#222230] rounded-[10px] px-[14px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="email-wrap">
              <i class="fa-solid fa-envelope text-[#4d7a56] text-[13px]"></i>
              <input class="w-full bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56] text-left" type="email" id="email-input" placeholder="ایمیل خود را وارد کنید" dir="ltr" />
            </div>
            <div class="hidden text-[10.5px] text-[#f05c5c]" id="email-error">لطفاً یک ایمیل معتبر وارد کنید</div>
          </div>
        </div>

        <button class="flex w-full py-3 border-0 rounded-[10px] bg-[#cffe00] text-[#04170c] text-[13.5px] font-black cursor-pointer items-center justify-center gap-2 transition-all hover:bg-[#b8e600] active:scale-[0.99]" onclick="completeProfile()">
          <span>ورود به وطن استودیو</span><i class="fa-solid fa-check"></i>
        </button>
      </div>

    </div>
  </div>

  {{-- ستون برندینگ / لوگو (سمت چپ، فقط دسکتاپ) --}}
  <div class="auth-brand-col relative flex-col items-center justify-center bg-[#0a0a0c] p-10 overflow-hidden border-r border-[#222230]">
    <div class="absolute w-[280px] h-[280px] rounded-full bg-[#cffe00] opacity-10 blur-[80px]"></div>
    <img src="{{ asset('assets/img/icon vatan.svg') }}" alt="وطن استودیو" class="relative z-[1] w-20 h-20 object-contain mb-5">
    <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن استودیو" class="relative z-[1] w-[140px] object-contain mb-4">
    <div class="relative z-[1] text-xs text-[#cffe00] text-center max-w-[220px] leading-[1.8]">وطن، ابزاری برای خلق بی‌نهایت</div>
  </div>

</div>

<script>
let mode = 'register';
let resendTimerId = null;
let otpExpired = false;
let currentPhone = '';
let otpVerificationInProgress = false;

// تشخیص کیبورد فارسی/عربی در فیلدهای رمز عبور
function isPersianKeyboardInput(value) {
  return /[؀-ۿ]/.test(value);
}

function attachPersianKeyboardGuard(inputId, wrapId, errorId, defaultErrorText) {
  const input = document.getElementById(inputId);
  const wrap = document.getElementById(wrapId);
  const errorEl = document.getElementById(errorId);
  if (!input || !wrap || !errorEl) return;
  input.addEventListener('input', () => {
    if (isPersianKeyboardInput(input.value)) {
      wrap.classList.add('border-rose-500');
      errorEl.textContent = 'به نظر می‌رسد کیبورد فارسی فعال است. لطفاً کیبورد را انگلیسی کنید و رمز را دوباره وارد کنید.';
      errorEl.classList.remove('hidden');
      updateStageHeight();
    } else if (errorEl.textContent.includes('کیبورد')) {
      wrap.classList.remove('border-rose-500');
      errorEl.classList.add('hidden');
      errorEl.textContent = defaultErrorText;
      updateStageHeight();
    }
  });
}

// ═══ تابع حیاتی برای محاسبه و به روزرسانی آنی ارتفاع کارت هنگام نمایش خطا ═══
function updateStageHeight() {
  setTimeout(() => {
    const stage = document.getElementById('step-stage');
    const activeStep = document.querySelector('.auth-step.active');
    if (stage && activeStep) {
      stage.style.height = activeStep.scrollHeight + 'px';
    }
  }, 50); // ۵۰ میلی‌ثانیه تاخیر برای رندر کامل تکستِ ارور در DOM
}

function switchTab(name) {
  mode = name;
  document.getElementById('tab-login').classList.toggle('active', name === 'login');
  document.getElementById('tab-register').classList.toggle('active', name === 'register');
  goToStep(name === 'register' ? 'reg-step-1' : 'login-step-1');
}

function goToStep(id) {
  const targetStep = document.getElementById(id);
  document.querySelectorAll('.auth-step').forEach(el => el.classList.remove('active'));
  targetStep.classList.add('active');
  updateStageHeight();
}

function validatePhone(phone) {
  return /^09\d{9}$/.test(phone);
}

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function toPersianDigits(str) {
  const fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
  return String(str).replace(/[0-9]/g, d => fa[d]);
}

// ثبت‌نام: بررسی شماره سپس رفتن به مرحله OTP
function goToOtp(fromMode) {
  const inputId = fromMode === 'login' ? 'login-phone-input' : 'reg-phone-input';
  const wrapId = fromMode === 'login' ? 'login-phone-wrap' : 'reg-phone-wrap';
  const errorId = fromMode === 'login' ? 'login-phone-error' : 'reg-phone-error';

  const input = document.getElementById(inputId);
  const wrap = document.getElementById(wrapId);
  const error = document.getElementById(errorId);
  const phone = input.value.trim();

  if (!validatePhone(phone)) {
    wrap.classList.add('border-rose-500');
    error.textContent = 'شماره موبایل معتبر نیست';
    error.classList.remove('hidden');
    updateStageHeight();
    return;
  }

  fetch('/auth/check-phone', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ phone: phone, mode: fromMode })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      wrap.classList.remove('border-rose-500');
      error.classList.add('hidden');
      currentPhone = phone;
      processOtpTransition(phone, fromMode);
    } else {
      wrap.classList.add('border-rose-500');
      error.textContent = data.message;
      error.classList.remove('hidden');
      updateStageHeight();
    }
  })
  .catch(() => {
    wrap.classList.add('border-rose-500');
    error.textContent = 'خطا در اتصال به سرور';
    error.classList.remove('hidden');
    updateStageHeight();
  });
}

function processOtpTransition(phone, fromMode) {
  fetch('/auth/send-otp', {
    method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
    body: JSON.stringify({phone: phone, purpose: fromMode})
  }).then(async res => ({ok:res.ok, data:await res.json()})).then(({ok,data}) => {
    if (!ok || data.status !== 'success') throw new Error(data.message || 'ارسال کد ناموفق بود');
    document.getElementById('otp-phone-display').textContent = toPersianDigits(phone);
    document.getElementById('otp-back').onclick = () => goToStep(fromMode === 'login' ? 'login-step-1' : 'reg-step-1');
    goToStep('step-otp');
    resetOtpBoxes();
    startResendTimer();
  }).catch(error => {
    const errorEl = document.getElementById(fromMode === 'login' ? 'login-phone-error' : 'reg-phone-error');
    errorEl.textContent = error.message; errorEl.classList.remove('hidden'); updateStageHeight();
  });
}

function goBackFromPassword() {
  goToStep('step-otp');
}

function resetOtpBoxes() {
  const boxes = document.querySelectorAll('.otp-box');
  boxes.forEach(b => { b.value = ''; b.classList.remove('border-rose-500'); });
  document.getElementById('otp-error').classList.add('hidden');
  otpVerificationInProgress = false;
  focusFirstOtpBox();
}

function focusFirstOtpBox() {
  const firstBox = document.querySelector('.otp-box');
  if (!firstBox) return;
  firstBox.focus({preventScroll: true});
  requestAnimationFrame(() => firstBox.focus({preventScroll: true}));
  setTimeout(() => firstBox.focus({preventScroll: true}), 120);
}

function startResendTimer() {
  let seconds = 60;
  otpExpired = false;
  const link = document.getElementById('resend-link');
  const timer = document.getElementById('resend-timer');
  link.style.pointerEvents = 'none';
  link.style.opacity = '0.5';
  timer.style.display = 'inline';
  timer.textContent = '(' + toPersianDigits(seconds) + ' ثانیه)';

  if (resendTimerId) clearInterval(resendTimerId);
  resendTimerId = setInterval(() => {
    seconds--;
    if (seconds <= 0) {
      clearInterval(resendTimerId);
      link.style.pointerEvents = 'auto';
      link.style.opacity = '1';
      timer.style.display = 'none';
      otpExpired = true;
    } else {
      timer.textContent = '(' + toPersianDigits(seconds) + ' ثانیه)';
    }
  }, 1000);
}

function resendOtp() {
  resetOtpBoxes();
  processOtpTransition(currentPhone, mode);
}

function showOtpError(message) {
  const errEl = document.getElementById('otp-error');
  errEl.textContent = message;
  errEl.classList.remove('hidden');

  const boxesWrap = document.getElementById('otp-boxes');
  boxesWrap.classList.remove('shake-effect');
  document.querySelectorAll('.otp-box').forEach(b => b.classList.add('border-rose-500'));
  void boxesWrap.offsetWidth;
  boxesWrap.classList.add('shake-effect');

  updateStageHeight();
}

function confirmOtp() {
  const boxes = document.querySelectorAll('.otp-box');
  const code = Array.from(boxes).map(b => b.value).join('');

  if (otpVerificationInProgress) return;

  if (code.length < boxes.length) {
    boxes.forEach(b => { if (!b.value) b.classList.add('border-rose-500'); });
    return;
  }
  if (otpExpired) {
    showOtpError('کد منقضی شده، ارسال مجدد را بزنید');
    return;
  }

  otpVerificationInProgress = true;

  fetch('/auth/verify-otp', {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
    body:JSON.stringify({phone:currentPhone,purpose:mode,code:code})
  }).then(async res => ({ok:res.ok,data:await res.json()})).then(({ok,data}) => {
    if (!ok || data.status !== 'success') throw new Error(data.message || 'کد نامعتبر است');
    if (resendTimerId) clearInterval(resendTimerId);
    if (mode === 'login') { window.location.href = data.redirect; return; }
    document.getElementById('pwd-title').textContent = 'تعیین رمز عبور';
    document.getElementById('pwd-desc').textContent = 'یک رمز عبور برای حساب خود انتخاب کنید';
    goToStep('step-password');
  }).catch(error => {
    otpVerificationInProgress = false;
    showOtpError(error.message);
    boxes.forEach(b => b.value='');
    focusFirstOtpBox();
  });
}

function togglePasswordVisibility(inputId, iconId) {
  const pwdInput = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (pwdInput.type === 'password') {
    pwdInput.type = 'text';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  } else {
    pwdInput.type = 'password';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  }
}

function submitPassword() {
  const pwdInput = document.getElementById('password-input');
  const pwdWrap = document.getElementById('password-wrap');
  const pwdError = document.getElementById('password-error');
  const confirmInput = document.getElementById('password-confirm-input');
  const confirmWrap = document.getElementById('password-confirm-wrap');
  const confirmError = document.getElementById('password-confirm-error');

  if (isPersianKeyboardInput(pwdInput.value) || isPersianKeyboardInput(confirmInput.value)) {
    pwdWrap.classList.add('border-rose-500');
    pwdError.textContent = 'به نظر می‌رسد کیبورد فارسی فعال است. لطفاً کیبورد را انگلیسی کنید و رمز را دوباره وارد کنید.';
    pwdError.classList.remove('hidden');
    updateStageHeight();
    return;
  }

  if (pwdInput.value.length < 6) {
    pwdWrap.classList.add('border-rose-500');
    pwdError.textContent = 'رمز عبور باید حداقل ۶ کاراکتر باشد';
    pwdError.classList.remove('hidden');
    updateStageHeight();
    return;
  }

  pwdWrap.classList.remove('border-rose-500');
  pwdError.classList.add('hidden');

  if (confirmInput.value !== pwdInput.value) {
    confirmWrap.classList.add('border-rose-500');
    confirmError.textContent = 'رمز عبور و تکرار آن یکسان نیست';
    confirmError.classList.remove('hidden');
    updateStageHeight();
    return;
  }

  confirmWrap.classList.remove('border-rose-500');
  confirmError.classList.add('hidden');

  // این مرحله فقط برای ثبت‌نام استفاده می‌شود؛ ورود مستقیماً از submitLogin() انجام می‌شود
  goToStep('step-3');
}

function completeProfile() {
  const nameInput = document.getElementById('name-input');
  const nameWrap = document.getElementById('name-wrap');
  const nameError = document.getElementById('name-error');
  const lastInput = document.getElementById('lastname-input');
  const lastWrap = document.getElementById('lastname-wrap');
  const lastError = document.getElementById('lastname-error');
  const emailInput = document.getElementById('email-input');
  const emailWrap = document.getElementById('email-wrap');
  const emailError = document.getElementById('email-error');
  const pwdInput = document.getElementById('password-input');
  let valid = true;

  if (!nameInput.value.trim()) {
    nameWrap.classList.add('border-rose-500');
    nameError.classList.remove('hidden');
    valid = false;
  } else {
    nameWrap.classList.remove('border-rose-500');
    nameError.classList.add('hidden');
  }

  if (!lastInput.value.trim()) {
    lastWrap.classList.add('border-rose-500');
    lastError.classList.remove('hidden');
    valid = false;
  } else {
    lastWrap.classList.remove('border-rose-500');
    lastError.classList.add('hidden');
  }

  // ایمیل اختیاری است: فقط اگر چیزی وارد شده، فرمتش چک می‌شود
  if (emailInput.value.trim() && !validateEmail(emailInput.value.trim())) {
    emailWrap.classList.add('border-rose-500');
    emailError.classList.remove('hidden');
    valid = false;
  } else {
    emailWrap.classList.remove('border-rose-500');
    emailError.classList.add('hidden');
  }

  updateStageHeight();

  if (!valid) return;

  fetch('/auth/register-submit', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({
      name: nameInput.value.trim(),
      last_name: lastInput.value.trim(),
      email: emailInput.value.trim(),
      phone: currentPhone,
      password: pwdInput.value
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      localStorage.setItem('show_welcome_modal', 'true');
      localStorage.setItem('user_first_name', data.user_name);
      window.location.href = data.redirect;
    } else {
      alert(data.message);
    }
  })
  .catch(() => alert('خطا در ذخیره‌سازی اطلاعات ثبت‌نام.'));
}

document.querySelectorAll('.otp-box').forEach((box, i, all) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/[^0-9]/g, '');
    box.classList.remove('border-rose-500');
    document.getElementById('otp-error').classList.add('hidden');
    updateStageHeight();
    if (box.value && i < all.length - 1) all[i + 1].focus();
    if (Array.from(all).every(b => b.value)) confirmOtp();
  });
  box.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !box.value && i > 0) {
      all[i - 1].focus();
      updateStageHeight();
    }
  });
});

attachPersianKeyboardGuard('password-input', 'password-wrap', 'password-error', 'رمز عبور باید حداقل ۶ کاراکتر باشد');
attachPersianKeyboardGuard('password-confirm-input', 'password-confirm-wrap', 'password-confirm-error', 'رمز عبور و تکرار آن یکسان نیست');
window.addEventListener('DOMContentLoaded', () => {
  updateStageHeight();
});
</script>

</body>
</html>
