@foreach($resolvedGalleries as $resolved)
  @php
    $gallery = $resolved['model'];
  @endphp
  <section class="article-gallery article-gallery--{{ $gallery->display_style }}">
    <div class="article-section-heading">
      <div><span>گالری مقاله</span><h2>{{ $gallery->title }}</h2></div>
      @if($gallery->description)<p>{{ $gallery->description }}</p>@endif
    </div>
    <div class="article-gallery__items">
      @foreach($resolved['items'] as $item)
        @php
          $itemUrl = $item['url'] ?? null;
          $videoUrl = $item['video_url'] ?? $itemUrl;
          $linkUrl = $item['link_url'] ?? null;
          $productId = $item['product_id'] ?? null;
        @endphp
        <article class="article-gallery__item">
          @if(($item['media_type'] ?? 'image') === 'video' && $videoUrl)
            <video controls playsinline preload="metadata" @if($itemUrl) poster="{{ $itemUrl }}" @endif><source src="{{ $videoUrl }}"></video>
          @elseif($itemUrl)
            @if($linkUrl)
              <a href="{{ $linkUrl }}" @if($productId) data-article-event="product_cta_click" data-product-id="{{ $productId }}" @endif><img src="{{ $itemUrl }}" alt="{{ $item['alt'] ?? $item['title'] ?? '' }}" loading="lazy"></a>
            @else
              <img src="{{ $itemUrl }}" alt="{{ $item['alt'] ?? $item['title'] ?? '' }}" loading="lazy">
            @endif
          @endif
          @if(!empty($item['title']) || !empty($item['description']))
            <div>
              <strong>{{ $item['title'] ?? '' }}</strong>
              @if(!empty($item['description']))<p>{{ str($item['description'])->limit(150) }}</p>@endif
              @if($linkUrl)<a href="{{ $linkUrl }}" @if($productId) data-article-event="product_cta_click" data-product-id="{{ $productId }}" @endif>مشاهده و ساخت <i class="fa-solid fa-arrow-left"></i></a>@endif
            </div>
          @endif
        </article>
      @endforeach
    </div>
  </section>
@endforeach
