{{-- ویترین · کارت دسته‌ها (مناسبت‌ها) --}}
@if(($tiles ?? collect())->isNotEmpty())
<div class="vt-sec">
  @include('app.home-builder.sections.partials.vt-head')
  <div class="vt-occ">
    @foreach($tiles as $tile)
      <a class="vt-oc" href="{{ $tile['url'] }}">
        @if($tile['image'])<img src="{{ $tile['image'] }}" alt="{{ $tile['title'] }}" loading="lazy" decoding="async">@endif
        <span><b>{{ $tile['title'] }}</b><small>{{ number_format($tile['count']) }} قالب</small></span>
      </a>
    @endforeach
  </div>
</div>
@endif
