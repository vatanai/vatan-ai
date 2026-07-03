# CLAUDE.md — پنل مدیریت وطن استودیو (Admin Dashboard)

این فایل در ریشه‌ی پروژه است و باید در هر چت/سشن جدید خودکار خونده بشه. هر کاری روی پنل مدیریت (`resources/views/admin/**`, `resources/views/layouts/admin.blade.php`) باید از قوانین همین فایل و سند کامل `doc/design-system.md` تبعیت کنه.

## منبع رسمی طراحی

- **سند کامل قوانین ظاهری:** `doc/design-system.md` — همیشه قبل از هر تغییر ظاهری این سند رو چک کن.
- **فایل مرجع اصلی رنگ/استایل:** «یوآی داشبورد محسن» — یک فایل HTML استاتیک جدا (خارج از این پروژه) که مبنای کل سیستم رنگی/کامپوننت‌هاست.
- **فایل توکن‌های رنگی و کامپوننت‌های پایه:** `public/admin/css/design-tokens.css` — لود می‌شه در `resources/views/layouts/admin.blade.php`.

## قوانین سخت‌گیرانه (Non-negotiable)

1. **هیچ رنگ hex ثابتی مستقیم در بلید فایل‌ها ننویس.** همیشه از `var(--...)` تعریف‌شده در `design-tokens.css` استفاده کن.
2. **هیچ توکن/رنگ جدیدی بدون تایید کاربر اضافه نکن.** اگر به رنگ جدیدی نیاز بود، اول از کاربر بپرس و در `doc/design-system.md` ثبتش کن.
3. **پارشیال‌های مشترک را همیشه استفاده کن، هرگز از نو ننویس:**
   - هدر: `resources/views/admin/partials/header.blade.php`
   - مینی‌سایدبار: `resources/views/admin/partials/mini-sidebar.blade.php`
   - سایدبار منوها: `resources/views/admin/partials/sidebar.blade.php`
4. **هر صفحه‌ی جدید ادمین باید دقیقاً این ساختار را داشته باشد** (نه `admin-wrap`/`admin-sidebar`/`admin-header` قدیمی):
   ```blade
   @section('content')
   <main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
     @include('admin.partials.header')
     <div class="admin-content flex-1 overflow-y-auto ..." id="content">
       ...
     </div>
   </main>
   @endsection
   ```
5. **صفحه‌ای که هنوز بک‌اند ندارد** نباید ۴۰۴ بدهد یا سکوت کامل کند — یا لینک آن به روت واقعی وصل شود، یا با روت catch-all موجود در `routes/web.php` به `resources/views/admin/coming-soon.blade.php` برسد.
6. **متن نشان‌دهنده‌ی بخش‌های ناتمام همیشه «بزودی» است** — نه «در حال طراحی»، نه «به زودی آماده می‌شه» و نه هیچ عبارت دیگر.
7. **فونت کل پنل فقط YekanBakh است** (فایل فونت در `public/fonts` موجود است) — از فونت دیگری استفاده نشود.
8. تغییر تم روز/شب با کلاس `light` روی `<body>` انجام می‌شود (نه `data-theme`)؛ مقدار در `localStorage` کلید `admin-theme` ذخیره می‌شود.

## جدول رنگی فعلی (خلاصه — جزئیات کامل در design-system.md بخش ۴)

| عنوان | روز | شب |
|---|---|---|
| Primary | `#16594f` | همان |
| Accent (لیمویی، فقط تیره) | `#C2FD75` | همان |
| پس‌زمینه صفحه | `#f5f5f5` | `#141a18` |
| سایدبار/هدر/کارت | `#ffffff` | `#030f09` |
| حاشیه + جداکننده (یکی شده) | `#E5E6E6` | `#0e1e14` |
| متن اصلی | `#000000` | `#e3e8f0` / `#a9b4c7` |
| متن ثانویه | `#686E6B` | `#60748a` |
| خط زیرمنو (صاف + کرو، یک متغیر مشترک) | `#dadcdb` | `#1e2d3d` |
| نقطه/متن زیرمنو فعال | `#17584f` | `#C2FD75` |
| موفق (Success) | `#16a34a` | همان |
| هشدار (Warning) | `#f5923a` | همان |
| خطر (Danger) | `#ef4444` | همان |

## قبل از هر تغییر بزرگ ظاهری

طبق روال جاری پروژه: هر مرحله را به‌صورت تسک فارسی جدا ثبت کن (TaskCreate)، تسک را کامل تیک نزن، فقط وقتی کاربر صراحتاً تایید کرد تیک بزن.
