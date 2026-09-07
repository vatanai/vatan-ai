<section class="vp-section vp-capabilities" id="features" aria-labelledby="features-title">
    <div class="vp-container">
        <header class="vp-section-head vp-reveal">
            <p class="vp-kicker">یک پلتفرم</p>
            <h2 id="features-title">امکانات بی‌مرز<br>برای ساخت محتوا</h2>
            <p>قالبی را انتخاب کن، عکس یا اطلاعات لازم را بده و نتیجه‌ای حرفه‌ای تحویل بگیر.</p>
        </header>
        <div class="vp-capabilities__grid">
            @foreach ([
                ['fa-regular fa-face-smile', 'عکس پروفایل', 'از یک عکس ساده، تصویر پروفایل حرفه‌ای، متفاوت و مناسب شبکه‌های اجتماعی بساز.', 'ساخت پروفایل', route('app.home')],
                ['fa-solid fa-fire', 'عکس‌های ترند', 'قالب‌های محبوب و ترند روز را انتخاب کن و نسخه خودت را بساز.', 'دیدن ترندها', route('app.trends')],
                ['fa-solid fa-user-tie', 'پرتره حرفه‌ای', 'بدون استودیو و عکاسی، پرتره‌های جذاب با سبک‌ها و فضاهای مختلف بساز.', 'ساخت پرتره', route('app.home')],
                ['fa-solid fa-mobile-screen-button', 'استوری اینستاگرام', 'برای استوری، کمپین، معرفی یا محتوای روزانه، تصویرهای آماده و جذاب بساز.', 'ساخت استوری', route('app.home')],
                ['fa-solid fa-photo-film', 'پست شبکه‌های اجتماعی', 'تصاویر مناسب پست اینستاگرام و سایر شبکه‌های اجتماعی را با قالب‌های آماده تولید کن.', 'ساخت پست', route('app.home')],
                ['fa-solid fa-bag-shopping', 'عکس محصول', 'محصولت را در صحنه‌ها و فضاهای حرفه‌ای نمایش بده، بدون نیاز به عکاسی پرهزینه.', 'ساخت عکس محصول', route('app.home')],
                ['fa-solid fa-bullhorn', 'محتوای تبلیغاتی', 'برای تبلیغات، معرفی محصول، کمپین و فروش، تصاویر چشم‌گیر بساز.', 'ساخت تبلیغ', route('app.home')],
                ['fa-solid fa-layer-group', 'قالب‌های آماده', 'نمی‌دونی چی بسازی؟ بین قالب‌های آماده بگرد، یکی رو انتخاب کن و فقط عکست رو بده.', 'دیدن قالب‌ها', route('products.index')],
            ] as $index => [$icon, $title, $description, $cta, $link])
                <article class="vp-capability vp-reveal" style="--delay: {{ $index * 70 }}ms">
                    <span class="vp-capability__icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                    <h3>{{ $title }}</h3>
                    <p>{{ $description }}</p>
                    <a href="{{ $link }}">{{ $cta }} <span aria-hidden="true">←</span></a>
                </article>
            @endforeach
        </div>
    </div>
</section>
