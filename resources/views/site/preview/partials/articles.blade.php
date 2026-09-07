@if(($homeArticles ?? collect())->isNotEmpty())
<section class="vp-home-articles" id="articles" aria-labelledby="home-articles-title">
    <div class="vp-container">
        <div class="vp-section-head vp-section-head--row">
            <div><span class="vp-kicker">از مجله وطن</span><h2 id="home-articles-title">یاد بگیر، بساز و بهتر نتیجه بگیر</h2></div>
            <a class="vp-home-articles__all" href="{{ route('articles.index') }}">مشاهده همه مقالات <i class="fa-solid fa-arrow-left"></i></a>
        </div>
        <div class="vp-home-articles__grid">
            @foreach($homeArticles as $article)
                <article class="vp-home-article">
                    <a class="vp-home-article__media" href="{{ $article->publicUrl() }}">
                        @if($article->imageUrl())<img src="{{ $article->imageUrl() }}" alt="{{ $article->featured_image_alt ?: $article->title }}" loading="lazy" width="720" height="405">@endif
                        <span>{{ $article->category->name }}</span>
                    </a>
                    <div class="vp-home-article__body">
                        <div><time datetime="{{ $article->published_at?->toDateString() }}">{{ $article->published_at?->locale('fa')->translatedFormat('j F Y') }}</time><small>{{ $article->reading_minutes }} دقیقه مطالعه</small></div>
                        <h3><a href="{{ $article->publicUrl() }}">{{ $article->title }}</a></h3>
                        <p>{{ $article->excerpt }}</p>
                        <a href="{{ $article->publicUrl() }}">مطالعه مقاله <i class="fa-solid fa-arrow-left"></i></a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif
