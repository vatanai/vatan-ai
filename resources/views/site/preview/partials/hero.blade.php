<section class="vp-hero" id="top" aria-labelledby="hero-title">
    @php
        $managedHeroTitle = ($sitePage?->content('show_page_title', true) ?? true)
            ? ($sitePage?->title ?: 'بدون محدودیت بساز؛ با وطن خلق کن')
            : null;
        $managedHeroTitleParts = $managedHeroTitle ? array_map('trim', explode('؛', $managedHeroTitle, 2)) : [];
        $managedHeroLead = $sitePage?->subtitle ?: 'عکس، ویدیو و محتوای خلاقانه را با وطن خلق کن. برای شبکه‌های اجتماعی، تصویر پروفایل، استوری، پرتره، بنر و محتوای تبلیغاتی، با چند کلیک خروجی حرفه‌ای بگیر.';
    @endphp
    <div class="vp-hero__grid-pattern" aria-hidden="true"></div>
    <div class="vp-container vp-hero__layout">
        <div class="vp-hero__content vp-reveal">
            <p class="vp-eyebrow"><span class="vp-spark">✦</span> پلتفرم هوش مصنوعی وطن</p>
            @if($managedHeroTitle)
                <h1 id="hero-title">{{ $managedHeroTitleParts[0] }}@if(isset($managedHeroTitleParts[1]))<br><em>{{ $managedHeroTitleParts[1] }}</em>@endif</h1>
            @endif
            <p class="vp-hero__lead">{{ $managedHeroLead }}</p>
            <div class="vp-hero__actions">
                <a class="vp-button vp-button--primary" href="{{ route('app.home') }}">رایگان شروع کن <span aria-hidden="true">←</span></a>
                <a class="vp-button vp-button--secondary" href="{{ route('app.home') }}">مشاهده نمونه‌ها <span aria-hidden="true">↙</span></a>
            </div>
            <ul class="vp-trust-list" aria-label="مزیت‌های شروع با وطن">
                <li>اعتبار هدیه برای کاربران جدید</li><li>ورود سریع با پیامک</li><li>بدون نیاز به دانش فنی</li>
            </ul>
        </div>
    </div>
    @php($showcaseFallback = [
        ['Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg', 'vp-showcase-card--edge'],
        ['best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'vp-showcase-card--tall'],
        ['A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg', 'vp-showcase-card--small'],
        ['gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp', 'vp-showcase-card--medium'],
        ['Screenshot-2025-12-09-at-12.33.35-PM.avif', 'vp-showcase-card--tall'],
        ['ai-photo-editor-prompt.webp', 'vp-showcase-card--large'],
        ['prompt-for-gemini-ai-girl.webp', 'vp-showcase-card--medium'],
        ['moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif', 'vp-showcase-card--tall'],
        ['elegant-woman-cafe-portrait-by-promptplum.avif', 'vp-showcase-card--small'],
        ['lookasjide.fbsbx.webp', 'vp-showcase-card--edge'],
    ])
    @php($showcase = $heroGallery ?? collect($showcaseFallback)->map(fn ($item) => ['url' => asset('assets/img/' . $item[0]), 'title' => 'نمونه وطن'])->all())
    <div class="vp-showcase vp-reveal" data-showcase aria-label="گالری متحرک نمونه‌های خروجی وطن">
        <div class="vp-showcase__rail" dir="ltr">
            @foreach ($showcase as $index => $item)
                <figure class="vp-showcase-card">
                    @php($showcaseLink = $item['link_url'] ?? null)
                    @if($showcaseLink)<a class="vp-showcase-card__link" href="{{ $showcaseLink }}" @if(!empty($item['open_in_new_tab'])) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ $item['link_label'] ?? $item['title'] ?? 'مشاهده نمونه' }}">@endif
                    <img src="{{ $item['url'] }}" alt="{{ $item['title'] ?? 'نمونه تولیدشده با وطن' }}" draggable="false" loading="{{ $index < 2 ? 'eager' : 'lazy' }}" decoding="async" @if($index === 0) fetchpriority="high" @endif>
                    @if(!empty($item['show_text']) && ($item['display_text'] ?? $item['title'] ?? null))<span class="vp-showcase-card__text">{{ $item['display_text'] ?? $item['title'] }}</span>@endif
                    @if($showcaseLink)</a>@endif
                </figure>
            @endforeach
        </div>
    </div>
</section>
