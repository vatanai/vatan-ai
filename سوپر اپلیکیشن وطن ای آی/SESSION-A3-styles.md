# SESSION-A3 — سبک‌ها (Styles Setup)
# پیش‌نیاز: A1 تموم شده باشه

## هدف این سشن
Seed کردن ۵ سبک اولیه + کامل کردن styles controller

## ۵ سبک اولیه
1. فشن و مد (fashion) — "تصویرت رو به سبک عکاسی مجلات مد تبدیل کن"
2. هنری و نقاشی (artistic) — "تصویرت رو به یک اثر هنری تبدیل کن"
3. کارتونی و انیمه (cartoon) — "تصویرت رو به سبک انیمه تبدیل کن"
4. طبیعت و روستایی (nature) — "تصویرت رو در دل طبیعت قرار بده"
5. مینیمال و مدرن (minimal) — "تصویرت رو با سبک مینیمال مدرن تبدیل کن"

## Admin Routes برای مدیریت سبک‌ها
```
GET  /admin/styles          → لیست
GET  /admin/styles/create   → فرم افزودن
POST /admin/styles          → ذخیره
GET  /admin/styles/{id}/edit → فرم ویرایش
PUT  /admin/styles/{id}     → آپدیت
DELETE /admin/styles/{id}   → حذف (soft delete)
```

## نکته مهم
- prompt هرگز در response API به frontend برنگرده
- فقط name_fa, name_en, description, cover_image, is_active برای frontend
- prompt فقط Worker می‌خونه از DB

## وضعیت
- [ ] ۵ سبک seed شدن
- [ ] Admin CRUD کار می‌کنه
- [ ] API endpoint بدون prompt برمی‌گردونه
