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
@include('auth.partials.auth-styles')
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

    <div class="flex gap-1 bg-[#16161c] border border-[#222230] rounded-[10px] p-1 mb-6 max-[480px]:mb-[14px]">
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
        <div class="text-xs text-[#4d7a56] text-center mb-[22px]">
          @env('local')
            با شماره موبایل و رمز عبور محلی وارد شو
          @else
            شماره موبایلت را وارد کن تا کد ۵ رقمی بفرستیم
          @endenv
        </div>

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
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="5" inputmode="numeric" autocomplete="one-time-code" name="one-time-code" pattern="[0-9۰-۹٠-٩]*" aria-label="رمز یک‌بارمصرف" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" pattern="[0-9۰-۹٠-٩]*" aria-label="رقم دوم رمز" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" pattern="[0-9۰-۹٠-٩]*" aria-label="رقم سوم رمز" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" pattern="[0-9۰-۹٠-٩]*" aria-label="رقم چهارم رمز" />
            <input class="otp-box w-12 h-[54px] bg-[#16161c] border border-[#222230] rounded-[10px] text-center text-[19px] font-bold text-white outline-none transition-colors duration-150 focus:border-[#cffe00] max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" pattern="[0-9۰-۹٠-٩]*" aria-label="رقم پنجم رمز" />
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
          <div class="grid grid-cols-2 gap-3">
          <div class="flex flex-col gap-[6px] min-w-0">
            <label class="text-[11px] font-semibold text-[#a8c4a8]">نام <span class="text-[#f05c5c]">*</span></label>
            <div class="w-full flex items-center gap-2 bg-[#16161c] border border-[#222230] rounded-[10px] px-[14px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="name-wrap">
              <i class="fa-solid fa-user text-[#4d7a56] text-[13px]"></i>
              <input class="w-full min-w-0 bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]" type="text" id="name-input" autocomplete="given-name" required aria-required="true" placeholder="مثلاً علی" />
            </div>
            <div class="hidden text-[10.5px] text-[#f05c5c]" id="name-error">لطفاً نام خود را وارد کنید</div>
          </div>

          <div class="flex flex-col gap-[6px] min-w-0">
            <label class="text-[11px] font-semibold text-[#a8c4a8]">نام خانوادگی <span class="text-[#f05c5c]">*</span></label>
            <div class="w-full flex items-center gap-2 bg-[#16161c] border border-[#222230] rounded-[10px] px-[14px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-[#cffe00]" id="lastname-wrap">
              <i class="fa-solid fa-user text-[#4d7a56] text-[13px]"></i>
              <input class="w-full min-w-0 bg-transparent border-0 outline-none text-base text-white placeholder:text-[#4d7a56]" type="text" id="lastname-input" autocomplete="family-name" required aria-required="true" placeholder="مثلاً محمدی" />
            </div>
            <div class="hidden text-[10.5px] text-[#f05c5c]" id="lastname-error">لطفاً نام خانوادگی خود را وارد کنید</div>
          </div>
          </div>

          @php
            [$currentJalaliYear] = \App\Support\Jalali::toJalaliYmd((int) now()->format('Y'), (int) now()->format('n'), (int) now()->format('j'));
            $jalaliMonths = [
              1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
              5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
              9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
            ];
          @endphp
          <div class="flex flex-col gap-[6px]">
            <label class="text-[11px] font-semibold text-[#a8c4a8]">تاریخ تولد <span class="text-[#f05c5c]">*</span></label>
            <div class="grid grid-cols-[0.8fr_1.4fr_1fr] gap-2" id="birthdate-wrap" dir="rtl">
              <input class="w-full min-w-0 bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] px-2 text-center text-sm text-white outline-none placeholder:text-[#4d7a56] focus:border-[#cffe00] transition-colors" type="text" id="birth-day-input" inputmode="numeric" maxlength="2" autocomplete="bday-day" placeholder="روز" aria-label="روز تولد" />
              <select class="birth-month-select w-full min-w-0 bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] px-2 text-center text-sm text-white outline-none focus:border-[#cffe00] transition-colors cursor-pointer" id="birth-month-input" autocomplete="bday-month" aria-label="ماه تولد">
                <option value="">ماه</option>
                @foreach($jalaliMonths as $monthNumber => $monthName)
                  <option value="{{ $monthNumber }}">{{ $monthName }}</option>
                @endforeach
              </select>
              <input class="w-full min-w-0 bg-[#16161c] border border-[#222230] rounded-[10px] h-11 max-[480px]:h-[42px] px-2 text-center text-sm text-white outline-none placeholder:text-[#4d7a56] focus:border-[#cffe00] transition-colors" type="text" id="birth-year-input" inputmode="numeric" maxlength="4" autocomplete="bday-year" placeholder="سال" aria-label="سال تولد" data-current-year="{{ $currentJalaliYear }}" />
            </div>
            <div class="hidden text-[10.5px] text-[#f05c5c]" id="birthdate-error">تاریخ تولد شمسی را کامل و صحیح وارد کنید</div>
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

@include('auth.partials.auth-scripts')

</body>
</html>
