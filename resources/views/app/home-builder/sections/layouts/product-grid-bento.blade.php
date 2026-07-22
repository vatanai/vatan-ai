{{--
  Layout «بنتو» (Bento Grid) — نمونه‌برداری‌شده از شات Trending Creations (pixifield.com).
  گرید نامتقارن با اندازه‌های مختلف کارت، برچسب دسته‌بندی گوشه‌ی بالا، هاور = زوم + کادر سبز/لیمویی.
  از $products (Collection محصولات) استفاده می‌کند.
--}}
@php $hbBentoSpans = ['hb-span-2x2', 'hb-span-1x2', 'hb-span-1x2', 'hb-span-2x1']; @endphp
<div class="hb-bento">
  @foreach($products as $hbBentoIndex => $product)
    <a class="hb-bento-item {{ $hbBentoSpans[$hbBentoIndex % count($hbBentoSpans)] }}"
       href="{{ route('app.product', $product->route_slug) }}"
       style="background-image: url('{{ $product->displayImageUrl() }}');">
      <span class="hb-bento-badge">{{ $product->subcategory ?: $product->category }}</span>
      <div class="hb-bento-overlay"></div>
    </a>
  @endforeach
</div>
