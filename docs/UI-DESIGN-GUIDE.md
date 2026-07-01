# راهنمای رابط کاربری داشبورد وطن استودیو
# UI Design Guide — AI Vatan Admin Panel

> این فایل مرجع کامل طراحی رابط کاربری پنل مدیریت است.
> هر سشن جدید ابتدا این فایل را بخوان تا یوآی اصلی را بشناسی.

---

## ۱. شناسه پروژه

- **نام:** وطن استودیو / AI Vatan
- **پنل:** Admin Panel (`/admin`)
- **مسیر layout:** `resources/views/layouts/admin.blade.php`
- **Partials:** `resources/views/admin/partials/`
  - `mini-rail.blade.php` — ستون آیکون ۶۴px سمت راست
  - `sidebar.blade.php` — سایدبار اصلی ۲۶۵px
- **راهنمای دیزاین:** `docs/UI-DESIGN-GUIDE.md` (همین فایل)
- **تم پیش‌فرض:** Dark (تاریک)
- **جهت:** RTL

---

## ۲. ساختار Layout

```
┌───────────────────────────────────────────────────────┐
│  TOPBAR (fixed, height:68px, right:329px, left:0)      │
├─────────────────────────────┬──────────┬──────────────┤
│                             │          │              │
│  MAIN CONTENT               │ SIDEBAR  │  MINI-RAIL   │
│  margin-right: 329px        │  265px   │    64px      │
│  padding-top: 68px          │right:64px│  right:0     │
│                             │          │              │
└─────────────────────────────┴──────────┴──────────────┘
```

| المان | Position | Width | Right |
|-------|----------|-------|-------|
| Mini-Rail | fixed | 64px | 0 |
| Sidebar | fixed | 265px | 64px |
| Topbar | fixed | 100%-329px | 329px |
| Content | normal | - | margin-right: 329px, pt: 68px |

---

## ۳. توکن‌های رنگ (CSS Variables)

### حالت روشن (Light — `:root`)
```css
--primary:        #16594f
--primary-l:      rgba(22,89,79,.10)
--primary-m:      rgba(22,89,79,.18)
--accent:         #C2FD75
--logo-green:     #0bbf53

--page-bg:        #f5f5f5
--sb-bg:          #ffffff
--topbar-bg:      #ffffff
--card-bg:        #ffffff

--border:         #E5E6E6
--divider:        #EAECEC

--text-h:         #000000
--text-main:      #000000
--text-soft:      #686E6B

--nav-text:       #2a2a2a
--nav-hover:      rgba(22,89,79,.06)
--nav-active:     rgba(22,89,79,.10)
--nav-active-t:   #16594f

--sub-line:       #D6D9D8
--sub-dot:        #C8CBCA
--sub-dot-active: #16594f
--sub-text:       #686E6B
--sub-text-active:#16594f

--input-bg:       #f5f5f5
--shadow-card:    rgba(145,158,171,.20) 0 0 2px, rgba(145,158,171,.12) 0 12px 24px -4px
--shadow-sb:      -4px 0 20px rgba(0,0,0,.05)
```

### حالت تاریک (Dark — `[data-theme="dark"]`)
```css
--page-bg:        #141a18
--sb-bg:          #030f09
--topbar-bg:      #030f09
--card-bg:        #030f09

--border:         #0e1e14
--divider:        #0a1710

--text-h:         #e3e8f0
--text-main:      #a9b4c7
--text-soft:      #60748a

--nav-text:       #8a99ad
--nav-hover:      rgba(255,255,255,.05)
--nav-active:     rgba(22,89,79,.25)
--nav-active-t:   #C2FD75

--sub-line:       #1e2d3d
--sub-dot:        #263545
--sub-dot-active: #C2FD75
--sub-text:       #60748a
--sub-text-active:#C2FD75

--input-bg:       #0d1a10
--shadow-card:    0 4px 24px rgba(0,0,0,.35)
--shadow-sb:      -4px 0 30px rgba(0,0,0,.3)
```

---

## ۴. تایپوگرافی

```
Font: Vazirmatn (Google Fonts)
Weights: 300, 400, 500, 600, 700, 800, 900
Import: https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900
Direction: RTL
```

---

## ۵. کتابخانه‌های CDN

```html
<!-- Font Awesome 6.5.0 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Chart.js 4.4.1 -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
```

---

## ۶. آیتم‌های منوی سایدبار (نگه‌داری شود)

### منوی اصلی (Main Nav)
| آیتم | URL | آیکون | نوع |
|------|-----|-------|-----|
| مرکز فرماندهی | /admin/dashboard | fa-bolt-lightning | لینک مستقیم |
| محصولات | /admin/products | fa-box-open | با زیرمنو |
| سفارشات | /admin/orders | fa-cart-shopping | با زیرمنو |
| مدیریت مدل‌ها | /admin/prompts | fa-microchip | لینک مستقیم |
| لاگ جاب‌ها | /admin/jobs | fa-list-check | لینک مستقیم |
| تنظیمات | — | fa-gear | با زیرمنو |

### زیرمنوی محصولات
- لیست محصولات → /admin/products
- ثبت محصول جدید → /admin/products/create
- داشبورد محصولات → /admin/products/dashboard
- دسته‌بندی‌ها → /admin/products/categories
- قیمت‌گذاری → /admin/products/pricing

### زیرمنوی سفارشات
- لیست سفارشات → /admin/orders
- آنالیتیکس سفارشات → /admin/orders/analytics

### زیرمنوی تنظیمات
- CRM → /admin/crm ✅ (کاری می‌کند)
- مدیریت ادمین‌ها → /admin/settings/admins (آینده)
- سطوح دسترسی → /admin/settings/access (آینده)
- تنظیمات سیستم → /admin/settings/system (آینده)
- درگاه پرداخت → /admin/settings/payment-gateway (آینده)
- پشتیبان‌گیری → /admin/settings/backup (آینده)
- لاگ فعالیت ادمین‌ها → /admin/settings/logs (آینده)

---

## ۷. ساختار CSS کلاس‌ها

### سایدبار
```css
.sidebar          /* aside اصلی */
.sidebar.sb-collapsed  /* حالت icon-only */
.sb-logo          /* بلوک لوگو بالا */
.sb-logo-mark     /* کادر ۴۰×۴۰ با گرادیانت سبز */
.sb-logo-name     /* نام وطن. */
.sb-logo-sub      /* زیرنویس Admin Panel */
.sb-section       /* برچسب section (MAIN, مدیریت, ...) */
.sb-divider       /* خط جداکننده */
.sb-user          /* ردیف کاربر پایین */
```

### آیتم‌های ناوبری
```css
.nav-item         /* wrapper div */
.nav-link         /* ردیف اصلی (flex) */
.nav-link.active  /* حالت فعال */
.nav-icon         /* جعبه آیکون ۳۷×۳۷ */
.nav-label        /* متن لینک */
.nav-chev         /* فلش باز/بسته */
.nav-chev.open    /* فلش چرخیده ۱۸۰ درجه */
.nav-badge        /* badge کوچک */
.badge-red        /* قرمز — اعلان */
.badge-purple     /* بنفش — آینده */
```

### زیرمنو سطح ۲
```css
.submenu          /* max-height:0 → open:max-height:1000px */
.submenu.open
.sub-track        /* wrapper با خط عمودی ::before */
.sub-item         /* هر آیتم با bracket arm ::before */
.sub-item.active
.sub-item.above-active  /* آیتم‌های بالاتر از active */
.sub-dot          /* دایره کوچک */
.sub-label        /* متن */
.sub-chev         /* فلش برای زیرمنو سطح ۳ */
```

### زیرمنو سطح ۳
```css
.sub-sub-wrap     /* max-height:0 → open */
.sub-sub-wrap.open
.sub-sub-track
.sub-sub-item
.sub-sub-item.active
.sub-sub-dot
.sub-sub-label
```

### بخش آپدیت در آینده
```css
.future-section
.sb-future-toggle     /* دکمه بازکردن */
.sb-future-label      /* متن بنفش */
.sb-future-chev       /* فلش */
.future-wrap          /* max-height:0 → open */
.future-nav-item
.future-nav-link      /* opacity:.5 — dimmer */
.future-nav-icon      /* سبک‌تر از nav-icon اصلی */
.future-sub-section   /* برچسب کوچک */
.future-sub-wrap      /* max-height:0 → open */
.future-sub-track
.future-sub-item
.future-sub-dot
.future-sub-label
```

### مینی ریل
```css
.mini-rail           /* ستون ۶۴px fixed right:0 */
.mini-rail-logo      /* لوگو ۳۸×۳۸ */
.mini-rail-divider   /* خط جداکننده */
.mini-rail-spacer    /* فاصله متغیر */
.mini-btn            /* دکمه ۳۸×۳۸ */
.mini-btn.active
.mini-btn-tooltip    /* tooltip سمت چپ */
```

### تاپ‌بار
```css
.topbar              /* fixed top:0 right:329px */
.tb-menu-btn         /* دکمه hamburger */
.tb-breadcrumb       /* مسیر صفحه */
.tb-search           /* جستجو */
.tb-actions          /* گروه دکمه‌ها */
.tb-btn              /* دکمه اکشن ۳۸×۳۸ */
.tb-sep              /* خط جداکننده عمودی */
.tb-live             /* chip زنده */
.tb-live-dot         /* نقطه سبز */
.tb-live-text
.tb-refresh-btn
```

### کارت محتوا
```css
.vtn-card            /* background + border + shadow */
```

---

## ۸. توابع JavaScript

| تابع | عملکرد |
|------|---------|
| `toggleSidebar()` | collapse سایدبار (icon-only) |
| `toggleTheme()` | تغییر تم روشن/تاریک روی `<html data-theme>` |
| `toggleSub(wrapId, chevId)` | باز/بسته زیرمنو سطح ۲ |
| `toggleSubSub(wrapId, chevId)` | باز/بسته زیرمنو سطح ۳ |
| `toggleFuture()` | باز/بسته بخش آپدیت در آینده |
| `toggleFutureSub(wrapId, el)` | باز/بسته زیرمنوهای future |
| `miniBtnGo(el)` | فعال کردن دکمه مینی ریل |
| `updateCharts()` | آپدیت رنگ نمودارها هنگام تغییر تم (باید در dashboard تعریف شود) |

---

## ۹. نکات مهم پیاده‌سازی

1. **Dark default**: `<html lang="fa" dir="rtl" data-theme="dark">` در layouts/admin.blade.php
2. **Bracket arm** (╮): با `::before` pseudo-element روی `.sub-item`
3. **خط پیشرفته**: `--line-pct` CSS variable با `getBoundingClientRect()` در JS
4. **Collapsed sidebar**: کلاس `sb-collapsed` روی `<aside>` — عرض ۶۸px
5. **Topbar right**: هنگام collapse، `right: 132px` (64 + 68)
6. **Content margin**: هنگام collapse، `margin-right: 132px`
7. **زیرمنو auto-open**: در `DOMContentLoaded` پاره‌ای از JS layout، زیرمنوی والد آیتم active باز می‌شود
8. **Logo files**: `/public/assets/img/icon_vatan.svg`, `/public/assets/img/vatan-logo.svg`

---

## ۱۰. ساختار فایل‌های View

```
resources/views/
  layouts/
    admin.blade.php          ← تمام CSS + ساختار HTML اصلی
  admin/
    partials/
      mini-rail.blade.php    ← ستون آیکون ۶۴px
      sidebar.blade.php      ← سایدبار ۲۶۵px
    dashboard.blade.php      ← صفحه داشبورد (extends layouts.admin)
    auth/
      login.blade.php        ← فرم ورود ادمین (/admin/login)
    ...
```

---

## ۱۱. قوانین کلی Design (NON-NEGOTIABLE)

- رنگ‌ها **دقیقاً** همان توکن‌های CSS بالا — هیچ رنگ inline جدیدی اضافه نشود
- تمام اعداد `border-radius`: کارت‌ها ۱۶px، ناو آیکون ۱۰px، لوگو ۱۲px، ناو لینک ۱۲px
- فونت فقط Vazirmatn — بدون IRANSans یا سایر فونت‌ها
- Transition همیشه با `cubic-bezier(.4,0,.2,1)`
- هیچ Tailwind inline نگذار در layoutهای admin — فقط کلاس‌های تعریف‌شده

---

*آخرین ویرایش: ۱ تیر ۱۴۰۵ — اعمال شده در session جدید*
