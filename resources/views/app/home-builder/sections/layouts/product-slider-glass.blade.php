{{--
  Layout «کارت شیشه‌ای» (Glass) — استایل نمونه‌ی اختصاصی.
  کارت با سطح نیمه‌شفاف و بلور پس‌زمینه؛ تصویر بالای کارت و متادیتا (کردیت/عنوان/توضیح) داخل بدنه.
--}}
@php $hbShowCredit = (bool) $section->setting('show_credit', true); @endphp
<div class="hb-glass-row">
  @foreach($products as $product)
    <a class="hb-glass-item" href="{{ route('app.product', $product->route_slug) }}">
      <div class="hb-glass-media" style="background-image: url('{{ $product->displayImageUrl() }}');"></div>
      <div class="hb-glass-body">
        @if($hbShowCredit)
          <div class="hb-card-cost"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</div>
        @endif
        <p class="hb-card-title">{{ $product->name_fa }}</p>
        <p class="hb-card-desc">{{ $product->subcategory ?: $product->category }}</p>
      </div>
    </a>
  @endforeach
</div>
