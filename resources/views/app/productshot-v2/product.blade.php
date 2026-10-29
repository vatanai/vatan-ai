@extends('layouts.app')

@php
  $fa = fn ($v) => strtr((string) $v, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
  $name = $product->name_fa ?: $product->name_en;
  $first = $gallery->first();
  $description = trim((string) ($product->description_fa ?: $product->description_en ?: ''));
  $tags = is_array($product->tags) ? array_values(array_filter(array_map('trim', $product->tags))) : [];
  $likeCount = (int) ($product->displayed_likes_count ?? 0);
@endphp

@section('page_title', $name . ' | پک شات محصول | وطن AI')

@push('meta')
  <meta name="robots" content="noindex,nofollow">
  @if($first)<link rel="preload" as="image" href="{{ $first['url'] }}" fetchpriority="high">@endif
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/productshot-common.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/productshot-pro.css') }}">
@endpush

@section('content')
<div class="ps-pro" dir="rtl" data-ps-pro>
  <div class="ps-shell">
    <aside class="ps-info">
      <div class="ps-info-scroll">
        <div class="ps-toprow">
          @include('app.productshot-v2.partials.back', ['href' => route('app.explore'), 'label' => 'برگشت', 'class' => 'ps-back-desk'])
          <span class="ps-kicker"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> پک شات محصول</span>
        </div>
        <h1 class="ps-title">{{ $name }}</h1>

        @if($tags)
          <div class="ps-tags">
            @foreach(array_slice($tags, 0, 6) as $tag)
              <span class="ps-tag">#{{ $tag }}</span>
            @endforeach
          </div>
        @endif

        <div class="ps-desc-box">
          <h2 class="ps-desc-title">توضیحات محصول</h2>
          <p class="ps-desc">{{ $description ?: 'یک عکس ساده از محصولت بده؛ چند عکس تبلیغاتی با نور و صحنه‌ی حرفه‌ای بگیر. شکل، رنگ و جنس محصول دقیق حفظ می‌شود.' }}</p>
          <div class="ps-facts">
            <div><span>اسلایدها</span><b>{{ $fa(count($shots)) }} صحنه</b></div>
            <div><span>ورودی لازم</span><b>یک عکس محصول</b></div>
            <div><span>شروع از</span><b>{{ $minCredit ? $fa($minCredit) . ' کردیت' : 'رایگان' }}</b></div>
          </div>
        </div>

        <div class="ps-actions">
          <div class="ps-credit-wrap">
            <button type="button" class="ps-credit" data-credit-toggle aria-expanded="false">
              <i class="fa-solid fa-coins" aria-hidden="true"></i>
              <span class="ps-credit-text">{{ $minCredit ? 'از' : 'رایگان' }} @if($minCredit)<b class="ps-num">{{ $fa($minCredit) }}</b> کردیت@endif</span>
            </button>
            <div class="ps-credit-pop" data-credit-pop>
              {{ $minCredit ? 'هزینه‌ی ساخت هر عکس از ' . $fa($minCredit) . ' کردیت شروع می‌شود.' : 'ساخت این پک رایگان است.' }}
            </div>
          </div>
          <button type="button" class="ps-iconbtn{{ $isSaved ? ' is-on' : '' }}" data-save="{{ route('app.product.save', $product->slug) }}" aria-pressed="{{ $isSaved ? 'true' : 'false' }}" aria-label="ذخیره" title="ذخیره">
            <i class="fa-{{ $isSaved ? 'solid' : 'regular' }} fa-bookmark" aria-hidden="true"></i>
          </button>
          <button type="button" class="ps-iconbtn" data-share aria-label="اشتراک‌گذاری" title="اشتراک‌گذاری">
            <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
          </button>
          <button type="button" class="ps-iconbtn ps-likebtn{{ $isLiked ? ' is-on' : '' }}" data-like="{{ route('app.product.like', $product->slug) }}" data-like-count="{{ $likeCount }}" aria-pressed="{{ $isLiked ? 'true' : 'false' }}" aria-label="لایک" title="لایک">
            <i class="fa-{{ $isLiked ? 'solid' : 'regular' }} fa-heart" aria-hidden="true"></i>
            <small class="ps-like-count">{{ number_format($likeCount) }}</small>
          </button>
        </div>

    <div class="ps-dock-wrap">
    <div class="ps-dock">
      <div class="ps-dock-info"><b>{{ $minCredit ? 'از ' . $fa($minCredit) . ' کردیت هر عکس' : 'رایگان' }}</b><span>{{ $fa(count($shots)) }} صحنه برای انتخاب</span></div>
      <a class="ps-cta" href="{{ $createUrl }}"><i class="fa-solid fa-bolt" aria-hidden="true"></i> بساز</a>
    </div>
    </div>

        <a class="ps-cta ps-cta-desk" href="{{ $createUrl }}"><span class="ps-cta-text">همین پک را بساز</span><i class="fa-solid fa-bolt" aria-hidden="true"></i></a>

        <ol class="ps-how">
          <li><i>۱</i><span><b>عکس محصولت را آپلود کن</b>یک عکس واضح از روبه‌رو کافی است.</span></li>
          <li><i>۲</i><span><b>شات‌ها و کیفیت را انتخاب کن</b>هر شات یک صحنه‌ی تبلیغاتی جداست.</span></li>
          <li><i>۳</i><span><b>پک را تحویل بگیر</b>همه‌ی عکس‌ها یکجا قابل دانلودند.</span></li>
        </ol>

        @if($gallery->count() > 1)
          <div class="ps-info-gal">
            <h2 class="ps-block-title">نمونه‌های این پک</h2>
            <div class="ps-thumbs">
              @foreach($gallery as $i => $g)
                <button type="button" data-gallery-thumb="{{ $i }}" data-src="{{ $g['url'] }}" data-label="{{ $g['label'] }}" data-kind="{{ $g['kind'] ?? 'slide' }}" class="{{ $i === 0 ? 'is-viewing' : '' }}{{ ($g['kind'] ?? '') === 'input' ? ' is-input' : '' }}" aria-label="{{ $g['label'] }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
                  <img src="{{ $g['url'] }}" alt="" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
                  @if(($g['kind'] ?? '') === 'input')<span class="ps-thumb-tag">عکس ورودی</span>@endif
                </button>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    </aside>

    <section class="ps-stage" aria-label="گالری نمونه‌های محصول">
      <div class="ps-main{{ ($first['kind'] ?? '') === 'input' ? ' is-input-view' : '' }}">
        @if($first)
          <img src="{{ $first['url'] }}" alt="{{ $first['label'] }}" data-main-image fetchpriority="high" decoding="async">
        @endif
        <span class="ps-counter" data-counter>{{ $gallery->count() > 1 ? $fa(1) . ' / ' . $fa($gallery->count()) : '' }}</span>
        @include('app.productshot-v2.partials.back', ['href' => route('app.explore'), 'label' => 'برگشت', 'class' => 'psb-on-media ps-back-mob'])
        <span class="ps-pack-badge" data-pack-badge @if(($first['kind'] ?? '') === 'input') hidden @endif><i class="fa-solid fa-images" aria-hidden="true"></i>{{ $gallery->count() > 1 ? $fa($gallery->where('kind', 'slide')->count()) . ' نمونه' : '' }}</span>
        @if($inputImage ?? null)
          <span class="ps-input-badge" data-input-badge @if(($first['kind'] ?? '') !== 'input') hidden @endif><i class="fa-solid fa-camera" aria-hidden="true"></i> عکس ورودی — نمونه‌ها با همین عکس ساخته شده‌اند</span>
          <img class="ps-compare-image" src="{{ $inputImage }}" alt="عکس ورودی" data-compare-image loading="lazy" decoding="async" aria-hidden="true">
          <button type="button" class="ps-compare" data-compare @if(($first['kind'] ?? '') === 'input') hidden @endif aria-pressed="false" title="نگه دار تا عکس ورودی را ببینی">
            <i class="fa-solid fa-code-compare" aria-hidden="true"></i><span data-compare-label>قبل / بعد</span>
          </button>
        @endif
        @if($gallery->count() > 1)
          <button type="button" class="ps-stage-nav is-prev" data-prev aria-label="نمونه‌ی قبلی"><i class="fa-solid fa-chevron-right"></i></button>
          <button type="button" class="ps-stage-nav is-next" data-next aria-label="نمونه‌ی بعدی"><i class="fa-solid fa-chevron-left"></i></button>
        @endif
      </div>


      <div class="ps-dock-slot"></div>
    </section>

    @if($gallery->count() > 1)
      <div class="ps-mob-gal">
        <h2 class="ps-block-title">نمونه‌های این پک</h2>
        <div class="ps-thumbs">
          @foreach($gallery as $i => $g)
            <button type="button" data-gallery-thumb="{{ $i }}" data-src="{{ $g['url'] }}" data-label="{{ $g['label'] }}" data-kind="{{ $g['kind'] ?? 'slide' }}" class="{{ $i === 0 ? 'is-viewing' : '' }}{{ ($g['kind'] ?? '') === 'input' ? ' is-input' : '' }}" aria-label="{{ $g['label'] }}" aria-pressed="{{ $i === 0 ? 'true' : 'false' }}">
              <img src="{{ $g['url'] }}" alt="" loading="lazy" decoding="async">
              @if(($g['kind'] ?? '') === 'input')<span class="ps-thumb-tag">عکس ورودی</span>@endif
            </button>
          @endforeach
        </div>
      </div>
    @endif
  </div>

  @if($similar->count())
    <section class="ps-similar" aria-label="محصولات مشابه">
      <div class="ps-wrap">
        <header class="ps-similar-head">
          <h2>محصولات مشابه</h2>
          <div class="ps-similar-nav">
            <button type="button" data-sim-prev aria-label="قبلی"><i class="fa-solid fa-chevron-right"></i></button>
            <button type="button" data-sim-next aria-label="بعدی"><i class="fa-solid fa-chevron-left"></i></button>
          </div>
        </header>
        <div class="ps-track" data-sim-track>
          @foreach($similar as $sp)
            <a class="ps-scard" href="{{ route('app.pro', ['product' => $sp->short_code]) }}">
              <img src="{{ $sp->displayImageUrl() }}" alt="" loading="lazy" decoding="async">
              <div class="ps-scard-body">
                @if($sp->category)<span class="ps-scard-cat">{{ $sp->category }}</span>@endif
                <h3 class="ps-scard-title">{{ $sp->name_fa ?: $sp->name_en }}</h3>
                @if($sp->description_fa)<p class="ps-scard-desc">{{ \Illuminate\Support\Str::limit(trim(strip_tags($sp->description_fa)), 70) }}</p>@endif
              </div>
            </a>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  <div class="ps-toast" data-toast role="status" aria-live="polite" hidden></div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var root = document.querySelector('[data-ps-pro]');
  if (!root) return;

  var FA = '۰۱۲۳۴۵۶۷۸۹';
  var fa = function (v) { return String(v).replace(/[0-9]/g, function (d) { return FA[d]; }); };
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var isAuth = @json(auth()->check());
  var loginUrl = @json(route('login', ['redirect' => request()->fullUrl()]));
  var toast = root.querySelector('[data-toast]');
  var timer;

  function say(message) {
    if (!toast) return;
    toast.textContent = message;
    toast.hidden = false;
    clearTimeout(timer);
    timer = setTimeout(function () { toast.hidden = true; }, 2200);
  }

  var thumbs = [].slice.call(root.querySelectorAll('[data-gallery-thumb]'));
  var galleryCount = {{ $gallery->count() }};
  var main = root.querySelector('[data-main-image]');
  var counter = root.querySelector('[data-counter]');
  var current = 0;

  var imageChangeSequence = 0;
  /* تصویر بعدی پیش‌بارگذاری می‌شود تا کلیک‌های سریع، تصویر قبلی را روی صحنه نگه ندارند. */
  function crossfade(src, label) {
    if (!main || !src) return;
    main.alt = label;
    var targetUrl = new URL(src, document.baseURI).href;
    var sequence = ++imageChangeSequence;
    if (main.src === targetUrl) {
      main.style.opacity = '1';
      return;
    }

    var incoming = new Image();
    var commit = function () {
      if (sequence !== imageChangeSequence) return;
      main.style.opacity = '0';
      main.src = targetUrl;
      main.alt = label;
      requestAnimationFrame(function () {
        if (sequence === imageChangeSequence) main.style.opacity = '1';
      });
    };
    incoming.onload = commit;
    // اگر تصویر خراب/غیرقابل‌دسترسی باشد، تصویر اصلی هم پنهان نمی‌ماند.
    incoming.onerror = commit;
    incoming.src = targetUrl;
  }

  function show(index, preferredThumb) {
    if (!galleryCount || !thumbs.length || !main) return;
    current = ((Number(index) % galleryCount) + galleryCount) % galleryCount;
    // دسکتاپ و موبایل هر دو فهرست thumbnail دارند. همیشه همان فهرست قابل‌مشاهده
    // را مبنا بگیر تا انتخاب موبایل روی نمونه‌ی مخفی دسکتاپ اسکرول نکند.
    var selected = preferredThumb && Number(preferredThumb.dataset.galleryThumb) === current
      ? preferredThumb
      : thumbs.filter(function (button) {
          return Number(button.dataset.galleryThumb) === current && button.getClientRects().length > 0;
        })[0];
    if (!selected) {
      selected = thumbs.filter(function (button) { return Number(button.dataset.galleryThumb) === current; })[0];
    }
    if (!selected) return;
    crossfade(selected.dataset.src, selected.dataset.label || '');
    syncInputState(selected.dataset.kind === 'input');
    if (counter) counter.textContent = fa(current + 1) + ' / ' + fa(galleryCount);
    thumbs.forEach(function (button) {
      var active = Number(button.dataset.galleryThumb) === current;
      button.classList.toggle('is-viewing', active);
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', active ? 'true' : 'false');
      if (button.hasAttribute('aria-selected')) button.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    if (selected.scrollIntoView && selected.getClientRects().length > 0) {
      selected.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }
  }

  /* عکس ورودی: نشان روی تصویر + مقایسه‌ی قبل/بعد با نگه داشتن */
  var inputBadge = root.querySelector('[data-input-badge]');
  var packBadge = root.querySelector('[data-pack-badge]');
  var compareButton = root.querySelector('[data-compare]');
  var compareImage = root.querySelector('[data-compare-image]');
  var mainWrap = root.querySelector('.ps-main');
  function syncInputState(isInput) {
    if (mainWrap) mainWrap.classList.toggle('is-input-view', !!isInput);
    if (inputBadge) inputBadge.hidden = !isInput;
    if (packBadge && inputBadge) packBadge.hidden = isInput;
    if (compareButton) compareButton.hidden = isInput;
    setCompare(false);
  }
  function setCompare(on) {
    if (!compareImage || !mainWrap) return;
    if (on && main) {
      compareImage.style.width = main.offsetWidth + 'px';
      compareImage.style.height = main.offsetHeight + 'px';
      compareImage.style.left = main.offsetLeft + 'px';
      compareImage.style.top = main.offsetTop + 'px';
    }
    mainWrap.classList.toggle('is-comparing', !!on);
    if (compareButton) {
      compareButton.setAttribute('aria-pressed', on ? 'true' : 'false');
      var label = compareButton.querySelector('[data-compare-label]');
      if (label) label.textContent = on ? 'عکس ورودی' : 'قبل / بعد';
    }
  }
  if (compareButton) {
    var holding = false;
    var start = function (event) { event.preventDefault(); event.stopPropagation(); holding = true; stopAuto(); setCompare(true); };
    var end = function (event) { if (!holding) return; event.stopPropagation(); holding = false; setCompare(false); startAuto(); };
    compareButton.addEventListener('pointerdown', start);
    compareButton.addEventListener('pointerup', end);
    compareButton.addEventListener('pointerleave', end);
    compareButton.addEventListener('pointercancel', end);
    compareButton.addEventListener('touchstart', function (event) { event.stopPropagation(); }, { passive: true });
    compareButton.addEventListener('touchend', function (event) { event.stopPropagation(); });
    compareButton.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        var on = !mainWrap.classList.contains('is-comparing');
        setCompare(on);
        if (on) stopAuto(); else startAuto();
      }
    });
    compareButton.addEventListener('contextmenu', function (event) { event.preventDefault(); });
  }

  /* چرخش خودکار: هر ۵ ثانیه، بی‌پایان (کاور ← عکس ورودی ← اسلایدها ← دوباره کاور).
     انتخاب دستی فقط شمارنده را از نو شروع می‌کند و چرخش متوقف نمی‌شود. */
  var AUTO_MS = 5000;
  var autoTimer = null;
  function stopAuto() {
    if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
  }
  function startAuto() {
    stopAuto();
    if (galleryCount > 1) autoTimer = setInterval(function () { show(current + 1); }, AUTO_MS);
  }
  thumbs.forEach(function (button) {
    button.addEventListener('click', function () {
      show(Number(button.dataset.galleryThumb), button);
      startAuto();
    });
  });
  var previous = root.querySelector('[data-prev]');
  var next = root.querySelector('[data-next]');
  if (previous) previous.addEventListener('click', function () { show(current - 1); startAuto(); });
  if (next) next.addEventListener('click', function () { show(current + 1); startAuto(); });

  var stage = root.querySelector('.ps-stage');
  var startX = null;
  if (stage) {
    stage.addEventListener('touchstart', function (event) {
      startX = event.touches[0].clientX;
    }, { passive: true });
    stage.addEventListener('touchend', function (event) {
      if (startX === null) return;
      var delta = event.changedTouches[0].clientX - startX;
      startX = null;
      if (Math.abs(delta) > 40) { show(current + (delta > 0 ? 1 : -1)); startAuto(); }
    });
  }

  startAuto();

  /* اسلایدر محصولات مشابه */
  var simTrack = root.querySelector('[data-sim-track]');
  var simPrev = root.querySelector('[data-sim-prev]');
  var simNext = root.querySelector('[data-sim-next]');
  function slideSimilar(direction) {
    if (simTrack) simTrack.scrollBy({ left: direction * 260, behavior: 'smooth' });
  }
  if (simPrev) simPrev.addEventListener('click', function () { slideSimilar(1); });
  if (simNext) simNext.addEventListener('click', function () { slideSimilar(-1); });

  /* باکس بساز (موبایل): ابتدا فریز بالای دکمه‌های شناور؛ وقتی جای طبیعی‌اش به همان نقطه برسد، عادی می‌شود */
  var dockWrap = root.querySelector('.ps-dock-wrap');
  var dock = root.querySelector('.ps-dock');
  var mobileOnly = window.matchMedia('(max-width: 767px)');
  var DOCK_BOTTOM = 108; /* همان فاصله‌ی قبلی از پایین، بالای نوار ناوبری اپ */
  function syncDock() {
    if (!dock || !dockWrap) return;
    if (!mobileOnly.matches) {
      dock.classList.remove('is-floating');
      dockWrap.style.height = '';
      dock.style.width = '';
      dock.style.left = '';
      return;
    }
    var h = dock.offsetHeight;
    var naturalTop = dockWrap.getBoundingClientRect().top;
    var spotTop = window.innerHeight - DOCK_BOTTOM - h;
    var floating = naturalTop > spotTop;
    dock.classList.toggle('is-floating', floating);
    dockWrap.style.height = floating ? h + 'px' : '';
    /* اندازه‌ی یکسان در هر دو حالت: عرض = ۹۵٪ کارت، همان اندازه‌ی حالت جای طبیعی */
    var w = dockWrap.clientWidth;
    dock.style.width = w + 'px';
    if (floating) {
      dock.style.left = (dockWrap.getBoundingClientRect().left + (dockWrap.clientWidth - w) / 2) + 'px';
    } else {
      dock.style.left = '';
    }
  }
  window.addEventListener('scroll', syncDock, { passive: true });
  window.addEventListener('resize', syncDock);
  window.addEventListener('load', syncDock);
  syncDock();

  var creditButton = root.querySelector('[data-credit-toggle]');
  var creditPop = root.querySelector('[data-credit-pop]');
  if (creditButton && creditPop) {
    creditButton.addEventListener('click', function () {
      var open = creditPop.classList.toggle('is-open');
      creditButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function toggle(button, key, icon, onMessage, offMessage) {
    if (!button) return;
    button.addEventListener('click', function () {
      if (!isAuth) { location.href = loginUrl; return; }
      button.disabled = true;
      fetch(button.dataset[key], {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      })
        .then(function (response) { return response.json(); })
        .then(function (body) {
          var active = !!(body.liked !== undefined ? body.liked : body.saved);
          button.classList.toggle('is-on', active);
          button.setAttribute('aria-pressed', active ? 'true' : 'false');
          var glyph = button.querySelector('i');
          if (glyph) glyph.className = (active ? 'fa-solid ' : 'fa-regular ') + icon;
          if (body.likes_count !== undefined) {
            var count = button.querySelector('.ps-like-count');
            if (count) count.textContent = Number(body.likes_count).toLocaleString('fa-IR');
          }
          say(active ? onMessage : offMessage);
        })
        .catch(function () { say('انجام نشد؛ دوباره امتحان کن.'); })
        .then(function () { button.disabled = false; });
    });
  }

  toggle(root.querySelector('[data-like]'), 'like', 'fa-heart', 'به علاقه‌مندی‌ها اضافه شد', 'از علاقه‌مندی‌ها برداشته شد');
  toggle(root.querySelector('[data-save]'), 'save', 'fa-bookmark', 'ذخیره شد', 'از ذخیره‌ها برداشته شد');

  var share = root.querySelector('[data-share]');
  if (share) share.addEventListener('click', function () {
    var url = location.href;
    if (navigator.share) {
      navigator.share({ title: document.title, url: url }).catch(function () {});
      return;
    }
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(function () { say('لینک کپی شد'); }, function () { say(url); });
    } else {
      say(url);
    }
  });

})();
</script>
@endpush

