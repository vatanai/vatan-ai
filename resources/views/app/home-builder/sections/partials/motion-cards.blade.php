<div class="hb-motion-row hb-motion-row--{{ $motionVariant }}">
  @foreach($products as $motionIndex => $product)
    <a class="hb-motion-card" href="{{ route('app.product', $product->route_slug) }}" style="--hb-motion-index:{{ $motionIndex }}">
      <div class="hb-motion-media" style="background-image:url('{{ $product->displayImageUrl() }}')">
        <span class="hb-motion-orbit"><i class="fa-solid fa-sparkles"></i></span>
        <span class="hb-motion-shine"></span>
      </div>
      <div class="hb-motion-meta">
        <span>{{ $product->name_fa }}</span>
        @if($section->setting('show_credit', true))<b><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }}</b>@endif
      </div>
    </a>
  @endforeach
</div>
