@if(($products ?? collect())->isNotEmpty())
@include('app.home-builder.partials.section-header')

@if($section->layout === 'bento')
  @include('app.home-builder.sections.layouts.product-grid-bento')
@elseif($section->layout === 'editorial')
  @include('app.home-builder.sections.layouts.product-grid-editorial')
@elseif($section->layout === 'family_duo')
  @include('app.home-builder.sections.layouts.product-grid-family-duo')
@elseif($section->layout === 'hover_showcase')
  @include('app.home-builder.sections.layouts.product-grid-hover-showcase')
@elseif($section->layout === 'hover_library')
  @include('app.home-builder.sections.layouts.product-grid-hover-library')
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
