{{-- ویترین · ویدیوهای عمودی ۹:۱۶ — پخش با هاور در دسکتاپ و با دیده‌شدن در موبایل --}}
@if(($products ?? collect())->isNotEmpty())
<div class="vt-sec">
  @include('app.home-builder.sections.partials.vt-head')
  <div class="vt-row vt-vrow" data-vt-row>
    @foreach($products as $product)
      @php $vurl = $product->previewVideoUrl(); @endphp
      <a class="vt-vcard" href="{{ route('app.product', $product->route_slug) }}">
        <span class="vt-badge vt-badge--vid"><i class="fa-solid fa-play"></i> ویدیو</span>
        <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="lazy" decoding="async">
        @if($vurl)<video muted loop playsinline preload="none" data-vt-video-src="{{ $vurl }}"></video>@endif
        <span class="vt-vplay" aria-hidden="true"><i class="fa-solid fa-play"></i></span>
        <span class="vt-vcap"><b>{{ $product->name_fa }}</b><small><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</small></span>
      </a>
    @endforeach
  </div>
</div>
@endif
