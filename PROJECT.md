# AIPIX — PROJECT BRAIN 🧠
> این فایل مغز مشترک همه چت‌هاست. هر چت جدید اول این فایل رو کامل بخون.
> آخرین آپدیت: ۱۴۰۵/۰۴/۰۶

---

## ۱. پروژه چیست

**AIPIX** یک پلتفرم SaaS تولید محتوای AI است.

- کاربر عکس/متن/ویدیو آپلود می‌کند
- سیستم از طریق OpenRouter به مدل‌های AI وصل می‌شود
- خروجی تصویر/ویدیو تولید و به کاربر تحویل داده می‌شود
- کاربر نه پرامپت می‌بیند، نه مدل AI — کاملاً بلک‌باکس
- خروجی‌ها در پروفایل کاربر ذخیره می‌شوند

**مخاطب:** عموم مردم، کسب‌وکارها، خلاقان محتوا

---

## ۲. Tech Stack

| لایه | ابزار |
|------|-------|
| Backend | Laravel (PHP) |
| Frontend | Blade + Tailwind CSS |
| Icons | Font Awesome 6.5 |
| Charts | Chart.js 4.4 |
| Font | Vazirmatn (RTL) |
| AI | OpenRouter API |
| Hosting | Liara |
| Direction | RTL (فارسی) |

---

## ۳. Design System

### CSS Variables (Dark Theme — پیش‌فرض):
```css
--bg: #0c0c10        /* پس‌زمینه اصلی */
--s1: #111116        /* لایه اول کارت */
--s2: #16161c        /* لایه دوم کارت */
--b1: #222230        /* بوردر */
--b2: #2e2e3e        /* بوردر hover */
--text: #ffffff      /* متن اصلی */
--text2: #a8c4a8     /* متن ثانویه */
--text3: #4d7a56     /* متن کم‌رنگ */
--green: #0BBF53     /* سبز اصلی برند */
--accent: #a07af5    /* بنفش اصلی */
--red: #f05c5c       /* خطا */
--orange: #f5923a    /* هشدار */
```

### Design Tokens:
- **border-radius کارت:** 12px
- **border-radius دکمه:** 8px
- **border-radius badge:** 99px (pill)
- **فونت:** Vazirmatn — وزن‌های 300، 400، 500، 600، 700، 800
- **سایز متن پایه:** 13px
- **فاصله‌گذاری:** gap-based (نه margin)

### کلاس‌های پایه موجود (در admin.css):
```
.crm-btn          → دکمه پایه
.crm-btn-primary  → دکمه اصلی (accent)
.crm-btn-danger   → دکمه خطرناک (red)
.crm-badge        → badge پایه
.crm-badge-green/purple/amber/red/blue/gray
.crm-stat-card    → کارت آمار
.crm-form-input   → input استاندارد
.crm-modal        → مودال
.crm-section-title → تیتر بخش
```

---

## ۴. ساختار فایل‌ها

```
resources/views/
├── layouts/
│   ├── admin.blade.php      ← Layout پنل ادمین (sidebar اینجاست)
│   ├── app.blade.php        ← Layout سایت کاربر
│   └── nav.blade.php        ← ناوبار سایت
├── admin/
│   ├── dashboard.blade.php  ← ✅ موجود (CRM + داشبورد) — 3256 خط
│   ├── crm.blade.php        ← ⚠️ خالی
│   ├── products.blade.php   ← ⚠️ خالی — باید ساخته شود
│   ├── products-create.blade.php ← ⚠️ خالی — باید ساخته شود
│   └── source.html          ← reference قدیمی
├── app/
│   ├── home.blade.php       ← ✅ موجود
│   ├── explore.blade.php    ← ✅ موجود
│   ├── create.blade.php     ← ✅ موجود
│   ├── profile.blade.php    ← ✅ موجود
│   └── ...
└── site/
    ├── home.blade.php       ← ✅ موجود
    └── index.blade.php      ← ✅ موجود

public/admin/
├── css/admin.css            ← ✅ کامل — کلاس‌های CRM
└── js/
    ├── admin.js
    ├── crm.js               ← ✅ موجود (localStorage-based)
    └── crm_js_final.js

routes/web.php               ← ✅ موجود
doc/design-system-colors.txt ← ✅ موجود
```

---

## ۵. Routes موجود

```php
// Root
GET /                        → redirect('/site/home')
GET /site                    → redirect('/site/home')

// Site (Landing)
GET /site/home               → site.home          (name: site.home)
GET /site/pricing            → site.pricing        (name: site.pricing)
GET /site/about              → site.about          (name: site.about)

// Auth
GET /login                   → login.index         (name: login)

// App (User)
GET /app                     → redirect('/app/home')
GET /app/home                → app.home            (name: app.home)
GET /app/explore             → app.explore         (name: app.explore)
GET /app/create              → app.create          (name: app.create)
GET /app/ideas               → app.ideas           (name: app.ideas)
GET /app/profile             → app.profile         (name: app.profile)
GET /app/product/{id}        → app.product         (name: app.product)

// Admin
GET /admin/dashboard                   → admin.dashboard        (name: admin.dashboard)
GET /admin/dashboard/crm               → admin.crm              (name: admin.crm)
GET /admin/dashboard/products          → admin.products         (name: admin.products)
GET /admin/dashboard/products/create   → admin.products-create  (name: admin.products-create)
GET /admin/dashboard/users             → admin.users            (name: admin.users)
GET /admin/dashboard/jobs              → admin.jobs             (name: admin.jobs)
GET /admin/dashboard/payments          → admin.payments         (name: admin.payments)
GET /admin/dashboard/bloggers          → admin.bloggers         (name: admin.bloggers)
```

---

## ۶. معماری محصول

### مدل داده محصول:
هر محصول دارای این بخش‌هاست:
- **Identity:** نام، دسته، وضعیت، تگ‌ها
- **Media:** thumbnail، sample output، before/after
- **AI Config:** مدل primary + fallback‌ها، prompt template
- **Input Schema:** فیلدهای دینامیک از کاربر (عکس/متن/ویدیو/لینک/تاریخ/...)
- **Output Config:** نوع خروجی، رزولوشن، watermark
- **Pricing:** کردیت، سطح قیمتی، تخفیف
- **Display:** استایل کارت، badge، رنگ

### دسته‌بندی محصولات:
```
PEOPLE / BUSINESS / EVENTS / FAMILY / KIDS / PETS / ENTERTAINMENT / PRODUCTS / AVATARS / VIDEOS
```

### AI Pipeline:
کاربر submit → Validation → ساخت prompt → OpenRouter (Primary) → اگه fail شد Fallback → Post-process → ذخیره → تحویل

---

## ۷. وضعیت کلی پروژه

| بخش | وضعیت | درصد |
|-----|--------|------|
| طراحی معماری | ✅ Done | 100% |
| Design System | ✅ Done | 100% |
| Layout ادمین | ✅ Done | 85% |
| Dashboard/CRM | ✅ Done | 90% |
| پنل محصولات | ⚠️ Empty | 0% |
| سایت کاربر (UI) | 🔄 Partial | 40% |
| Backend/API | ❌ Not Started | 0% |
| AI Integration | 🔄 Basic Test | 10% |
| CRM آنلاین | ❌ localStorage only | 5% |
| Deploy | 🔄 در حال انجام | — |

---

## ۸. تسک رجیستری

### 🔴 بحرانی (باید همین الان):
- [ ] Deploy روی Liara
- [ ] ساخت admin/products.blade.php
- [ ] ساخت admin/products-create.blade.php

### 🟠 مهم (این هفته):
- [ ] اضافه کردن منوی محصولات به Sidebar
- [ ] ساخت admin/products-categories.blade.php
- [ ] ساخت admin/products-pricing.blade.php
- [ ] ساخت admin/orders.blade.php
- [ ] ساخت admin/analytics.blade.php

### 🟡 ضروری (هفته آینده):
- [ ] Database migrations + Models
- [ ] Products API
- [ ] Orders API + AI Pipeline
- [ ] CRM آنلاین (localStorage → database)

### 🟢 بعداً:
- [ ] Users API + Auth کامل
- [ ] Payment integration
- [ ] Marketing pages
- [ ] Launch

---

## ۹. چت‌بندی پروژه

> هر چت = یک فایل/صفحه. بعد از اتمام هر چت، وضعیت همین فایل رو آپدیت کن.

### 🧠 مغز پروژه — BRAIN (همیشه open):
- نگه‌داری PROJECT.md
- تصمیمات استراتژیک
- هیچ کدی اینجا نمی‌زنیم

### 📦 پنل ادمین:
| اسم چت | فایل هدف | وضعیت |
|--------|----------|--------|
| پنل ادمین — منو و سایدبار | layouts/admin.blade.php | ⏳ Pending |
| طراحی صفحه داشبورد *(موجود)* | admin/dashboard.blade.php | 🔄 نیاز به KPI محصولات |
| پنل ادمین — لیست محصولات | admin/products.blade.php | ⏳ Pending |
| پنل ادمین — ثبت محصول (بخش اول) | products-create — Identity+Media | ⏳ Pending |
| پنل ادمین — ثبت محصول (بخش دوم) | products-create — AI Config+Schema | ⏳ Pending |
| پنل ادمین — ثبت محصول (بخش سوم) | products-create — Output+Pricing+Display | ⏳ Pending |
| پنل ادمین — دسته‌بندی‌ها | admin/products-categories.blade.php | ⏳ Pending |
| پنل ادمین — قیمت‌گذاری و تخفیف | admin/products-pricing.blade.php | ⏳ Pending |
| پنل ادمین — سفارشات | admin/orders.blade.php | ⏳ Pending |
| پنل ادمین — گزارش و آنالیتیکس | admin/analytics.blade.php | ⏳ Pending |

### 🌐 سایت کاربر:
| اسم چت | فایل هدف | وضعیت |
|--------|----------|--------|
| طراحی صفحه لندینگ aivatan.com *(موجود)* | site/home.blade.php | 🔄 Partial |
| طراحی صفحه خانه اپلیکیشن *(موجود)* | app/home.blade.php | 🔄 Partial |
| طراحی صفحه اکسپلور | app/explore.blade.php | ⏳ Pending |
| طراحی صفحه بساز *(موجود)* | app/create.blade.php | 🔄 Partial |
| طراحی صفحه پروفایل *(موجود)* | app/profile.blade.php | 🔄 Partial |
| صفحه لاگین *(موجود)* | login/index.blade.php | 🔄 Partial |

### 🎨 سیستم طراحی:
| اسم چت | هدف | وضعیت |
|--------|-----|--------|
| طراحی دکمه های سفارشی *(موجود)* | کامپوننت‌های مشترک | 🔄 Partial |

### ⚙️ بک‌اند:
| اسم چت | هدف | وضعیت |
|--------|-----|--------|
| بک‌اند — دیتابیس و مدل‌ها | migrations + models | ⏳ Pending |
| بک‌اند — API محصولات | ProductController | ⏳ Pending |
| بک‌اند — API سفارشات و AI | OrderController + OpenRouter | ⏳ Pending |
| بک‌اند — API کاربران و احراز هویت | UserController + Auth | ⏳ Pending |

### 📊 سی‌آر‌ام:
| اسم چت | هدف | وضعیت |
|--------|-----|--------|
| سی‌آر‌ام آنلاین | localStorage → database + API | ⏳ Pending |

---

## ۱۰. دسترسی‌های هر چت

هر چت جدید باید:
1. **این فایل رو بخونه** → `PROJECT.md`
2. **فولدر پروژه رو وصل کنه** → `/Users/mohsenmac/01. mohsen/VATAN WEB/01. vatan ai/website/ai-vatan-v4`
3. **فقط فایل هدف خودش رو ویرایش کنه**
4. **به فایل‌های دیگه دست نزنه**
5. **بعد از اتمام، وضعیت رو اینجا آپدیت کنه**

---

## ۱۱. Role Prompts

### 🎨 UI Agent:
```
تو یه UI/UX متخصص هستی که روی پروژه AIPIX کار می‌کنی.
اول PROJECT.md رو بخون. فولدر پروژه رو وصل کن.
هدف این چت: [نام فایل]
Design system: Vazirmatn + Tailwind + Font Awesome + Chart.js
CSS variables توی PROJECT.md هستن. کلاس‌های موجود رو توی admin.css رعایت کن.
فقط فایل هدف رو ویرایش کن. گام به گام و بعد از تأیید من.
```

### ⚙️ Backend Agent:
```
تو یه Laravel developer هستی که روی پروژه AIPIX کار می‌کنی.
اول PROJECT.md رو بخون. فولدر پروژه رو وصل کن.
هدف این چت: [نام migration/model/controller]
Stack: Laravel + MySQL + OpenRouter API
فقط فایل‌های هدف رو ایجاد/ویرایش کن. به فایل‌های موجود دست نزن مگه ضرورت باشه.
```

### 📊 CRM Agent:
```
تو یه full-stack developer هستی که CRM پروژه AIPIX رو آنلاین می‌کنی.
اول PROJECT.md رو بخون. فولدر پروژه رو وصل کن.
هدف: تبدیل CRM از localStorage به database + API
فایل CRM موجود: public/admin/js/crm.js (2204 خط — localStorage-based)
```

---

## ۱۲. قوانین کار (Rules)

1. **هیچ‌وقت** بدون تأیید شروع نکن
2. **گام به گام** پیش برو — یه گام = یه commit
3. **فقط فایل هدف** رو لمس کن
4. **بعد از هر گام** وضعیت PROJECT.md رو آپدیت کن
5. **لیمیت چت** رو در نظر بگیر — اگه چت سنگین شد، بگو که چت جدید باز کنیم
6. **Design system** رو همیشه رعایت کن
7. **RTL** رو فراموش نکن

---

## ۱۳. Deployment

- **هاستینگ:** Liara
- **فایل config:** `liara.json` (در root پروژه)
- **مسیر پروژه:** `/Users/mohsenmac/01. mohsen/VATAN WEB/01. vatan ai/website/ai-vatan-v4`
- **مسیر در Bash:** `/sessions/nice-quirky-pasteur/mnt/ai-vatan-v4/`

---

*آخرین آپدیت توسط: BRAIN Chat — تاریخ: ۱۴۰۵/۰۴/۰۶*
