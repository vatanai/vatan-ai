@php
    $spotlightProducts = $products->take(3)->values();
@endphp
<div class="hb-video-spotlight">
  @foreach($spotlightProducts as $spotlightIndex => $product)
    @php
      $videoUrl = trim((string) $product->preview_video_url);
      if ($videoUrl !== '' && !str_starts_with($videoUrl, 'http') && !str_starts_with($videoUrl, '/')) $videoUrl = asset('storage/' . ltrim($videoUrl, '/'));
    @endphp
    <a class="hb-video-story {{ $spotlightIndex === 0 ? 'is-featured' : '' }}" href="{{ route('app.product', $product->route_slug) }}">
      @if($videoUrl)
        <video src="{{ $videoUrl }}" poster="{{ $product->displayImageUrl() }}" autoplay muted loop playsinline preload="metadata"></video>
      @else
        <span class="hb-video-story-poster" style="background-image:url('{{ $product->displayImageUrl() }}')"></span>
      @endif
      <div class="hb-video-story-shade"></div>
      <span class="hb-video-story-play"><i class="fa-solid fa-play"></i></span>
      <div class="hb-video-story-copy"><b>{{ $product->name_fa }}</b><small>{{ $product->subcategory ?: 'ویدیوی هوش مصنوعی' }}</small></div>
    </a>
  @endforeach
</div>
<script>
  (function () {
    if (window.__hbVideoAutoplayBound) return;
    window.__hbVideoAutoplayBound = true;
    function playHomeVideos() {
      document.querySelectorAll('.hb-video-story video, .hb-video-loop-card video').forEach(function (video) {
        video.muted = true;
        var promise = video.play();
        if (promise && promise.catch) promise.catch(function () {});
      });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', playHomeVideos);
    else playHomeVideos();
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) playHomeVideos();
    });
  })();
</script>
