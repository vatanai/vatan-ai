@if(($products ?? collect())->isNotEmpty())
@include('app.home-builder.partials.section-header')

<div class="home-cards-scroll">
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
