@php
    $spotlightProducts = $products->take(3)->values();
@endphp
<div class="hb-video-spotlight">
  @foreach($spotlightProducts as $spotlightIndex => $product)
    <a class="hb-video-story {{ $spotlightIndex === 0 ? 'is-featured' : '' }}" href="{{ route('app.product', $product->route_slug) }}">
      <img
        class="hb-video-story-poster"
        src="{{ $product->displayImageUrl() }}"
        alt="{{ $product->name_fa }}"
        loading="lazy"
        decoding="async"
      >
      <div class="hb-video-story-shade"></div>
      <span class="hb-video-story-play"><i class="fa-solid fa-play"></i></span>
      <div class="hb-video-story-copy"><b>{{ $product->name_fa }}</b><small>{{ $product->subcategory ?: 'ویدیوی هوش مصنوعی' }}</small></div>
    </a>
  @endforeach
</div>
