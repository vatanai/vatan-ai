@if(($products ?? collect())->isNotEmpty())
<div class="home-section-title home-section-title--sub">
  <div>
    <span class="home-section-title-right">{{ $section->title_fa }}</span>
    @if($section->subtitle_fa)<p class="home-section-title-caption">{{ $section->subtitle_fa }}</p>@endif
  </div>
</div>

@if($section->layout === 'bento')
  @include('app.home-builder.sections.layouts.product-grid-bento')
@else
  @php $cols = match($section->layout) { 'two_col' => 2, 'four_col' => 4, default => 3 }; @endphp
  <div class="hb-grid hb-cols-{{ $cols }}">
    @foreach($products as $product)
      <a class="home-card" href="{{ route('app.product', $product->route_slug) }}" style="background-image: url('{{ $product->displayImageUrl() }}');">
        <div class="home-card-overlay"></div>
        <div class="home-card-info">
          <p class="home-card-name">{{ $product->name_fa }}</p>
          <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
          @if($section->setting('show_credit', true))
            <p class="home-card-credit"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</p>
          @endif
        </div>
      </a>
    @endforeach
  </div>
@endif
@endif
