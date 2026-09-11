@php
    $spotlightProducts = $products->take(3)->values();
@endphp
<div class="hb-video-spotlight">
  @foreach($spotlightProducts as $spotlightIndex => $product)
    @php
      $videoUrl = trim((string) $product->preview_video_url);
      if ($videoUrl !== '' && !str_starts_with($videoUrl, 'http') && !str_starts_with($videoUrl, '/')) {
        $videoUrl = str_starts_with($videoUrl, 'assets/')
          ? asset($videoUrl)
          : asset('storage/' . ltrim($videoUrl, '/'));
      }
    @endphp
    <a class="hb-video-story {{ $spotlightIndex === 0 ? 'is-featured' : '' }}" href="{{ route('app.product', $product->route_slug) }}">
      @if($videoUrl)
        <video class="hb-video-story-poster" data-hb-video-src="{{ $videoUrl }}" poster="{{ $product->displayImageUrl() }}" muted loop playsinline preload="none" aria-label="پیش‌نمایش {{ $product->name_fa }}"></video>
      @else
        <img class="hb-video-story-poster" src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="lazy" decoding="async">
      @endif
      <div class="hb-video-story-shade"></div>
      <span class="hb-video-story-play"><i class="fa-solid fa-play"></i></span>
      <div class="hb-video-story-copy"><b>{{ $product->name_fa }}</b><small>{{ $product->subcategory ?: 'ویدیوی هوش مصنوعی' }}</small></div>
    </a>
  @endforeach
</div>
