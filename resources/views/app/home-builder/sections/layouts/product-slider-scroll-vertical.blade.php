<div class="hb-scroll-vertical">
  @foreach($products as $product)
    <a class="hb-scroll-vertical-card" href="{{ route('app.product', $product->route_slug) }}">
      <span style="background-image:url('{{ $product->displayImageUrl() }}')"></span>
      <div><b>{{ $product->name_fa }}</b><small>{{ $product->subcategory ?: $product->category }}</small></div>
      <i class="fa-solid fa-arrow-up-left"></i>
    </a>
  @endforeach
</div>
