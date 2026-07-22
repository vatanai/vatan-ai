{{--
  Layout «کارت بزرگ» — نمونه‌برداری‌شده از شات Create (pixifield.com).
  اسلایدر با کارت‌های کمتر و بزرگ‌تر در هر نما + برچسب وضعیت رنگی (NEW/TOOL/STUDIO).
  برچسب از فیلدهای موجود مدل Product مشتق می‌شود (بدون افزودن ستون جدید):
  is_new → NEW، ویدیویی → TOOL، is_featured → STUDIO.
--}}
@php
  $hbLargeBadge = function ($product) {
    if ($product->is_new) {
      return ['label' => 'NEW', 'class' => 'hb-badge-new'];
    }
    if (in_array($product->media_type, ['video', 'both'])) {
      return ['label' => 'TOOL', 'class' => 'hb-badge-tool'];
    }
    if ($product->is_featured) {
      return ['label' => 'STUDIO', 'class' => 'hb-badge-studio'];
    }
    return null;
  };
@endphp
<div class="hb-large-row">
  @foreach($products as $product)
    @php($hbBadge = $hbLargeBadge($product))
    <a class="hb-large-card" href="{{ route('app.product', $product->route_slug) }}">
      <div class="hb-large-media" style="background-image: url('{{ $product->displayImageUrl() }}');">
        @if($hbBadge)
          <span class="hb-large-badge {{ $hbBadge['class'] }}">{{ $hbBadge['label'] }}</span>
        @endif
      </div>
      <div class="hb-large-body">
        @if($section->setting('show_credit', true))
          <div class="hb-card-cost"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</div>
        @endif
        <p class="hb-large-title">{{ $product->name_fa }}</p>
        <p class="hb-large-desc">{{ $product->subcategory ?: $product->category }}</p>
      </div>
    </a>
  @endforeach
</div>
