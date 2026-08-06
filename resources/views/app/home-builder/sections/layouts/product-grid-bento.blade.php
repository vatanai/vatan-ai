{{--
  Layout «بنتو آینه‌ای» — دو ردیف قرینه:
  ردیف اول یک کارت بزرگ سمت راست و دو کارت سمت چپ؛ ردیف دوم دقیقاً برعکس.
  از $products (Collection محصولات) استفاده می‌کند.
--}}
<div class="hb-bento">
  @foreach($products->take(6) as $hbBentoIndex => $product)
    <a class="hb-bento-item hb-bento-item--{{ $hbBentoIndex + 1 }}"
       href="{{ route('app.product', $product->route_slug) }}"
       style="background-image: url('{{ $product->displayImageUrl() }}');">
      <span class="hb-bento-badge">{{ $product->subcategory ?: $product->category }}</span>
      <div class="hb-bento-overlay"></div>
    </a>
  @endforeach
</div>
