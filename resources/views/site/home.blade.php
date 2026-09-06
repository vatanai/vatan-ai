{{--
  صفحه نخست — وطن AI
  aivatan.com
  لندینگ پیج کامل با ۱۰ سکشن + هدر + فوتر
  ریسپانسیو — دارک/لایت مود
--}}
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <title>وطن استودیو — عکس حرفه‌ای با هوش مصنوعی</title>
  <meta name="description" content="عکس خودت را به سبک‌های حرفه‌ای تبدیل کن. وطن استودیو در کمتر از ۲ دقیقه عکس سینمایی، ترند و حرفه‌ای می‌سازد.">
  @include('partials.site-icons')

  <link href="{{ asset('css/fonts.css') }}" rel="stylesheet">
  <link href="{{ asset('css/theme-tokens.css') }}?v={{ filemtime(public_path('css/theme-tokens.css')) }}" rel="stylesheet">
  <link href="{{ asset('css/plan-cards.css') }}?v={{ filemtime(public_path('css/plan-cards.css')) }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <script>
    /* ─── تم — اول از همه لود می‌شه ─── */
    (function () {
      var saved = localStorage.getItem('vatan-theme');
      if (saved === 'light') document.documentElement.classList.add('light');
      window.vatanToggleTheme = function () {
        var isLight = document.documentElement.classList.toggle('light');
        localStorage.setItem('vatan-theme', isLight ? 'light' : 'dark');
        updateThemeIcon();
      };
    }());
  </script>

@include('site.partials.home-styles')
</head>
<body id="top">

<!-- ══════════════ HEADER ══════════════ -->
<header id="site-header">
  <div class="container">
    <!-- لوگو -->
    <a href="{{ route('site.home.root') }}#top" class="header-logo" aria-label="رفتن به ابتدای سایت">
      <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" class="logo-icon">
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" class="logo-text">
    </a>

    <!-- منو دسکتاپ -->
    <nav class="header-nav">
      <a href="#styles">سبک‌ها</a>
      <a href="#samples">نمونه‌ها</a>
      <a href="#features">ویژگی‌ها</a>
      <a href="#pricing">تعرفه‌ها</a>
      <a href="#faq">سوالات</a>
    </nav>

    <!-- اکشن‌ها -->
    <div class="header-actions">
      <!-- دکمه روز/شب -->
      <button class="theme-btn" onclick="vatanToggleTheme()" id="theme-btn" aria-label="تغییر تم">
        <i class="fa-solid fa-moon" id="theme-icon"></i>
      </button>

      <!-- CTA -->
      <a href="{{ route('app.home') }}" class="btn btn-primary btn-header">
        شروع رایگان
      </a>

      <!-- همبرگر موبایل -->
      <button class="hamburger" id="hamburger-btn" onclick="toggleMenu()" aria-label="منو">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</header>

<!-- منوی موبایل -->
<div id="mobile-menu">
  <a href="#styles" onclick="closeMenu()">سبک‌ها</a>
  <a href="#samples" onclick="closeMenu()">نمونه خروجی‌ها</a>
  <a href="#features" onclick="closeMenu()">ویژگی‌ها</a>
  <a href="#pricing" onclick="closeMenu()">تعرفه‌ها</a>
  <a href="#faq" onclick="closeMenu()">سوالات متداول</a>
  <div class="mobile-menu-footer">
    <a href="{{ route('app.home') }}" class="btn btn-primary" onclick="closeMenu()">شروع رایگان</a>
    <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}" class="btn btn-ghost" onclick="closeMenu()">ورود</a>
  </div>
</div>


<!-- ══════════════ SECTION 1: HERO ══════════════ -->
<section id="hero">
  <div class="container">
    <div class="hero-inner">

      <!-- متن -->
      <div>
        <div class="hero-badge reveal">
          <span class="dot"></span>
          وطن استودیو — هوش مصنوعی
        </div>

        <h1 class="hero-title reveal reveal-delay-1">
          دنیایی از محصولات هوش مصنوعی، فقط چند کلیک با تو فاصله دارد.
        </h1>

        <p class="hero-desc reveal reveal-delay-2">
          ما بقیه مسیر را برایت ساده کرده‌ایم؛ فقط انتخاب کن، سریع بساز و از نتیجه‌ی حرفه‌ای لذت ببر.
        </p>

        <div class="hero-trust reveal reveal-delay-3">
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            تحویل زیر ۱ دقیقه
          </div>
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            بیش از ۱۲۰۰ نمونه
          </div>
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            کاربری ساده ولی حرفه‌ای
          </div>
          <div class="hero-trust-item">
            <span class="check"><i class="fa-solid fa-check"></i></span>
            حفظ شباهت چهره
          </div>
        </div>

        <div class="hero-cta-box reveal reveal-delay-4">
          <a href="{{ route('app.home') }}" class="btn btn-primary">
            <i class="fa-solid fa-bolt"></i>
            شروع رایگان
          </a>
        </div>
      </div>

      <!-- ویژوال -->
      <div class="hero-visual reveal reveal-delay-2">
        <div class="hero-float-badge">
          <span class="icon">⚡️</span>
          <div>
            <div class="text">زیر ۱ دقیقه</div>
            <div class="sub">تحویل سریع</div>
          </div>
        </div>

        <div class="hero-visual-grid">
          <div class="hero-card">
            <img src="{{ asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg') }}" alt="نمونه سینمایی" loading="lazy">
            <span class="hero-card-label">سبک سینمایی</span>
          </div>
          <div class="hero-card">
            <img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="پرتره" loading="lazy">
            <span class="hero-card-label">پرتره حرفه‌ای</span>
          </div>
          <div class="hero-card">
            <img src="{{ asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg') }}" alt="فشن" loading="lazy">
            <span class="hero-card-label">فشن و مدلینگ</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 2: سبک‌ها ══════════════ -->
<section id="styles">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-label">
        <i class="fa-solid fa-palette"></i>
        سبک‌های آماده
      </div>
      <h2 class="section-title">محبوب‌ترین سبک‌ها</h2>
      <p class="section-sub">از بین ده‌ها سبک آماده انتخاب کن — بدون نیاز به هیچ دانش فنی</p>
    </div>

    <div class="styles-scroll-wrap reveal reveal-delay-1">
      <div class="styles-track">

        <div class="style-card">
          <div class="style-card-img">📸
            <img src="{{ asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg') }}" alt="پرتره" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">پرتره حرفه‌ای</div>
            <div class="style-card-sub">کلاسیک و شیک</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">👗
            <img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="فشن" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">فشن و مدلینگ</div>
            <div class="style-card-sub">ترند روز</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🎬
            <img src="{{ asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg') }}" alt="سینمایی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">سینمایی</div>
            <div class="style-card-sub">نور دراماتیک</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">💼
            <img src="{{ asset('assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp') }}" alt="بیزینسی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">بیزینسی</div>
            <div class="style-card-sub">حرفه‌ای رسمی</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🔗
            <img src="{{ asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp') }}" alt="لینکدین" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">پروفایل لینکدین</div>
            <div class="style-card-sub">اعتمادساز</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">📱
            <img src="{{ asset('assets/img/best-friends-ai-prompt-2.webp') }}" alt="اینستاگرام" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">ترند اینستاگرام</div>
            <div class="style-card-sub">وایرال استایل</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🎨</div>
          <div class="style-card-body">
            <div class="style-card-name">کارتونی</div>
            <div class="style-card-sub">انیمه و تون</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">✨
            <img src="{{ asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp') }}" alt="رویایی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">رویایی</div>
            <div class="style-card-sub">فانتزی و هنری</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">💍
            <img src="{{ asset('assets/img/Realistic-emotional-hug-scene-with-cinematic-lighting-created-using-Gemini-AI-768x1365.jpg') }}" alt="عروسی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">عروسی</div>
            <div class="style-card-sub">رمانتیک و خاص</div>
          </div>
        </div>

        <div class="style-card">
          <div class="style-card-img">🏋️
            <img src="{{ asset('assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg') }}" alt="ورزشی" loading="lazy">
          </div>
          <div class="style-card-body">
            <div class="style-card-name">ورزشی</div>
            <div class="style-card-sub">پرانرژی</div>
          </div>
        </div>

      </div>
    </div>
  </div>
</section>


<!-- ══════════════ SECTION 3: چرا وطن استودیو؟ ══════════════ -->
<section id="why" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-star"></i>
        چرا وطن؟
      </div>
      <h2 class="section-title">از یک عکس ساده تا یک تصویر حرفه‌ای؛ سریع، آسان و بدون دردسر</h2>
    </div>

    <div class="why-steps">
      <div class="why-step reveal reveal-delay-1">
        <div class="why-step-num">۱</div>
        <h3 class="why-step-title">فقط عکس بفرست</h3>
        <p class="why-step-desc">نیازی به نوشتن پرامپت نیست. یک سلفی ساده کافی است.</p>
      </div>

      <div class="why-step reveal reveal-delay-2">
        <div class="why-step-num">۲</div>
        <h3 class="why-step-title">سبک را انتخاب کن</h3>
        <p class="why-step-desc">از بین ده‌ها سبک آماده، هر کدام که دوست داری انتخاب کن.</p>
      </div>

      <div class="why-step reveal reveal-delay-3">
        <div class="why-step-num">۳</div>
        <h3 class="why-step-title">نتیجه را تحویل بگیر</h3>
        <p class="why-step-desc">کمتر از ۲ دقیقه بعد عکس حرفه‌ای آماده است.</p>
      </div>
    </div>
  </div>
</section>


<!-- ══════════════ SECTION 4: نمونه خروجی‌ها ══════════════ -->
<section id="samples">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-images"></i>
        نمونه کارها
      </div>
      <h2 class="section-title">ببین کاربران چه ساخته‌اند</h2>
      <p class="section-sub">از یک عکس معمولی تا یک تصویر حرفه‌ای؛ نتیجه‌ای که در کمتر از ۲ دقیقه آماده می‌شود</p>
    </div>

    <div class="samples-grid">

      <div class="sample-card reveal reveal-delay-1">
        <div class="sample-card-inner">
          <div class="sample-before">
            <img src="{{ asset('assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp') }}" alt="قبل" loading="lazy">
            <span class="sample-tag">قبل</span>
          </div>
          <div class="sample-after">
            <img src="{{ asset('assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg') }}" alt="بعد" loading="lazy">
            <span class="sample-tag">بعد</span>
          </div>
          <div class="sample-divider"></div>
        </div>
        <div class="sample-card-foot">
          <span class="style-name">سبک سینمایی</span>
          <span class="time-badge">۴۵ ثانیه</span>
        </div>
      </div>

      <div class="sample-card reveal reveal-delay-2">
        <div class="sample-card-inner">
          <div class="sample-before">
            <img src="{{ asset('assets/img/gemini-boy-man-sitting-on-chair-ai-prompt-riuuaksek4.webp') }}" alt="قبل" loading="lazy">
            <span class="sample-tag">قبل</span>
          </div>
          <div class="sample-after">
            <img src="{{ asset('assets/img/elegant-woman-cafe-portrait-by-promptplum.avif') }}" alt="بعد" loading="lazy">
            <span class="sample-tag">بعد</span>
          </div>
          <div class="sample-divider"></div>
        </div>
        <div class="sample-card-foot">
          <span class="style-name">پرتره حرفه‌ای</span>
          <span class="time-badge">۶۰ ثانیه</span>
        </div>
      </div>

      <div class="sample-card reveal reveal-delay-3">
        <div class="sample-card-inner">
          <div class="sample-before">
            <img src="{{ asset('assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp') }}" alt="قبل" loading="lazy">
            <span class="sample-tag">قبل</span>
          </div>
          <div class="sample-after">
            <img src="{{ asset('assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg') }}" alt="بعد" loading="lazy">
            <span class="sample-tag">بعد</span>
          </div>
          <div class="sample-divider"></div>
        </div>
        <div class="sample-card-foot">
          <span class="style-name">فشن و مدلینگ</span>
          <span class="time-badge">۹۰ ثانیه</span>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 5: تمایزات ══════════════ -->
<section id="features" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-gem"></i>
        چرا ما؟
      </div>
      <h2 class="section-title">چرا کاربران وطن استودیو را انتخاب می‌کنند؟</h2>
    </div>

    <div class="features-grid">

      <div class="feature-card reveal reveal-delay-1">
        <div class="feature-icon-wrap">🪞</div>
        <h3 class="feature-title">حفظ چهره واقعی</h3>
        <p class="feature-desc">نتیجه شبیه خودت می‌ماند. هویت چهره‌ات در تمام تبدیل‌ها حفظ می‌شود.</p>
      </div>

      <div class="feature-card reveal reveal-delay-2">
        <div class="feature-icon-wrap">⚡️</div>
        <h3 class="feature-title">سرعت بالا</h3>
        <p class="feature-desc">اکثر سفارش‌ها زیر ۲ دقیقه آماده می‌شوند. بدون انتظار طولانی.</p>
      </div>

      <div class="feature-card reveal reveal-delay-3">
        <div class="feature-icon-wrap">🧠</div>
        <h3 class="feature-title">بدون دانش فنی</h3>
        <p class="feature-desc">فقط عکس ارسال می‌کنی. هیچ پرامپت یا تنظیماتی نیاز نیست.</p>
      </div>

      <div class="feature-card reveal reveal-delay-1">
        <div class="feature-icon-wrap">🔄</div>
        <h3 class="feature-title">همیشه سبک‌های جدید</h3>
        <p class="feature-desc">سبک‌های ترند به‌صورت مداوم اضافه می‌شوند. همیشه به‌روز هستی.</p>
      </div>

      <div class="feature-card reveal reveal-delay-2">
        <div class="feature-icon-wrap">🔒</div>
        <h3 class="feature-title">حریم خصوصی</h3>
        <p class="feature-desc">عکس خام پس از پردازش حذف می‌شود. اطلاعاتت محفوظ است.</p>
      </div>

      <div class="feature-card reveal reveal-delay-3">
        <div class="feature-icon-wrap">📱</div>
        <h3 class="feature-title">استفاده آسان</h3>
        <p class="feature-desc">تلگرام و وب، هر دو در دسترس. از هر دستگاهی استفاده کن.</p>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 6: چطور کار می‌کند؟ ══════════════ -->
<section id="how">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-list-ol"></i>
        روند کار
      </div>
      <h2 class="section-title">فقط ۳ مرحله</h2>
      <p class="section-sub">از ارسال عکس تا دریافت نتیجه حرفه‌ای — بدون پیچیدگی</p>
    </div>

    <div class="how-steps">

      <div class="how-step reveal reveal-delay-1">
        <div class="how-step-icon">
          <span class="how-step-num">۱</span>
          📷
        </div>
        <div>
          <h3 class="how-step-title">عکس خودت را ارسال کن</h3>
          <p class="how-step-desc">یک سلفی یا عکس معمولی کافی است. کیفیت متوسط هم مشکلی ندارد.</p>
        </div>
      </div>

      <div class="how-step reveal reveal-delay-2">
        <div class="how-step-icon">
          <span class="how-step-num">۲</span>
          🎨
        </div>
        <div>
          <h3 class="how-step-title">سبک مورد علاقه‌ات را انتخاب کن</h3>
          <p class="how-step-desc">از لیست سبک‌های آماده یکی را انتخاب کن. همین و بس.</p>
        </div>
      </div>

      <div class="how-step reveal reveal-delay-3">
        <div class="how-step-icon">
          <span class="how-step-num">۳</span>
          ⚡️
        </div>
        <div>
          <h3 class="how-step-title">نتیجه را دریافت کن</h3>
          <p class="how-step-desc">کمتر از ۲ دقیقه بعد عکس حرفه‌ای تحویلت داده می‌شود.</p>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 7: برای چه کسانی؟ ══════════════ -->
<section id="audience" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-users"></i>
        مخاطبان
      </div>
      <h2 class="section-title">مناسب برای</h2>
      <p class="section-sub">هر کسی که می‌خواهد عکس بهتری داشته باشد</p>
    </div>

    <div class="audience-tags">
      <div class="audience-tag reveal reveal-delay-1">
        <span class="tag-icon">✍️</span>
        بلاگرها
      </div>
      <div class="audience-tag reveal reveal-delay-1">
        <span class="tag-icon">🎬</span>
        تولیدکنندگان محتوا
      </div>
      <div class="audience-tag reveal reveal-delay-2">
        <span class="tag-icon">🛍️</span>
        فروشگاه‌های آنلاین
      </div>
      <div class="audience-tag reveal reveal-delay-2">
        <span class="tag-icon">💼</span>
        صاحبان کسب‌وکار
      </div>
      <div class="audience-tag reveal reveal-delay-3">
        <span class="tag-icon">🎓</span>
        دانشجویان
      </div>
      <div class="audience-tag reveal reveal-delay-3">
        <span class="tag-icon">👤</span>
        کاربران عادی
      </div>
    </div>
  </div>
</section>


<!-- ══════════════ SECTION 8: تعرفه‌ها ══════════════ -->
<section id="pricing">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-tag"></i>
        قیمت‌گذاری
      </div>
      <h2 class="section-title">{{ $planDisplay['title'] ?? 'تعرفه‌ها' }}</h2>
      <p class="section-sub">{{ $planDisplay['subtitle'] ?? 'ساده و شفاف' }}</p>
    </div>

    <div class="pricing-grid">
      @forelse($homePlans as $plan)
        @include('site.partials.plan-card', ['offer' => $plan->offer, 'planDisplay' => $planDisplay])
      @empty
        <p class="section-sub">در حال حاضر پلن فعالی وجود ندارد.</p>
      @endforelse
    </div>
    @if($homePlans->isNotEmpty())<div style="text-align:center;margin-top:24px"><a href="{{ route('pricing.index') }}" class="btn btn-ghost">مشاهده همه پلن‌ها و مقایسه کامل</a></div>@endif
  </div>
</section>


<!-- ══════════════ SECTION 9: سوالات متداول ══════════════ -->
<section id="faq" class="section">
  <div class="container">
    <div class="section-header center reveal">
      <div class="section-label">
        <i class="fa-solid fa-circle-question"></i>
        سوالات متداول
      </div>
      <h2 class="section-title">جواب سوالاتت را اینجا پیدا کن</h2>
    </div>

    <div class="faq-list reveal reveal-delay-1">

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا نیاز به دانش هوش مصنوعی یا پرامپت‌نویسی دارم؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>خیر. کاملاً ساده است. فقط عکس خودت را ارسال کن، سبک را انتخاب کن و منتظر نتیجه باش. نیازی به هیچ دانش فنی ندارید.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">چقدر طول می‌کشد تا نتیجه آماده شود؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>معمولاً بین ۳۰ تا ۱۲۰ ثانیه. در اوقات شلوغی ممکن است کمی بیشتر طول بکشد اما هرگز بیش از ۵ دقیقه نخواهد بود.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا عکس من ذخیره یا به اشتراک گذاشته می‌شود؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>عکس خام شما بلافاصله پس از پردازش حذف می‌شود. ما به حریم خصوصی کاربران اهمیت می‌دهیم و تصاویر هرگز با شخص ثالثی به اشتراک گذاشته نمی‌شود.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا با موبایل هم کار می‌کند؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>بله. هم از طریق وب‌سایت و هم از طریق ربات تلگرام می‌توانید به راحتی از موبایل استفاده کنید.</p>
        </div>
      </div>

      <div class="faq-item" onclick="toggleFaq(this)">
        <div class="faq-q">
          <span class="faq-q-text">آیا می‌توانم سبک‌های مختلف را با یک خروجی امتحان کنم؟</span>
          <span class="faq-arrow"><i class="fa-solid fa-chevron-down"></i></span>
        </div>
        <div class="faq-a">
          <p>هر سفارش یک سبک مشخص دارد. اگر می‌خواهید یک عکس را با سبک‌های مختلف تبدیل کنید، هر بار یک خروجی از اعتبار شما کم می‌شود.</p>
        </div>
      </div>

    </div>
  </div>
</section>


<!-- ══════════════ SECTION 10: CTA نهایی ══════════════ -->
<section id="cta-final">
  <div class="container">
    <span class="cta-final-icon reveal">✨</span>
    <h2 class="cta-final-title reveal reveal-delay-1">
      اولین تصویرت را<br>همین حالا بساز
    </h2>
    <p class="cta-final-sub reveal reveal-delay-2">فقط یک عکس کافی است.</p>
    <a href="{{ route('app.home') }}" class="btn btn-primary cta-final-btn reveal reveal-delay-3">
      <i class="fa-solid fa-bolt"></i>
      شروع رایگان — همین حالا
    </a>
  </div>
</section>


<!-- ══════════════ FOOTER ══════════════ -->
<footer id="site-footer">
  <div class="container">
    <div class="footer-inner">
      <a href="/" class="footer-logo">
        <img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="وطن AI" class="logo-icon">
        <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن AI" class="logo-text">
      </a>

      <div class="footer-links">
        <a href="#styles">سبک‌ها</a>
        <a href="#pricing">تعرفه‌ها</a>
        <a href="#faq">سوالات</a>
        <a href="{{ route('app.home') }}">ورود به اپ</a>
      </div>

      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <p class="footer-copy">© ۱۴۰۴ وطن استودیو — تمام حقوق محفوظ است</p>
        <a href="{{ route('admin.dashboard') }}" class="footer-admin-box">
          <i class="fa-solid fa-gauge-high"></i>
          ورود به داشبورد
        </a>
      </div>
    </div>
  </div>
</footer>


<!-- ══════════════ دکمه شناور تلگرام ══════════════ -->
<a href="https://t.me/vatanstudio_bot" class="telegram-fab" target="_blank" aria-label="تلگرام">
  <svg viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.94z"/>
  </svg>
</a>


<!-- ══════════════ SCRIPTS ══════════════ -->
@include('site.partials.home-scripts')

</body>
</html>
