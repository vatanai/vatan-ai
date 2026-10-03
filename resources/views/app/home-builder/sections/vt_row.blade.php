{{-- ویترین · ردیف محصول — اسکرولی یا حرکت پیوسته، با کارت استاندارد ویترین --}}
@if(($products ?? collect())->isNotEmpty())
<div class="vt-sec">
  @include('app.home-builder.sections.partials.vt-head')
  @if($section->layout === 'marquee' && $products->count() >= 4)
    <div class="vt-marquee" style="--vt-marquee-duration: {{ max(30, $products->count() * 7) }}s">
      <div class="vt-marquee-track">
        @foreach($products as $product)
          @include('app.home-builder.sections.partials.vt-card', ['product' => $product])
        @endforeach
        <span class="vt-marquee-clone" aria-hidden="true">
          @foreach($products as $product)
            @include('app.home-builder.sections.partials.vt-card', ['product' => $product])
          @endforeach
        </span>
      </div>
    </div>
  @else
    <div class="vt-row" data-vt-row>
      @foreach($products as $product)
        @include('app.home-builder.sections.partials.vt-card', ['product' => $product])
      @endforeach
    </div>
  @endif
</div>
@endif
