<div class="hb-marquee"><div class="hb-marquee-track">
  @foreach($products->concat($products) as $product)
    <a class="hb-marquee-card" href="{{ route('app.product', $product->route_slug) }}" data-hb-background style="--hb-card-image:url('{{ $product->displayImageUrl() }}');background-image:var(--hb-card-image)"><span>{{ $product->name_fa }}</span></a>
  @endforeach
</div></div>
