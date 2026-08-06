@php
  $hoverModels = [
    ['code' => 'hover_zoom', 'name' => 'زوم نرم'],
    ['code' => 'hover_frame', 'name' => 'قاب لیمویی'],
    ['code' => 'hover_reveal', 'name' => 'نمایش اطلاعات'],
    ['code' => 'hover_lift', 'name' => 'شناور و سایه'],
  ];
  $hoverCols = in_array((string) $section->setting('hover_grid_cols', '4'), ['2','3','4','5'], true) ? (string) $section->setting('hover_grid_cols', '4') : '4';
  $hoverRows = max(1, min(8, (int) $section->setting('hover_grid_rows', 4)));
  $visibleProducts = $products->take((int) $hoverCols * $hoverRows);
@endphp
<div class="hb-hover-showcase hb-hover-cols-{{ $hoverCols }}">
  @foreach($visibleProducts as $hoverIndex => $product)
    @php($hoverModel = $hoverModels[$hoverIndex % count($hoverModels)])
    <a class="hb-hover-card {{ $hoverModel['code'] }}" href="{{ route('app.product', $product->route_slug) }}">
      <div class="hb-hover-media" style="background-image:url('{{ $product->displayImageUrl() }}')">
        <span class="hb-hover-model-name">{{ $hoverModel['name'] }}</span>
        <div class="hb-hover-shade"></div>
        <div class="hb-hover-info">
          <p>{{ $product->name_fa }}</p>
          <span>{{ $product->subcategory ?: $product->category }}</span>
        </div>
      </div>
      <small>{{ $hoverModel['code'] }}</small>
    </a>
  @endforeach
</div>
