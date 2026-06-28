# SESSION-B1 — صفحه ایجاد (Create Page)
# پیش‌نیاز: A1 + A2 تموم شده باشن

## هدف این سشن
صفحه /app/create — کاربر سبک انتخاب می‌کنه + عکس آپلود می‌کنه + دکمه بساز

## UI Requirements
- فایل: app/pages/create.html
- RTL, Dark mode, Tailwind v3
- Instagram-like

## بخش‌های صفحه

### ۱. Style Selector
- لیست سبک‌ها از DB (styles table)
- نمایش: cover_image + name_fa
- یک ردیف اسکرول‌پذیر افقی
- انتخاب شده: border سبز + scale-up
- hover: رنگی

### ۲. Upload Box
- آپلود عکس با drag & drop یا کلیک
- نمایش preview عکس انتخاب‌شده
- validation: فقط jpg/png/webp، max 10MB
- آیکون سمت چپ، عرض متوسط، opacity کمتر

### ۳. Generate Button
- متن: «بساز»
- رنگ: --green
- disabled اگر عکس یا سبک انتخاب نشده
- loading state هنگام ارسال

## Backend Route
```
POST /app/create/generate
```
Input:
- style_id: UUID
- image: file

Logic:
1. validate ورودی‌ها
2. بررسی quota_remaining > 0 (اگر نه → redirect به خرید)
3. آپلود عکس به Supabase Storage (bucket: inputs)
4. ساخت job_log record (status: pending)
5. push به Redis queue
6. return job_id به frontend
7. frontend polling یا redirect به صفحه نتیجه

## Queue Job (Laravel)
```php
// App\Jobs\ProcessImageJob
// dispatch به redis queue
// Worker Python این رو pick می‌کنه
```

## توجه مهم
- prompt هرگز به frontend نمی‌ره
- model name هرگز به frontend نمی‌ره
- Worker فقط style_id دریافت می‌کنه

## وضعیت
- [ ] UI سبک‌ها از DB لود می‌شه
- [ ] آپلود عکس کار می‌کنه
- [ ] job ساخته می‌شه و به queue می‌ره
- [ ] quota چک می‌شه
