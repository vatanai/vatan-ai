@if($product->watermark_enabled && $product->watermark_position !== 'none')
  @php
    $watermarkCorner = in_array($product->new_watermark_corner_precise, ['tl', 'tr', 'bl', 'br'], true)
        ? $product->new_watermark_corner_precise
        : 'tr';
    $watermarkPosition = $product->watermark_position === 'center'
        ? 'top:50%;left:50%;transform:translate(-50%,-50%);'
        : ($watermarkCorner[0] === 't' ? 'top:3%;' : 'bottom:3%;')
          . ($watermarkCorner[1] === 'r' ? 'right:3%;' : 'left:3%;');
    $watermarkOpacity = max(0, min(100, (int) $product->new_watermark_opacity)) / 100;
    $watermarkSize = max(10, min(100, (int) $product->new_watermark_size));
  @endphp
  <span class="product-watermark pointer-events-none absolute z-[5] inline-flex items-center justify-center"
        style="{{ $watermarkPosition }}opacity:{{ $watermarkOpacity }};width:{{ $watermarkSize }}%;">
    @if($product->new_watermark_type === 'text')
      <strong style="color:{{ $product->new_watermark_text_color ?: '#FFFFFF' }};font-size:clamp(11px,2vw,28px);text-shadow:0 1px 5px rgba(0,0,0,.45);">VATAN AI</strong>
    @else
      <img src="{{ asset('assets/img/vatan-logo.svg') }}" alt="Vatan AI" class="w-full h-auto object-contain">
    @endif
  </span>
@endif
