<div class="hb-scroll-stack">
  @foreach($products as $stackIndex => $product)
    <a class="hb-stack-card" href="{{ route('app.product', $product->route_slug) }}" data-hb-background style="--hb-stack-angle:{{ ($stackIndex - 2) * 1.4 }}deg;--hb-card-image:url('{{ $product->displayImageUrl() }}');background-image:var(--hb-card-image)"><span>{{ $product->name_fa }}</span></a>
  @endforeach
</div>
