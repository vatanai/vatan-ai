<div class="hb-scroll-stack">
  @foreach($products as $stackIndex => $product)
    <a class="hb-stack-card" href="{{ route('app.product', $product->route_slug) }}" style="--hb-stack-angle:{{ ($stackIndex - 2) * 1.4 }}deg;background-image:url('{{ $product->displayImageUrl() }}')"><span>{{ $product->name_fa }}</span></a>
  @endforeach
</div>
