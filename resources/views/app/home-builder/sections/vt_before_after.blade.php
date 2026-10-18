{{-- ویترین · قبل / بعد — اسلایدر کشیدنی؛ نمونه‌های آماده یا تصاویر محصول --}}
@if(($items ?? collect())->isNotEmpty())
<div class="vt-sec">
  @include('app.home-builder.sections.partials.vt-head')
  <div class="vt-ba">
    @foreach($items as $item)
      @php
        $itemTitle = $item['title'] ?? $item['product']->name_fa;
        $itemUrl = $item['url'] ?? route('app.product', $item['product']->route_slug);
      @endphp
      <div class="vt-ba-card">
        <div class="vt-cmp" data-vt-compare>
          <img class="vt-cmp-b" src="{{ $item['before'] }}" alt="قبل — {{ $itemTitle }}" loading="lazy" decoding="async" draggable="false">
          <img class="vt-cmp-a" src="{{ $item['after'] }}" alt="بعد — {{ $itemTitle }}" loading="lazy" decoding="async" draggable="false">
          <span class="vt-cmp-line" aria-hidden="true"><i class="fa-solid fa-left-right"></i></span>
          <span class="vt-cmp-l vt-cmp-l--b">قبل</span><span class="vt-cmp-l vt-cmp-l--a">بعد</span>
        </div>
        @if($section->setting('show_meta', true))
          <div class="vt-ba-meta">
            <b>{{ $itemTitle }}</b>
            <a href="{{ $itemUrl }}">{{ $section->setting('cta_label') ?: 'بساز' }}</a>
          </div>
        @endif
      </div>
    @endforeach
  </div>
</div>
@endif
