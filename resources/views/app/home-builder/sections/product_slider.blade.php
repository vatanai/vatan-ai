@if(($products ?? collect())->isNotEmpty())
@include('app.home-builder.partials.section-header')

@if($section->layout === 'peek')
  @include('app.home-builder.sections.layouts.product-slider-peek')
@elseif($section->layout === 'intro')
  @include('app.home-builder.sections.layouts.product-slider-intro')
@elseif($section->layout === 'intro_dual')
  @include('app.home-builder.sections.layouts.product-slider-intro-dual')
@elseif($section->layout === 'large')
  @include('app.home-builder.sections.layouts.product-slider-large')
@elseif($section->layout === 'glass')
  @include('app.home-builder.sections.layouts.product-slider-glass')
@elseif($section->layout === 'neon')
  @include('app.home-builder.sections.layouts.product-slider-neon')
@elseif($section->layout === 'cinema')
  @include('app.home-builder.sections.layouts.product-slider-cinema')
@elseif($section->layout === 'minimal')
  @include('app.home-builder.sections.layouts.product-slider-minimal')
@elseif(str_starts_with($section->layout, 'motion_'))
  @includeIf('app.home-builder.sections.layouts.product-slider-' . str_replace('_', '-', $section->layout))
@elseif($section->layout === 'video_loop')
  @include('app.home-builder.sections.layouts.product-slider-video-loop')
@elseif($section->layout === 'video_spotlight')
  @include('app.home-builder.sections.layouts.product-slider-video-spotlight')
@elseif(str_starts_with($section->layout, 'scroll_'))
  @includeIf('app.home-builder.sections.layouts.product-slider-' . str_replace('_', '-', $section->layout))
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
