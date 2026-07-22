{{--
  Layout «لبه‌نما» (Peek Scroll) — نمونه‌برداری‌شده از شات Photoshoot (pixifield.com).
  اسلایدر افقی با کارت‌های نیمه‌نمایان، هاور/فعال = کادر و تیتر لیمویی + دکمه دایره‌ای «رفتن».
  از $products (Collection محصولات) و $section (برای شناسه یکتای اسکرول) استفاده می‌کند.
--}}
@php $peekId = 'hb-peek-' . $section->id; @endphp
<div class="hb-peek-wrap">
  <button type="button" class="hb-peek-nav hb-prev" onclick="document.getElementById('{{ $peekId }}').scrollBy({left: 220, behavior: 'smooth'})" aria-label="آیتم قبلی">
    <i class="fa-solid fa-chevron-right"></i>
  </button>
  <button type="button" class="hb-peek-nav hb-next" onclick="document.getElementById('{{ $peekId }}').scrollBy({left: -220, behavior: 'smooth'})" aria-label="آیتم بعدی">
    <i class="fa-solid fa-chevron-left"></i>
  </button>

  <div class="hb-peek-scroll" id="{{ $peekId }}">
    @foreach($products as $product)
      <a class="hb-peek-item" href="{{ route('app.product', $product->route_slug) }}">
        <div class="hb-peek-card" style="background-image: url('{{ $product->displayImageUrl() }}');">
          <div class="hb-peek-overlay"></div>
          <span class="hb-peek-go"><i class="fa-solid fa-arrow-left"></i></span>
        </div>
        <div class="hb-peek-meta">
          @if($section->setting('show_credit', true))
            <div class="hb-card-cost"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</div>
          @endif
          <p class="hb-card-title">{{ $product->name_fa }}</p>
          <p class="hb-card-desc">{{ $product->subcategory ?: $product->category }}</p>
        </div>
      </a>
    @endforeach
  </div>
</div>
