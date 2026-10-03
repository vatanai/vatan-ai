{{-- ویترین · بنر دعوت — متن و دو دکمه + کلاژ سه تصویر از محصولات انتخابی --}}
@php
  $vtLink = function ($link) {
    $link = trim((string) $link);
    if ($link === '') return null;
    return str_starts_with($link, '/') ? url($link) : (filter_var($link, FILTER_VALIDATE_URL) ? $link : null);
  };
  $cta1 = $vtLink($section->setting('cta_link'));
  $cta2 = $vtLink($section->setting('cta2_link'));
@endphp
@if($section->setting('heading'))
<div class="vt-sec">
  <div class="vt-cta-banner {{ ($products ?? collect())->isEmpty() ? 'is-text-only' : '' }}">
    <div class="vt-cta-text">
      @if($section->setting('kicker'))<small>{{ $section->setting('kicker') }}</small>@endif
      <h3>{{ $section->setting('heading') }}</h3>
      @if($section->setting('body'))<p>{{ $section->setting('body') }}</p>@endif
      <div class="vt-cta-actions">
        @if($cta1 && $section->setting('cta_label'))<a class="vt-btn" href="{{ $cta1 }}">{{ $section->setting('cta_label') }}</a>@endif
        @if($cta2 && $section->setting('cta2_label'))<a class="vt-btn vt-btn--ghost" href="{{ $cta2 }}">{{ $section->setting('cta2_label') }}</a>@endif
      </div>
    </div>
    @if(($products ?? collect())->isNotEmpty())
      <div class="vt-cta-collage vt-cta-collage--n{{ min(3, $products->count()) }}">
        @foreach($products->take(3) as $product)
          <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="lazy" decoding="async">
        @endforeach
      </div>
    @endif
  </div>
</div>
@endif
