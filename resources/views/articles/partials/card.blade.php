<article class="article-card">
  <a class="article-card__media" href="{{ $article->publicUrl() }}" aria-label="{{ $article->title }}">
    @if($article->imageUrl())
      <img src="{{ $article->imageUrl() }}" alt="{{ $article->featured_image_alt ?: $article->title }}" loading="lazy" width="720" height="405">
    @endif
    @if($article->is_featured)<span class="article-card__featured"><i class="fa-solid fa-star"></i> منتخب</span>@endif
  </a>
  <div class="article-card__body">
    <div class="article-card__meta">
      <a href="{{ $article->category->publicUrl() }}">{{ $article->category->name }}</a>
      <span>{{ $article->reading_minutes }} دقیقه مطالعه</span>
    </div>
    <h2><a href="{{ $article->publicUrl() }}">{{ $article->title }}</a></h2>
    <p>{{ $article->excerpt }}</p>
    <div class="article-card__footer">
      <span>{{ $article->published_at?->locale('fa')->translatedFormat('j F Y') }}</span>
      <a href="{{ $article->publicUrl() }}">مطالعه مقاله <i class="fa-solid fa-arrow-left"></i></a>
    </div>
  </div>
</article>
