@php($galleryFallback = [
    ['best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'سینمایی', 'پرتره‌ای با نور عمیق'],
    ['gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp', 'پروفایل', 'برای حضور حرفه‌ای‌تر'],
    ['60ed34f8-ed85-4ae0-9b63-191dcbe11800.mp4', 'خلاقانه', 'ایده‌ای خارج از قاب', 'video'],
    ['Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg', 'ترند', 'حال‌وهوای امروزی'],
    ['a1be8a17-0f52-44e3-8693-6f2d7a3056b2.mp4', 'فشن', 'قاب ادیتوریال', 'video'],
])
@php($gallery = $inspirationGallery ?? collect($galleryFallback)->map(fn ($item) => ['url' => asset(($item[3] ?? 'image') === 'video' ? 'assets/videos/' . $item[0] : 'assets/img/' . $item[0]), 'tag' => $item[1], 'title' => $item[2], 'media_type' => $item[3] ?? 'image'])->all())
<section class="vp-section vp-gallery" id="gallery" aria-labelledby="gallery-title">
    <div class="vp-container">
        <header class="vp-section-head vp-section-head--row vp-reveal">
            <div><p class="vp-kicker">از نتیجه‌ها الهام بگیر</p><h2 id="gallery-title">الهام بگیر و بساز</h2></div>
            <p>نمونه‌های محبوب و خروجی‌های ترند را ببین، ایده بگیر و ساخت خودت را شروع کن.</p>
        </header>
        <div class="vp-gallery__grid">
            @foreach ($gallery as $index => $item)
                <article class="vp-gallery-card vp-gallery-card--{{ ($index % 5) + 1 }} vp-reveal" style="--delay: {{ $index * 55 }}ms">
                    @php($galleryLink = $item['link_url'] ?? route('app.home'))
                    <a class="vp-gallery-card__link" href="{{ $galleryLink }}" @if(!empty($item['open_in_new_tab'])) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ $item['link_label'] ?? $item['title'] }}">
                    @if(($item['media_type'] ?? 'image') === 'video')
                        <video src="{{ $item['url'] }}" poster="{{ $item['poster'] ?? '' }}" muted loop playsinline preload="none" aria-label="{{ $item['title'] }}"></video>
                    @else
                        <img src="{{ $item['url'] }}" alt="{{ $item['title'] }}" loading="lazy" decoding="async">
                    @endif
                    <div class="vp-gallery-card__caption"><span>{{ $item['tag'] ?? 'نمونه وطن' }}</span><strong>{{ !empty($item['show_text']) ? ($item['display_text'] ?? $item['title']) : $item['title'] }}</strong></div>
                    <span class="vp-gallery-card__action">{{ $item['link_label'] ?? 'ساخت مشابه' }} <b>←</b></span>
                    </a>
                </article>
            @endforeach
        </div>
        <div class="vp-centered-action vp-reveal"><a class="vp-button vp-button--secondary" href="{{ route('app.explore') }}">مشاهده بیشتر <span aria-hidden="true">←</span></a></div>
    </div>
</section>
