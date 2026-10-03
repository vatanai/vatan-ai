{{--
  کارت استاندارد ویترین — تنها کارت محصول همه‌ی سکشن‌های vt_* (یک ظاهر، یک هاور).
  ورودی: $product، اختیاری: $ratio ('3-4' | '9-16' | 'auto')، $eager (bool)
--}}
@php
  $vtRatio = $ratio ?? '3-4';
  $vtIsVideo = in_array((string) $product->media_type, ['video', 'both'], true);
  $vtBadge = $product->is_trending ? ['پرطرفدار', 'hot']
    : ($product->is_new ? ['جدید', 'new']
    : ($vtIsVideo ? ['ویدیو', 'vid'] : null));
  $vtMeta = $product->subcategory ?: $product->category;
@endphp
<a class="vt-card vt-card--{{ $vtRatio }}" href="{{ route('app.product', $product->route_slug) }}">
  @if($vtBadge)<span class="vt-badge vt-badge--{{ $vtBadge[1] }}">{{ $vtBadge[0] }}</span>@endif
  <span class="vt-card-media">
    <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa }}" loading="{{ !empty($eager) ? 'eager' : 'lazy' }}" decoding="async">
    <span class="vt-card-go">همین رو بساز</span>
  </span>
  <span class="vt-card-meta">
    <b>{{ $product->name_fa }}</b>
    <span>
      <small>{{ $vtMeta }}</small>
      <em><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</em>
    </span>
  </span>
</a>
