<section class="trends-section trends-time-section">
  @include('app.trends.partials.section-heading', [
    'title' => 'محبوب‌ترین ترندها در بازه‌های زمانی',
    'description' => 'هر کارت بر اساس بیشترین اجرای ثبت‌شده در همان بازه انتخاب می‌شود.',
  ])
  <div class="trends-time-grid">
    @foreach($sections as $section)
      <article class="trends-time-column">
        <h3>{{ $section['title'] }}</h3>
        @include('app.trends.partials.product-card', ['card' => $section['card']])
      </article>
    @endforeach
  </div>
</section>
