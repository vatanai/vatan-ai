<div class="hb-scroll-wheel">
  @foreach($products as $wheelIndex => $product)
    <a class="hb-wheel-card" href="{{ route('app.product', $product->route_slug) }}" style="--hb-wheel-angle:{{ ($wheelIndex - 2) * 5 }}deg;--hb-wheel-drop:{{ abs($wheelIndex - 2) * 3 }}px;background-image:url('{{ $product->displayImageUrl() }}')"><span>{{ $product->name_fa }}</span></a>
  @endforeach
</div>
