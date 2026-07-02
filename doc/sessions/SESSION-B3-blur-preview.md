# SESSION-B3 — Blur Preview (هسته MVP)
# پیش‌نیاز: B1 + B2 تموم شده باشن

## هدف این سشن
نمایش خروجی blur قبل از ثبت‌نام → انگیزه ثبت‌نام

## فلوی کامل MVP
```
کاربر صفحه رو باز می‌کنه
  ↓
سبک انتخاب می‌کنه (بدون نیاز به login)
  ↓
عکس آپلود می‌کنه (بدون نیاز به login)
  ↓
دکمه «بساز» می‌زنه
  ↓
سیستم عکس رو پردازش می‌کنه (job در queue)
  ↓
خروجی blur شده نمایش داده می‌شه
  ↓
پیام: «برای دریافت عکس کامل ثبت‌نام کن»
  ↓
کاربر ثبت‌نام می‌کنه
  ↓
خروجی کامل (بدون blur) نمایش داده می‌شه
  ↓
توکن کم می‌شه
```

## Backend Logic

### Guest Job
- کاربر guest می‌تونه یک job بزنه
- job با guest_token (UUID در session) ذخیره می‌شه
- بعد از ثبت‌نام، job به user_id وصل می‌شه

### Blur Generation
```php
// بعد از آماده شدن output
// دو نسخه ذخیره کن:
// output_url: عکس کامل (فقط بعد از login)
// output_blur_url: عکس blur شده (برای guest)
```

روش blur: CSS blur روی frontend کافیه (`filter: blur(20px)`)
نیازی به blur واقعی روی سرور نیست در فاز اول.

### Claim Job (بعد از ثبت‌نام)
```
POST /app/create/claim
body: { guest_token: "..." }
→ job.user_id = current_user.id
→ quota_remaining -= 1
→ return output_url (بدون blur)
```

## UI Result Page
- آدرس: /app/result/{job_id}
- اگر guest: عکس blur + دکمه ثبت‌نام
- اگر logged in: عکس کامل + دکمه دانلود + دکمه اشتراک‌گذاری

## وضعیت
- [ ] guest می‌تونه job بزنه
- [ ] blur نمایش داده می‌شه
- [ ] بعد از ثبت‌نام job claim می‌شه
- [ ] توکن کم می‌شه
- [ ] خروجی کامل نمایش داده می‌شه
