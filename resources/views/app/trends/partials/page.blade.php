<div class="trends-page" dir="rtl">
  <header class="trends-header">
    <div class="trends-heading-row">
      <div>
        <span class="trends-eyebrow"><i class="fa-solid fa-arrow-trend-up"></i> انتخاب‌های محبوب وطن</span>
        <h1 class="trends-title">ترندز</h1>
        <p class="trends-subtitle">محبوب‌ترین سبک‌ها و ایده‌هایی که این روزها بیشتر دیده و استفاده می‌شوند.</p>
      </div>
    </div>
    @include('app.trends.partials.search')
  </header>

  <section class="trends-section trends-feed-section">
    @include('app.trends.partials.section-heading', [
      'title' => 'محصولات ترند',
      'description' => 'همه محصولاتی که در مدیریت ترند فعال شده‌اند، یکپارچه و پشت‌سرهم نمایش داده می‌شوند.',
    ])

    <div class="trends-feed-grid">
      @forelse($trendProducts as $index => $card)
        @include('app.trends.partials.product-card', ['card' => $card])

        @foreach($trendBanners as $banner)
          @if(in_array($banner->display_target, ['desktop', 'both'], true) && $banner->row_number * 4 === $index + 1)
            @include('app.trends.partials.banner', ['banner' => $banner, 'device' => 'desktop'])
          @endif
          @if(in_array($banner->display_target, ['mobile', 'both'], true) && $banner->row_number * 2 === $index + 1)
            @include('app.trends.partials.banner', ['banner' => $banner, 'device' => 'mobile'])
          @endif
        @endforeach
      @empty
        <p class="trends-empty">فعلاً محصولی برای نمایش در صفحه ترند فعال نشده است.</p>
      @endforelse
    </div>
  </section>
</div>
