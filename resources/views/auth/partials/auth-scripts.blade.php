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

function normalizePhoneDigits(value) {
  const fa = '۰۱۲۳۴۵۶۷۸۹';
  const ar = '٠١٢٣٤٥٦٧٨٩';
  let phone = String(value).trim()
    .replace(/[۰-۹]/g, digit => String(fa.indexOf(digit)))
    .replace(/[٠-٩]/g, digit => String(ar.indexOf(digit)))
    .replace(/[\s\-()]/g, '');

  if (phone.startsWith('+98')) return '0' + phone.slice(3);
  if (phone.startsWith('0098')) return '0' + phone.slice(4);
  if (phone.startsWith('98') && phone.length === 12) return '0' + phone.slice(2);
  if (/^9\d{9}$/.test(phone)) return '0' + phone;
  return phone;
}

function normalizeOtpDigits(value) {
  return normalizePhoneDigits(value).replace(/[^0-9]/g, '').slice(0, 5);
}

function applyOtpCode(value, shouldSubmit = true) {
  const code = normalizeOtpDigits(value);
  const boxes = Array.from(document.querySelectorAll('.otp-box'));
  boxes.forEach((box, index) => {
    box.value = code[index] || '';
    box.classList.remove('border-rose-500');
  });
  document.getElementById('otp-error').classList.add('hidden');
  updateStageHeight();

  if (code.length === boxes.length) {
    boxes[boxes.length - 1]?.focus({preventScroll: true});
    if (shouldSubmit) confirmOtp();
  } else {
    boxes[code.length]?.focus({preventScroll: true});
  }
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
  const phone = normalizePhoneDigits(input.value);
  input.value = phone;

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
  const code = normalizeOtpDigits(Array.from(boxes).map(b => b.value).join(''));

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
    goToStep('step-3');
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
  const birthDayInput = document.getElementById('birth-day-input');
  const birthMonthInput = document.getElementById('birth-month-input');
  const birthYearInput = document.getElementById('birth-year-input');
  const birthdateWrap = document.getElementById('birthdate-wrap');
  const birthdateError = document.getElementById('birthdate-error');
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

  const birthDay = Number(normalizePhoneDigits(birthDayInput.value));
  const birthMonth = Number(birthMonthInput.value);
  const birthYear = Number(normalizePhoneDigits(birthYearInput.value));
  const currentJalaliYear = Number(birthYearInput.dataset.currentYear);
  const maxDay = birthMonth >= 1 && birthMonth <= 6 ? 31 : 30;
  const birthdateIsValid = Number.isInteger(birthDay)
    && Number.isInteger(birthMonth)
    && Number.isInteger(birthYear)
    && birthMonth >= 1 && birthMonth <= 12
    && birthDay >= 1 && birthDay <= maxDay
    && birthYear >= 1250 && birthYear <= currentJalaliYear;

  if (!birthdateIsValid) {
    birthdateWrap.querySelectorAll('input, select').forEach(el => el.classList.add('border-rose-500'));
    birthdateError.classList.remove('hidden');
    valid = false;
  } else {
    birthdateWrap.querySelectorAll('input, select').forEach(el => el.classList.remove('border-rose-500'));
    birthdateError.classList.add('hidden');
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
      birth_day: birthDay,
      birth_month: birthMonth,
      birth_year: birthYear,
      phone: currentPhone
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
    const normalized = normalizeOtpDigits(box.value);
    if (normalized.length > 1) {
      applyOtpCode(normalized);
      return;
    }
    box.value = normalized;
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
  box.addEventListener('paste', (event) => {
    const pastedCode = normalizeOtpDigits(event.clipboardData?.getData('text') || '');
    if (!pastedCode) return;
    event.preventDefault();
    applyOtpCode(pastedCode);
  });
});

attachPersianKeyboardGuard('password-input', 'password-wrap', 'password-error', 'رمز عبور باید حداقل ۶ کاراکتر باشد');
attachPersianKeyboardGuard('password-confirm-input', 'password-confirm-wrap', 'password-confirm-error', 'رمز عبور و تکرار آن یکسان نیست');
window.addEventListener('DOMContentLoaded', () => {
  updateStageHeight();
});
</script>
