<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>ورود | وطن استودیو</title>
<link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
@vite(['resources/css/app.css'])
</head>
<body class="m-0 min-h-screen min-h-[100dvh] flex items-center justify-center p-6 max-[480px]:p-4 bg-bg text-watan-text font-[IRANSansXFaNum,_sans-serif]">

<div class="fixed inset-0 -z-10 bg-bg overflow-hidden before:content-[''] before:absolute before:rounded-full before:blur-[90px] before:opacity-[0.14] before:w-[420px] before:h-[420px] before:bg-green before:-top-[120px] before:-right-[100px] after:content-[''] after:absolute after:rounded-full after:blur-[90px] after:opacity-[0.14] after:w-[380px] after:h-[380px] after:bg-green after:-bottom-[140px] after:-left-[100px]"></div>

<div class="w-full max-w-[420px] md:max-w-[920px] max-h-full md:flex md:items-stretch bg-s1 border border-b1 rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.4)]">

  <div class="w-full md:w-[420px] md:flex-shrink-0 max-h-full overflow-y-auto py-8 px-7 max-[480px]:py-[18px] max-[480px]:px-4">

  <div class="flex flex-col items-center gap-2 mb-6 max-[480px]:mb-[14px] max-[480px]:gap-[6px]">
    <img src="{{ asset('assets/img/icon vatan.svg') }}" alt="وطن استودیو" class="h-14 w-auto max-[480px]:h-11">
    <div class="text-base font-extrabold text-watan-text tracking-[-0.3px]">وطن استودیو</div>
  </div>

  <div class="flex bg-s2 border border-b1 rounded-[10px] p-1 mb-6 max-[480px]:mb-[14px]">
    <div class="auth-tab active flex-1 text-center py-[9px] text-[13px] font-bold text-watan-text3 rounded-lg cursor-pointer select-none transition-colors duration-200" id="tab-register" onclick="switchTab('register')">ثبت‌نام</div>
    <div class="auth-tab flex-1 text-center py-[9px] text-[13px] font-bold text-watan-text3 rounded-lg cursor-pointer select-none transition-colors duration-200" id="tab-login" onclick="switchTab('login')">ورود</div>
  </div>

  <div class="relative w-full overflow-hidden transition-[height] duration-[350ms]" id="step-stage">

    <div class="auth-step active absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s]" id="reg-step-1">
      <div class="text-[17px] font-extrabold text-watan-text mb-1 text-center">بیا شروع کنیم 👋</div>
      <div class="text-xs text-watan-text3 text-center mb-[22px]">با شماره موبایلت ثبت‌نام کن</div>
      <div class="flex flex-col gap-[6px] mb-4 max-[480px]:mb-[10px]">
        <label class="text-[11px] font-semibold text-watan-text2">موبایل یا ایمیل</label>
        <div class="auth-input-wrap relative flex items-center bg-s2 border border-b1 rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-green" id="reg-phone-wrap">
          <i class="fa-solid fa-mobile-screen-button text-watan-text3 text-[13px] absolute left-[14px] top-1/2 -translate-y-1/2 pointer-events-none"></i>
          <input class="auth-input w-full min-w-0 bg-transparent border-0 outline-none text-base text-watan-text text-right pr-[14px] pl-[40px] placeholder:text-watan-text3 autofill:shadow-[0_0_0px_1000px_var(--s2)_inset] autofill:[-webkit-text-fill-color:var(--text)] autofill:[caret-color:var(--text)] autofill:[transition:background-color_5000s_ease-in-out_0s]" type="text" id="reg-phone-input" placeholder="موبایل یا ایمیل" dir="ltr" />
        </div>
        <div class="field-error hidden text-[10.5px] text-red" id="reg-phone-error">شماره موبایل معتبر نیست</div>
      </div>
      <button class="flex w-full py-3 border-0 rounded-[10px] bg-green text-white text-[13.5px] font-bold cursor-pointer items-center justify-center gap-2 [transition:background-color_0.15s,transform_0.1s] hover:bg-[#09a447] active:scale-[0.99]" onclick="goToOtp('register')">
        <span>ادامه</span><i class="fa-solid fa-arrow-left"></i>
      </button>
      <div class="flex items-center gap-[10px] my-[18px] text-watan-text3 text-[11px] max-[480px]:my-[10px] before:content-[''] before:flex-1 before:h-px before:bg-b1 after:content-[''] after:flex-1 after:h-px after:bg-b1">یا با این روش‌ها</div>
      <div class="flex flex-col gap-[10px] mt-[18px] max-[480px]:mt-[10px] max-[480px]:flex-row max-[480px]:gap-2">
        <div class="flex items-center gap-3 w-full py-3 px-4 rounded-[10px] border border-b1 bg-s2 cursor-pointer [transition:border-color_0.15s,background-color_0.15s,transform_0.1s] hover:border-b2 hover:-translate-y-px active:translate-y-0 max-[480px]:flex-1 max-[480px]:w-auto max-[480px]:flex-col max-[480px]:items-center max-[480px]:justify-center max-[480px]:gap-1 max-[480px]:py-2 max-[480px]:px-1">
          <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-[15px] flex-shrink-0 bg-[#29a9eb]/[0.12] max-[480px]:w-7 max-[480px]:h-7 max-[480px]:text-xs"><img src="{{ asset('assets/img/icon-telegram.svg') }}" alt="تلگرام" class="w-[18px] h-[18px] object-contain max-[480px]:w-[15px] max-[480px]:h-[15px]"></div>
          <div class="flex-1 text-right max-[480px]:flex-none max-[480px]:text-center"><div class="text-[13px] font-bold text-watan-text flex items-center gap-2 max-[480px]:text-[10px] max-[480px]:justify-center max-[480px]:gap-0">ورود با تلگرام</div></div>
          <span class="text-[9px] font-bold py-[2px] px-[7px] rounded-md bg-orange/[0.1] text-orange border border-orange/[0.25] flex-shrink-0 max-[480px]:hidden">به زودی</span>
        </div>
        <div class="flex items-center gap-3 w-full py-3 px-4 rounded-[10px] border border-b1 bg-s2 cursor-pointer [transition:border-color_0.15s,background-color_0.15s,transform_0.1s] hover:border-b2 hover:-translate-y-px active:translate-y-0 max-[480px]:flex-1 max-[480px]:w-auto max-[480px]:flex-col max-[480px]:items-center max-[480px]:justify-center max-[480px]:gap-1 max-[480px]:py-2 max-[480px]:px-1">
          <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-[15px] flex-shrink-0 bg-orange/[0.12] max-[480px]:w-7 max-[480px]:h-7 max-[480px]:text-xs"><img src="{{ asset('assets/img/Bale-icon.svg') }}" alt="بله" class="w-[18px] h-[18px] object-contain max-[480px]:w-[15px] max-[480px]:h-[15px]"></div>
          <div class="flex-1 text-right max-[480px]:flex-none max-[480px]:text-center"><div class="text-[13px] font-bold text-watan-text flex items-center gap-2 max-[480px]:text-[10px] max-[480px]:justify-center max-[480px]:gap-0">ورود با بله</div></div>
          <span class="text-[9px] font-bold py-[2px] px-[7px] rounded-md bg-orange/[0.1] text-orange border border-orange/[0.25] flex-shrink-0 max-[480px]:hidden">به زودی</span>
        </div>
        <div class="flex items-center gap-3 w-full py-3 px-4 rounded-[10px] border border-b1 bg-s2 cursor-pointer [transition:border-color_0.15s,background-color_0.15s,transform_0.1s] hover:border-b2 hover:-translate-y-px active:translate-y-0 max-[480px]:flex-1 max-[480px]:w-auto max-[480px]:flex-col max-[480px]:items-center max-[480px]:justify-center max-[480px]:gap-1 max-[480px]:py-2 max-[480px]:px-1">
          <div class="w-[34px] h-[34px] rounded-lg flex items-center justify-center text-[15px] text-watan-text2 flex-shrink-0 bg-s3 max-[480px]:w-7 max-[480px]:h-7 max-[480px]:text-xs"><i class="fa-brands fa-google"></i></div>
          <div class="flex-1 text-right max-[480px]:flex-none max-[480px]:text-center"><div class="text-[13px] font-bold text-watan-text flex items-center gap-2 max-[480px]:text-[10px] max-[480px]:justify-center max-[480px]:gap-0">ورود با گوگل</div></div>
          <span class="text-[9px] font-bold py-[2px] px-[7px] rounded-md bg-orange/[0.1] text-orange border border-orange/[0.25] flex-shrink-0 max-[480px]:hidden">به زودی</span>
        </div>
      </div>
      <div class="text-center text-[11.5px] text-watan-text3 mt-[18px] max-[480px]:mt-[10px]">قبلاً ثبت‌نام کردی؟ <span class="link" onclick="switchTab('login')">ورود</span></div>
      <div class="mt-[14px] text-center text-[10.5px] text-watan-text3 leading-[1.8] max-[480px]:mt-2 max-[480px]:leading-[1.5]">با ادامه، <a href="#" class="text-green no-underline">قوانین و مقررات</a> و <a href="#" class="text-green no-underline">حریم خصوصی</a> وطن استودیو رو می‌پذیری.</div>
    </div>

    <div class="auth-step absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s]" id="login-step-1">
      <div class="text-[17px] font-extrabold text-watan-text mb-1 text-center">ادامه بدیم</div>
      <div class="text-xs text-watan-text3 text-center mb-[22px]">شماره موبایلت رو وارد کن</div>
      <div class="flex flex-col gap-[6px] mb-4 max-[480px]:mb-[10px]">
        <label class="text-[11px] font-semibold text-watan-text2">موبایل یا ایمیل</label>
        <div class="auth-input-wrap relative flex items-center bg-s2 border border-b1 rounded-[10px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-green" id="login-phone-wrap">
          <i class="fa-solid fa-mobile-screen-button text-watan-text3 text-[13px] absolute left-[14px] top-1/2 -translate-y-1/2 pointer-events-none"></i>
          <input class="auth-input w-full min-w-0 bg-transparent border-0 outline-none text-base text-watan-text text-right pr-[14px] pl-[40px] placeholder:text-watan-text3 autofill:shadow-[0_0_0px_1000px_var(--s2)_inset] autofill:[-webkit-text-fill-color:var(--text)] autofill:[caret-color:var(--text)] autofill:[transition:background-color_5000s_ease-in-out_0s]" type="text" id="login-phone-input" placeholder="موبایل یا ایمیل" dir="ltr" />
        </div>
        <div class="field-error hidden text-[10.5px] text-red" id="login-phone-error">شماره موبایل معتبر نیست</div>
      </div>
      <button class="flex w-full py-3 border-0 rounded-[10px] bg-green text-white text-[13.5px] font-bold cursor-pointer items-center justify-center gap-2 [transition:background-color_0.15s,transform_0.1s] hover:bg-[#09a447] active:scale-[0.99]" onclick="goToOtp('login')">
        <span>ادامه</span><i class="fa-solid fa-arrow-left"></i>
      </button>
      <div class="text-center text-[11.5px] text-watan-text3 mt-[18px] max-[480px]:mt-[10px]">حساب نداری؟ <span class="link" onclick="switchTab('register')">ثبت‌نام</span></div>
      <div class="mt-[14px] text-center text-[10.5px] text-watan-text3 leading-[1.8] max-[480px]:mt-2 max-[480px]:leading-[1.5]">با ادامه، <a href="#" class="text-green no-underline">قوانین و مقررات</a> و <a href="#" class="text-green no-underline">حریم خصوصی</a> وطن استودیو رو می‌پذیری.</div>
    </div>

    <div class="auth-step absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s]" id="step-otp">
      <div class="flex items-center gap-[6px] text-xs text-watan-text2 cursor-pointer mb-[14px] w-fit transition-colors duration-150 hover:text-watan-text" id="otp-back"><i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت</div>
      <div class="text-[17px] font-extrabold text-watan-text mb-1 text-center">کد تأیید رو وارد کن</div>
      <div class="text-xs text-watan-text3 text-center mb-[22px]">کد ۵ رقمی به <span id="otp-phone-display" dir="ltr" class="inline-block">۰۹۱۲۳۴۵۶۷۸۹</span> ارسال شد</div>
      <div class="min-h-[70px] mb-4 max-[480px]:min-h-0 max-[480px]:mb-[10px]">
        <div class="otp-boxes flex gap-2 [direction:ltr] justify-center mb-3" id="otp-boxes">
          <input class="otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none transition-colors duration-150 focus:border-green max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
          <input class="otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none transition-colors duration-150 focus:border-green max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
          <input class="otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none transition-colors duration-150 focus:border-green max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
          <input class="otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none transition-colors duration-150 focus:border-green max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
          <input class="otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none transition-colors duration-150 focus:border-green max-[420px]:w-10 max-[420px]:h-12 max-[420px]:text-[17px]" type="text" maxlength="1" inputmode="numeric" />
        </div>
        <div class="otp-error hidden text-center text-[11.5px] text-red mb-3" id="otp-error">کد وارد شده اشتباهه، دوباره امتحان کن</div>
      </div>
      <button class="flex w-full py-3 border-0 rounded-[10px] bg-green text-white text-[13.5px] font-bold cursor-pointer items-center justify-center gap-2 [transition:background-color_0.15s,transform_0.1s] hover:bg-[#09a447] active:scale-[0.99]" onclick="confirmOtp()">
        <span>ورود</span><i class="fa-solid fa-check"></i>
      </button>
      <div class="text-center text-[11.5px] text-watan-text3 mb-1 mt-[14px]">
        کد نرسید؟ <span class="link disabled" id="resend-link" onclick="resendOtp()">ارسال مجدد</span>
        <span id="resend-timer">(۶۰ثانیه)</span>
      </div>
      <div class="mt-[14px] text-center text-[10.5px] text-watan-text3 leading-[1.8] max-[480px]:mt-2 max-[480px]:leading-[1.5]">با ادامه، <a href="#" class="text-green no-underline">قوانین و مقررات</a> و <a href="#" class="text-green no-underline">حریم خصوصی</a> وطن استودیو رو می‌پذیری.</div>
    </div>

    <div class="auth-step absolute top-0 left-0 w-full min-w-0 opacity-0 invisible pointer-events-none translate-y-[10px] [transition:opacity_0.28s_ease,transform_0.28s_ease,visibility_0.28s]" id="step-3">
      <div class="flex items-center gap-[6px] text-xs text-watan-text2 cursor-pointer mb-[14px] w-fit transition-colors duration-150 hover:text-watan-text" onclick="goToStep('step-otp')"><i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت</div>
      <div class="text-[17px] font-extrabold text-watan-text mb-1 text-center">یه قدم مونده</div>
      <div class="text-xs text-watan-text3 text-center mb-[22px]">اطلاعاتت رو کامل کن</div>
      <div class="grid grid-cols-2 gap-[10px] w-full min-h-[70px] mb-4 max-[480px]:min-h-0 max-[480px]:mb-[10px]">
        <div class="flex flex-col gap-[6px]">
          <label class="text-[11.5px] font-semibold text-watan-text2">نام</label>
          <div class="auth-input-wrap w-full flex items-center gap-2 bg-s2 border border-b1 rounded-[10px] px-[14px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-green" id="name-wrap">
            <i class="fa-solid fa-user text-watan-text3 text-[13px]"></i>
            <input class="auth-input flex-1 min-w-0 bg-transparent border-0 outline-none text-base text-watan-text placeholder:text-watan-text3" type="text" id="name-input" placeholder="مثلاً علی" />
          </div>
          <div class="field-error hidden text-[10.5px] text-red" id="name-error">نام رو وارد کن</div>
        </div>
        <div class="flex flex-col gap-[6px]">
          <label class="text-[11.5px] font-semibold text-watan-text2">فامیل</label>
          <div class="auth-input-wrap w-full flex items-center gap-2 bg-s2 border border-b1 rounded-[10px] px-[14px] h-11 max-[480px]:h-[42px] transition-colors duration-150 focus-within:border-green" id="lastname-wrap">
            <i class="fa-solid fa-user text-watan-text3 text-[13px]"></i>
            <input class="auth-input flex-1 min-w-0 bg-transparent border-0 outline-none text-base text-watan-text placeholder:text-watan-text3" type="text" id="lastname-input" placeholder="مثلاً محمدی" />
          </div>
          <div class="field-error hidden text-[10.5px] text-red" id="lastname-error">فامیل رو وارد کن</div>
        </div>
      </div>
      <button class="flex w-full py-3 border-0 rounded-[10px] bg-green text-white text-[13.5px] font-bold cursor-pointer items-center justify-center gap-2 [transition:background-color_0.15s,transform_0.1s] hover:bg-[#09a447] active:scale-[0.99]" onclick="completeProfile()">
        <span>ورود به وطن استودیو</span><i class="fa-solid fa-check"></i>
      </button>
      <div class="mt-[14px] text-center text-[10.5px] text-watan-text3 leading-[1.8] max-[480px]:mt-2 max-[480px]:leading-[1.5]">با ادامه، <a href="#" class="text-green no-underline">قوانین و مقررات</a> و <a href="#" class="text-green no-underline">حریم خصوصی</a> وطن استودیو رو می‌پذیری.</div>
    </div>

  </div>
  </div>

  <div class="hidden md:flex md:flex-1 relative flex-col items-center justify-center bg-[#111116] p-10 overflow-hidden">
    <div class="absolute w-[280px] h-[280px] rounded-full bg-green opacity-10 blur-[80px]"></div>
    <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن استودیو" class="relative z-[1] w-20 h-20 object-contain mb-5">
    <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن استودیو" class="relative z-[1] w-[140px] object-contain mb-4">
    <div class="relative z-[1] text-xs text-watan-text3 text-center max-w-[220px] leading-[1.8]">خلاقیت بی‌مرز، با هوش مصنوعی وطن</div>
  </div>

</div>

<script>
let mode = 'register';
let resendTimerId = null;
let otpExpired = false;
const VALID_CODE = '11111';
const RETURNING_PHONE = '09120000000';

function switchTab(name) {
  mode = name;
  document.getElementById('tab-login').classList.toggle('active', name === 'login');
  document.getElementById('tab-register').classList.toggle('active', name === 'register');
  goToStep(name === 'register' ? 'reg-step-1' : 'login-step-1');
}

const LOCKED_HEIGHT_STEPS = ['reg-step-1', 'login-step-1', 'step-otp', 'step-3'];

function stepHeight(id) {
  if (LOCKED_HEIGHT_STEPS.includes(id)) {
    return Math.max(...LOCKED_HEIGHT_STEPS.map(stepId => document.getElementById(stepId).scrollHeight));
  }
  return document.getElementById(id).scrollHeight;
}

function goToStep(id) {
  const stage = document.getElementById('step-stage');
  stage.style.height = stepHeight(id) + 'px';
  document.querySelectorAll('.auth-step').forEach(el => el.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}

function syncStageHeight() {
  const stage = document.getElementById('step-stage');
  const active = document.querySelector('.auth-step.active');
  if (!active) return;
  const prevTransition = stage.style.transition;
  stage.style.transition = 'none';
  stage.style.height = stepHeight(active.id) + 'px';
  void stage.offsetHeight;
  stage.style.transition = prevTransition;
}

function validatePhone(phone) {
  return /^09\d{9}$/.test(phone);
}

function toPersianDigits(str) {
  const fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
  return String(str).replace(/[0-9]/g, d => fa[d]);
}

let currentPhone = '';

function goToOtp(fromMode) {
  const inputId = fromMode === 'register' ? 'reg-phone-input' : 'login-phone-input';
  const wrapId = fromMode === 'register' ? 'reg-phone-wrap' : 'login-phone-wrap';
  const errorId = fromMode === 'register' ? 'reg-phone-error' : 'login-phone-error';
  const input = document.getElementById(inputId);
  const wrap = document.getElementById(wrapId);
  const error = document.getElementById(errorId);
  const phone = input.value.trim();
  if (!validatePhone(phone)) {
    wrap.classList.add('error');
    error.classList.add('show');
    return;
  }
  wrap.classList.remove('error');
  error.classList.remove('show');
  currentPhone = phone;
  document.getElementById('otp-phone-display').textContent = toPersianDigits(phone);
  document.getElementById('otp-back').onclick = () => goToStep(fromMode === 'register' ? 'reg-step-1' : 'login-step-1');
  goToStep('step-otp');
  resetOtpBoxes();
  startResendTimer();
}

function resetOtpBoxes() {
  const boxes = document.querySelectorAll('.otp-box');
  boxes.forEach(b => { b.value = ''; b.classList.remove('error'); });
  document.getElementById('otp-error').classList.remove('show');
  document.getElementById('otp-error').textContent = 'کد وارد شده اشتباهه، دوباره امتحان کن';
  boxes[0].focus();
}

function startResendTimer() {
  let seconds = 60;
  otpExpired = false;
  const link = document.getElementById('resend-link');
  const timer = document.getElementById('resend-timer');
  link.classList.add('disabled');
  timer.style.display = 'inline';
  timer.textContent = '(' + toPersianDigits(seconds) + 'ثانیه)';
  if (resendTimerId) clearInterval(resendTimerId);
  resendTimerId = setInterval(() => {
    seconds--;
    if (seconds <= 0) {
      clearInterval(resendTimerId);
      link.classList.remove('disabled');
      timer.style.display = 'none';
      otpExpired = true;
    } else {
      timer.textContent = '(' + toPersianDigits(seconds) + 'ثانیه)';
    }
  }, 1000);
}

function resendOtp() {
  if (document.getElementById('resend-link').classList.contains('disabled')) return;
  resetOtpBoxes();
  startResendTimer();
}

function showOtpError(message) {
  const errEl = document.getElementById('otp-error');
  errEl.textContent = message;
  errEl.classList.add('show');
  const boxesWrap = document.getElementById('otp-boxes');
  boxesWrap.classList.remove('shake');
  document.querySelectorAll('.otp-box').forEach(b => b.classList.add('error'));
  void boxesWrap.offsetWidth;
  boxesWrap.classList.add('shake');
}

function confirmOtp() {
  const boxes = document.querySelectorAll('.otp-box');
  const code = Array.from(boxes).map(b => b.value).join('');
  if (code.length < boxes.length) {
    boxes.forEach(b => { if (!b.value) b.classList.add('error'); });
    return;
  }
  if (otpExpired) {
    showOtpError('کد منقضی شده، ارسال مجدد کن');
    return;
  }
  if (code !== VALID_CODE) {
    showOtpError('کد وارد شده اشتباهه، دوباره امتحان کن');
    boxes.forEach(b => { b.value = ''; });
    boxes[0].focus();
    return;
  }
  if (resendTimerId) clearInterval(resendTimerId);
  if (mode === 'login' && currentPhone === RETURNING_PHONE) {
    window.location.href = '/admin/dashboard';
  } else {
    goToStep('step-3');
  }
}

function completeProfile() {
  const nameInput = document.getElementById('name-input');
  const nameWrap = document.getElementById('name-wrap');
  const nameError = document.getElementById('name-error');
  const lastInput = document.getElementById('lastname-input');
  const lastWrap = document.getElementById('lastname-wrap');
  const lastError = document.getElementById('lastname-error');
  let valid = true;
  if (!nameInput.value.trim()) {
    nameWrap.classList.add('error');
    nameError.classList.add('show');
    valid = false;
  } else {
    nameWrap.classList.remove('error');
    nameError.classList.remove('show');
  }
  if (!lastInput.value.trim()) {
    lastWrap.classList.add('error');
    lastError.classList.add('show');
    valid = false;
  } else {
    lastWrap.classList.remove('error');
    lastError.classList.remove('show');
  }
  if (!valid) return;

  fetch('/auth/complete-profile', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    body: JSON.stringify({ first_name: nameInput.value.trim(), last_name: lastInput.value.trim() }),
  })
  .then(r => r.json())
  .then(data => {
    if (data.message) window.location.href = '/admin/dashboard';
  })
  .catch(() => { window.location.href = '/admin/dashboard'; });
}

document.querySelectorAll('.otp-box').forEach((box, i, all) => {
  box.addEventListener('input', () => {
    box.value = box.value.replace(/[^0-9]/g, '');
    box.classList.remove('error');
    document.getElementById('otp-error').classList.remove('show');
    document.getElementById('otp-boxes').classList.remove('shake');
    if (box.value && i < all.length - 1) all[i + 1].focus();
    if (Array.from(all).every(b => b.value)) confirmOtp();
  });
  box.addEventListener('keydown', (e) => {
    if (e.key === 'Backspace' && !box.value && i > 0) all[i - 1].focus();
  });
});

['reg-phone-input', 'login-phone-input'].forEach(id => {
  const input = document.getElementById(id);
  const wrapId = id === 'reg-phone-input' ? 'reg-phone-wrap' : 'login-phone-wrap';
  const errorId = id === 'reg-phone-input' ? 'reg-phone-error' : 'login-phone-error';
  input.addEventListener('input', () => {
    document.getElementById(wrapId).classList.remove('error');
    document.getElementById(errorId).classList.remove('show');
  });
});

['name-input', 'lastname-input'].forEach(id => {
  const el = document.getElementById(id);
  const wrapId = id === 'name-input' ? 'name-wrap' : 'lastname-wrap';
  const errorId = id === 'name-input' ? 'name-error' : 'lastname-error';
  el.addEventListener('input', () => {
    document.getElementById(wrapId).classList.remove('error');
    document.getElementById(errorId).classList.remove('show');
  });
  el.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); completeProfile(); }
  });
});

syncStageHeight();
window.addEventListener('load', syncStageHeight);
window.addEventListener('resize', syncStageHeight);
</script>

</body>
</html>
