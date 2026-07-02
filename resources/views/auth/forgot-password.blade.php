<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>بازیابی رمز عبور — وطن استودیو</title>

    <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])

    <style>
        .step-content { display: none; }
        .step-content.active { display: block; }
    </style>
</head>
<body class="m-0 bg-[#0a0a0c] text-white font-[IRANSansXFaNum,_sans-serif] antialiased overflow-x-hidden">

    {{-- نئون سبز بک‌گراند --}}
    <div class="fixed inset-0 -z-10 bg-[#0a0a0c] overflow-hidden">
        <div class="absolute top-[-20%] left-[-10%] w-[500px] h-[500px] bg-emerald-500/10 rounded-full blur-[120px] pointer-events-none"></div>
        <div class="absolute bottom-[-20%] right-[-10%] w-[500px] h-[500px] bg-teal-500/10 rounded-full blur-[120px] pointer-events-none"></div>
    </div>

    {{-- رپِر فیکس کننده کارت در وسط صفحه --}}
    <div class="min-h-screen min-h-[100dvh] w-full flex items-center justify-center p-4 relative z-10">
        
        <div class="w-full max-w-md bg-[#111114]/80 backdrop-blur-xl border border-white/[0.06] rounded-2xl p-6 sm:p-8 shadow-2xl">
            
            {{-- هدر کارت --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500/10 to-teal-500/20 border border-emerald-500/20 mb-3 shadow-inner">
                    <i class="fa-solid mt-0.5 text-xl text-emerald-400 fa-shield-halved" id="card-icon"></i>
                </div>
                <h1 class="text-lg font-black text-gray-100 mb-1.5" id="card-title">بازیابی رمز عبور</h1>
                <p class="text-[11.5px] text-gray-400 leading-relaxed" id="card-desc">شماره موبایل خود را وارد کنید تا کد تایید ارسال شود.</p>
            </div>

            {{-- باکس نمایش خطاها --}}
            <div id="global-error" class="hidden mb-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span id="error-text"></span>
            </div>

            {{-- ══════════════ مرحله اول: دریافت شماره موبایل ══════════════ --}}
            <div id="step-phone" class="step-content active">
                <div class="space-y-5">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 mb-2 mr-1">شماره موبایل</label>
                        <div class="relative flex items-center bg-[#16161a] border border-white/[0.08] rounded-xl h-11 focus-within:border-emerald-500 transition-colors">
                            <i class="fa-solid fa-mobile-screen-button text-gray-500 text-sm absolute right-3.5"></i>
                            <input type="text" id="phone-input" placeholder="۰۹۱۲۳۴۵۶۷۸۹" dir="ltr" class="w-full bg-transparent border-0 outline-none pr-11 pl-4 text-sm text-white placeholder-gray-600 text-left">
                        </div>
                    </div>
                    <button onclick="sendOtpCode()" id="btn-send-otp" class="w-full h-11 bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-black text-[13px] rounded-xl transition-all active:scale-[0.99] flex items-center justify-center gap-2">
                        <span>ارسال کد تایید</span>
                        <i class="fa-solid fa-arrow-left text-xs mt-0.5"></i>
                    </button>
                </div>
            </div>

            {{-- ══════════════ مرحله دوم: تایید کد OTP ══════════════ --}}
            <div id="step-otp" class="step-content">
                <div class="space-y-5">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 mb-2 mr-1">کد تایید ۴ رقمی</label>
                        <div class="relative flex items-center bg-[#16161a] border border-white/[0.08] rounded-xl h-11 focus-within:border-emerald-500 transition-colors">
                            <i class="fa-solid fa-key text-gray-500 text-sm absolute right-3.5"></i>
                            <input type="text" id="otp-input" maxlength="4" placeholder="••••" dir="ltr" class="w-full bg-transparent border-0 outline-none pr-11 pl-4 text-sm text-white tracking-[0.5em] placeholder-gray-600 text-center font-bold">
                        </div>
                    </div>
                    <button onclick="verifyOtpCode()" id="btn-verify-otp" class="w-full h-11 bg-emerald-500 hover:bg-emerald-400 text-neutral-950 font-black text-[13px] rounded-xl transition-all active:scale-[0.99] flex items-center justify-center gap-2">
                        <span>بررسی و تایید کد</span>
                        <i class="fa-solid fa-check text-xs mt-0.5"></i>
                    </button>
                </div>
            </div>

            {{-- ══════════════ مرحله سوم: ثبت رمز عبور جدید ══════════════ --}}
            <div id="step-password" class="step-content">
                <div class="space-y-5">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-400 mb-2 mr-1">رمز عبور جدید</label>
                        <div class="relative flex items-center bg-[#16161a] border border-white/[0.08] rounded-xl h-11 focus-within:border-emerald-500 transition-colors">
                            <i class="fa-solid fa-lock text-gray-500 text-sm absolute right-3.5"></i>
                            <input type="password" id="password-input" placeholder="حداقل ۶ کاراکتر" dir="ltr" class="w-full bg-transparent border-0 outline-none pr-11 pl-4 text-sm text-white placeholder-gray-600 text-left">
                        </div>
                    </div>
                    <button onclick="submitNewPassword()" id="btn-reset-final" class="w-full h-11 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-black text-[13px] rounded-xl transition-all active:scale-[0.99] flex items-center justify-center">
                        بروزرسانی و تغییر رمز عبور
                    </button>
                </div>
            </div>

            {{-- فوتر کارت --}}
            <div class="mt-8 pt-5 border-t border-white/[0.05] text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-xs text-gray-400 hover:text-emerald-400 transition-colors duration-200">
                    <i class="fa-solid fa-arrow-right-to-bracket text-sm"></i>
                    <span>بازگشت به صفحه ورود</span>
                </a>
            </div>

        </div>
    </div>

    <script>
        let userPhone = '';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function showError(msg) {
            const errBox = document.getElementById('global-error');
            document.getElementById('error-text').innerText = msg;
            errBox.classList.remove('hidden');
        }

        function clearError() {
            document.getElementById('global-error').classList.add('hidden');
        }

        // ۱. ارسال کد تایید
        function sendOtpCode() {
            clearError();
            const phoneInput = document.getElementById('phone-input').value.trim();
            
            if(!/^09\d{9}$/.test(phoneInput)) {
                showError('شماره موبایل وارد شده معتبر نیست.');
                return;
            }

            userPhone = phoneInput;
            const btn = document.getElementById('btn-send-otp');
            btn.innerText = 'در حال ارسال...';
            btn.disabled = true;

            fetch('/auth/forgot-send-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ phone: userPhone })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(res => {
                btn.innerText = 'ارسال کد تایید';
                btn.disabled = false;

                if (res.status === 200) {
                    document.getElementById('step-phone').classList.remove('active');
                    document.getElementById('step-otp').classList.add('active');
                    
                    document.getElementById('card-title').innerText = 'تایید کد پیامک شده';
                    document.getElementById('card-desc').innerText = `کد ارسال شده به شماره ${userPhone} را وارد کنید.`;
                    
                    if(res.body.debug_code) {
                        console.log('OTP DEBUG: ' + res.body.debug_code);
                        alert('کد تایید تستی شما: ' + res.body.debug_code);
                    }
                } else {
                    showError(res.body.message || 'خطایی رخ داد.');
                }
            }).catch(() => { btn.disabled = false; showError('ارتباط با سرور قطع شد.'); });
        }

        // ۲. بررسی صحت کد تایید (صرفاً بررسی کد)
        function verifyOtpCode() {
            clearError();
            const otpVal = document.getElementById('otp-input').value.trim();

            if(otpVal.length !== 4) {
                showError('کد تایید باید ۴ رقمی باشد.');
                return;
            }

            const btn = document.getElementById('btn-verify-otp');
            btn.innerText = 'در حال بررسی...';
            btn.disabled = true;

            fetch('/auth/forgot-verify-otp', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ phone: userPhone, otp: otpVal })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(res => {
                btn.innerText = 'بررسی و تایید کد';
                btn.disabled = false;

                if (res.status === 200) {
                    // تغییر فاز به مرحله آخر (ست کردن پسورد جدید)
                    document.getElementById('step-otp').classList.remove('active');
                    document.getElementById('step-password').classList.add('active');
                    
                    document.getElementById('card-icon').className = "fa-solid mt-0.5 text-xl text-emerald-400 fa-lock-open";
                    document.getElementById('card-title').innerText = 'تعیین رمز عبور جدید';
                    document.getElementById('card-desc').innerText = 'رمز عبور ایمن و جدید خود را وارد کنید.';
                } else {
                    showError(res.body.message || 'کد وارد شده صحیح نیست.');
                }
            }).catch(() => { btn.disabled = false; showError('ارتباط با سرور قطع شد.'); });
        }

        // ۳. تغییر نهایی رمز عبور
        function submitNewPassword() {
            clearError();
            const pwdVal = document.getElementById('password-input').value;

            if(pwdVal.length < 6) {
                showError('رمز عبور جدید باید حداقل ۶ کاراکتر باشد.');
                return;
            }

            const btn = document.getElementById('btn-reset-final');
            btn.innerText = 'در حال ثبت...';
            btn.disabled = true;

            fetch('/auth/forgot-verify-reset', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ phone: userPhone, password: pwdVal })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(res => {
                if (res.status === 200) {
                    alert(res.body.message);
                    window.location.href = res.body.redirect;
                } else {
                    btn.innerText = 'بروزرسانی و تغییر رمز عبور';
                    btn.disabled = false;
                    showError(res.body.message || 'خطایی رخ داد.');
                }
            }).catch(() => { btn.disabled = false; showError('ارتباط با سرور قطع شد.'); });
        }
    </script>
</body>
</html>