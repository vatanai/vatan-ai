@foreach (($createdMedia ?? []) as $item)
  @php
    $isVideo = ($item->media_kind ?? 'image') === 'video';
    $mediaUrl = (string) ($item->media_url ?? ($item->imageUrl() ?? ''));
    $product = $item->product;
    $productName = optional($product)->name_fa ?? optional($product)->name_en ?? 'نامشخص';
    $productSlug = optional($product)->route_slug;
  @endphp
  <div class="profile-media-card">
    <button type="button" class="grid-cell grid-cell--clickable {{ $isVideo ? 'grid-cell--video' : '' }}"
            data-media-kind="{{ $isVideo ? 'video' : 'image' }}"
            data-media-url="{{ $mediaUrl }}"
            data-image="{{ $isVideo ? '' : $mediaUrl }}"
            data-video="{{ $isVideo ? $mediaUrl : '' }}"
            data-poster="{{ $isVideo ? ($item->poster_url ?? '') : '' }}"
            data-date="{{ $item->jalali_created_at }}"
            data-product-name="{{ $productName }}"
            data-product-url="{{ $productSlug ? route('app.product', $productSlug) : '' }}"
            data-product-create-url="{{ $productSlug ? route('app.create.product', $productSlug) : '' }}"
            data-product-download-url="{{ optional($product)->slug ? route('app.product.download', $product->slug) : '' }}"
            data-delete-url="{{ $isVideo ? route('profile.generated-videos.destroy', $item) : route('profile.generated-images.destroy', $item) }}"
            aria-label="نمایش {{ $isVideo ? 'ویدیوی' : 'عکس' }} ساخته‌شده">
      @if($isVideo)
        <img class="grid-img grid-video-poster" alt="" loading="lazy" decoding="async"
             data-video-source="{{ $mediaUrl }}"
             @if($item->poster_url ?? false) src="{{ $item->poster_url }}" @endif>
        <video data-src="{{ $mediaUrl }}" class="grid-video-source" muted playsinline preload="none" aria-hidden="true" hidden></video>
        <span class="grid-cell-video-badge" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M8 5v14l11-7L8 5Z"></path></svg></span>
      @else
        <img src="{{ route('profile.generated-images.thumbnail', $item) }}" alt="" class="grid-img" loading="{{ ($eagerMedia ?? false) && $loop->index < 4 ? 'eager' : 'lazy' }}" decoding="async">
      @endif
    </button>
  </div>
@endforeach
