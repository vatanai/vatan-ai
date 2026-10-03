{{-- ویترین · قبل / بعد — اسلایدر کشیدنی؛ «قبل» از before_images محصول --}}
@if(($items ?? collect())->isNotEmpty())
<div class="vt-sec">
  @include('app.home-builder.sections.partials.vt-head')
  <div class="vt-ba">
    @foreach($items as $item)
      <div class="vt-ba-card">
        <div class="vt-cmp" data-vt-compare>
          <img class="vt-cmp-b" src="{{ $item['before'] }}" alt="قبل — {{ $item['product']->name_fa }}" loading="lazy" decoding="async" draggable="false">
          <img class="vt-cmp-a" src="{{ $item['after'] }}" alt="بعد — {{ $item['product']->name_fa }}" loading="lazy" decoding="async" draggable="false">
          <span class="vt-cmp-line" aria-hidden="true"><i class="fa-solid fa-left-right"></i></span>
          <span class="vt-cmp-l vt-cmp-l--b">قبل</span><span class="vt-cmp-l vt-cmp-l--a">بعد</span>
        </div>
        <div class="vt-ba-meta">
          <b>{{ $item['product']->name_fa }}</b>
          <a href="{{ route('app.product', $item['product']->route_slug) }}">{{ $section->setting('cta_label') ?: 'بساز' }}</a>
        </div>
      </div>
    @endforeach
  </div>
</div>
@endif
