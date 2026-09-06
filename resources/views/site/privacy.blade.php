@extends('layouts.app')

@section('page_title', 'سیاست حفظ حریم خصوصی | وطن')

@php
    $privacySections = [
        [
            'title' => 'اطلاعاتی که ممکن است دریافت کنیم',
            'items' => [
                'نام و اطلاعات حساب کاربری؛',
                'شماره تلفن یا آدرس ایمیل؛',
                'اطلاعات موردنیاز برای ورود و احراز هویت؛',
                'تصاویر و فایل‌های بارگذاری‌شده؛',
                'درخواست‌ها و تنظیمات مربوط به تولید محتوا؛',
                'خروجی‌های ایجادشده توسط کاربر؛',
                'اطلاعات مربوط به توکن‌ها، بسته‌ها و اشتراک‌ها؛',
                'سوابق تراکنش و وضعیت پرداخت؛',
                'اطلاعات مربوط به دستگاه، مرورگر و سیستم‌عامل؛',
                'آدرس IP و اطلاعات امنیتی؛',
                'گزارش‌های فنی و خطاها؛',
                'اطلاعات مربوط به نحوه تعامل با پلتفرم؛',
                'اطلاعات کوکی‌ها و فناوری‌های مشابه، در صورت استفاده.'
            ],
        ],
        [
            'title' => 'تصاویر و فایل‌های کاربران',
            'paragraphs' => [
                'برای ارائه خدمات هوش مصنوعی، ممکن است لازم باشد تصاویر و فایل‌های ارسال‌شده توسط کاربر دریافت، به‌صورت موقت ذخیره، منتقل یا توسط سامانه‌های پردازشی تحلیل شوند.',
                'کاربر با ارسال فایل تأیید می‌کند که اختیار قانونی لازم برای ارائه و پردازش آن را دارد.',
                'کاربر نباید تصویر یا اطلاعات شخص دیگری را بدون داشتن مجوز یا مبنای قانونی لازم در پلتفرم بارگذاری کند. مسئولیت قانونی بودن محتوای بارگذاری‌شده بر عهده همان کاربر است.',
                'در صورتی که فایل یا اطلاعات بارگذاری‌شده شامل داده‌های حساس یا خصوصی اشخاص باشد، کاربر باید پیش از بارگذاری، رضایت صریح و مبنای قانونی لازم را اخذ کرده باشد. پردازش چنین اطلاعاتی نیز فقط در حدود لازم برای ارائه خدمت و قوانین قابل اعمال انجام می‌شود.'
            ],
        ],
        [
            'title' => 'پردازش تصاویر توسط هوش مصنوعی',
            'paragraphs' => [
                'برای ایجاد خروجی، تصاویر و اطلاعات ورودی ممکن است توسط زیرساخت‌های پردازشی پلتفرم یا ارائه‌دهندگان فنی و مدل‌های هوش مصنوعی مورد استفاده پلتفرم پردازش شوند.',
                'این پردازش ممکن است شامل انتقال فنی اطلاعات به زیرساخت ارائه‌دهندگانی باشد که انجام درخواست کاربر به آن‌ها ضروری است.',
                'پلتفرم وطن تلاش می‌کند فقط اطلاعات لازم برای ارائه خدمت را در اختیار سرویس‌های مرتبط قرار دهد. استفاده از ارائه‌دهندگان شخص ثالث تابع الزامات فنی، قراردادی و سیاست‌های قابل اعمال آن ارائه‌دهندگان نیز خواهد بود.'
            ],
        ],
        [
            'title' => 'اهداف استفاده از اطلاعات',
            'items' => [
                'ایجاد و مدیریت حساب کاربری؛',
                'ارائه خدمات پلتفرم؛',
                'پردازش درخواست‌های تولید تصویر و ویدیو؛',
                'نمایش تاریخچه و خروجی‌های کاربر؛',
                'مدیریت توکن و اشتراک؛',
                'پردازش و پیگیری پرداخت‌ها؛',
                'پشتیبانی کاربران؛',
                'جلوگیری از تقلب و سوءاستفاده؛',
                'حفظ امنیت پلتفرم؛',
                'شناسایی و رفع مشکلات فنی؛',
                'بهبود تجربه کاربری و تحلیل عملکرد سرویس؛',
                'اجرای الزامات قانونی؛',
                'ارسال پیام‌های ضروری مرتبط با حساب یا خدمات.'
            ],
            'after' => 'در صورت نیاز قانونی، استفاده از اطلاعات برای اهداف دیگری که نیازمند رضایت جداگانه است، فقط پس از اخذ رضایت مربوط انجام خواهد شد.'
        ],
        [
            'title' => 'مالکیت تصاویر',
            'paragraphs' => [
                'بارگذاری تصویر در پلتفرم وطن به معنای انتقال مالکیت آن تصویر به پلتفرم نیست.',
                'کاربر حقوقی را که نسبت به محتوای اصلی خود دارد حفظ می‌کند.',
                'کاربر فقط مجوز محدود و لازم برای پردازش فنی محتوا جهت ارائه خدمات درخواست‌شده را به پلتفرم و ارائه‌دهندگان فنی ضروری می‌دهد.'
            ],
        ],
        [
            'title' => 'استفاده از تصاویر کاربران برای تبلیغات',
            'paragraphs' => [
                'پلتفرم وطن تصاویر خصوصی کاربران را صرفاً به دلیل بارگذاری در سرویس، به‌عنوان محتوای تبلیغاتی عمومی منتشر نمی‌کند.',
                'استفاده مشخص از محتوای خصوصی کاربر در تبلیغات، شبکه‌های اجتماعی یا نمونه‌کارهای عمومی پلتفرم، در مواردی که نیازمند رضایت است، فقط با رضایت یا اقدام آگاهانه کاربر انجام خواهد شد.'
            ],
        ],
        [
            'title' => 'اکسپلور و ترند',
            'paragraphs' => [
                'اگر پلتفرم قابلیت انتشار عمومی محتوا را داشته باشد، عمومی شدن محتوا باید بر اساس انتخاب یا اقدام مشخص کاربر یا شرایطی باشد که پیش از انتشار به وی اعلام شده است.',
                'در صورت انتشار عمومی، محتوا ممکن است برای سایر کاربران قابل مشاهده باشد و در قسمت‌هایی مانند اکسپلور، ترند یا صفحات عمومی پلتفرم نمایش داده شود.',
                'کاربر باید پیش از عمومی کردن محتوایی که شامل اطلاعات یا تصویر اشخاص دیگر است، مجوزهای لازم را دریافت کند.'
            ],
        ],
        [
            'title' => 'اطلاعات پرداخت',
            'paragraphs' => [
                'پرداخت‌های اینترنتی ممکن است توسط درگاه‌ها یا شرکت‌های ارائه‌دهنده خدمات پرداخت پردازش شوند.',
                'پلتفرم وطن لزوماً اطلاعات کامل کارت بانکی کاربران را دریافت یا نگهداری نمی‌کند و اطلاعات حساس پرداخت می‌تواند مستقیماً توسط ارائه‌دهنده خدمات پرداخت پردازش شود.',
                'اطلاعاتی مانند مبلغ، شماره پیگیری، زمان تراکنش و وضعیت پرداخت ممکن است برای امور مالی و پشتیبانی نگهداری شود.'
            ],
        ],
        [
            'title' => 'کوکی‌ها',
            'paragraphs' => [
                'پلتفرم ممکن است از کوکی‌ها یا فناوری‌های مشابه برای عملکرد صحیح سرویس، حفظ نشست کاربر، امنیت، تنظیمات، تحلیل عملکرد و بهبود تجربه کاربری استفاده کند.',
                'کاربر می‌تواند برخی کوکی‌ها را از طریق تنظیمات مرورگر مدیریت کند؛ بااین‌حال، غیرفعال کردن کوکی‌های ضروری ممکن است موجب اختلال در برخی قابلیت‌های پلتفرم شود.'
            ],
        ],
        [
            'title' => 'اطلاعات فنی',
            'paragraphs' => [
                'برای امنیت، رفع خطا و بهبود سرویس ممکن است اطلاعاتی مانند IP، نوع دستگاه، سیستم‌عامل، مرورگر، زمان ورود، فعالیت‌های امنیتی و گزارش خطاها ثبت شود.',
                'این اطلاعات می‌توانند برای تشخیص حملات، جلوگیری از سوءاستفاده، رفع مشکلات فنی و حفاظت از کاربران استفاده شوند.'
            ],
        ],
        [
            'title' => 'ارائه‌دهندگان خدمات شخص ثالث',
            'items' => [
                'زیرساخت ابری و میزبانی؛',
                'مدل‌های هوش مصنوعی؛',
                'پردازش تصویر و ویدیو؛',
                'ذخیره‌سازی؛',
                'ارسال پیام یا ایمیل؛',
                'تحلیل فنی؛',
                'امنیت؛',
                'پرداخت.'
            ],
            'before' => 'برای ارائه خدمات ممکن است از شرکت‌ها و زیرساخت‌های شخص ثالث در حوزه‌های زیر استفاده شود:',
            'after' => 'در این موارد ممکن است بخشی از اطلاعات موردنیاز برای انجام خدمت به ارائه‌دهنده مربوط منتقل شود. پلتفرم تلاش می‌کند دسترسی ارائه‌دهندگان را به میزان لازم برای انجام خدمات محدود کند.'
        ],
        [
            'title' => 'انتقال و محل پردازش اطلاعات',
            'paragraphs' => [
                'با توجه به ماهیت خدمات اینترنتی و هوش مصنوعی، برخی ارائه‌دهندگان زیرساخت ممکن است سرورهایی در خارج از کشور محل اقامت کاربر داشته باشند.',
                'در نتیجه، اطلاعات موردنیاز برای انجام درخواست ممکن است در زیرساخت‌های داخلی یا خارجی پردازش شود.',
                'استفاده کاربر از قابلیت‌هایی که مستلزم چنین پردازشی هستند، تابع الزامات قانونی مربوط به انتقال و پردازش داده خواهد بود.'
            ],
        ],
        [
            'title' => 'امنیت اطلاعات',
            'paragraphs' => [
                'پلتفرم وطن تلاش می‌کند از اقدامات فنی و سازمانی متعارف برای حفاظت از اطلاعات کاربران استفاده کند.',
                'بااین‌حال، هیچ سامانه اینترنتی، شبکه، سرور یا روش ذخیره‌سازی الکترونیکی را نمی‌توان در برابر تمام تهدیدهای امنیتی صددرصد ایمن دانست.',
                'بنابراین پلتفرم امنیت مطلق و غیرقابل نفوذ بودن سامانه را تضمین نمی‌کند، اما اقدامات متعارف و قانونی لازم را برای حفاظت از اطلاعات تحت کنترل خود انجام می‌دهد.'
            ],
        ],
        [
            'title' => 'مسئولیت امنیت حساب',
            'paragraphs' => [
                'کاربر مسئول حفاظت از اطلاعات ورود به حساب خود است و نباید رمز عبور، کد ورود یا سایر اطلاعات امنیتی حساب را در اختیار دیگران قرار دهد.',
                'فعالیت‌هایی که از طریق حساب کاربر انجام می‌شوند ممکن است به صاحب همان حساب منتسب شوند، مگر اینکه خلاف آن بر اساس بررسی‌های لازم احراز شود.',
                'در صورت مشاهده فعالیت مشکوک، کاربر باید در اسرع وقت با پشتیبانی ارتباط برقرار کند.'
            ],
        ],
        [
            'title' => 'نگهداری اطلاعات',
            'paragraphs' => [
                'اطلاعات کاربران تا زمانی نگهداری می‌شود که برای ارائه خدمات، حفظ امنیت، انجام تعهدات قراردادی، حل اختلافات یا رعایت الزامات قانونی ضروری باشد.',
                'مدت نگهداری انواع اطلاعات می‌تواند بر اساس ماهیت داده و هدف پردازش متفاوت باشد.',
                'برخی اطلاعات ممکن است پس از حذف حساب نیز برای مدت لازم جهت رعایت الزامات قانونی، مالی، امنیتی یا رسیدگی به اختلافات نگهداری شوند.'
            ],
        ],
        [
            'title' => 'حذف حساب و اطلاعات',
            'paragraphs' => [
                'در صورتی که قابلیت حذف حساب در پلتفرم ارائه شده باشد، کاربر می‌تواند از طریق همان قابلیت درخواست خود را ثبت کند؛ در غیر این صورت می‌تواند از مسیر ارتباطی اعلام‌شده توسط پلتفرم درخواست حذف حساب یا اطلاعات قابل حذف را ارسال کند.',
                'حذف اطلاعات در حدود امکانات فنی و الزامات قانونی انجام خواهد شد.',
                'برخی اطلاعات ممکن است به دلایل قانونی، مالی، امنیتی، جلوگیری از تقلب یا اثبات تراکنش‌ها برای مدت لازم نگهداری شوند. همچنین حذف اطلاعات از نسخه‌های پشتیبان ممکن است مطابق چرخه فنی حذف نسخه‌های پشتیبان انجام شود.'
            ],
        ],
        [
            'title' => 'حقوق کاربران',
            'paragraphs' => [
                'کاربر می‌تواند حسب قوانین قابل اعمال، درخواست دسترسی، اصلاح یا حذف اطلاعات خود را مطرح کند.',
                'برای جلوگیری از دسترسی غیرمجاز، پلتفرم می‌تواند پیش از اجرای درخواست، احراز هویت درخواست‌کننده را مطالبه کند.',
                'برخی درخواست‌ها ممکن است در مواردی که نگهداری یا پردازش اطلاعات طبق قانون الزامی است، محدود شوند.'
            ],
        ],
        [
            'title' => 'اطلاعات اشخاص ثالث',
            'paragraphs' => [
                'اگر کاربر اطلاعات یا تصویر شخص دیگری را در پلتفرم وارد کند، مسئول داشتن مجوز و مبنای قانونی لازم برای این اقدام است.',
                'پلتفرم وطن نمی‌تواند به‌طور مستقل رضایت تمام اشخاص حاضر در تصاویر بارگذاری‌شده توسط کاربران را احراز کند.',
                'مسئولیت نقض حریم خصوصی یا حقوق اشخاص ثالث ناشی از بارگذاری غیرمجاز محتوا، در حدود قوانین قابل اعمال، بر عهده همان کاربر است.'
            ],
        ],
        [
            'title' => 'کودکان و نوجوانان',
            'paragraphs' => [
                'کاربران نباید برخلاف قوانین مربوط به کودکان و نوجوانان، اطلاعات یا تصاویر آنان را جمع‌آوری، بارگذاری یا مورد استفاده قرار دهند.',
                'در مواردی که رضایت والدین یا سرپرست قانونی لازم است، مسئولیت دریافت این رضایت بر عهده شخصی است که اطلاعات را در اختیار پلتفرم قرار می‌دهد.',
                'در صورت اطلاع معتبر از پردازش غیرمجاز اطلاعات کودک، پلتفرم می‌تواند اقدامات لازم برای محدودسازی یا حذف اطلاعات مربوط را انجام دهد.'
            ],
        ],
        [
            'title' => 'افشای قانونی اطلاعات',
            'paragraphs' => [
                'پلتفرم وطن اطلاعات خصوصی کاربران را به‌طور عمومی منتشر نمی‌کند، مگر با رضایت یا اقدام کاربر یا در مواردی که قانون اجازه یا الزام کرده باشد.',
                'در صورت دریافت دستور معتبر از مرجع قانونی صالح، پلتفرم ممکن است در حدود الزامات قانونی اطلاعات مورد درخواست را ارائه کند.',
                'همچنین در موارد ضروری برای مقابله با تقلب، تهدید امنیتی یا حفاظت از حقوق قانونی پلتفرم و کاربران، اطلاعات ممکن است در حدود مجاز قانونی پردازش یا ارائه شوند.'
            ],
        ],
        [
            'title' => 'لینک‌ها و سرویس‌های خارجی',
            'paragraphs' => [
                'پلتفرم ممکن است دارای لینک یا اتصال به سرویس‌های دیگر باشد.',
                'پلتفرم وطن کنترل مستقیمی بر سیاست حفظ حریم خصوصی وب‌سایت‌ها و خدمات مستقل شخص ثالث ندارد و کاربر باید شرایط آن‌ها را نیز بررسی کند.'
            ],
        ],
        [
            'title' => 'تغییر سیاست حفظ حریم خصوصی',
            'paragraphs' => [
                'این سیاست ممکن است در نتیجه توسعه خدمات، تغییر فناوری یا تغییر الزامات قانونی اصلاح شود.',
                'نسخه جدید از طریق پلتفرم منتشر خواهد شد و تاریخ آخرین به‌روزرسانی در ابتدای سند درج می‌شود.',
                'در مواردی که تغییرات طبق قانون نیازمند اطلاع یا رضایت جدید کاربر باشد، اقدامات لازم انجام خواهد شد.'
            ],
        ],
        [
            'title' => 'ارتباط با پلتفرم وطن',
            'paragraphs' => [
                'کاربران می‌توانند برای طرح درخواست‌های مرتبط با حساب، اطلاعات شخصی، گزارش نقض حریم خصوصی یا سایر موضوعات مربوط به این سیاست، از طریق پشتیبانی رسمی وطن اقدام کنند.'
            ],
        ],
        [
            'title' => 'پذیرش سیاست حفظ حریم خصوصی',
            'paragraphs' => [
                'با استفاده از پلتفرم وطن، کاربر تأیید می‌کند که این سیاست را مطالعه کرده و از نحوه پردازش اطلاعات مطابق این سند آگاه شده است.',
                'کاربر همچنین تأیید می‌کند که مسئول قانونی بودن اطلاعات و تصاویری است که در اختیار پلتفرم قرار می‌دهد و در صورت ارائه اطلاعات متعلق به شخص دیگر، مجوزهای لازم را اخذ کرده است.'
            ],
        ],
    ];
@endphp

@push('styles')
<style>
  .privacy-page {
    --privacy-accent: #cffe00;
    --privacy-accent-soft: rgba(207, 254, 0, .10);
    --privacy-surface: color-mix(in srgb, var(--vatan-text-page) 4%, var(--vatan-bg-page));
    --privacy-surface-strong: color-mix(in srgb, var(--vatan-text-page) 7%, var(--vatan-bg-page));
    --privacy-border: color-mix(in srgb, var(--vatan-text-page) 12%, transparent);
    --privacy-muted: color-mix(in srgb, var(--vatan-text-page) 62%, transparent);
    --privacy-faint: color-mix(in srgb, var(--vatan-text-page) 43%, transparent);
    width: min(100%, 1240px);
    margin-inline: auto;
    padding: 48px clamp(16px, 4vw, 44px) 96px;
    color: var(--vatan-text-page);
    font-family: 'YekanBakh', 'IRANSansXFaNum', sans-serif;
  }

  html.light .privacy-page {
    --privacy-accent: #16594f;
    --privacy-accent-soft: rgba(22, 89, 79, .09);
    --privacy-surface: #f8f9f8;
    --privacy-surface-strong: #f1f4f2;
    --privacy-border: #e5e6e6;
    --privacy-muted: #686e6b;
    --privacy-faint: #87908b;
  }

  .privacy-hero { max-width: 780px; margin: 0 auto 42px; text-align: center; }
  .privacy-kicker {
    display: inline-flex; align-items: center; gap: 8px; padding: 7px 13px;
    border: 1px solid color-mix(in srgb, var(--privacy-accent) 35%, transparent);
    border-radius: 999px; color: var(--privacy-accent); background: var(--privacy-accent-soft);
    font-size: 11px; font-weight: 800;
  }
  .privacy-kicker::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
  .privacy-hero h1 { margin: 18px 0 10px; font-size: clamp(28px, 5vw, 46px); line-height: 1.35; font-weight: 900; letter-spacing: -.5px; }
  .privacy-hero p { max-width: 650px; margin: 0 auto; color: var(--privacy-muted); font-size: 14px; line-height: 2.15; }
  .privacy-update { display: inline-block; margin-top: 14px; color: var(--privacy-faint); font-size: 11px; }

  .privacy-layout { display: grid; grid-template-columns: 238px minmax(0, 1fr); gap: 34px; align-items: start; direction: ltr; }
  .privacy-sidebar, .privacy-document { direction: rtl; }
  .privacy-sidebar { position: sticky; top: 88px; }
  .privacy-toc { padding: 16px; border: 1px solid var(--privacy-border); border-radius: 18px; background: var(--privacy-surface); }
  .privacy-toc-title { display: block; margin-bottom: 10px; color: var(--privacy-faint); font-size: 10px; font-weight: 800; }
  .privacy-toc a { display: block; padding: 7px 8px; border-radius: 8px; color: var(--privacy-muted); font-size: 10px; line-height: 1.65; text-decoration: none; transition: color .2s ease, background .2s ease; }
  .privacy-toc a:hover { color: var(--privacy-accent); background: var(--privacy-accent-soft); }
  .privacy-note { margin-top: 14px; padding: 15px; border: 1px solid color-mix(in srgb, var(--privacy-accent) 35%, transparent); border-radius: 16px; background: var(--privacy-accent-soft); color: var(--privacy-muted); font-size: 10px; line-height: 1.95; }
  .privacy-note strong { display: block; margin-bottom: 5px; color: var(--privacy-accent); font-size: 11px; }

  .privacy-mobile-toc { display: none; margin-bottom: 18px; border: 1px solid var(--privacy-border); border-radius: 14px; background: var(--privacy-surface); }
  .privacy-mobile-toc summary { cursor: pointer; padding: 13px 15px; color: var(--privacy-accent); font-size: 12px; font-weight: 800; }
  .privacy-mobile-toc nav { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 3px 8px; padding: 0 12px 12px; }
  .privacy-mobile-toc a { padding: 6px; color: var(--privacy-muted); font-size: 10px; text-decoration: none; }

  .privacy-document { min-width: 0; }
  .privacy-intro { margin-bottom: 18px; padding: 22px 24px; border: 1px solid color-mix(in srgb, var(--privacy-accent) 28%, var(--privacy-border)); border-radius: 20px; background: linear-gradient(135deg, var(--privacy-accent-soft), var(--privacy-surface)); }
  .privacy-intro h2 { margin: 0 0 8px; color: var(--privacy-accent); font-size: 16px; font-weight: 900; }
  .privacy-intro p { margin: 0; color: var(--privacy-muted); font-size: 12px; line-height: 2.1; }
  .privacy-section { scroll-margin-top: 88px; padding: 24px; border: 1px solid var(--privacy-border); border-radius: 20px; background: var(--privacy-surface); }
  .privacy-section + .privacy-section { margin-top: 12px; }
  .privacy-section h2 { display: flex; align-items: baseline; gap: 8px; margin: 0 0 13px; color: var(--vatan-text-page); font-size: 16px; line-height: 1.6; font-weight: 900; }
  .privacy-section h2 span { flex: none; color: var(--privacy-accent); font-size: 11px; font-weight: 800; }
  .privacy-section p, .privacy-section li { color: var(--privacy-muted); font-size: 12px; line-height: 2.15; }
  .privacy-section p { margin: 0 0 9px; }
  .privacy-section p:last-child { margin-bottom: 0; }
  .privacy-section ul { display: grid; gap: 4px; margin: 0 0 11px; padding: 0 20px 0 0; list-style: none; }
  .privacy-section li { position: relative; }
  .privacy-section li::before { content: ''; position: absolute; top: .85em; right: -15px; width: 5px; height: 5px; border-radius: 50%; background: var(--privacy-accent); }
  .privacy-contact { display: flex; align-items: center; justify-content: space-between; gap: 18px; margin-top: 16px; padding: 18px 20px; border: 1px solid var(--privacy-border); border-radius: 18px; background: var(--privacy-surface-strong); }
  .privacy-contact p { margin: 0; color: var(--privacy-muted); font-size: 11px; line-height: 1.9; }
  .privacy-contact strong { display: block; margin-bottom: 4px; color: var(--vatan-text-page); font-size: 13px; }
  .privacy-contact a { flex: none; padding: 10px 15px; border-radius: 10px; background: var(--privacy-accent); color: #07100b; font-size: 11px; font-weight: 900; text-decoration: none; }
  html.light .privacy-contact a { color: #fff; }

  @media (max-width: 860px) {
    .privacy-layout { display: block; }
    .privacy-sidebar { display: none; }
    .privacy-mobile-toc { display: block; }
  }
  @media (max-width: 560px) {
    .privacy-page { padding: 30px 13px 72px; }
    .privacy-hero { margin-bottom: 28px; }
    .privacy-hero h1 { font-size: 28px; }
    .privacy-hero p { font-size: 12px; }
    .privacy-intro, .privacy-section { padding: 18px 16px; border-radius: 16px; }
    .privacy-section h2 { font-size: 14px; }
    .privacy-section p, .privacy-section li { font-size: 11px; line-height: 2.2; }
    .privacy-mobile-toc nav { grid-template-columns: 1fr; }
    .privacy-contact { align-items: stretch; flex-direction: column; }
    .privacy-contact a { text-align: center; }
  }
</style>
@endpush

@section('content')
<div class="privacy-page" dir="rtl">
  <header class="privacy-hero">
    <span class="privacy-kicker">سیاست رسمی وطن</span>
    <h1>حریم خصوصی کاربران</h1>
    <p>این سند توضیح می‌دهد هنگام استفاده از پلتفرم وطن چه اطلاعاتی ممکن است دریافت و پردازش شود، این اطلاعات برای چه اهدافی استفاده می‌شود و کاربران چه حقوق و مسئولیت‌هایی در ارتباط با اطلاعات خود دارند.</p>
    <span class="privacy-update">آخرین به‌روزرسانی: مرداد ۱۴۰۵</span>
  </header>

  <details class="privacy-mobile-toc">
    <summary>فهرست بخش‌های سیاست حریم خصوصی</summary>
    <nav aria-label="فهرست سیاست حریم خصوصی">
      @foreach($privacySections as $index => $section)
        <a href="#privacy-{{ $index + 1 }}">{{ $index + 1 }}. {{ $section['title'] }}</a>
      @endforeach
    </nav>
  </details>

  <div class="privacy-layout">
    <aside class="privacy-sidebar" aria-label="فهرست سیاست حریم خصوصی">
      <nav class="privacy-toc">
        <span class="privacy-toc-title">در این سند می‌خوانید</span>
        @foreach($privacySections as $index => $section)
          <a href="#privacy-{{ $index + 1 }}">{{ $index + 1 }}. {{ $section['title'] }}</a>
        @endforeach
      </nav>
      <div class="privacy-note">
        <strong>خلاصه مهم</strong>
        محتوای خصوصی شما صرفاً به دلیل بارگذاری در وطن عمومی یا تبلیغاتی نمی‌شود. انتشار عمومی محتوا باید با انتخاب یا اقدام مشخص شما انجام شود.
      </div>
    </aside>

    <main class="privacy-document">
      <section class="privacy-intro">
        <h2>تعهد وطن به حریم خصوصی</h2>
        <p>حفظ حریم خصوصی کاربران برای پلتفرم وطن اهمیت دارد. این سیاست توسط «پلتفرم وطن» منتشر شده است و استفاده از پلتفرم به معنای آگاهی از این سیاست و، در مواردی که قانون نیازمند رضایت است، ارائه رضایت لازم از طریق سازوکارهای پیش‌بینی‌شده در پلتفرم است.</p>
      </section>

      @foreach($privacySections as $index => $section)
        <section class="privacy-section" id="privacy-{{ $index + 1 }}">
          <h2><span>{{ $index + 1 }}.</span>{{ $section['title'] }}</h2>
          @if(!empty($section['before']))
            <p>{{ $section['before'] }}</p>
          @endif
          @foreach($section['paragraphs'] ?? [] as $paragraph)
            <p>{{ $paragraph }}</p>
          @endforeach
          @if(!empty($section['items']))
            <ul>
              @foreach($section['items'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          @endif
          @if(!empty($section['after']))
            <p>{{ $section['after'] }}</p>
          @endif
        </section>
      @endforeach

      <div class="privacy-contact">
        <p><strong>درخواست یا گزارش حریم خصوصی دارید؟</strong>برای درخواست‌های مرتبط با حساب و اطلاعات شخصی، از پشتیبانی رسمی وطن پیام بفرستید.</p>
        <a href="https://t.me/vatanstudio_bot" target="_blank" rel="noopener noreferrer">ارتباط با پشتیبانی</a>
      </div>
    </main>
  </div>
</div>
@endsection
