@php($ideas = [
    ['پرتره حرفه‌ای', 'برای تصویر پروفایل و رزومه', 'elegant-woman-cafe-portrait-by-promptplum.avif'],
    ['قاب سینمایی', 'نورپردازی دراماتیک و خاص', 'best-ai-prompts-for-cinematic-photos-and-portraits.jpeg'],
    ['استوری ترند', 'آماده برای شبکه‌های اجتماعی', 'best-friends-ai-prompt-2.webp'],
    ['عکس محصول', 'تمیز، روشن و فروشنده', '9cb93b50-d93f-462f-b6d4-113f63ffc603.avif'],
    ['فشن ادیتوریال', 'استایل مجله‌ای و لوکس', 'ai-photo-editor-prompt.webp'],
    ['کاور محتوا', 'برای پست و ویدیوی کوتاه', 'prompt-for-gemini-ai-girl.webp'],
])
<section class="vp-section vp-ideas" id="ideas" aria-labelledby="ideas-title">
    <div class="vp-container">
        <header class="vp-section-head vp-section-head--row vp-reveal">
            <div><p class="vp-kicker">برای شروع سریع</p><h2 id="ideas-title" class="vp-ideas__title">ایده‌های آماده برای ساخت</h2></div>
            <p>فقط ایده‌ات را انتخاب کن؛ پرامپت، تنظیمات و مسیر ساخت به‌صورت خودکار آماده می‌شوند.</p>
        </header>
        <div class="vp-ideas__grid">
            @foreach ($ideas as $index => [$title, $description, $image])
                @php($ideaItem = $ideasGallery[$index] ?? ['url' => asset('assets/img/' . $image), 'title' => $title])
                @php($imageUrl = $ideaItem['url'])
                @php($ideaLink = $ideaItem['link_url'] ?? route('app.home'))
                <article class="vp-idea-card vp-reveal" style="--delay: {{ $index * 65 }}ms">
                    <a class="vp-idea-card__link" href="{{ $ideaLink }}" @if(!empty($ideaItem['open_in_new_tab'])) target="_blank" rel="noopener noreferrer" @endif aria-label="{{ $ideaItem['link_label'] ?? $ideaItem['title'] ?? $title }}">
                        <img src="{{ $imageUrl }}" alt="{{ $ideaItem['title'] ?? $title }}" loading="lazy" decoding="async">
                        <div class="vp-idea-card__shade"></div>
                        <div class="vp-idea-card__content"><span>{{ $ideaItem['tag'] ?? 'ایده آماده' }}</span><h3>{{ !empty($ideaItem['show_text']) ? ($ideaItem['display_text'] ?? $ideaItem['title'] ?? $title) : $title }}</h3><p>{{ $description }}</p></div>
                        <span class="vp-idea-card__action">{{ $ideaItem['link_label'] ?? 'امتحان کن' }} <b>←</b></span>
                    </a>
                </article>
            @endforeach
        </div>
        <div class="vp-centered-action vp-reveal"><a class="vp-button vp-button--secondary" href="{{ route('app.home') }}">مشاهده همه ایده‌ها <span aria-hidden="true">←</span></a></div>
    </div>
</section>
