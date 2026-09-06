@extends('layouts.app')

@section('page_title', 'نمونه‌های معماری صفحه بساز | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-architecture.css') }}?v={{ filemtime(public_path('css/create-architecture.css')) }}">
@endpush

@section('content')
  <div class="ca-page" dir="rtl">
    <div class="ca-container">
      <header class="ca-header">
        <div>
          <span class="ca-kicker">پیش‌نمایش مستقل</span>
          <h1>معماری جدید صفحه بساز</h1>
          <p>سه چیدمان شماتیک برای انتخاب جایگاه بخش‌ها؛ فعلاً بدون فرم، داده و عملکرد واقعی.</p>
        </div>
        <span class="ca-badge"><i></i> فقط نسخه دسکتاپ</span>
      </header>

      <section class="ca-concepts" aria-labelledby="ca-concepts-title">
        <div class="ca-concepts-heading">
          <div>
            <span class="ca-kicker">بازطراحی ورک‌اسپیس واقعی</span>
            <h2 id="ca-concepts-title">سه مدل برای صفحه‌ای که بعد از انتخاب محصول باز می‌شود</h2>
            <p>این‌ها نسخه‌ی واقعی‌تر همان صفحه‌ی ساخت هستند؛ با عکس، جایگاه ورودی‌ها، پیش‌نمایش و دکمه‌ی ساخت.</p>
          </div>
          <span class="ca-badge"><i></i> بر اساس ورک‌اسپیس فعلی</span>
        </div>

        <div class="ca-concepts-list">
          <article class="ca-concept">
            <div class="ca-concept-preview ca-concept-focus">
              <div class="ca-mock-topbar"><span class="ca-mock-back">‹</span><span class="ca-mock-product">پرتره سینمایی</span><span class="ca-mock-top-action">ذخیره</span></div>
              <div class="ca-mock-focus-body">
                <div class="ca-mock-focus-stage">
                  <img src="{{ asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif') }}" alt="پیش‌نمایش پرتره سینمایی" loading="lazy">
                  <div class="ca-mock-stage-label"><span>پیش‌نمایش خروجی</span><span>۴:۵</span></div>
                </div>
                <div class="ca-mock-focus-controls">
                  <div class="ca-mock-line-title"><b>ساخت تصویر</b><span>مرحله‌ی اصلی فقط همین‌جاست</span></div>
                  <div class="ca-mock-upload-row"><span class="ca-mock-upload-icon">↑</span><span><b>عکس اصلی را اضافه کنید</b><small>JPG، PNG یا WebP · حداکثر ۱۰ مگابایت</small></span><em>انتخاب</em></div>
                  <div class="ca-mock-two-fields"><div>شرح کوتاه ایده</div><div>سبک سینمایی</div></div>
                  <div class="ca-mock-cta-row"><span>۱۸ توکن · حدود ۴۵ ثانیه</span><strong>بساز ✦</strong></div>
                </div>
              </div>
            </div>
            <div class="ca-concept-info">
              <span class="ca-concept-number">مدل ۱</span>
              <h3>محور تصویر؛ تنظیمات فقط در پایین</h3>
              <p>بوم خروجی بیشترین فضا را می‌گیرد. فقط ورودی‌های ضروری کنار آن هستند و تنظیمات پیشرفته در مرحله‌ی بعد یا پنل جمع‌شونده قرار می‌گیرند.</p>
              <div class="ca-concept-points"><span>پیش‌نمایش بزرگ</span><span>آپلود واضح</span><span>دکمه ساخت همیشه قابل دیدن</span></div>
            </div>
          </article>

          <article class="ca-concept">
            <div class="ca-concept-preview ca-concept-steps">
              <div class="ca-mock-topbar"><span class="ca-mock-product">پرتره سینمایی</span><span class="ca-mock-top-action">۱ از ۳</span></div>
              <div class="ca-mock-stepper"><b class="active">۱</b><i></i><b>۲</b><i></i><b>۳</b><span>تصویر ← سبک ← خروجی</span></div>
              <div class="ca-mock-step-body">
                <div class="ca-mock-step-copy"><span>مرحله ۱</span><b>تصویر و ایده‌ی اصلی</b><small>اول ورودی را آماده کن؛ جزئیات بعداً اضافه می‌شوند.</small></div>
                <div class="ca-mock-large-upload"><span>＋</span><b>تصویر را اینجا رها کنید</b><small>یا از دستگاه انتخاب کنید</small></div>
                <div class="ca-mock-two-fields"><div>فضایی که در ذهن دارید...</div><div>تصاویر مرجع بیشتر</div></div>
                <div class="ca-mock-cta-row"><span>پیش‌نمایش مرحله بعد</span><strong>ادامه ←</strong></div>
              </div>
            </div>
            <div class="ca-concept-info">
              <span class="ca-concept-number">مدل ۲</span>
              <h3>ساخت مرحله‌ای؛ هر بار فقط یک تصمیم</h3>
              <p>به‌جای سه تب پر از فیلد، کاربر در سه مرحله جلو می‌رود: تصویر، سبک و خروجی. این مدل برای محصولاتی که ورودی زیاد دارند خلوت‌تر و قابل فهم‌تر است.</p>
              <div class="ca-concept-points"><span>مسیر روشن</span><span>فرم کوتاه در هر مرحله</span><span>مناسب ورودی‌های زیاد</span></div>
            </div>
          </article>

          <article class="ca-concept">
            <div class="ca-concept-preview ca-concept-drawer">
              <div class="ca-mock-topbar"><span class="ca-mock-back">‹</span><span class="ca-mock-product">پرتره سینمایی</span><span class="ca-mock-top-action">راهنما</span></div>
              <div class="ca-mock-drawer-stage">
                <img src="{{ asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp') }}" alt="پیش‌نمایش پرتره وینتیج" loading="lazy">
                <div class="ca-mock-drawer-overlay"><span>فضای خروجی</span><button type="button">تنظیمات</button></div>
              </div>
              <div class="ca-mock-drawer-panel">
                <div class="ca-mock-line-title"><b>تنظیمات سریع</b><span>۳ انتخاب اصلی</span></div>
                <div class="ca-mock-choice-row"><span class="selected">آپلود تصویر</span><span>پریست وینتیج</span><span>نسبت ۴:۵</span></div>
                <div class="ca-mock-drawer-footer"><span>پیشرفته</span><strong>بساز · ۱۸ توکن</strong></div>
              </div>
            </div>
            <div class="ca-concept-info">
              <span class="ca-concept-number">مدل ۳</span>
              <h3>پیش‌نمایش آزاد؛ تنظیمات در کشوی کناری</h3>
              <p>صفحه با یک خروجی بزرگ و خلوت شروع می‌شود. کاربر فقط وقتی نیاز دارد، تنظیمات را باز می‌کند؛ برای ابزارهای تصویری و تجربه‌ی سریع مناسب‌تر است.</p>
              <div class="ca-concept-points"><span>شروع خلوت</span><span>تنظیمات قابل توسعه</span><span>تمرکز روی نتیجه</span></div>
            </div>
          </article>
        </div>
      </section>

      <section class="ca-samples" aria-label="سه نمونه معماری صفحه">
        <article class="ca-sample">
          <div class="ca-sample-head">
            <div>
              <span class="ca-sample-number">۰۱</span>
              <h2>محور نتیجه</h2>
            </div>
            <span class="ca-sample-tag">تمرکز روی تصویر</span>
          </div>
          <p class="ca-sample-copy">خروجی در مرکز توجه است؛ تنظیمات فقط در دو پنل جمع‌وجور اطراف قرار می‌گیرند.</p>

          <div class="ca-wire ca-wire-focus">
            <div class="ca-wire-top"><span>هدر محصول و وضعیت ساخت</span><span>اکشن‌های صفحه</span></div>
            <div class="ca-wire-columns">
              <div class="ca-wire-rail ca-wire-rail-wide">
                <span class="ca-wire-label">تنظیمات ضروری</span>
                <div class="ca-wire-box ca-wire-box-highlight">آپلود تصویر اصلی</div>
                <div class="ca-wire-box">پرامپت کوتاه</div>
                <div class="ca-wire-box">سبک و نسبت تصویر</div>
                <div class="ca-wire-box ca-wire-box-action">دکمه ساخت + هزینه</div>
              </div>
              <div class="ca-wire-canvas"><span>پیش‌نمایش خروجی</span><small>فضای اصلی تصویر</small></div>
              <div class="ca-wire-rail">
                <span class="ca-wire-label">خلاصه نتیجه</span>
                <div class="ca-wire-box ca-wire-box-tall">نمونه / راهنمای کوتاه</div>
                <div class="ca-wire-box">آمادگی ساخت</div>
                <div class="ca-wire-box">توکن و زمان</div>
              </div>
            </div>
            <div class="ca-wire-bottom"><span>حریم خصوصی و راهنما</span><span>نتیجه / مقایسه</span></div>
          </div>
        </article>

        <article class="ca-sample">
          <div class="ca-sample-head">
            <div>
              <span class="ca-sample-number">۰۲</span>
              <h2>ساخت مرحله‌ای</h2>
            </div>
            <span class="ca-sample-tag">کمترین شلوغی</span>
          </div>
          <p class="ca-sample-copy">کاربر در هر مرحله فقط با یک گروه محدود از ورودی‌ها روبه‌رو می‌شود و مسیر همیشه مشخص است.</p>

          <div class="ca-wire ca-wire-steps">
            <div class="ca-wire-top"><span>هدر محصول</span><span>مرحله ۱ از ۳</span></div>
            <div class="ca-wire-stepper"><b class="active">۱</b><i></i><b>۲</b><i></i><b>۳</b></div>
            <div class="ca-wire-step-body">
              <div class="ca-wire-box ca-wire-box-title">۱. تصویر و ایده اصلی</div>
              <div class="ca-wire-box ca-wire-box-upload">ناحیه بزرگ آپلود تصویر</div>
              <div class="ca-wire-row"><div class="ca-wire-box">توضیح کوتاه</div><div class="ca-wire-box">عکس مرجع</div></div>
              <div class="ca-wire-row"><div class="ca-wire-box">بازگشت</div><div class="ca-wire-box ca-wire-box-action">ادامه به مرحله بعد</div></div>
            </div>
            <div class="ca-wire-bottom"><span>راهنمای همین مرحله</span><span>ذخیره موقت</span></div>
          </div>
        </article>

        <article class="ca-sample">
          <div class="ca-sample-head">
            <div>
              <span class="ca-sample-number">۰۳</span>
              <h2>پنل بازشونده</h2>
            </div>
            <span class="ca-sample-tag">فضای قابل توسعه</span>
          </div>
          <p class="ca-sample-copy">صفحه در شروع خلوت است؛ تنظیمات پیشرفته فقط وقتی لازم باشند از پنل کناری باز می‌شوند.</p>

          <div class="ca-wire ca-wire-drawer">
            <div class="ca-wire-top"><span>هدر محصول و عنوان پروژه</span><span>توکن / پروفایل</span></div>
            <div class="ca-wire-canvas ca-wire-canvas-large"><span>فضای خالی خروجی</span><small>اول تصویر یا ایده را اضافه کنید</small></div>
            <div class="ca-wire-toolbar"><span>نتیجه</span><span>مقایسه</span><span>راهنما</span><strong>تنظیمات</strong></div>
            <div class="ca-wire-drawer-panel">
              <span class="ca-wire-label">پنل تنظیمات از کنار</span>
              <div class="ca-wire-row"><div class="ca-wire-box ca-wire-box-highlight">ورودی اصلی</div><div class="ca-wire-box">سبک</div></div>
              <div class="ca-wire-box">تنظیمات بیشتر / پیشرفته</div>
              <div class="ca-wire-box ca-wire-box-action">ساخت تصویر</div>
            </div>
            <div class="ca-wire-bottom"><span>حریم خصوصی</span><span>خلاصه هزینه</span></div>
          </div>
        </article>
      </section>

      <section class="ca-real-section" aria-labelledby="ca-real-title">
        <div class="ca-real-heading">
          <div>
            <span class="ca-kicker">نمونه‌ی واقعی محتوا</span>
            <h2 id="ca-real-title">اگر این معماری را اجرا کنیم، محتوای واقعی چطور دیده می‌شود؟</h2>
            <p>سه سناریوی واقعی برای صفحه‌ی ساخت؛ هر نمونه هم عکس، هدف و ورودی‌های اصلی خودش را دارد.</p>
          </div>
          <div class="ca-inspiration-note">
            <span>الهام گرفته از تجربه‌ی</span>
            <a href="https://higgsfield.ai/" target="_blank" rel="noreferrer">Higgsfield</a>
            <span>و</span>
            <a href="https://uniset.ai/" target="_blank" rel="noreferrer">Uniset</a>
          </div>
        </div>

        <div class="ca-real-list">
          <article class="ca-real-example">
            <div class="ca-real-image-wrap">
              <img src="{{ asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif') }}" alt="نمونه پرتره سینمایی در فضای باز" loading="lazy">
              <span class="ca-real-image-label">نمونه ۱ · پرتره</span>
            </div>
            <div class="ca-real-content">
              <span class="ca-real-eyebrow">حفظ هویت + ساخت فضای جدید</span>
              <h3>پرتره‌ی سینمایی در فضای باز</h3>
              <p>کاربر یک عکس واضح از چهره وارد می‌کند، فضای خروجی را به «پرتره‌ی سینمایی در مزرعه» تغییر می‌دهد و قبل از ساخت، نسبت تصویر و میزان حفظ شباهت را می‌بیند.</p>
              <div class="ca-real-meta"><span>ورودی: عکس چهره</span><span>سبک: سینمایی</span><span>خروجی: ۴ تصویر</span></div>
            </div>
          </article>

          <article class="ca-real-example ca-real-example-reverse">
            <div class="ca-real-image-wrap">
              <img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="نمونه عکس ادیتوریال فشن در کافه" loading="lazy">
              <span class="ca-real-image-label">نمونه ۲ · ادیتوریال</span>
            </div>
            <div class="ca-real-content">
              <span class="ca-real-eyebrow">پریست آماده + توضیح کوتاه</span>
              <h3>عکس فشن برای کمپین و شبکه‌های اجتماعی</h3>
              <p>به‌جای نمایش ده‌ها تنظیمات، کاربر از یک پریست آماده شروع می‌کند؛ تصویر مرجع، فضای بصری و توضیح کمپین را انتخاب می‌کند و سیستم خروجی مناسب همان هدف را پیشنهاد می‌دهد.</p>
              <div class="ca-real-meta"><span>هدف: کمپین تبلیغاتی</span><span>پریست: فشن</span><span>نسبت: ۴:۵</span></div>
            </div>
          </article>

          <article class="ca-real-example">
            <div class="ca-real-image-wrap">
              <img src="{{ asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp') }}" alt="نمونه پرتره هنری با حال‌وهوای وینتیج" loading="lazy">
              <span class="ca-real-image-label">نمونه ۳ · سبک تصویری</span>
            </div>
            <div class="ca-real-content">
              <span class="ca-real-eyebrow">انتخاب نتیجه، نه انتخاب مدل</span>
              <h3>تبدیل یک عکس ساده به پرتره‌ی وینتیج</h3>
              <p>کاربر به‌جای درگیرشدن با نام مدل‌ها، نتیجه‌ی موردنظرش را انتخاب می‌کند: نور نرم، رنگ‌های قدیمی و حس نوستالژیک. تنظیمات فنی در پس‌زمینه باقی می‌مانند.</p>
              <div class="ca-real-meta"><span>ورودی: عکس معمولی</span><span>حس‌وحال: وینتیج</span><span>کیفیت: ۲K</span></div>
            </div>
          </article>
        </div>
      </section>

    </div>
  </div>
@endsection
