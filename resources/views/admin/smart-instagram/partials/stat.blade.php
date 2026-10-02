{{-- کارت آمار سریع: $icon, $tone, $value, $label, $hint?, $hintTone?, $href?, $tip? --}}
@php($siTag = !empty($href) ? 'a' : 'div')
<{{ $siTag }} @if(!empty($href)) href="{{ $href }}" @endif class="stat-card {{ !empty($tip) ? 'pro-tooltip-wrap' : '' }}">
  <div class="stat-card-icon" style="background:var(--{{ $tone }}-l);color:var(--{{ $tone }});"><i class="fa-solid {{ $icon }}"></i></div>
  <div style="min-width:0">
    <div class="stat-card-value si-num">{{ $value }}</div>
    <div class="stat-card-label">{{ $label }}</div>
    @if(!empty($hint))<div class="si-stat-hint {{ !empty($hintTone) ? 'is-'.$hintTone : '' }}">{{ $hint }}</div>@endif
  </div>
  @if(!empty($tip))<div class="pro-tooltip">{{ $tip }}</div>@endif
</{{ $siTag }}>
