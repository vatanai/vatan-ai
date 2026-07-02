<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>ورود | وطن استودیو</title>
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
</style>
</head>
<body class="m-0 min-h-screen min-h-[100dvh] flex items-center justify-center p-4 bg-[#0a0a0c] text-white font-[IRANSansXFaNum,_sans-serif] overflow-x-hidden">

{{-- هاله درخشان سبز/زمردی نئون پس‌زمینه --}}
<div class="fixed inset-0 -z-10 bg-[#0a0a0c] overflow-hidden before:content-[''] before:absolute before:rounded-full before:blur-[90px] before:opacity-[0.12] before:w-[420px] before:h-[420px] before:bg-emerald-500 before:-top-[120px] before:-right-[100px] after:content-[''] after:absolute after:rounded-full after:blur-[90px] after:opacity-[0.12] after:w-[380px] after:h-[380px] after:bg-emerald-500 after:-bottom-[140px] after:-left-[100px]"></div>

{{-- باکس اصلی فرم با تم کاملاً تاریک و شیشه‌ای --}}
<div class="w-full max-w-[400px] md:max-w-[840px] bg-[#111116] border border-white/5 rounded-2xl overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.6)] transition-all duration-300">
  <div class="flex flex-col md:flex-row md:items-stretch">
    
    {{-- بخش فرم‌ها (سمت راست) --}}
    <div class="w-full md:w-[400px] p-6 max-[480px]:p-4 flex flex-col justify-center">
      
      {{-- لوگو بالا --}}
      <div class="flex flex-col items-center gap-1.5 mb-4 text-center">
        <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن استودیو" class="h-11 w-auto">
        <div class="text-sm font-extrabold text-white tracking-[-0.3px]">وطن استودیو</div>
      </div>

      {{-- تب‌های سوییچ ورود / ثبت‌نام --}}
      <div class="flex bg-[#16161f] border border-white/5 rounded-[10px] p-1 mb-5">
        <div class="auth-tab active flex-1 text-center py-2 text-[12.5px] font-bold text-slate-400 rounded-lg cursor-pointer select-none transition-colors duration-200 [&.active]:bg-white/10 [&.active]:text-white" id="tab-register" onclick="switchTab('register')">ثبت‌نام</div>
        <div class="auth-tab flex-1 text-center py-2 text-[12.5px] font-bold text-slate-400 rounded-lg cursor-pointer select-none transition-colors duration-200 [&.active]:bg-white/10 [&.active]:text-white" id="tab-login" onclick="switchTab('login')">ورود</div>
      </div>

      {{-- کانتینر مراحل متحرک --}}
      <div class="relative w-full overflow-hidden transition-[height] duration-300 ease-out" id="step-stage">

        {{-- مرحله اول ثبت نام --}}
        <div class="auth-step active absolute top-0 left-0 w-full opacity-0 invisible pointer-events-none translate-y-2 [transition:opacity_0.25s_ease,transform_0.25s_ease,visibility_0.25s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="reg-step-1">
          <div class="text-[16px] font-extrabold text-white mb-0.5 text-center">بیا شروع کنیم 👋</div>
          <div class="text-[11.5px] text-slate-400 text-center mb-4">با شماره موبایلت ثبت‌نام کن</div>
          
          <div class="flex flex-col gap-1 mb-3.5">
            <label class="text-[11px] font-semibold text-slate-300">شماره موبایل</label>
            <div class="auth-input-wrap relative flex items-center bg-[#16161f] border border-white/5 rounded-[10px] h-10 transition-colors duration-150 focus-within:border-emerald-500" id="reg-phone-wrap">
              <i class="fa-solid fa-mobile-screen-button text-slate-400 text-[12px] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <input class="w-full bg-transparent border-0 outline-none text-[14.5px] text-white text-right pr-3 pl-9 placeholder:text-slate-500" type="text" id="reg-phone-input" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" />
            </div>
            <div class="hidden text-[10.5px] text-rose-400 mt-0.5" id="reg-phone-error">شماره موبایل معتبر نیست</div>
          </div>
          
          <button class="flex w-full py-2.5 border-0 rounded-[10px] bg-emerald-500 text-neutral-950 text-[13px] font-black cursor-pointer items-center justify-center gap-1.5 transition-all hover:bg-emerald-400 active:scale-[0.99]" onclick="goToOtp('register')">
            <span>ادامه</span><i class="fa-solid fa-arrow-left text-[11px]"></i>
          </button>
          
          <div class="flex items-center gap-2 my-3.5 text-slate-400 text-[10.5px] before:content-[''] before:flex-1 before:h-px before:bg-white/5 after:content-[''] after:flex-1 after:h-px after:bg-white/5">یا با این روش‌ها</div>
          
          <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2.5 w-full py-2 px-3 rounded-[10px] border border-white/5 bg-[#16161f] cursor-pointer transition-all hover:border-white/20 hover:-translate-y-px active:translate-y-0">
              <img src="{{ asset('assets/img/icon-telegram.svg') }}" alt="تلگرام" class="w-4 h-4 object-contain">
              <div class="flex-1 text-right text-[12px] font-bold text-white">ورود با تلگرام</div>
              <span class="text-[8.5px] font-bold py-0.5 px-1.5 rounded bg-orange-500/10 text-orange-400 border border-orange-500/25">به‌زودی</span>
            </div>
            <div class="flex items-center gap-2.5 w-full py-2 px-3 rounded-[10px] border border-white/5 bg-[#16161f] cursor-pointer transition-all hover:border-white/20 hover:-translate-y-px active:translate-y-0">
              <img src="{{ asset('assets/img/Bale-icon.svg') }}" alt="بله" class="w-4 h-4 object-contain">
              <div class="flex-1 text-right text-[12px] font-bold text-white">ورود با بله</div>
              <span class="text-[8.5px] font-bold py-0.5 px-1.5 rounded bg-orange-500/10 text-orange-400 border border-orange-500/25">به‌زودی</span>
            </div>
          </div>
          
          <div class="text-center text-[11px] text-slate-400 mt-4">قبلاً ثبت‌نام کردی؟ <span class="text-emerald-400 cursor-pointer font-bold" onclick="switchTab('login')">ورود</span></div>
        </div>

        {{-- مرحله اول ورود --}}
        <div class="auth-step absolute top-0 left-0 w-full opacity-0 invisible pointer-events-none translate-y-2 [transition:opacity_0.25s_ease,transform_0.25s_ease,visibility_0.25s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="login-step-1">
          <div class="text-[16px] font-extrabold text-white mb-0.5 text-center">خوش آمدید مجدد</div>
          <div class="text-[11.5px] text-slate-400 text-center mb-4">شماره موبایلت رو وارد کن</div>
          
          <div class="flex flex-col gap-1 mb-3.5">
            <label class="text-[11px] font-semibold text-slate-300">شماره موبایل</label>
            <div class="auth-input-wrap relative flex items-center bg-[#16161f] border border-white/5 rounded-[10px] h-10 transition-colors duration-150 focus-within:border-emerald-500" id="login-phone-wrap">
              <i class="fa-solid fa-mobile-screen-button text-slate-400 text-[12px] absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <input class="w-full bg-transparent border-0 outline-none text-[14.5px] text-white text-right pr-3 pl-9 placeholder:text-slate-500" type="text" id="login-phone-input" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" />
            </div>
            <div class="hidden text-[10.5px] text-rose-400 mt-0.5" id="login-phone-error">شماره موبایل معتبر نیست</div>
          </div>
          
          <button class="flex w-full py-2.5 border-0 rounded-[10px] bg-emerald-500 text-neutral-950 text-[13px] font-black cursor-pointer items-center justify-center gap-1.5 transition-all hover:bg-emerald-400 active:scale-[0.99]" onclick="goToOtp('login')">
            <span>ادامه</span><i class="fa-solid fa-arrow-left text-[11px]"></i>
          </button>
          <div class="text-center mt-3.5">
            <a href="{{ route('password.request') }}" class="text-[11px] text-slate-500 hover:text-emerald-400 transition-colors duration-200">
              رمز عبور خود را فراموش کرده‌اید؟
            </a>
          </div>
          <div class="text-center text-[11px] text-slate-400 mt-4">حساب نداری؟ <span class="text-emerald-400 cursor-pointer font-bold" onclick="switchTab('register')">ثبت‌نام</span></div>
        </div>

        {{-- مرحله تایید کد OTP --}}
        <div class="auth-step absolute top-0 left-0 w-full opacity-0 invisible pointer-events-none translate-y-2 [transition:opacity_0.25s_ease,transform_0.25s_ease,visibility_0.25s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="step-otp">
          <div class="flex items-center gap-1 text-[11px] text-slate-400 cursor-pointer mb-2 w-fit transition-colors hover:text-white" id="otp-back"><i class="fa-solid fa-arrow-right text-[10px]"></i> بازگشت</div>
          <div class="text-[16px] font-extrabold text-white mb-0.5 text-center">کد تأیید رو وارد کن</div>
          <div class="text-[11.5px] text-slate-400 text-center mb-4">کد ۵ رقمی به شماره <span id="otp-phone-display" dir="ltr" class="text-white font-bold">۰۹۱۲۳۴۵۶۷۸۹</span> ارسال شد</div>
          
          <div class="mb-3">
            <div class="flex gap-2 [direction:ltr] justify-center mb-2" id="otp-boxes">
              <input class="otp-box w-10 h-12 bg-[#16161f] border border-white/5 rounded-[10px] text-center text-[18px] font-bold text-white outline-none transition-colors focus:border-emerald-500" type="text" maxlength="1" inputmode="numeric" />
              <input class="otp-box w-10 h-12 bg-[#16161f] border border-white/5 rounded-[10px] text-center text-[18px] font-bold text-white outline-none transition-colors focus:border-emerald-500" type="text" maxlength="1" inputmode="numeric" />
              <input class="otp-box w-10 h-12 bg-[#16161f] border border-white/5 rounded-[10px] text-center text-[18px] font-bold text-white outline-none transition-colors focus:border-emerald-500" type="text" maxlength="1" inputmode="numeric" />
              <input class="otp-box w-10 h-12 bg-[#16161f] border border-white/5 rounded-[10px] text-center text-[18px] font-bold text-white outline-none transition-colors focus:border-emerald-500" type="text" maxlength="1" inputmode="numeric" />
              <input class="otp-box w-10 h-12 bg-[#16161f] border border-white/5 rounded-[10px] text-center text-[18px] font-bold text-white outline-none transition-colors focus:border-emerald-500" type="text" maxlength="1" inputmode="numeric" />
            </div>
            <div class="hidden text-center text-[11px] text-rose-400 mt-1" id="otp-error">کد وارد شده اشتباه است</div>
          </div>
          
          <button class="flex w-full py-2.5 border-0 rounded-[10px] bg-emerald-500 text-neutral-950 text-[13px] font-black cursor-pointer items-center justify-center gap-1.5 transition-all hover:bg-emerald-400 active:scale-[0.99]" onclick="confirmOtp()">
            <span>تأیید و ادامه</span><i class="fa-solid fa-arrow-left text-[11px]"></i>
          </button>
          
          <div class="text-center text-[11px] text-slate-400 mt-3.5">
            کد نرسید؟ <span class="text-emerald-400 cursor-pointer disabled:opacity-50 font-bold" id="resend-link" onclick="resendOtp()">ارسال مجدد</span>
            <span id="resend-timer" class="text-slate-500">(۶۰ ثانیه)</span>
          </div>
        </div>

        {{-- مرحله دریافت رمز عبور --}}
        <div class="auth-step absolute top-0 left-0 w-full opacity-0 invisible pointer-events-none translate-y-2 [transition:opacity_0.25s_ease,transform_0.25s_ease,visibility_0.25s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="step-password">
          <div class="flex items-center gap-1 text-[11px] text-slate-400 cursor-pointer mb-2 w-fit transition-colors hover:text-white" onclick="goBackFromPassword()"><i class="fa-solid fa-arrow-right text-[10px]"></i> بازگشت</div>
          
          <div class="text-[16px] font-extrabold text-white mb-0.5 text-center" id="pwd-title">تعیین رمز عبور</div>
          <div class="text-[11.5px] text-slate-400 text-center mb-4" id="pwd-desc">لطفاً رمز عبور خود را وارد کنید</div>
          
          <div class="flex flex-col gap-1 mb-4">
            <label class="text-[11px] font-semibold text-slate-300">رمز عبور</label>
            <div class="w-full flex items-center bg-[#16161f] border border-white/5 rounded-[10px] h-10 transition-colors focus-within:border-emerald-500 relative" id="password-wrap">
              <i class="fa-solid fa-lock text-slate-400 text-[12px] absolute right-3 top-1/2 -translate-y-1/2"></i>
              <input class="w-full bg-transparent border-0 outline-none text-[14px] text-white pr-9 pl-10 placeholder:text-slate-500" type="password" id="password-input" placeholder="••••••••" />
              <i class="fa-solid fa-eye-slash text-slate-400 text-[12px] absolute left-3 top-1/2 -translate-y-1/2 cursor-pointer transition-colors hover:text-white" id="toggle-password" onclick="togglePasswordVisibility()"></i>
            </div>
            <div class="hidden text-[10.5px] text-rose-400 mt-0.5" id="password-error">رمز عبور باید حداقل ۶ کاراکتر باشد</div>
          </div>
          
          <button class="flex w-full py-2.5 border-0 rounded-[10px] bg-emerald-500 text-neutral-950 text-[13px] font-black cursor-pointer items-center justify-center gap-1.5 transition-all hover:bg-emerald-400 active:scale-[0.99]" onclick="submitPassword()">
            <span>تأیید و ادامه</span><i class="fa-solid fa-arrow-left text-[11px]"></i>
          </button>
        </div>

        {{-- مرحله نهایی تکمیل پروفایل ثبت نام --}}
        <div class="auth-step absolute top-0 left-0 w-full opacity-0 invisible pointer-events-none translate-y-2 [transition:opacity_0.25s_ease,transform_0.25s_ease,visibility_0.25s] [&.active]:opacity-100 [&.active]:visible [&.active]:pointer-events-auto [&.active]:translate-y-0" id="step-3">
          <div class="text-[16px] font-extrabold text-white mb-0.5 text-center">یه قدم مونده 🚀</div>
          <div class="text-[11.5px] text-slate-400 text-center mb-4">اطلاعاتت رو کامل کن</div>
          
          <div class="flex flex-col gap-3 mb-4">
            <div class="flex flex-col gap-1">
              <label class="text-[11px] font-semibold text-slate-300">نام</label>
              <div class="w-full flex items-center gap-2 bg-[#16161f] border border-white/5 rounded-[10px] px-3 h-10 transition-colors focus-within:border-emerald-500" id="name-wrap">
                <i class="fa-solid fa-user text-slate-400 text-[12px]"></i>
                <input class="w-full bg-transparent border-0 outline-none text-[14px] text-white placeholder:text-slate-500" type="text" id="name-input" placeholder="مثلاً علی" />
              </div>
              <div class="hidden text-[10.5px] text-rose-400 mt-0.5" id="name-error">لطفاً نام خود را وارد کنید</div>
            </div>
            
            <div class="flex flex-col gap-1">
              <label class="text-[11px] font-semibold text-slate-300">نام خانوادگی</label>
              <div class="w-full flex items-center gap-2 bg-[#16161f] border border-white/5 rounded-[10px] px-3 h-10 transition-colors focus-within:border-emerald-500" id="lastname-wrap">
                <i class="fa-solid fa-user text-slate-400 text-[12px]"></i>
                <input class="w-full bg-transparent border-0 outline-none text-[14px] text-white placeholder:text-slate-500" type="text" id="lastname-input" placeholder="مثلاً محمدی" />
              </div>
              <div class="hidden text-[10.5px] text-rose-400 mt-0.5" id="lastname-error">لطفاً نام خانوادگی خود را وارد کنید</div>
            </div>

            <div class="flex flex-col gap-1">
              <label class="text-[11px] font-semibold text-slate-300">ایمیل</label>
              <div class="w-full flex items-center gap-2 bg-[#16161f] border border-white/5 rounded-[10px] px-3 h-10 transition-colors focus-within:border-emerald-500" id="email-wrap">
                <i class="fa-solid fa-envelope text-slate-400 text-[12px]"></i>
                <input class="w-full bg-transparent border-0 outline-none text-[14px] text-white placeholder:text-slate-500 text-left" type="email" id="email-input" placeholder="info@vatanstudio.com" dir="ltr" />
              </div>
              <div class="hidden text-[10.5px] text-rose-400 mt-0.5" id="email-error">لطفاً یک ایمیل معتبر وارد کنید</div>
            </div>
          </div>
          
          <button class="flex w-full py-2.5 border-0 rounded-[10px] bg-emerald-500 text-neutral-950 text-[13px] font-black cursor-pointer items-center justify-center gap-1.5 transition-all hover:bg-emerald-400 active:scale-[0.99]" onclick="completeProfile()">
            <span>ورود به وطن استودیو</span><i class="fa-solid fa-check text-[11px]"></i>
          </button>
        </div>

      </div>
    </div>

    {{-- بخش بنر گرافیکی سمبلیک --}}
    <div class="hidden md:flex md:flex-1 relative flex-col items-center justify-center bg-[#0d0d12] p-8 overflow-hidden border-r border-white/5">
      <div class="absolute w-[240px] h-[240px] rounded-full bg-emerald-500 opacity-[0.06] blur-[70px]"></div>
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن استودیو" class="relative z-[1] w-14 h-14 object-contain mb-3">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن استودیو" class="relative z-[1] w-[110px] object-contain mb-3">
      <div class="relative z-[1] text-[11.5px] text-slate-400 text-center max-w-[200px] leading-relaxed">خلاقیت بی‌مرز، با هوش مصنوعی وطن</div>
    </div>

  </div>
</div>

<script>
let mode = 'register';
let resendTimerId = null;
let otpExpired = false;
let currentPhone = '';
let localGeneratedCode = ''; 

// ═══ تابع حیاتی جدید برای محاسبه و به روزرسانی آنی ارتفاع کارت هنگام نمایش خطا ═══
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
  updateStageHeight(); // فراخوانی تابع ارتفاع تنظیم‌کننده جدید
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

function generateRandomOTP() {
  return Math.floor(10000 + Math.random() * 90000).toString();
}

function goToOtp(fromMode) {
  const inputId = fromMode === 'register' ? 'reg-phone-input' : 'login-phone-input';
  const wrapId = fromMode === 'register' ? 'reg-phone-wrap' : 'login-phone-wrap';
  const errorId = fromMode === 'register' ? 'reg-phone-error' : 'login-phone-error';
  
  const input = document.getElementById(inputId);
  const wrap = document.getElementById(wrapId);
  const error = document.getElementById(errorId);
  const phone = input.value.trim();

  if (!validatePhone(phone)) {
    wrap.classList.add('border-rose-500');
    error.textContent = 'شماره موبایل معتبر نیست';
    error.classList.remove('hidden');
    updateStageHeight(); // اصلاح فیکس ارتفاع کارت
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
      
      if (fromMode === 'login') {
        document.getElementById('password-input').value = '';
        document.getElementById('password-wrap').classList.remove('border-rose-500');
        document.getElementById('password-error').classList.add('hidden');
        document.getElementById('pwd-title').textContent = 'ورود با رمز عبور';
        document.getElementById('pwd-desc').textContent = 'رمز عبور خود را وارد کنید';
        goToStep('step-password');
      } else {
        processOtpTransition(phone, fromMode);
      }
    } else {
      wrap.classList.add('border-rose-500');
      error.textContent = data.message;
      error.classList.remove('hidden');
      updateStageHeight(); // اصلاح فیکس ارتفاع کارت
    }
  })
  .catch(() => {
    wrap.classList.add('border-rose-500');
    error.textContent = 'خطا در اتصال به سرور';
    error.classList.remove('hidden');
    updateStageHeight(); // اصلاح فیکس ارتفاع کارت
  });
}

function processOtpTransition(phone, fromMode) {
  localGeneratedCode = generateRandomOTP();
  alert("🔑 کد تأیید وطن استودیو (شبیه‌ساز لوکال):\n" + localGeneratedCode);
  
  document.getElementById('otp-phone-display').textContent = toPersianDigits(phone);
  document.getElementById('otp-back').onclick = () => goToStep('reg-step-1');
  goToStep('step-otp');
  resetOtpBoxes();
  startResendTimer();
}

function goBackFromPassword() {
  if (mode === 'register') {
    goToStep('step-otp');
  } else {
    goToStep('login-step-1');
  }
}

function resetOtpBoxes() {
  const boxes = document.querySelectorAll('.otp-box');
  boxes.forEach(b => { b.value = ''; b.classList.remove('border-rose-500'); });
  document.getElementById('otp-error').classList.add('hidden');
  boxes[0].focus();
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
  localGeneratedCode = generateRandomOTP();
  alert("🔑 کد تأیید جدید (شبیه‌ساز لوکال):\n" + localGeneratedCode);
  startResendTimer();
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
  
  updateStageHeight(); // اصلاح فیکس ارتفاع کارت
}

function confirmOtp() {
  const boxes = document.querySelectorAll('.otp-box');
  const code = Array.from(boxes).map(b => b.value).join('');
  
  if (code.length < boxes.length) {
    boxes.forEach(b => { if (!b.value) b.classList.add('border-rose-500'); });
    return;
  }
  if (otpExpired) {
    showOtpError('کد منقضی شده، ارسال مجدد را بزنید');
    return;
  }

  if (code === localGeneratedCode) {
    if (resendTimerId) clearInterval(resendTimerId);
    
    document.getElementById('pwd-title').textContent = 'تعیین رمز عبور';
    document.getElementById('pwd-desc').textContent = 'یک رمز عبور برای حساب خود انتخاب کنید';
    
    goToStep('step-password');
  } else {
    showOtpError('کد وارد شده اشتباه است');
    boxes.forEach(b => b.value = '');
    boxes[0].focus();
  }
}

function togglePasswordVisibility() {
  const pwdInput = document.getElementById('password-input');
  const icon = document.getElementById('toggle-password');
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
  
  if (pwdInput.value.length < 6) {
    pwdWrap.classList.add('border-rose-500');
    pwdError.textContent = 'رمز عبور باید حداقل ۶ کاراکتر باشد';
    pwdError.classList.remove('hidden');
    updateStageHeight(); // اصلاح فیکس ارتفاع کارت
    return;
  }
  
  pwdWrap.classList.remove('border-rose-500');
  pwdError.classList.add('hidden');
  
  if (mode === 'register') {
    goToStep('step-3');
  } else {
    fetch('/auth/login-submit', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
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
        pwdWrap.classList.add('border-rose-500');
        pwdError.textContent = data.message;
        pwdError.classList.remove('hidden');
        updateStageHeight(); // اصلاح فیکس ارتفاع کارت مپ‌شده به ارور سرور
      }
    })
    .catch(() => {
      pwdWrap.classList.add('border-rose-500');
      pwdError.textContent = 'خطا در برقراری ارتباط با سرور';
      pwdError.classList.remove('hidden');
      updateStageHeight(); // اصلاح فیکس ارتفاع کارت
    });
  }
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

  if (!validateEmail(emailInput.value.trim())) {
    emailWrap.classList.add('border-rose-500');
    emailError.classList.remove('hidden');
    valid = false;
  } else {
    emailWrap.classList.remove('border-rose-500');
    emailError.classList.add('hidden');
  }

  updateStageHeight(); // برای باز شدن فضاهای خطای پروفایل حساب

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

window.addEventListener('DOMContentLoaded', () => {
  updateStageHeight();
});
</script>

</body>
</html>