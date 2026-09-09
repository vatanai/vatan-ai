{{--
  Layout «قاب نئونی» (Neon) — استایل نمونه‌ی اختصاصی.
  کارت تصویر با قاب لیمویی درخشان دائمی که هاور شدت می‌گیرد + نشان کردیت گوشه‌ی بالا.
--}}
@php $hbShowCredit = (bool) $section->setting('show_credit', true); @endphp
<div class="hb-neon-row">
  @foreach($products as $product)
    <a class="hb-neon-card" href="{{ route('app.product', $product->route_slug) }}" data-hb-background style="--hb-card-image:url('{{ $product->displayImageUrl() }}');background-image:var(--hb-card-image);">
      @if($hbShowCredit)
        <span class="hb-neon-credit"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }}</span>
      @endif
      <div class="hb-neon-overlay"></div>
      <div class="hb-neon-info">
        <p class="hb-neon-name">{{ $product->name_fa }}</p>
        <p class="hb-neon-tag">{{ $product->subcategory ?: $product->category }}</p>
      </div>
    </a>
  @endforeach
</div>
