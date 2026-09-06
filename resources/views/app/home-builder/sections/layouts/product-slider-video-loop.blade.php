<div class="hb-video-loop-row {{ (string) $section->setting('display_rows', '1') === '2' ? 'hb-rows-2' : '' }}">
  @foreach($products as $product)
    @php
      $videoUrl = trim((string) $product->preview_video_url);
      if ($videoUrl !== '' && !str_starts_with($videoUrl, 'http') && !str_starts_with($videoUrl, '/')) $videoUrl = asset('storage/' . ltrim($videoUrl, '/'));
    @endphp
    <a class="hb-video-loop-card" href="{{ route('app.product', $product->route_slug) }}">
      @if($videoUrl)
        <video src="{{ $videoUrl }}" poster="{{ $product->displayImageUrl() }}" autoplay muted loop playsinline preload="metadata"></video>
      @else
        <span class="hb-video-loop-fallback" style="background-image:url('{{ $product->displayImageUrl() }}')"></span>
      @endif
      <span class="hb-video-live"><i></i> پخش زنده</span>
      <div class="hb-video-loop-info"><b>{{ $product->name_fa }}</b><small>{{ $product->subcategory ?: $product->category }}</small></div>
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
