# SESSION-A2 — احراز هویت (Auth)
# پیش‌نیاز: SESSION-A1 تموم شده باشه

## هدف این سشن
ثبت‌نام + ورود با موبایل و OTP — بدون پکیج خارجی، فقط Laravel

## Login Flow
1. کاربر شماره موبایل وارد می‌کنه
2. OTP ۵ رقمی به SMS ارسال می‌شه
3. کاربر OTP را وارد می‌کنه
4. اگر کاربر جدید → ثبت‌نام خودکار + توکن رایگان از settings
5. اگر کاربر قدیمی → ورود مستقیم
6. Redirect → /app/home

## Test Data
- شماره تست: 09120000000 → OTP: 11111
- مستقیم وارد /app می‌شه بدون SMS واقعی

## Default Tab
صفحه login → tab پیش‌فرض: ثبت‌نام (نه ورود)

## Auth Routes
```
POST /auth/send-otp     → ارسال کد
POST /auth/verify-otp   → تایید کد + login/register
POST /auth/logout       → خروج
GET  /auth/check        → بررسی session فعال
```

## Middleware
- auth.app → برای تمام /app routes
- auth.admin → برای تمام /admin routes
- اگر بدون auth → redirect به /login

## Token Grant (هنگام ثبت‌نام)
```php
// بعد از ثبت‌نام موفق
$defaultQuota = Settings::getValue('default_quota', 3);
$user->quota_remaining = $defaultQuota;
$user->save();
```

## Session / Token
از Laravel Session استفاده کن (نه JWT در فاز اول)

## OTP Table (اضافه کن به migrations)
- id: UUID PK
- phone: TEXT
- code: TEXT (hashed)
- expires_at: TIMESTAMPTZ (5 دقیقه)
- used_at: TIMESTAMPTZ NULLABLE
- created_at: TIMESTAMPTZ

## وضعیت
- [ ] OTP ارسال کار می‌کنه
- [ ] verify کار می‌کنه
- [ ] کاربر جدید → توکن رایگان دریافت می‌کنه
- [ ] redirect بعد از login درست کار می‌کنه
- [ ] test با 09120000000 کار می‌کنه
