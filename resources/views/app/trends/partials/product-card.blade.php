@if(!empty($card))
  @php
    $cardIndex = (int) ($cardIndex ?? 0);
    $eagerMedia = $cardIndex < 4;
    $highPriorityMedia = $cardIndex < 2;
    $mediaUrl = $card['video'] ? $card['poster'] : $card['src'];
  @endphp
  <a href="{{ $card['link'] }}" class="trends-card{{ $cardIndex >= 4 ? ' trends-card--deferred' : '' }}" aria-label="{{ $card['name'] }}" @if($card['video']) data-trends-video-card @endif>
    @if($card['video'])
      <img
        class="trends-card-media trends-card-poster"
        @if($eagerMedia) src="{{ $mediaUrl }}" @else data-src="{{ $mediaUrl }}" @endif
        alt="{{ $card['name'] }}"
        loading="{{ $eagerMedia ? 'eager' : 'lazy' }}"
        decoding="async"
        fetchpriority="{{ $highPriorityMedia ? 'high' : 'auto' }}"
      >
      <video class="trends-card-media trends-card-video" data-src="{{ $card['src'] }}" muted loop playsinline preload="none" aria-hidden="true"></video>
    @else
      <img
        class="trends-card-media"
        @if($eagerMedia) src="{{ $mediaUrl }}" @else data-src="{{ $mediaUrl }}" @endif
        alt="{{ $card['name'] }}"
        loading="{{ $eagerMedia ? 'eager' : 'lazy' }}"
        decoding="async"
        fetchpriority="{{ $highPriorityMedia ? 'high' : 'auto' }}"
      >
    @endif
    <span class="trends-card-overlay"></span>
    <span class="trends-download-badge"><i class="fa-solid fa-download"></i> {{ number_format((int) $card['downloads']) }} دانلود</span>
    <span class="trends-card-type"><i class="fa-solid {{ $card['video'] ? 'fa-video' : 'fa-image' }}"></i></span>
    <span class="trends-card-info">
      <strong>{{ $card['name'] }}</strong>
      <small>{{ $card['tag'] }}</small>
    </span>
  </a>
@endif
