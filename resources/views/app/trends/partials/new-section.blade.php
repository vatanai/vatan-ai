<section class="trends-section">
  @include('app.trends.partials.section-heading', [
    'title' => 'محصولات ترند جدید',
    'description' => 'محصولات تازه‌ای که هم جدید هستند و هم در فهرست ترند قرار گرفته‌اند.',
  ])
  <div class="trends-three-grid">
    @forelse($products as $card)
      @include('app.trends.partials.product-card', ['card' => $card])
    @empty
      <p class="trends-empty">فعلاً محصول ترند جدیدی برای نمایش وجود ندارد.</p>
    @endforelse
  </div>
</section>
