{{-- ویترین · نوار ابزارها — هر کاشی یک دسته‌بندی با تصویر آخرین محصول و تعداد قالب --}}
@if(($tiles ?? collect())->isNotEmpty())
<div class="vt-sec">
  @include('app.home-builder.sections.partials.vt-head')
  <div class="vt-tools">
    @foreach($tiles as $tile)
      <a class="vt-tool" href="{{ $tile['url'] }}">
        @if($tile['badge'])<span class="vt-badge {{ $tile['badge'] === 'پرطرفدار' ? 'vt-badge--hot' : 'vt-badge--new' }}">{{ $tile['badge'] }}</span>@endif
        <span class="vt-tool-img">@if($tile['image'])<img src="{{ $tile['image'] }}" alt="{{ $tile['title'] }}" loading="lazy" decoding="async">@endif</span>
        <span class="vt-tool-name">{{ $tile['title'] }}<small>{{ number_format($tile['count']) }} قالب</small></span>
      </a>
    @endforeach
  </div>
</div>
@endif
