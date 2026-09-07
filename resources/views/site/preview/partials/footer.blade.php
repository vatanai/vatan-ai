@php
    $isPublicHome = request()->routeIs('site.home.root');
    $footerSectionUrl = fn (string $section) => $isPublicHome
        ? '#' . $section
        : route('site.home.root') . '#' . $section;
@endphp

<footer class="vp-footer">
    <div class="vp-container vp-footer__grid">
        <div class="vp-footer__intro"><a href="{{ $footerSectionUrl('top') }}" class="vp-brand vp-brand--app" aria-label="وطن، صفحه نخست"><img src="{{ asset('assets/img/icon_vatan.svg') }}" alt="" width="31" height="31"><img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="وطن" width="65" height="29"></a><p>وطن یک پلتفرم فارسی هوش مصنوعی است که ساخت عکس و ویدیو را ساده می‌کند. ایده یا عکس خودت را وارد کن، سبک دلخواهت را انتخاب کن و در چند ثانیه نتیجه‌ای حرفه‌ای بگیر. از استوری تا عکس محصول، همه‌چیز با چند کلیک و بدون نیاز به دانش فنی آماده می‌شود.</p></div>
        <div><h3>دسترسی سریع</h3><a href="{{ $footerSectionUrl('features') }}">ویژگی‌ها</a><a href="{{ $footerSectionUrl('ideas') }}">سبک‌ها</a><a href="{{ $footerSectionUrl('gallery') }}">نمونه‌ها</a><a href="{{ $footerSectionUrl('pricing-proposal') }}">تعرفه‌ها</a><a href="{{ $footerSectionUrl('faq') }}">سوالات</a></div>
        <div><h3>راهنما و پشتیبانی</h3><a href="{{ $footerSectionUrl('faq') }}">راهنما و سوالات</a><a href="{{ $footerSectionUrl('pricing-proposal') }}">تعرفه‌ها</a><a href="{{ route('privacy') }}">قوانین</a><a href="{{ route('privacy') }}">حریم خصوصی</a><a href="{{ route('support.index') }}">پشتیبانی</a></div>
        <div><h3>مقالات</h3><a href="{{ route('articles.index') }}">همه مقالات</a><a href="{{ route('articles.category', 'ai-image-tutorials') }}">راهنمای ساخت عکس</a><a href="{{ route('articles.category', 'ai-video-tutorials') }}">آموزش ساخت ویدیو</a><a href="{{ route('articles.category', 'prompts-and-ideas') }}">ایده و پرامپت‌های آماده</a><a href="{{ route('articles.category', 'ai-models-and-trends') }}">مدل‌ها و ترندهای هوش مصنوعی</a></div>
        <div class="vp-footer__social">
            <h3>در ارتباط باشیم</h3>
            <a class="vp-footer__text-link" href="#">همکاری با وطن</a>
            <a class="vp-footer__text-link" href="{{ route('support.index') }}">چت آنلاین</a>
            <a class="vp-footer__text-link" href="{{ route('support.index') }}">تماس با ما</a>
            <div class="vp-footer__trust-row">
                <div class="vp-footer__social-links" aria-label="شبکه‌های اجتماعی وطن">
                    <a class="vp-footer__social-link" href="https://t.me/vatan_support" target="_blank" rel="noopener noreferrer" aria-label="تلگرام" title="تلگرام"><i class="fa-brands fa-telegram-plane vp-footer__social-mark" aria-hidden="true"></i></a>
                    <a class="vp-footer__social-link" href="#" aria-label="بله" title="بله"><span class="vp-footer__social-mark vp-footer__social-mark--bale" aria-hidden="true"></span></a>
                    <a class="vp-footer__social-link" href="https://instagram.com/ai_vatan" target="_blank" rel="noopener noreferrer" aria-label="اینستاگرام" title="اینستاگرام"><i class="fa-brands fa-instagram vp-footer__social-mark" aria-hidden="true"></i></a>
                    <a class="vp-footer__social-link" href="#" aria-label="واتس‌اپ" title="واتس‌اپ"><i class="fa-brands fa-whatsapp vp-footer__social-mark" aria-hidden="true"></i></a>
                </div>
                @if($isPublicHome)
                    <a class="vp-footer__enamad" referrerpolicy="origin" target="_blank" rel="noopener" href="https://trustseal.enamad.ir/?id=6946181&amp;Code=0NHFoHstfdAM2C6oT3VRzfUGMbMrumYG" aria-label="مشاهده اعتبار اینماد وطن">
                        <img class="vp-footer__enamad-image" referrerpolicy="origin" src="https://trustseal.enamad.ir/logo.aspx?id=6946181&amp;Code=0NHFoHstfdAM2C6oT3VRzfUGMbMrumYG" alt="نشان اعتماد الکترونیکی وطن" code="0NHFoHstfdAM2C6oT3VRzfUGMbMrumYG">
                    </a>
                @endif
            </div>
        </div>
    </div>
</footer>
