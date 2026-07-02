<div class="flip-front bg-s1 border border-b1 rounded-2xl overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.4)] flex flex-col md:flex-row md:items-stretch">
  
  <!-- ستون برندینگ چپ -->
  <div class="hidden md:flex md:w-[460px] relative flex-col items-center justify-center bg-[#111116] p-10 overflow-hidden border-l border-b1">
    <div class="absolute w-[280px] h-[280px] rounded-full bg-green opacity-10 blur-[80px]"></div>
    <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن استودیو" class="relative z-[1] w-20 h-20 object-contain mb-5">
    <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن استودیو" class="relative z-[1] w-[140px] object-contain mb-4">
    <div class="relative z-[1] text-xs text-watan-text3 text-center max-w-[220px] leading-[1.8]">خلاقیت بی‌مرز، با هوش مصنوعی وطن</div>
  </div>

  <!-- ستون فرم راست -->
  <div class="w-full md:w-[460px] flex-shrink-0 h-full overflow-y-auto py-8 px-7 max-[480px]:py-[18px] max-[480px]:px-4 flex flex-col justify-between">
    <div>
      <div class="flex flex-col items-center gap-2 mb-6 max-[480px]:mb-[14px] max-[480px]:gap-[6px]">
        <img src="{{ asset('assets/img/icon vatan.svg') }}" alt="وطن استودیو" class="h-14 w-auto max-[480px]:h-11">
        <div class="text-base font-extrabold text-watan-text tracking-[-0.3px]">وطن استودیو</div>
      </div>

      <div class="flex bg-s2 border border-b1 rounded-[10px] p-1 mb-6 max-[480px]:mb-[14px]">
        <div class="flex-1 text-center py-[9px] text-[13px] font-bold text-watan-text3 rounded-lg cursor-pointer select-none transition-colors duration-200" onclick="switchTab('register')">ثبت‌نام</div>
        <div class="bg-s1 border border-b1 flex-1 text-center py-[9px] text-[13px] font-bold text-watan-text rounded-lg cursor-pointer select-none shadow-sm" onclick="switchTab('login')">ورود</div>
      </div>

      <div class="relative w-full" id="login-stage">
        <!-- گام اول ورود -->
        <div class="auth-step active" id="login-step-1">
          <div class="text-[17px] font-extrabold text-watan-text mb-1 text-center">ادامه بدیم</div>
          <div class="text-xs text-watan-text3 text-center mb-[22px]">شماره موبایلت رو وارد کن</div>
          <div class="flex flex-col gap-[6px] mb-4">
            <label class="text-[11px] font-semibold text-watan-text2">موبایل یا ایمیل</label>
            <div class="auth-input-wrap relative flex items-center bg-s2 border border-b1 rounded-[10px] h-11 transition-colors focus-within:border-green" id="login-phone-wrap">
              <i class="fa-solid fa-mobile-screen-button text-watan-text3 text-[13px] absolute left-[14px] top-1/2 -translate-y-1/2"></i>
              <input class="auth-input w-full bg-transparent border-0 outline-none text-base text-watan-text text-right pr-[14px] pl-[40px] placeholder:text-watan-text3" type="text" id="login-phone-input" placeholder="موبایل یا ایمیل" dir="ltr" />
            </div>
            <div class="field-error hidden text-[10.5px] text-red" id="login-phone-error">شماره موبایل معتبر نیست</div>
          </div>
          <button class="flex w-full py-3 rounded-[10px] bg-green text-white text-[13.5px] font-bold items-center justify-center gap-2 hover:bg-[#09a447] active:scale-[0.99] transition-all" onclick="goToOtp('login')">
            <span>ادامه</span><i class="fa-solid fa-arrow-left"></i>
          </button>
          <div class="text-center text-[11.5px] text-watan-text3 mt-6">حساب نداری؟ <span class="text-green cursor-pointer font-bold" onclick="switchTab('register')">ثبت‌نام کن</span></div>
        </div>

        <!-- گام دوم ورود (OTP) -->
        <div class="auth-step" id="login-step-otp">
          <div class="flex items-center gap-[6px] text-xs text-watan-text2 cursor-pointer mb-[14px] w-fit hover:text-watan-text" onclick="backToStep('login', 'login-step-1')"><i class="fa-solid fa-arrow-right text-[11px]"></i> بازگشت</div>
          <div class="text-[17px] font-extrabold text-watan-text mb-1 text-center">ککد تأیید رو وارد کن</div>
          <div class="text-xs text-watan-text3 text-center mb-[22px]">کد ۵ رقمی به <span id="login-otp-phone-display" dir="ltr" class="inline-block text-green font-bold">۰۹۱۲۳۴۵۶۷۸۹</span> ارسال شد</div>
          <div class="mb-4">
            <div class="flex gap-2 [direction:ltr] justify-center mb-3" id="login-otp-boxes">
              <input class="login-otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none focus:border-green" type="text" maxlength="1" inputmode="numeric" />
              <input class="login-otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none focus:border-green" type="text" maxlength="1" inputmode="numeric" />
              <input class="login-otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none focus:border-green" type="text" maxlength="1" inputmode="numeric" />
              <input class="login-otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none focus:border-green" type="text" maxlength="1" inputmode="numeric" />
              <input class="login-otp-box w-12 h-[54px] bg-s2 border border-b1 rounded-[10px] text-center text-[19px] font-bold text-watan-text outline-none focus:border-green" type="text" maxlength="1" inputmode="numeric" />
            </div>
            <div class="hidden text-center text-[11.5px] text-red mb-3" id="login-otp-error">کد اشتباه است</div>
          </div>
          <button class="flex w-full py-3 rounded-[10px] bg-green text-white text-[13.5px] font-bold items-center justify-center gap-2 hover:bg-[#09a447]" onclick="confirmOtp('login')">
            <span>ورود</span><i class="fa-solid fa-check"></i>
          </button>
          <div class="text-center text-[11.5px] text-watan-text3 mt-4">کد نرسید؟ <span class="text-green cursor-pointer opacity-50 pointer-events-none" id="login-resend-link" onclick="resendOtp('login')">ارسال مجدد</span> <span id="login-resend-timer">(۶۰ثانیه)</span></div>
        </div>
      </div>
    </div>

    <div class="text-center text-[10.5px] text-watan-text3 leading-[1.8] mt-4">با ادامه، <a href="#" class="text-green no-underline">قوانین و مقررات</a> و <a href="#" class="text-green no-underline">حریم خصوصی</a> وطن استودیو رو می‌پذیری.</div>
  </div>

</div>