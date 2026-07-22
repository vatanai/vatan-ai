@php
  $heading = $section->setting('heading', $section->title_fa);
  $sub = $section->setting('subheading', $section->subtitle_fa);
  $image = $section->setting('image');
  $ctaLabel = $section->setting('cta_label');
  $ctaLink = $section->setting('cta_link') ?: '#';
  $layoutClass = $section->effectiveLayoutFor('mobile') === 'centered' || $section->layout === 'centered' ? 'hb-hero--centered' : '';
@endphp
<div class="hb-hero {{ $layoutClass }}" @if($image) style="background-image:url('{{ $image }}');" @endif>
  <div class="hb-hero-inner">
    @if($heading)<h2 class="hb-hero-heading">{{ $heading }}</h2>@endif
    @if($sub)<p class="hb-hero-sub">{{ $sub }}</p>@endif
    @if($ctaLabel)<a href="{{ $ctaLink }}" class="hb-hero-cta">{{ $ctaLabel }}</a>@endif
  </div>
</div>
