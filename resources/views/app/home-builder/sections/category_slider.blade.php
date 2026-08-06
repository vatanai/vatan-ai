@if(($categories ?? collect())->isNotEmpty())
@include('app.home-builder.partials.section-header', [
  'viewAllUrl' => $section->layout === 'tabs' ? null : ($viewAllUrl ?? null),
])

@if($section->layout === 'tabs')
  @include('app.home-builder.sections.layouts.category-slider-tabs', ['productsByCategory' => $productsByCategory ?? collect()])
@else
  <div class="home-cards-scroll">
    @foreach($categories as $category)
      <a class="hb-cat-card" href="{{ $category->url() }}">
        <div class="hb-cat-card-icon">
          @if($category->image)
            <img src="{{ asset('storage/' . $category->image) }}" alt="{{ $category->name_fa }}">
          @else
            <i class="fa-solid {{ $category->icon ?: 'fa-layer-group' }}"></i>
          @endif
        </div>
        <div class="hb-cat-card-name">{{ $category->name_fa }}</div>
      </a>
    @endforeach
  </div>
@endif
@endif
