{{-- ویترین · هیرو اسپات‌لایت — اسلاید اول بزرگ (ویدیو/تصویر)، بقیه کوچک؛ در موبایل اسلایدی --}}
@if(($products ?? collect())->isNotEmpty())
@php
  $ctaLabel = $section->setting('cta_label') ?: 'همین رو بساز';
  $first = $products->first();
  $videoUrl = trim((string) $section->setting('video_url')) ?: $first->previewVideoUrl();
@endphp
<div class="vt-sec vt-hero-wrap {{ $section->setting('placement', 'top') === 'top' ? 'vt-hero-wrap--top' : '' }}" data-vt-hero data-vt-autoplay="{{ filter_var($section->setting('autoplay', true), FILTER_VALIDATE_BOOLEAN) ? '1' : '0' }}">
  <div class="vt-hero vt-hero--n{{ min(3, $products->count()) }}">
    @foreach($products->take(5) as $i => $product)
      @php
        $isFirst = $i === 0;
        $heading = $isFirst && $section->setting('heading') ? $section->setting('heading') : $product->name_fa;
        $kicker = $isFirst && $section->setting('kicker') ? $section->setting('kicker') : ($product->subcategory ?: $product->category);
        $isVideo = in_array((string) $product->media_type, ['video', 'both'], true);
      @endphp
      <a class="vt-hcard {{ $isFirst ? 'is-main' : '' }}" href="{{ route('app.product', $product->route_slug) }}">
        <img class="vt-hcard-bg" src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" @if($i < 3) loading="eager" @if($isFirst) fetchpriority="high" @endif @else loading="lazy" @endif decoding="async">
        @if($isFirst && $videoUrl)
          <video class="vt-hcard-bg" src="{{ $videoUrl }}" poster="{{ $product->displayImageUrl() }}" autoplay muted loop playsinline preload="metadata"></video>
        @endif
        @if($product->is_trending)<span class="vt-badge vt-badge--hot">پرطرفدار</span>
        @elseif($product->is_new)<span class="vt-badge vt-badge--new">جدید</span>
        @elseif($isVideo)<span class="vt-badge vt-badge--vid">ویدیو</span>@endif
        <span class="vt-hcard-cap">
          @if($kicker)<small>{{ $kicker }}</small>@endif
          <strong>{{ $heading }}</strong>
          @if($isFirst && $section->setting('subheading'))<span class="vt-hcard-sub">{{ $section->setting('subheading') }}</span>@endif
          <span class="vt-cta">{{ $ctaLabel }} <i class="fa-solid fa-arrow-left"></i></span>
        </span>
      </a>
    @endforeach
  </div>
  @if($products->count() > 1)
    <div class="vt-dots" aria-hidden="true">@foreach($products->take(5) as $i => $p)<span class="{{ $i === 0 ? 'is-on' : '' }}"></span>@endforeach</div>
  @endif
</div>
@endif
