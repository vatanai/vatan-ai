{{-- ویترین · اکسپلور موزاییکی — نسبت واقعی تصاویر حفظ می‌شود --}}
@if(($products ?? collect())->isNotEmpty())
@php
  $allLink = trim((string) $section->setting('all_button_link', '/app/products')) ?: '/app/products';
  $allLink = str_starts_with($allLink, '/') ? url($allLink) : $allLink;
  $allLabel = str_replace(':count', number_format((int) ($totalProducts ?? 0)), (string) ($section->setting('all_button_label') ?: 'مشاهده همه :count محصول'));
@endphp
<div class="vt-sec">
  @include('app.home-builder.sections.partials.vt-head')
  <div class="vt-mas">
    @foreach($products as $product)
      <a class="vt-mi" href="{{ route('app.product', $product->route_slug) }}">
        <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="lazy" decoding="async">
        <span class="vt-mi-cap">{{ $product->name_fa }}</span>
      </a>
    @endforeach
  </div>
  <div class="vt-all"><a class="vt-btn vt-btn--ghost" href="{{ $allLink }}">{{ $allLabel }}</a></div>
</div>
@endif
