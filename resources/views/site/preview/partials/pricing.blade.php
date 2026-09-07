<section class="vp-section vp-pricing" id="pricing" aria-labelledby="pricing-title">
    <div class="vp-container">
        <header class="vp-section-head vp-section-head--center vp-reveal">
            <p class="vp-kicker">برای هر ریتم ساخت</p><h2 id="pricing-title">تعرفه‌ها و پلن‌ها</h2><p>به‌اندازه نیازت انتخاب کن و هر زمان خواستی پلنت را ارتقا بده</p>
        </header>
        <p class="vp-pricing__note vp-reveal">پلن‌ها و قیمت‌ها مستقیماً از تعرفه‌های فعال وطن نمایش داده می‌شوند</p>
        <div class="vp-plans vp-plans--catalog">
            @forelse ($plans as $index => $plan)
                <div class="vp-reveal" style="--delay: {{ $index * 65 }}ms">
                    @include('site.partials.plan-card', ['plan' => $plan, 'offer' => $plan->offer, 'planDisplay' => ['show_images' => false]])
                </div>
            @empty
                <p class="vp-pricing__empty">در حال حاضر پلن فعالی برای نمایش وجود ندارد.</p>
            @endforelse
        </div>
    </div>
</section>
