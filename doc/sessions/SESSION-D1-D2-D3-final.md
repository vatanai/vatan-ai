# SESSION-D1 — پرداخت (زرین‌پال)
# پیش‌نیاز: A2 + B4

## هدف
خرید توکن با زرین‌پال

## پکیج‌های قیمت‌گذاری (از pricing_plans table)
نمونه اولیه:
- ۱۰ توکن — ۵۰,۰۰۰ تومان
- ۳۰ توکن — ۱۳۰,۰۰۰ تومان
- ۱۰۰ توکن — ۴۰۰,۰۰۰ تومان

## Payment Flow
```
کاربر پکیج انتخاب می‌کنه
  ↓
POST /payment/initiate { plan_id }
  ↓
Laravel → زرین‌پال → دریافت payment_url
  ↓
redirect کاربر به زرین‌پال
  ↓
زرین‌پال → callback به /payment/verify
  ↓
verify موفق → quota اضافه بشه → redirect به /app/home
```

## Laravel Routes
```
POST /payment/initiate
GET  /payment/verify   ← callback از زرین‌پال
GET  /app/pricing      ← صفحه انتخاب پکیج
```

## بعد از پرداخت موفق
- payments record ذخیره بشه
- quota_remaining += quota_granted
- total_quota_purchased += quota_granted
- purchase_count += 1
- اگر bloger_ref دارد → commission محاسبه بشه

---

# SESSION-D2 — تکمیل پنل ادمین
# پیش‌نیاز: همه A ها

## بخش‌های admin که باید تموم بشن
- /admin/dashboard: تعداد کاربران، جاب‌های امروز، درآمد
- /admin/users: لیست + جستجو + بلاک
- /admin/products (styles): CRUD کامل با آپلود تصویر
- /admin/jobs: لیست job ها + وضعیت + retry
- /admin/payments: تراکنش‌ها

---

# SESSION-D3 — تست MVP End-to-End
# پیش‌نیاز: همه سشن‌ها

## چک‌لیست نهایی
- [ ] کاربر جدید → سایت باز می‌کنه
- [ ] سبک انتخاب می‌کنه (بدون login)
- [ ] عکس آپلود می‌کنه (بدون login)
- [ ] بساز می‌زنه
- [ ] خروجی blur نمایش داده می‌شه
- [ ] پیام ثبت‌نام نمایش داده می‌شه
- [ ] ثبت‌نام با موبایل و OTP
- [ ] خروجی کامل نمایش داده می‌شه
- [ ] توکن کم شده (۳-۱=۲)
- [ ] پروفایل رو می‌بینه
- [ ] فایل‌ها رو می‌بینه
- [ ] توکن تموم می‌شه → صفحه خرید
- [ ] پرداخت → توکن اضافه می‌شه
- [ ] همه بدون خطا در موبایل

## Performance
- زمان پردازش < 120 ثانیه
- UI بدون lag روی موبایل
