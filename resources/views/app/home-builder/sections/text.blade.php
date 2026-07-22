@php
  $heading = $section->setting('heading', $section->title_fa);
  $body = $section->setting('body', $section->subtitle_fa);
  $align = $section->setting('align', 'right');
@endphp
@if($heading || $body)
<div class="hb-text-block hb-align-{{ $align }}">
  @if($heading)<h3 class="hb-text-heading">{{ $heading }}</h3>@endif
  @if($body)<p class="hb-text-body">{{ $body }}</p>@endif
</div>
@endif
