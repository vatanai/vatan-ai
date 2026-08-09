<div class="trends-page" dir="rtl">
  <header class="trends-header">
    <div class="trends-heading-row">
      @if(!isset($sitePage) || $sitePage->content('show_page_title', true))
      <div>
        <span class="trends-eyebrow"><i class="fa-solid fa-arrow-trend-up"></i> انتخاب‌های محبوب وطن</span>
        <h1 class="trends-title">{{ isset($sitePage) ? $sitePage->title : 'ترندز' }}</h1>
        <p class="trends-subtitle">{{ isset($sitePage) ? $sitePage->subtitle : 'محبوب‌ترین سبک‌ها و ایده‌هایی که این روزها بیشتر دیده و استفاده می‌شوند.' }}</p>
      </div>
      @endif
    </div>
    @if(!isset($sitePage) || $sitePage->content('show_search', true))
      @include('app.trends.partials.search')
    @endif
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
