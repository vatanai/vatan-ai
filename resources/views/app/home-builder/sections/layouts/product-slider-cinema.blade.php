{{-- مدل اختصاصی وطن: قاب‌های عریض سینمایی با شماره و اطلاعات روی تصویر. --}}
<div class="hb-cinema-row">
  @foreach($products as $cinemaIndex => $product)
    <a class="hb-cinema-card" href="{{ route('app.product', $product->route_slug) }}">
      <div class="hb-cinema-media" style="background-image:url('{{ $product->displayImageUrl() }}')">
        <div class="hb-cinema-shade"></div>
        <span class="hb-cinema-index">{{ str_pad($cinemaIndex + 1, 2, '0', STR_PAD_LEFT) }}</span>
        <div class="hb-cinema-copy">
          <p>{{ $product->name_fa }}</p>
          <span>{{ $product->subcategory ?: $product->category }}</span>
        </div>
        @if($section->setting('show_credit', true))
          <span class="hb-cinema-credit"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }}</span>
        @endif
      </div>
    </a>
  @endforeach
</div>
