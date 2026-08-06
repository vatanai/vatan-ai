@php $hbScrollMode = $section->setting('intro_scroll_mode', 'together') === 'fixed' ? 'is-fixed' : 'is-together'; @endphp
<div class="hb-intro-row hb-intro-row--dual {{ $hbScrollMode }}">
  @include('app.home-builder.sections.partials.intro-card')
  <div class="hb-intro-dual-products">
    @foreach($products as $product)
      <a class="hb-intro-dual-card" href="{{ route('app.product', $product->route_slug) }}" style="background-image:url('{{ $product->displayImageUrl() }}')">
        <div class="hb-neon-overlay"></div>
        @if($section->setting('show_credit', true) || $section->setting('show_title', true) || $section->setting('show_category', true))
          <div class="hb-neon-info">
            @if($section->setting('show_credit', true))<span class="hb-intro-dual-credit"><i class="fa-solid fa-bolt"></i>{{ number_format((int) $product->credit_cost) }} کردیت</span>@endif
            @if($section->setting('show_title', true))<p class="hb-neon-name">{{ $product->name_fa }}</p>@endif
            @if($section->setting('show_category', true))<p class="hb-neon-tag">{{ $product->subcategory ?: $product->category }}</p>@endif
          </div>
        @endif
      </a>
    @endforeach
  </div>
</div>
