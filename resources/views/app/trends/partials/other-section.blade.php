<section class="trends-section trends-other-section">
  @include('app.trends.partials.section-heading', [
    'title' => 'سایر ترندها',
    'description' => 'محصولات محبوب دیگری که در سکشن‌های قبلی نمایش داده نشده‌اند.',
  ])
  <div class="trends-other-list">
    @forelse($rows as $row)
      <div class="trends-other-row">
        @foreach($row as $card)
          @include('app.trends.partials.product-card', ['card' => $card])
        @endforeach
      </div>
    @empty
      <p class="trends-empty">فعلاً ترند دیگری برای نمایش وجود ندارد.</p>
    @endforelse
  </div>
</section>
