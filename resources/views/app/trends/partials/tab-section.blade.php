@if($tabs->isNotEmpty())
  <section class="trends-section trends-tab-section" data-trends-tab-group>
    @include('app.trends.partials.section-heading', [
      'title' => $title,
      'description' => $description,
    ])

    <div class="trends-tab-toolbar">
      <div class="trends-tabs" role="tablist" aria-label="{{ $title }}">
        @foreach($tabs as $index => $tab)
          <button type="button" class="trends-tab {{ $index === 0 ? 'is-active' : '' }}" role="tab" aria-selected="{{ $index === 0 ? 'true' : 'false' }}" data-tab-target="{{ $sectionId }}-{{ $tab['id'] }}">
            {{ $tab['title'] }}
          </button>
        @endforeach
      </div>
      <div class="trends-slider-actions" aria-label="حرکت بین محصولات">
        <button type="button" data-slider-prev aria-label="قبلی"><i class="fa-solid fa-arrow-right"></i></button>
        <button type="button" data-slider-next aria-label="بعدی"><i class="fa-solid fa-arrow-left"></i></button>
      </div>
    </div>

    @foreach($tabs as $index => $tab)
      <div id="{{ $sectionId }}-{{ $tab['id'] }}" class="trends-tab-panel {{ $index === 0 ? 'is-active' : '' }}" role="tabpanel" {{ $index !== 0 ? 'hidden' : '' }}>
        <div class="trends-four-slider" data-trends-slider>
          <div class="trends-four-track">
            @foreach($tab['products'] as $card)
              <div class="trends-four-item">
                @include('app.trends.partials.product-card', ['card' => $card])
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endforeach
  </section>
@endif
