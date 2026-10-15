{{-- باکس جست‌وجوی هوشمند صفحه‌ی خانه: جست‌وجوی زنده + نوار میانبرهای رونده (دسته‌بندی‌های واقعی دارای محصول) --}}
@php
  $svgPaths = [
    'upscale' => '<path d="M4 4h6"/><path d="M4 4v6"/><path d="M20 20h-6"/><path d="M20 20v-6"/><path d="m8 16 3-3 2 2 3-4 2 3"/><circle cx="8" cy="8" r="1.4"/>',
    'char' => '<circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/><path d="M18 4v4"/><path d="M16 6h4"/>',
    'tag' => '<path d="M20 12 12 20 4 12V4h8z"/><circle cx="8.5" cy="8.5" r="1.5"/>',
    'search' => '<circle cx="11" cy="11" r="7"/><line x1="16.5" y1="16.5" x2="21" y2="21"/>',
  ];
  $svg = fn (string $k, string $cls = '') => '<svg class="'.$cls.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.($svgPaths[$k] ?? '').'</svg>';
  $searchUrl = route('app.home.search');
  $productsUrl = route('products.index');

  // ویژه: صفحه‌های اختصاصی کیفیت و کارکتر شیت (ظاهر متفاوت در نوار)
  $featured = [
    ['title' => 'افزایش کیفیت', 'icon' => 'upscale', 'href' => route('landing.image-quality')],
    ['title' => 'کارکتر شیت', 'icon' => 'char', 'href' => route('landing.character-sheet')],
  ];
  // دسته‌بندی‌های واقعی که محصول فعال دارند (از کنترلر می‌آیند)
  $categoryItems = ($searchCategories ?? collect())->map(fn ($c) => [
    'title' => $c->name_fa ?: $c->name,
    'icon' => 'tag',
    'href' => route('categories.show', ['path' => $c->path ?: $c->slug]),
  ])->values()->all();

  $base = array_merge(
    array_map(fn ($i) => $i + ['featured' => true], $featured),
    array_map(fn ($i) => $i + ['featured' => false], $categoryItems),
  );
  // اگر دسته‌ها کم باشند، فهرست تکرار می‌شود تا نوار همیشه پر بماند
  $marquee = $base;
  while ($base && count($marquee) < 10) { $marquee = array_merge($marquee, $base); }

  $examplesShop = ['عکس محصول کرم ضدآفتاب روی سنگ مرمر…', 'استوری فروش برای کیف چرمی جدید…', 'پوستر حراج پاییزی برای فروشگاه…', 'ریلز ۱۵ ثانیه‌ای از عطر جدید…'];
  // ۸ مورد کسب‌وکار و آنلاین شاپ + ۲ مورد پرتره (متن تزئینی است)
  $headlineWords = ['موکاپ لوازم آرایشی', 'طرح برای کیف و کفش', 'ساخت پست برای آنلاین شاپت', 'عکس محصول برای فروشگاهت', 'استوری فروش برای آنلاین شاپت', 'پوستر حراج برای فروشگاهت', 'ریلز محصول برای برندت', 'مدل لباس برای مزونت', 'کارکتر شیت', 'پروفایل حرفه‌ای'];
@endphp

<div class="hsv-wrap" dir="rtl">
  <section class="hsv hsv-v5" aria-label="جست‌وجوی هوشمند">
    <div class="hsv-card hsv-hero">
      <p class="hsv-hero-line">دنبال <span class="hsv-typed" data-hsv-typed data-words="{{ json_encode($headlineWords, JSON_UNESCAPED_UNICODE) }}">موکاپ لوازم آرایشی</span> هستی؟</p>
      <form class="hsv-form" data-hsv-form action="{{ $productsUrl }}" method="GET" data-search-url="{{ $searchUrl }}">
        <input type="hidden" name="search" value="">
        <div class="hsv-bar">
          <span class="hsv-lead">{!! $svg('search') !!}</span>
          <input class="hsv-input" type="text" autocomplete="off" aria-label="جست‌وجوی هوشمند" data-examples="{{ json_encode($examplesShop, JSON_UNESCAPED_UNICODE) }}">
          <button type="submit" class="hsv-btn"><span class="hsv-btn-txt">جست‌وجو</span></button>
        </div>
        <div class="hsv-results" hidden></div>
      </form>
    </div>

    @if(count($marquee))
      <div class="hsv-marquee" aria-label="میانبرها">
        <div class="hsv-marquee-track">
          @for($r = 0; $r < 2; $r++)
            <div class="hsv-marquee-group" @if($r) aria-hidden="true" @endif>
              @foreach($marquee as $i)
                <a class="hsv-chip{{ $i['featured'] ? ' is-featured' : '' }}" href="{{ $i['href'] }}" @if($r) tabindex="-1" @endif>{!! $svg($i['icon']) !!}<span>{{ $i['title'] }}</span></a>
              @endforeach
            </div>
          @endfor
        </div>
      </div>
    @endif
  </section>
</div>
