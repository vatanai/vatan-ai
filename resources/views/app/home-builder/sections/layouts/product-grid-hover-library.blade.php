@php
  $hoverLibrary = [
    'neon_glow' => 'درخشش نئونی', 'grayscale_color' => 'سیاه‌وسفید به رنگی', 'zoom_soft' => 'زوم نرم',
    'lift_shadow' => 'شناور با سایه', 'overlay_reveal' => 'نمایش اطلاعات', 'tilt' => 'چرخش سه‌بعدی',
    'shine' => 'عبور نور', 'blur_focus' => 'فوکوس از محو', 'border_draw' => 'ترسیم قاب',
    'pulse' => 'ضربان نرم', 'slide_caption' => 'ورود توضیحات', 'darken' => 'تاریک سینمایی',
    'saturate' => 'تقویت رنگ', 'rotate_soft' => 'چرخش نرم', 'token_bounce' => 'حرکت آیکون توکن',
  ];
  $hoverCols = in_array((string) $section->setting('hover_grid_cols', '4'), ['2','3','4','5'], true) ? (string) $section->setting('hover_grid_cols', '4') : '4';
  $hoverRows = max(1, min(8, (int) $section->setting('hover_grid_rows', 4)));
@endphp
<div class="hb-hover-library hb-hover-cols-{{ $hoverCols }}">
  @foreach(collect($hoverLibrary)->take((int) $hoverCols * $hoverRows) as $hoverCode => $hoverName)
    @php($product = $products[$loop->index % max(1, $products->count())])
    <a class="hb-library-card hb-hover-effect--{{ $hoverCode }}" href="{{ route('app.product', $product->route_slug) }}">
      <div class="hb-library-media hb-effect-target" style="background-image:url('{{ $product->displayImageUrl() }}')">
        <span class="hb-effect-shine"></span><i class="fa-solid fa-bolt hb-effect-token"></i>
        <div class="hb-library-caption"><b>{{ $hoverName }}</b><small>{{ $hoverCode }}</small></div>
      </div>
    </a>
  @endforeach
</div>
