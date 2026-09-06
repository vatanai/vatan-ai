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

  .birth-month-select,
  .birth-month-select option {
    font-family: 'YekanBakh', sans-serif !important;
  }
</style>
