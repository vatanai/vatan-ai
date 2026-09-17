<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ساخت کارکتر شیت با هوش مصنوعی | شیت چهره و بدن | وطن AI</title>
    <meta name="description" content="با کارکتر شیت وطن، یک مرجع تصویری منسجم از چهره و بدن شخصیتت بساز و برای ساخت تصاویر، تیزر و پروژه‌های تبلیغاتی از آن استفاده کن.">
    <meta name="keywords" content="کارکتر شیت, کاراکتر شیت, ساخت کارکتر با هوش مصنوعی, شیت چهره, شیت بدن, طراحی شخصیت با هوش مصنوعی, حفظ ثبات کاراکتر">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="وطن AI">
    <meta property="og:title" content="ساخت کارکتر شیت با هوش مصنوعی | وطن AI">
    <meta property="og:description" content="یک‌بار کارکترت را تعریف کن؛ بعداً با همان مرجع تصویری، خروجی‌های هماهنگ‌تری بساز.">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $heroImages->first() }}">
    <meta property="og:image:alt" content="نمونه کارکتر شیت ساخته‌شده با هوش مصنوعی وطن">
    <meta property="og:locale" content="fa_IR">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="ساخت کارکتر شیت با هوش مصنوعی | وطن AI">
    <meta name="twitter:description" content="ساخت شیت چهره و بدن برای ثبات بیشتر شخصیت در تصاویر و پروژه‌های تبلیغاتی.">
    <meta name="twitter:image" content="{{ $heroImages->first() }}">
    <meta name="twitter:image:alt" content="نمونه کارکتر شیت وطن">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preload" as="image" href="{{ $heroImages->first() }}" fetchpriority="high">
    @include('partials.site-icons')
    <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/theme-tokens.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/app-footer.css') }}?v={{ filemtime(public_path('css/app-footer.css')) }}">
    @include('layouts.partials.nav-styles')
    <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('assets/site/css/home-preview.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/character-sheet-landing.css') }}">
    <script>
        (function () {
            var html = document.documentElement;
            var systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
            function resolve(mode) { return mode === 'system' ? (systemTheme.matches ? 'dark' : 'light') : (mode === 'light' ? 'light' : 'dark'); }
            function apply(mode) {
                var resolved = resolve(mode);
                html.classList.toggle('light', resolved === 'light');
                html.classList.toggle('dark', resolved === 'dark');
                document.querySelector('[data-vatan-theme-color]')?.setAttribute('content', resolved === 'light' ? '#F5F7F6' : '#0B0F0D');
            }
            window.vatanGetThemeMode = function () { return localStorage.getItem('vatan-theme') || 'dark'; };
            window.vatanSetTheme = function (mode) { if (['light', 'dark', 'system'].includes(mode)) { localStorage.setItem('vatan-theme', mode); apply(mode); } };
            apply(window.vatanGetThemeMode());
        }());
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => 'ساخت کارکتر شیت با هوش مصنوعی',
        'description' => 'راهنمای ساخت کارکتر شیت چهره و بدن با محصولات آماده وطن.',
        'url' => $canonicalUrl,
        'inLanguage' => 'fa-IR',
        'isPartOf' => ['@type' => 'WebSite', 'name' => 'وطن AI', 'url' => route('site.home.root')],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'محصولات ساخت کارکتر شیت وطن',
        'numberOfItems' => $cards->count(),
        'itemListElement' => $cards->values()->map(fn ($card, $index) => [
            '@type' => 'ListItem', 'position' => $index + 1, 'name' => $card['title'], 'url' => $card['url'],
        ])->all(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => route('site.home.root')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'کارکتر شیت', 'item' => $canonicalUrl],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    <script type="application/ld+json">
    {!! json_encode([
        '@' . 'context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            ['@type' => 'Question', 'name' => 'کارکتر شیت چیست؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'کارکتر شیت یک مرجع تصویری از چهره، بدن، تناسبات و ظاهر شخصیت است که به حفظ هماهنگی او در تصاویر مختلف کمک می‌کند.']],
            ['@type' => 'Question', 'name' => 'چرا باید کارکتر شیت بسازم؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'با داشتن یک مرجع ثابت، لازم نیست در هر ساخت عکس را از ابتدا توضیح بدهی و احتمال تغییر ناخواسته‌ی چهره یا ظاهر شخصیت کمتر می‌شود.']],
            ['@type' => 'Question', 'name' => 'کارکتر شیت چهره و بدن چه تفاوتی دارند؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'شیت چهره روی ویژگی‌های صورت و نماهای مختلف تمرکز دارد؛ شیت بدن فرم کلی، تناسبات و ظاهر بدنی شخصیت را بهتر ثبت می‌کند.']],
            ['@type' => 'Question', 'name' => 'آیا می‌توانم بعداً از کارکتر شیت در ساخت‌های دیگر استفاده کنم؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'بله. هدف این صفحه و قابلیت پروفایل چهره وطن این است که مرجع شخصیت را یک‌بار بسازی و در ساخت‌های بعدی دوباره انتخاب کنی.']],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
</head>
<body class="vatan-preview character-sheet-page">
    @include('site.preview.partials.header')

    <main>
        <section class="cs-hero" id="top" aria-labelledby="cs-title">
            <div class="cs-hero__glow" aria-hidden="true"></div>
            <div class="vp-container cs-hero__layout">
                <div class="cs-hero__copy vp-reveal">
                    <p class="vp-kicker"><span aria-hidden="true">✦</span> راهنمای ساخت کارکتر شیت</p>
                    <h1 id="cs-title">کارکترت را یک‌بار بساز،<br><em>همیشه ثابت نگه دار</em></h1>
                    <p class="cs-hero__lead">با کارکتر شیت وطن، یک مرجع تصویری از چهره و بدن شخصیتت بساز و بعداً در تصویرسازی، پرتره، تیزر و پروژه‌های تبلیغاتی از همان شخصیت استفاده کن؛ بدون اینکه هر بار همه‌چیز را از اول توضیح بدهی.</p>
                    <div class="cs-hero__actions">
                        <a class="vp-button vp-button--primary" href="#products">انتخاب کارکتر شیت <span aria-hidden="true">←</span></a>
                        <a class="vp-button vp-button--secondary" href="{{ route('app.home') }}">ورود به استودیو <span aria-hidden="true">↙</span></a>
                    </div>
                    <ul class="cs-trust" aria-label="مزیت‌های کارکتر شیت وطن">
                        <li><i class="fa-solid fa-user-astronaut" aria-hidden="true"></i> مرجع ثابت برای شخصیت</li>
                        <li><i class="fa-solid fa-layer-group" aria-hidden="true"></i> مناسب چهره و بدن</li>
                        <li><i class="fa-solid fa-bolt" aria-hidden="true"></i> آماده برای ساخت‌های بعدی</li>
                    </ul>
                </div>
                <div class="cs-visual vp-reveal" aria-label="نمایش تصویری کارکتر شیت">
                    <div class="cs-visual__frame">
                        <div class="cs-visual__topline"><span><i></i><i></i><i></i></span><b>کارکتر شیت با وطن</b><span>CHARACTER REF</span></div>
                        <div class="cs-visual__grid">
                            @foreach($heroImages as $image)
                                <figure><img src="{{ $image }}" alt="نمایی از کارکتر شیت وطن" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async"><figcaption>{{ ['نمای روبه‌رو', 'جزئیات چهره', 'مرجع تبلیغاتی'][$loop->index] ?? 'مرجع شخصیت' }}</figcaption></figure>
                            @endforeach
                        </div>
                        <div class="cs-visual__status"><span class="cs-status-dot"></span> مرجع شخصیت آماده‌ی استفاده است <b>هویت ثابت +</b></div>
                    </div>
                    <div class="cs-float-card"><span><i class="fa-solid fa-wand-magic-sparkles"></i></span><div><strong>یک‌بار بساز</strong><small>بعداً دوباره انتخابش کن</small></div></div>
                </div>
            </div>
        </section>

        <section class="cs-intro vp-section" aria-labelledby="cs-intro-title">
            <div class="vp-container cs-intro__layout">
                <div class="vp-section-head vp-reveal"><p class="vp-kicker">کارکتر شیت چیست؟</p><h2 id="cs-intro-title">شخصیتت را از یک ایده،<br><em>به یک مرجع تبدیل کن</em></h2></div>
                <div class="cs-intro__copy vp-reveal"><p>کارکتر شیت یا شیت مرجع شخصیت، مجموعه‌ای از تصاویر و جزئیات بصری است که ظاهر کارکتر را مشخص می‌کند؛ از فرم چهره و بدن گرفته تا لباس، حالت کلی و نماهای مختلف.</p><p>وقتی این مرجع را یک‌بار می‌سازی، برای پروژه‌های بعدی پایه‌ی منسجم‌تری داری و خروجی‌ها احتمال بیشتری دارد که همان شخصیت را با ظاهر قابل تشخیص ادامه دهند.</p></div>
            </div>
        </section>

        <section class="cs-products vp-section" id="products" aria-labelledby="cs-products-title">
            <div class="vp-container">
                <div class="vp-section-head vp-section-head--row vp-reveal"><div><p class="vp-kicker">محصولات آماده وطن</p><h2 id="cs-products-title">مرجع مناسب شخصیتت را<br><em>انتخاب کن</em></h2></div><p>بسته به اینکه می‌خواهی روی چهره، فرم بدن یا کاربرد تبلیغاتی تمرکز کنی، محصول مناسب را انتخاب کن و مستقیم وارد ساخت شو.</p></div>
                <div class="cs-product-grid">
                    @foreach($cards as $card)
                        <a class="cs-product-card vp-reveal" href="{{ $card['url'] }}">
                            <div class="cs-product-card__media"><img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async"><span>{{ $card['label'] }}</span><i class="fa-solid {{ $card['icon'] }}" aria-hidden="true"></i></div>
                            <div class="cs-product-card__body"><h3>{{ $card['title'] }}</h3><p>{{ $card['description'] }}</p><span class="cs-product-card__cta">مشاهده و شروع ساخت <b aria-hidden="true">←</b></span></div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="cs-use-cases vp-section" aria-labelledby="cs-use-title">
            <div class="vp-container">
                <div class="vp-section-head vp-section-head--center vp-reveal"><p class="vp-kicker">چرا کارکتر شیت؟</p><h2 id="cs-use-title">یک مرجع ثابت،<br><em>چند مسیر خلاقانه</em></h2><p>شخصیتت را برای استفاده‌های مختلف آماده کن و هویت تصویری آن را منسجم نگه دار.</p></div>
                <div class="cs-use-grid">
                    <article class="cs-use-card vp-reveal"><span><i class="fa-solid fa-user"></i></span><h3>پروفایل و آواتار</h3><p>برای ساخت پرتره‌ها و تصاویر پروفایل هماهنگ، مرجع چهره‌ی شخصیتت را همیشه آماده داشته باش.</p></article>
                    <article class="cs-use-card vp-reveal"><span><i class="fa-solid fa-film"></i></span><h3>تیزر و تبلیغات</h3><p>در قاب‌ها و ایده‌های مختلف، ظاهر شخصیت را قابل تشخیص و یکدست نگه دار.</p></article>
                    <article class="cs-use-card vp-reveal"><span><i class="fa-solid fa-book-open"></i></span><h3>داستان و کمیک</h3><p>برای روایت‌های چندتصویری، یک مرجع بصری داشته باش تا شخصیت در صحنه‌ها تغییر ناخواسته نکند.</p></article>
                    <article class="cs-use-card vp-reveal"><span><i class="fa-solid fa-cubes"></i></span><h3>طراحی و توسعه</h3><p>جزئیات چهره، بدن و لباس را منسجم کن تا مسیر توسعه‌ی ایده برای تصویر، ویدیو یا مدل سه‌بعدی روشن‌تر شود.</p></article>
                </div>
            </div>
        </section>

        <section class="cs-how vp-section" aria-labelledby="cs-how-title">
            <div class="vp-container cs-how__layout">
                <div class="vp-section-head vp-reveal"><p class="vp-kicker">مسیر ساده ساخت</p><h2 id="cs-how-title">سه قدم تا<br><em>شخصیت آماده</em></h2><p>از عکس یا ایده‌ی اولیه شروع کن، محصول مناسب را انتخاب کن و مرجع شخصیتت را برای ساخت‌های بعدی آماده کن.</p><a class="vp-button vp-button--primary" href="#products">شروع ساخت کارکتر شیت <span aria-hidden="true">←</span></a></div>
                <ol class="cs-steps">
                    <li class="vp-reveal"><b>۱</b><div><h3>محصول مناسب را انتخاب کن</h3><p>اگر تمرکزت روی هویت چهره است، شیت چهره را انتخاب کن؛ برای فرم کلی بدن یا پروژه‌ی تبلیغاتی، گزینه‌ی مناسب‌تر را باز کن.</p></div></li>
                    <li class="vp-reveal"><b>۲</b><div><h3>تصویر و جزئیات را وارد کن</h3><p>یک عکس واضح اضافه کن و در صورت نیاز، ایده‌ی ظاهری یا کاربرد شخصیت را کوتاه توضیح بده.</p></div></li>
                    <li class="vp-reveal"><b>۳</b><div><h3>مرجع شخصیت را ذخیره کن</h3><p>بعد از دریافت خروجی، آن را به‌عنوان مرجع کارکترت نگه دار و در ساخت‌های بعدی دوباره از آن استفاده کن.</p></div></li>
                </ol>
            </div>
        </section>

        <section class="cs-faq vp-section" id="faq" aria-labelledby="cs-faq-title">
            <div class="vp-container cs-faq__layout">
                <div class="vp-section-head vp-reveal"><p class="vp-kicker">سوالات متداول</p><h2 id="cs-faq-title">قبل از شروع<br><em>بدان</em></h2></div>
                <div class="cs-faq-list">
                    <details class="vp-reveal" open><summary>کارکتر شیت چیست و چه کاربردی دارد؟ <i class="fa-solid fa-plus"></i></summary><p>کارکتر شیت یک مرجع تصویری برای ثبت ظاهر شخصیت است. این مرجع کمک می‌کند چهره، فرم بدن و جزئیات ظاهری کارکتر در تصاویر و صحنه‌های مختلف هماهنگ‌تر بماند.</p></details>
                    <details class="vp-reveal"><summary>برای ساخت کارکتر شیت چهره چه عکسی مناسب است؟ <i class="fa-solid fa-plus"></i></summary><p>عکس واضح، روبه‌رو، با نور متعادل و بدون پوشش روی صورت بهترین نقطه‌ی شروع است. اگر عکس‌های بیشتری از زاویه‌های مختلف داشته باشی، مرجع کامل‌تری در اختیار مدل قرار می‌گیرد.</p></details>
                    <details class="vp-reveal"><summary>شیت بدن برای چه پروژه‌هایی مناسب است؟ <i class="fa-solid fa-plus"></i></summary><p>شیت بدن برای پروژه‌هایی مناسب است که فرم کلی بدن، تناسبات، لباس و ظاهر کامل شخصیت اهمیت دارد؛ از طراحی شخصیت و داستان تصویری تا تبلیغات و محتوای برند.</p></details>
                    <details class="vp-reveal"><summary>آیا بعداً می‌توانم از کارکتر شیت در وطن استفاده کنم؟ <i class="fa-solid fa-plus"></i></summary><p>بله. می‌توانی تصاویر مرجع شخصیتت را در پروفایل نگه داری و در ساخت‌های بعدی به‌جای آپلود دوباره، همان مرجع را انتخاب کنی.</p></details>
                </div>
            </div>
        </section>

        <section class="cs-final vp-section" aria-labelledby="cs-final-title">
            <div class="vp-container"><div class="cs-final__panel vp-reveal"><p class="vp-kicker">وقتشه شخصیتت را ثابت کنی</p><h2 id="cs-final-title">از یک ایده‌ی ساده<br><em>شروع کن</em></h2><p>محصول مناسب را انتخاب کن و مرجع تصویری کارکترت را برای ساخت‌های بعدی آماده کن.</p><a class="vp-button vp-button--primary" href="#products">انتخاب محصول کارکتر شیت <span aria-hidden="true">←</span></a></div></div>
        </section>
    </main>

    @include('site.preview.partials.footer')
    <section class="vp-app-footer-wrap vp-app-footer-wrap--public" aria-label="فوتر اپ وطن">@include('app.partials.footer')</section>
    @include('layouts.partials.nav-scripts')
    <script src="{{ \App\Support\AppAsset::url('assets/site/js/home-preview.js') }}" defer></script>
</body>
</html>
