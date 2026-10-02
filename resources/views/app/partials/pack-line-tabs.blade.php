{{-- «استودیو محصول»: تب جدای کسب‌وکار در کاتالوگ (فقط وقتی قابلیت باز است). --}}
@php $isBusinessLine = request('line') === 'business'; @endphp
<nav class="pack-line-tabs" aria-label="نوع محصولات">
  <a href="{{ request()->fullUrlWithQuery(['line' => null, 'page' => null]) }}" class="{{ $isBusinessLine ? '' : 'is-active' }}" @unless($isBusinessLine) aria-current="page" @endunless>همه‌ی محصولات</a>
  <a href="{{ request()->fullUrlWithQuery(['line' => 'business', 'page' => null]) }}" class="{{ $isBusinessLine ? 'is-active' : '' }}" @if($isBusinessLine) aria-current="page" @endif><i class="fa-solid fa-store" aria-hidden="true"></i> کسب‌وکار · پک شات</a>
</nav>
<style>
.pack-line-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:0 0 14px}
.pack-line-tabs a{display:inline-flex;gap:6px;align-items:center;padding:8px 14px;border-radius:99px;border:1px solid var(--vt-border);background:var(--vt-surface);color:var(--vt-text-2);text-decoration:none;font-size:12px;font-weight:800}
.pack-line-tabs a.is-active{background:var(--vt-brand);border-color:var(--vt-brand);color:var(--vt-on-brand)}
.pack-card .product-image{display:grid;grid-template-columns:2fr 1fr;grid-template-rows:1fr 1fr;gap:2px}
.pack-card .product-image img:first-child{grid-row:1/3}
.pack-card .product-image.pack-1{display:block}
.pack-card .product-image.pack-2{grid-template-columns:1fr 1fr;grid-template-rows:1fr}
.pack-card .product-image.pack-2 img:first-child{grid-row:auto}
.pack-card .product-image.pack-3 img:nth-child(3){grid-column:2}
.pack-card .product-image.pack-4{grid-template-columns:2fr 1fr 1fr}
.pack-card .product-image.pack-4 img:nth-child(4){grid-column:2/4}
.pack-card .product-type{background:var(--vt-brand);color:var(--vt-on-brand);border-color:var(--vt-brand)}
</style>
