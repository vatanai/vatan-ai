{{-- مدل اختصاصی وطن: آیتم اول بزرگ و بقیه کارت‌ها در ستون ادیتوریال. --}}
<div class="hb-editorial-grid">
  @foreach($products as $editorialIndex => $product)
    <a class="hb-editorial-card {{ $editorialIndex === 0 ? 'is-featured' : '' }}"
       href="{{ route('app.product', $product->route_slug) }}"
       style="background-image:url('{{ $product->displayImageUrl() }}')">
      <div class="hb-editorial-shade"></div>
      <span class="hb-editorial-kicker">{{ $editorialIndex === 0 ? 'انتخاب ویژه' : str_pad($editorialIndex + 1, 2, '0', STR_PAD_LEFT) }}</span>
      <div class="hb-editorial-copy">
        <p>{{ $product->name_fa }}</p>
        <span>{{ $product->subcategory ?: $product->category }}</span>
      </div>
    </a>
  @endforeach
</div>
