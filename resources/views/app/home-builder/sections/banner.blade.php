@php
  $image = $section->setting('image');
  $link = $section->setting('link') ?: '#';
  $alt = $section->setting('alt_text', $section->title_fa);
  $size = $section->setting('height', 'medium');
  $roundedClass = $section->layout === 'rounded' ? 'hb-rounded' : '';
@endphp
@if($image)
<a class="hb-banner hb-size-{{ $size }} {{ $roundedClass }}" href="{{ $link }}">
  <img src="{{ $image }}" alt="{{ $alt }}">
</a>
@endif
