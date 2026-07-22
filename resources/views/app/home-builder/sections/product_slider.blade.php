@if(($products ?? collect())->isNotEmpty())
<div class="home-section-title home-section-title--sub">
  <div>
    <span class="home-section-title-right">{{ $section->title_fa }}</span>
    @if($section->subtitle_fa)<p class="home-section-title-caption">{{ $section->subtitle_fa }}</p>@endif
  </div>
  @if(!in_array($section->layout, ['intro']) && $section->setting('show_view_all', true))
    <button type="button" class="home-section-viewall">مشاهده همه</button>
  @endif
</div>

@if($section->layout === 'peek')
  @include('app.home-builder.sections.layouts.product-slider-peek')
@elseif($section->layout === 'intro')
  @include('app.home-builder.sections.layouts.product-slider-intro')
@elseif($section->layout === 'large')
  @include('app.home-builder.sections.layouts.product-slider-large')
@elseif($section->layout === 'glass')
  @include('app.home-builder.sections.layouts.product-slider-glass')
@elseif($section->layout === 'neon')
  @include('app.home-builder.sections.layouts.product-slider-neon')
@else
  @php
    $cardClass = $section->layout === 'compact' ? 'home-card--compact' : '';
    $isGridMode = (string) $section->setting('display_mode', 'scroll') === 'grid';
    $gridCols = in_array((string) $section->setting('grid_cols', '3'), ['2', '3', '4']) ? (string) $section->setting('grid_cols', '3') : '3';
    $showCredit = (bool) $section->setting('show_credit', true);
  @endphp
  <div class="{{ $isGridMode ? 'hb-grid hb-cols-' . $gridCols : 'home-cards-scroll' }}">
    @foreach($products as $product)
      <a class="home-card {{ $cardClass }}" href="{{ route('app.product', $product->route_slug) }}" style="background-image: url('{{ $product->displayImageUrl() }}');">
        <div class="home-card-overlay"></div>
        <i class="fa-solid {{ $product->media_type === 'video' ? 'fa-video' : 'fa-image' }} home-card-badge-type"></i>
        @if($product->is_featured)
          <i class="fa-solid fa-crown home-card-badge-tier"></i>
        @elseif($product->is_new)
          <i class="fa-solid fa-bolt home-card-badge-tier"></i>
        @endif
        <div class="home-card-info">
          <p class="home-card-name">{{ $product->name_fa }}</p>
          <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
          @if($showCredit)
            <p class="home-card-credit"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</p>
          @endif
        </div>
      </a>
    @endforeach
  </div>
@endif
@endif
