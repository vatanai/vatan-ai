{{-- مدل اختصاصی وطن: کارت مینیمال با تصویر تمیز و اطلاعات خارج تصویر. --}}
<div class="hb-minimal-row">
  @foreach($products as $product)
    <a class="hb-minimal-card" href="{{ route('app.product', $product->route_slug) }}">
      <div class="hb-minimal-media">
        <span class="hb-minimal-image" data-hb-background style="--hb-card-image:url('{{ $product->displayImageUrl() }}');background-image:var(--hb-card-image)"></span>
        <span class="hb-minimal-arrow"><i class="fa-solid fa-arrow-left"></i></span>
      </div>
      <div class="hb-minimal-copy">
        <div class="hb-minimal-meta">
          <span>{{ $product->subcategory ?: $product->category }}</span>
          @if($section->setting('show_credit', true))
            <b><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }}</b>
          @endif
        </div>
        <p>{{ $product->name_fa }}</p>
      </div>
    </a>
  @endforeach
</div>
