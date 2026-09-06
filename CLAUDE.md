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
9. **شماره نسخه داشبورد باید با هر تغییری روی پنل مدیریت افزایش پیدا کند.** این عدد ردیف «ورژن داشبورد» توی فایل مشترک `VERSION` (ریشه‌ی پروژه — همون فایلی که شمارش UI محسن/Backend امیر رو هم داره) نگه‌داری می‌شه و توی هدر (`header.blade.php`) به‌صورت کم‌رنگ به شکل `V.<عدد>` نمایش داده می‌شه. بعد از هر تغییر/تسک روی `resources/views/admin/**` یا `resources/views/layouts/admin.blade.php`، یا دستور `php artisan admin:bump-version` رو بزن (خودکار یکی زیادش می‌کنه)، یا خودِ عدد جلوی «ورژن داشبورد» توی فایل `VERSION` رو دستی عوض کن — هر دو حالت بلافاصله توی هدر پنل و کل پروژه اعمال می‌شه.

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

## دیپلوی و پوش (Deploy) — همیشه دقیقاً همین مراحل

**اپ صحیح روی Liara همیشه `demovatan` است، نه `aivatan`** (اون اپ اصلاً وجود نداره). دامنه‌ی `aivatan.com` به‌صورت custom domain روی همین اپ `demovatan` ست شده. پلتفرم عمداً `laravel` (بدون Docker) است و نباید بدون تایید صریح کاربر عوض بشه — یه Dockerfile قدیمی و بلااستفاده هم توی ریشه‌ی پروژه با اسم `_archive_unused_Dockerfile.txt` نگه داشته شده، فعالش نکن.

### روال استاندارد هر بار (کد + دیتابیس با هم)

```bash
# ۱. کامیت و پوش (توصیه‌شده، برای بک‌آپ و تاریخچه)
cd "/Users/mohsenmac/01. mohsen/VATAN WEB/01. vatan ai/website/vatan-ai"
git add -A
git commit -m "توضیح تغییرات"
git push origin crm-integration

# ۲. دیپلوی — بدون --no-cache (سریع‌تره، از کش لایه‌های Docker استفاده می‌کنه)
liara deploy --app demovatan --platform laravel --port 3000

# ۳. فقط اگر صفحه‌ای بعد از دیپلوی هنوز نسخه‌ی قدیمی رو نشون داد (شک به کش خراب)،
#    یک‌بار با --no-cache بزن تا کل ایمیج از صفر ساخته بشه:
# liara deploy --app demovatan --platform laravel --port 3000 --no-cache

# ۴. بعد از تمومِ کامل دیپلوی، حتماً یک SSH تازه (نه تب قدیمی) به demovatan بزن:
php artisan migrate --force
```

نیازی به پاک کردن دستی کش (`view:clear`/`config:clear`) نیست — این کار الان خودکار توی هر build از طریق `composer.json` (`post-install-cmd` / `post-update-cmd`) انجام می‌شه، چون پوشه‌ی `storage` روی یک دیسک دائمی (persistent disk) مونت شده و کش‌های قدیمی می‌تونن بین دیپلوی‌ها باقی بمونن.

### نکته‌ی حیاتی: دیتابیس لوکال با Production جدا هستن

هر چیزی که از پنل ادمین **لوکال** اضافه/ویرایش می‌کنی (دسته‌بندی، محصول، تنظیمات...) فقط توی دیتابیس لوکال (`vatan_ai` روی پورت 8889) می‌مونه. دیپلوی فقط **کد** رو به سایت می‌بره، هیچ ردیف دیتابیسی خودکار منتقل نمی‌شه. اگه بعد از یه سری کار لوکال چیزی روی سایت نبود، احتمالاً دیتاست نه کد.

برای انتقال داده‌ی جدید لوکال به Production:
1. یه dump فقط-داده از جدول موردنظر بگیر (با مسیر کامل mysqldump چون MAMP توی PATH نیست):
   ```bash
   /Applications/MAMP/Library/bin/mysql80/bin/mysqldump -h127.0.0.1 -P8889 -uroot -proot --no-create-info --complete-insert --skip-triggers --skip-add-locks vatan_ai NAME_JADVAL > database/data-import/NAME_JADVAL.sql
   ```
2. توی همون فایل، `INSERT INTO` رو به `INSERT IGNORE INTO` تغییر بده (وگرنه اگه یه ردیف با id تکراری برخورد کنه، کل import fail می‌شه و هیچی اضافه نمی‌شه).
3. یه migration جدید idempotent بساز که این فایل رو با `DB::unprepared(file_get_contents(database_path('data-import/NAME_JADVAL.sql')))` اجرا کنه — **حتماً توی `database/` باشه، نه `storage/app/`** (چون `storage/app/.gitignore` تقریباً همه‌چیز رو نادیده می‌گیره و فایل هیچ‌وقت توی دیپلوی نمی‌ره).
4. مرحله ۴ روال بالا (`php artisan migrate --force`) این ایمپورت رو هم خودکار انجام می‌ده.

### اگه دیپلوی خیلی کند بود یا ارور داد

- پلن فعلی اپ (CPU/RAM) رو از اینجا چک/بالا ببر: `https://console.liara.ir/apps/demovatan/resize` — با منابع خیلی کم (مثلاً ۰.۵ گیگ رم)، build ممکنه خیلی کند بشه یا گیر کنه.
- `vendor/` توی `.liaraignore` هست و نباید حذفش کنی — Liara خودش موقع build با composer نصبش می‌کنه، آپلود کردنش فقط زمان تلف می‌کنه.

### خطای Permission denied بعد از دیپلوی (فایل config یا هر فایل php)

اگه بعد از دیپلوی، سایت با `Warning: require(...): Failed to open stream: Permission denied` بالا نیومد، یعنی یک یا چند فایل روی مک با پرمیشن بسته (600 — فقط خواندنی برای مالک) ذخیره شدن. `liara deploy` پرمیشن فایل‌ها رو عیناً به سرور می‌بره و چون PHP روی سرور با یوزر دیگه‌ای اجرا می‌شه، نمی‌تونه فایل رو بخونه. (خطای بعدیش مثل `Class "view" does not exist` فقط عارضه‌ی همینه، نه مشکل جدا.)

**قبل از هر دیپلوی** (یا حداقل هر وقت فایل جدیدی به پروژه اضافه شده) این رو یک بار اجرا کن تا پرمیشن‌های بسته درست بشن:

```bash
find . -type f -perm 600 -not -path "./vendor/*" -not -path "./node_modules/*" -not -path "./.git/*" -not -path "./storage/*" -not -name ".env" -exec chmod 644 {} \;
```

(`.env` عمداً مستثنی شده — اون باید خصوصی بمونه و اصلاً دیپلوی هم نمی‌شه.)
