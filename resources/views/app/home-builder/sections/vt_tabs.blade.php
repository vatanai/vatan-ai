{{-- ویترین · ردیف تب‌دار — هر تب یک دسته؛ جابه‌جایی تب‌ها بدون بارگذاری مجدد صفحه --}}
@if(($tabs ?? collect())->isNotEmpty())
@php $vtTabsId = 'vt-tabs-' . $section->id; @endphp
<div class="vt-sec" data-vt-tabs>
  @include('app.home-builder.sections.partials.vt-head')
  <div class="vt-tabbar" role="tablist">
    @if($showAllTab)
      <button type="button" class="is-on" role="tab" data-vt-tab="all">همه <small>{{ number_format($allProducts->count()) }}</small></button>
    @endif
    @foreach($tabs as $i => $tab)
      <button type="button" class="{{ !$showAllTab && $i === 0 ? 'is-on' : '' }}" role="tab" data-vt-tab="{{ $tab['key'] }}">{{ $tab['label'] }} <small>{{ number_format($tab['products']->count()) }}</small></button>
    @endforeach
  </div>
  @if($showAllTab)
    <div class="vt-row" data-vt-panel="all">
      @foreach($allProducts as $product)
        @include('app.home-builder.sections.partials.vt-card', ['product' => $product])
      @endforeach
    </div>
  @endif
  @foreach($tabs as $i => $tab)
    <div class="vt-row" data-vt-panel="{{ $tab['key'] }}" @if($showAllTab || $i > 0) hidden @endif>
      @foreach($tab['products'] as $product)
        @include('app.home-builder.sections.partials.vt-card', ['product' => $product])
      @endforeach
    </div>
  @endforeach
</div>
@endif
