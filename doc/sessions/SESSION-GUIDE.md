# SESSION-GUIDE.md — راهنمای سشن‌بندی وطن استودیو

## چطور از این فایل‌ها استفاده کنی

### قدم اول — همیشه
CLAUDE.md را در root پروژه قرار بده.
Claude Code هر سشن خودکار می‌خونه. نیازی به توضیح مجدد نیست.

### قدم دوم — شروع هر سشن
```
read docs/sessions/[SESSION-FILE].md and start
```

---

## نقشه سشن‌ها — ترتیب اجرا برای MVP

### 🔴 PHASE A — پایه (باید اول تموم بشه)

| سشن | فایل | هدف | وضعیت |
|-----|------|-----|--------|
| A1 | SESSION-A1-database.md | ساخت همه جداول Supabase | ❌ |
| A2 | SESSION-A2-auth.md | ثبت‌نام + ورود با موبایل/OTP | ❌ |
| A3 | SESSION-A3-styles.md | سیستم سبک‌ها + seed data | ❌ |

### 🟡 PHASE B — هسته MVP

| سشن | فایل | هدف | وضعیت |
|-----|------|-----|--------|
| B1 | SESSION-B1-create-page.md | صفحه ایجاد — آپلود عکس + انتخاب سبک | 🔄 |
| B2 | SESSION-B2-job-queue.md | ارسال job به Redis + Worker | ❌ |
| B3 | SESSION-B3-blur-preview.md | نمایش خروجی blur قبل از ثبت‌نام | ❌ |
| B4 | SESSION-B4-token-system.md | سیستم توکن — کم کردن + نمایش | ❌ |

### 🟢 PHASE C — تکمیل UI

| سشن | فایل | هدف | وضعیت |
|-----|------|-----|--------|
| C1 | SESSION-C1-home.md | صفحه اصلی app — تکمیل | 🔄 |
| C2 | SESSION-C2-profile.md | داشبورد کاربر + تاریخچه | 🔄 |
| C3 | SESSION-C3-files.md | صفحه فایل‌ها + دانلود | 🔄 |

### 🔵 PHASE D — تکمیل نهایی

| سشن | فایل | هدف | وضعیت |
|-----|------|-----|--------|
| D1 | SESSION-D1-payment.md | اتصال زرین‌پال + خرید توکن | ❌ |
| D2 | SESSION-D2-admin.md | تکمیل پنل ادمین | 🔄 |
| D3 | SESSION-D3-mvp-test.md | تست end-to-end کل flow | ❌ |

---

## قانون کنترل لیمیت

**هر سشن = یک هدف مشخص**
- شروع سشن: `read CLAUDE.md and [SESSION-FILE].md`
- وسط سشن: اگر موضوع عوض شد → `/clear` یا سشن جدید
- پایان سشن: نتیجه را در فایل سشن ثبت کن (وضعیت: ✅)

**پیام‌های کوتاه = لیمیت کمتر**
- به جای: "همه صفحه رو درست کن"
- بگو: "فقط تابع uploadImage در CreateController را بنویس"

---

## وضعیت کلی MVP
```
[██████░░░░] 60% UI
[████░░░░░░] 40% Backend
[░░░░░░░░░░] 0% Integration
```
هدف: رسیدن به flow کامل بدون خطا
