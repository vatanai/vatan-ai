<div class="hb-family-duo">
  @foreach($products->take(2) as $familyIndex => $product)
    <a class="hb-family-card" href="{{ route('app.product', $product->route_slug) }}" style="background-image:url('{{ $product->displayImageUrl() }}')">
      <div class="hb-family-shade"></div>
      <span class="hb-family-kicker">{{ $familyIndex === 0 ? 'برای خاطره‌های خانوادگی' : 'کنار هم، برای همیشه' }}</span>
      <div class="hb-family-copy">
        <b>{{ $product->name_fa }}</b>
        <small>{{ $familyIndex === 0 ? 'یک قاب گرم از آدم‌های مهم زندگی' : 'لحظه‌ای که ارزش ماندگار شدن دارد' }}</small>
      </div>
    </a>
  @endforeach
</div>
