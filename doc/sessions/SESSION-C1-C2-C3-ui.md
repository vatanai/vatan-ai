# SESSION-C1 — صفحه اصلی App (Home)
# پیش‌نیاز: A1 + A3

## هدف
تکمیل app/index.html — صفحه خانه

## وضعیت فعلی (از app-brief.txt)
- هدر: تقریباً کامل ✅
- بخش خوش‌آمد: نیاز به اصلاح
- اسلایدر سبک‌ها: نیاز به اصلاح
- مدل‌های AI: نیاز به اصلاح
- گالری: موجود
- نوار پایین: ✅

## کارهای باقیمانده
1. اسلایدر سبک‌ها: بی‌نهایت، بدون فلش، hover روشن بشه
2. مدل‌های AI: همه در یک ردیف، بدون باکس، hover رنگی
3. سرچ: آیکون سمت چپ، عرض کمتر، opacity کمتر
4. بخش خوش‌آمد: ۲۰٪ پایین‌تر

## قوانین UI
- RTL
- Dark mode (--bg: #0c0c10)
- Tailwind v3
- Font: Vazirmatn
- هدر padding-top: 48px

---

# SESSION-C2 — پروفایل کاربر
# پیش‌نیاز: A2 + B4

## هدف
app/pages/profile.html — داشبورد کاربر

## بخش‌ها
- آمار: تعداد تولیدها، توکن باقی، کل خرج‌شده
- تاریخچه تولیدها (thumbnail grid)
- تنظیمات: نام، شماره (read-only)
- دکمه خروج (قرمز)
- دکمه خرید توکن (سبز)

## API
```
GET /api/user/profile → { name, phone, quota, stats, segment }
GET /api/user/jobs?page=1 → لیست تولیدها
```

---

# SESSION-C3 — صفحه فایل‌ها
# پیش‌نیاز: B2 + B3

## هدف
app/pages/files.html — تمام عکس‌های تولیدشده کاربر

## UI
- grid ۲ ستونه (مثل اینستاگرام)
- thumbnail هر عکس
- کلیک → full view
- دکمه دانلود
- دکمه «دوباره بساز» (با همان سبک)
- حذف با تایید

## API
```
GET /api/user/files?page=1 → لیست با output_url
DELETE /api/user/files/{job_id}
```
