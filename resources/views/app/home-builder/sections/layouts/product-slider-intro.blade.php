@php
  $hbRatio = in_array($section->setting('card_aspect_ratio', '4:5'), ['1:1','4:5','3:4','9:16','2:3'], true)
      ? str_replace(':', '/', $section->setting('card_aspect_ratio', '4:5')) : '4/5';
  $hbScrollMode = $section->setting('intro_scroll_mode', 'together') === 'fixed' ? 'is-fixed' : 'is-together';
@endphp
<div class="hb-intro-row {{ $hbScrollMode }}">
  @include('app.home-builder.sections.partials.intro-card')
  <div class="hb-intro-products">
    @foreach($products as $product)
      <a class="hb-wide-item" href="{{ route('app.product', $product->route_slug) }}">
        <div class="hb-wide-card" style="aspect-ratio:{{ $hbRatio }};background-image:url('{{ $product->displayImageUrl() }}')">
          @if(in_array($product->media_type, ['video', 'both']))<i class="fa-solid fa-circle-play hb-wide-play"></i>@endif
        </div>
        @if($section->setting('show_credit', true) || $section->setting('show_title', true) || $section->setting('show_category', true))
          <div class="hb-wide-meta">
            @if($section->setting('show_credit', true))<div class="hb-card-cost"><i class="fa-solid fa-bolt"></i>{{ number_format((int) $product->credit_cost) }} کردیت</div>@endif
            @if($section->setting('show_title', true))<p class="hb-card-title">{{ $product->name_fa }}</p>@endif
            @if($section->setting('show_category', true))<p class="hb-card-desc">{{ $product->subcategory ?: $product->category }}</p>@endif
          </div>
        @endif
      </a>
    @endforeach
  </div>
</div>
