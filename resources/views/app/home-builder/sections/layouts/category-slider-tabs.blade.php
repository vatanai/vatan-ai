{{--
  Layout «تب دسته‌بندی + اسلایدر» — نمونه‌برداری‌شده از شات پنجم (pixifield.com).
  ردیف تب‌های دایره‌ای دسته‌بندی بالای اسلایدر محصول؛ کلیک روی هر تب بدون AJAX (سمت کاربر)
  پنل محصولات همان دسته را نمایش می‌دهد. داده از $categories و $productsByCategory
  (که در HomeSectionRenderService با یک Query گروه‌بندی‌شده آماده شده) خوانده می‌شود.
--}}
@php $hbTabsId = 'hb-tabs-' . $section->id; @endphp
<div id="{{ $hbTabsId }}">
  <div class="hb-tabs-row">
    <button type="button" class="hb-tab-pill is-active"
            data-tabs-group="{{ $hbTabsId }}"
            data-tabs-target="{{ $hbTabsId }}-panel-all">نمایش همه</button>
    @foreach($categories as $hbTabIndex => $category)
      <button type="button"
              class="hb-tab-pill"
              data-tabs-group="{{ $hbTabsId }}"
              data-tabs-target="{{ $hbTabsId }}-panel-{{ $category->id }}">
        {{ $category->name_fa }}
      </button>
    @endforeach
  </div>

  <div class="hb-tabs-panel is-active" id="{{ $hbTabsId }}-panel-all">
    <div class="home-cards-scroll">
      @foreach(($allTabProducts ?? collect()) as $product)
        <a class="home-card" href="{{ route('app.product', $product->route_slug) }}" style="background-image:url('{{ $product->displayImageUrl() }}')">
          <div class="home-card-overlay"></div>
          <div class="home-card-info">
            <p class="home-card-name">{{ $product->name_fa }}</p>
            <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
            @if($section->setting('show_credit', true))<p class="home-card-credit"><i class="fa-solid fa-bolt"></i>{{ number_format((int) $product->credit_cost) }} کردیت</p>@endif
          </div>
        </a>
      @endforeach
    </div>
  </div>

  @foreach($categories as $hbTabIndex => $category)
    <div class="hb-tabs-panel" id="{{ $hbTabsId }}-panel-{{ $category->id }}">
      <div class="home-cards-scroll">
        @foreach(($productsByCategory[$category->id] ?? collect()) as $product)
          <a class="home-card" href="{{ route('app.product', $product->route_slug) }}" style="background-image: url('{{ $product->displayImageUrl() }}');">
            <div class="home-card-overlay"></div>
            <div class="home-card-info">
              <p class="home-card-name">{{ $product->name_fa }}</p>
              <p class="home-card-tag">{{ $product->subcategory ?: $product->category }}</p>
              @if($section->setting('show_credit', true))
                <p class="home-card-credit"><i class="fa-solid fa-bolt"></i> {{ number_format((int) $product->credit_cost) }} کردیت</p>
              @endif
            </div>
          </a>
        @endforeach
      </div>
    </div>
  @endforeach
</div>

@once
<script>
  /* سوییچ تب‌ها کاملاً سمت کاربر (بدون AJAX) — یک Listener سراسری، امن برای چند Section از این Layout در یک صفحه. */
  (function () {
    if (window.__hbTabsBound) return;
    window.__hbTabsBound = true;
    document.addEventListener('click', function (e) {
      const btn = e.target.closest('.hb-tab-pill');
      if (!btn) return;
      const group = btn.dataset.tabsGroup;
      const targetId = btn.dataset.tabsTarget;
      document.querySelectorAll('.hb-tab-pill[data-tabs-group="' + group + '"]').forEach(function (b) {
        b.classList.remove('is-active');
      });
      btn.classList.add('is-active');
      const groupEl = document.getElementById(group);
      if (groupEl) {
        groupEl.querySelectorAll('.hb-tabs-panel').forEach(function (p) { p.classList.remove('is-active'); });
      }
      const panel = document.getElementById(targetId);
      if (panel) panel.classList.add('is-active');
    });
  })();
</script>
@endonce
