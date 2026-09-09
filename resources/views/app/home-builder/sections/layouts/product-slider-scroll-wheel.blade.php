<div class="hb-scroll-wheel">
  @foreach($products as $wheelIndex => $product)
    <a class="hb-wheel-card" href="{{ route('app.product', $product->route_slug) }}" data-hb-background style="--hb-wheel-angle:{{ ($wheelIndex - 2) * 5 }}deg;--hb-wheel-drop:{{ abs($wheelIndex - 2) * 3 }}px;--hb-card-image:url('{{ $product->displayImageUrl() }}');background-image:var(--hb-card-image)"><span>{{ $product->name_fa }}</span></a>
  @endforeach
</div>
