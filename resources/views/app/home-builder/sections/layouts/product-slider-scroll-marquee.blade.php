<div class="hb-marquee"><div class="hb-marquee-track">
  @foreach($products->concat($products) as $product)
    <a class="hb-marquee-card" href="{{ route('app.product', $product->route_slug) }}" style="background-image:url('{{ $product->displayImageUrl() }}')"><span>{{ $product->name_fa }}</span></a>
  @endforeach
</div></div>
