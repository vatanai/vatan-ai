{{-- کارت «پک» محصول پروداکتی: کولاژ تا ۴ شات نمونه. --}}
@php $urls = array_slice((array) ($pack['urls'] ?? []), 0, 4); @endphp
<a class="product-card pack-card" href="{{ route('app.create', ['product' => $product->route_slug]) }}">
  <div class="product-image pack-{{ max(1, count($urls)) }}">
    @foreach($urls as $url)<img src="{{ $url }}" alt="{{ $loop->first ? $product->name_fa : '' }}" loading="lazy" decoding="async">@endforeach
    <span class="product-type">پک {{ \App\Support\Jalali::toPersianDigits((string) ($pack['shots'] ?? count($urls))) }} شات</span>
  </div>
  <div class="product-info"><h2>{{ $product->name_fa }}</h2><p>{{ \Illuminate\Support\Str::limit($product->description_fa ?: 'یک عکس محصول بده، پک عکس تبلیغاتی بگیر.', 78) }}</p>
    <div class="product-meta"><span><i class="fa-solid fa-bolt"></i> از {{ \App\Support\Jalali::toPersianDigits(number_format((int) $product->credit_cost)) }} کردیت هر شات</span><span>بساز <i class="fa-solid fa-arrow-left"></i></span></div>
  </div>
</a>
