<section class="vp-section vp-workflow" id="workflow" aria-labelledby="workflow-title">
    <div class="vp-container vp-workflow__layout">
        <div class="vp-workflow__copy vp-reveal">
            <p class="vp-kicker">چطور با وطن بسازی؟</p>
            <h2 id="workflow-title">فقط انتخاب کن،<br>عکست رو بده، بساز</h2>
            <p>نه پرامپت لازم داری، نه تنظیمات پیچیده.<br>قالبی که دوست داری را انتخاب کن و در چند ثانیه به نتیجه برس.</p>
            <div class="vp-workflow__decision-row">
                <ul class="vp-check-list">
                    <li>بدون نیاز به پرامپت</li><li>بدون تنظیمات پیچیده</li><li>فقط چند ثانیه تا خروجی</li>
                </ul>
                <div class="vp-workflow__cta-stack">
                    <a class="vp-button vp-button--primary" href="{{ route('app.home') }}">رفتن به بساز <span aria-hidden="true">←</span></a>
                    <small>در موبایل و دسکتاپ</small>
                </div>
            </div>
        </div>
        <div class="vp-workflow-demo vp-reveal" aria-label="نمایی از روند ساخت در وطن">
            <div class="vp-workflow-demo__bar"><i></i><i></i><i></i><span>فضای ساخت وطن</span></div>
            <div class="vp-workflow-demo__board">
                <div class="vp-node vp-node--source"><b><span>۱</span> قالب رو انتخاب کن</b><small>بین قالب‌ها و ترندها بگرد</small><div class="vp-mini-options"><i></i><i class="is-on"></i><i></i></div></div>
                <span class="vp-flow-arrow" aria-hidden="true">←</span>
                <div class="vp-node vp-node--active"><b><span>۲</span> عکست رو اضافه کن</b><small>عکس خودت یا محصولت رو وارد کن؛ بقیه کارها با وطن</small><div class="vp-prompt-line"><i></i><i></i><i></i></div></div>
                <span class="vp-flow-arrow" aria-hidden="true">←</span>
                <div class="vp-node vp-node--result"><b><span>۳</span> خروجی رو بگیر</b><small>چند ثانیه صبر کن و نتیجه آماده‌ات رو ببین</small><img src="{{ asset('assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif') }}" alt="پیش‌نمایش خروجی"></div>
            </div>
            <div class="vp-workflow-demo__status"><span></span> خروجی تو آماده ساختن است</div>
        </div>
    </div>
</section>
