@if(!empty($card))
  <a href="{{ $card['link'] }}" class="trends-card" aria-label="{{ $card['name'] }}">
    @if($card['video'])
      <video class="trends-card-media" src="{{ $card['src'] }}" poster="{{ $card['poster'] }}" autoplay muted loop playsinline preload="metadata"></video>
    @else
      <img class="trends-card-media" src="{{ $card['src'] }}" alt="{{ $card['name'] }}" loading="lazy">
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
