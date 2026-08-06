@php
  $mode = $section->setting('spacing_mode', 'standard');
  $isManual = $mode === 'manual';
  $desktopHeight = max(0, min(300, (int) $section->setting('desktop_height', 31)));
  $tabletHeight = max(0, min(250, (int) $section->setting('tablet_height', 31)));
  $mobileHeight = max(0, min(200, (int) $section->setting('mobile_height', 31)));
@endphp
<div class="hb-spacer {{ $isManual ? 'hb-h-manual' : 'hb-h-standard' }}"
     @if($isManual) style="--hb-space-desktop:{{ $desktopHeight }}px;--hb-space-tablet:{{ $tabletHeight }}px;--hb-space-mobile:{{ $mobileHeight }}px" @endif></div>
