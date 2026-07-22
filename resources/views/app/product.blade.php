@extends('layouts.app')

@php
  $seoTitle = $product->meta_title ?: ($product->name_fa . ' | وطن AI');
  $seoDesc  = $product->meta_description
      ?: \Illuminate\Support\Str::limit(trim(strip_tags($product->description_fa ?: $product->description_en ?: '')), 160);
  $seoImg   = $product->og_image
      ? asset('storage/'.$product->og_image)
      : $product->displayImageUrl();
  $seoUrl   = url()->current();

  // ── تبدیل عدد به رقم فارسی ──
  $__fa = fn ($n) => strtr((string) $n, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);

  // ── هزینه توکن هر ساخت ──
  $__isPerCredit = $product->pricing_model === 'per_credit';
  $__cost        = (int) ($product->credit_cost ?? 0);
  $__tokenLabel  = ($__isPerCredit && $__cost > 0) ? ($__fa($__cost) . ' توکن') : 'رایگان';
  $__tokenPopTxt = ($__isPerCredit && $__cost > 0)
      ? ('برای ساخت و تولید این عکس ' . $__fa($__cost) . ' توکن نیاز است.')
      : 'ساخت این محصول رایگان است و توکنی از حساب شما کم نمی‌شود.';

  // ── دسته‌بندی‌ها (رابطه چندگانه؛ اگر خالی بود از فیلد قدیمی category) ──
  $__cats = $product->categories->pluck('name_fa')->filter()->values()->all();
  if (empty($__cats) && $product->category) $__cats = [$product->category];

  // ── تگ‌ها ──
  $__tags = is_array($product->tags) ? array_values(array_filter(array_map('trim', $product->tags))) : [];

  // ── توضیحات ──
  $__desc = trim((string) ($product->description_fa ?: $product->description_en ?: ''));

  // ── گالری‌ها: عکس‌های اصلی محصول و عکس‌های قبل (before_images) ──
  $__isVideo = fn ($p) => $p && preg_match('/\.(mp4|webm|mov)$/i', (string) $p);
  $__productImages = [$product->displayImageUrl()];
  foreach ((array) ($product->sample_outputs ?? []) as $__o) {
      if (!$__isVideo($__o)) $__productImages[] = asset('storage/' . $__o);
  }
  $__rawImages = [];
  foreach ((array) ($product->before_images ?? []) as $__b) {
      if (!$__isVideo($__b)) $__rawImages[] = asset('storage/' . $__b);
  }
@endphp

@section('page_title', $seoTitle)

@push('meta')
  @if($seoDesc)<meta name="description" content="{{ $seoDesc }}">@endif
  @if($product->meta_keywords)<meta name="keywords" content="{{ $product->meta_keywords }}">@endif
  <link rel="canonical" href="{{ $seoUrl }}">
  <meta property="og:type" content="product">
  <meta property="og:site_name" content="وطن AI">
  <meta property="og:title" content="{{ $seoTitle }}">
  @if($seoDesc)<meta property="og:description" content="{{ $seoDesc }}">@endif
  <meta property="og:image" content="{{ $seoImg }}">
  <meta property="og:url" content="{{ $seoUrl }}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $seoTitle }}">
  @if($seoDesc)<meta name="twitter:description" content="{{ $seoDesc }}">@endif
  <meta name="twitter:image" content="{{ $seoImg }}">
  <script type="application/ld+json">
  {!! json_encode([
      '@context'    => 'https://schema.org',
      '@type'       => 'Product',
      'name'        => $product->name_fa,
      'description' => $seoDesc,
      'image'       => $seoImg,
      'category'    => $product->category,
      'url'         => $seoUrl,
      'brand'       => ['@type' => 'Brand', 'name' => 'وطن AI'],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
  </script>
@endpush

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════
   صفحه محصول اصلی — طراحی دوستونه فول‌صفحه
   راست: توضیحات محصول | چپ: نمایش بزرگ تصویر (عرض ۱۲۰۰)
   رنگ‌ها فقط از توکن‌های رسمی اپ (resources/css/app.css)
═══════════════════════════════════════════════════════ */

/* قانون حاشیه استاندارد صفحه — پشتیبان (نسخه اصلی در app.css) */
:root{
  --page-max: 1400px;
  --page-margin: clamp(24px, 5vw, 96px);
}
.page-container{
  width:100%;
  max-width:calc(var(--page-max) + (2 * var(--page-margin)));
  margin-inline:auto;
  padding-inline:var(--page-margin);
}

.pd-shell{
  display:flex;
  flex-direction:row;      /* در RTL: فرزند اول = سمت راست */
  width:100%;
  background:var(--bg-page);
  color:var(--text-primary);
}

/* ── دسکتاپ و تبلت (از 768px به بالا): ارتفاع سکشن اول = یک صفحه کامل ── */
@media (min-width:768px){
  .pd-shell{
    height:calc(100vh - 64px); /* 64px هدر فیکس بالای صفحه */
    overflow:hidden;
  }
}

/* ═══════════ ستون راست: توضیحات محصول ═══════════ */
.pd-info{
  flex:1 1 0;
  min-width:0;
  background:var(--bg-surface);
  border-inline-start:1px solid var(--border-subtle);
  display:flex;
  flex-direction:column;
}
@media (min-width:768px){
  .pd-info{ min-width:340px; }
  .pd-info-scroll{ overflow-y:auto; }
}
@media (min-width:1280px){
  .pd-info{ min-width:380px; }
}
/* اسکرول‌بار کانتینر توضیحات مخفی (اسکرول کار می‌کند) */
.pd-info-scroll{
  flex:1 1 auto;
  padding:28px 26px 40px;
  display:flex;
  flex-direction:column;
  gap:22px;
  scrollbar-width:none;
}
.pd-info-scroll::-webkit-scrollbar{ width:0; height:0; display:none; }

/* نام محصول */
.pd-title{
  font-size:23px;
  font-weight:800;
  line-height:1.35;
  margin:0;
}

/* دسته‌بندی‌ها (باکس‌دار) + تگ‌ها (بدون باکس) */
.pd-meta{ display:flex; flex-direction:column; gap:12px; }
.pd-cats{ display:flex; flex-wrap:wrap; gap:8px; }
.pd-cat{
  height:26px;
  padding:0 11px;
  display:inline-flex;
  align-items:center;
  border-radius:8px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  font-size:11.5px;
  font-weight:700;
  color:var(--text-primary);
  cursor:pointer;
  transition:all .2s ease;
  user-select:none;
}
.pd-cat:hover{ border-color:var(--green); }
.pd-tags{ display:flex; flex-wrap:wrap; gap:4px 12px; }
.pd-tag{
  font-size:11px;
  font-weight:600;
  color:var(--text-secondary);
  cursor:pointer;
  transition:color .2s ease;
}
.pd-tag:hover{ color:var(--green); }

/* باکس توضیحات */
.pd-desc-box{
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  border-radius:12px;
  padding:18px 18px 20px;
  display:flex;
  flex-direction:column;
  gap:12px;
}
.pd-desc-box h2{
  font-size:15px;
  font-weight:800;
  margin:0;
}
.pd-desc-text{
  font-size:13.5px;
  line-height:2;
  color:var(--text-secondary);
  margin:0;
  max-height:170px;
  overflow-y:auto;
  scrollbar-width:none;
  padding-inline-end:6px;
}
.pd-desc-text::-webkit-scrollbar{ width:0; display:none; }

/* ردیف توکن / سیو / انتشار / لایک — خمیدگی ۱۲، باکس‌های کناری مربع ۴۸×۴۸ */
.pd-actions{ display:flex; gap:10px; align-items:stretch; }
.pd-token-wrap{ position:relative; flex:1 1 auto; min-width:0; }
.pd-token{
  width:100%;
  height:40px;
  border-radius:12px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  font-size:13.5px;
  font-weight:700;
  color:var(--text-primary);
  cursor:pointer;
  transition:all .2s ease;
  font-family:inherit;
}
.pd-token:hover{ border-color:var(--green); }
.pd-token i{ color:var(--green); font-size:13px; }
.pd-token b{ color:var(--green); font-weight:800; }
/* پاپ‌آپ توضیح توکن — زیر باکس توکن باز می‌شود */
.pd-token-pop{
  position:absolute;
  top:calc(100% + 8px);
  right:0;
  left:0;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  border-radius:12px;
  padding:12px 14px;
  font-size:12.5px;
  font-weight:600;
  line-height:1.9;
  color:var(--text-secondary);
  box-shadow:0 14px 34px rgba(0,0,0,.35);
  opacity:0;
  visibility:hidden;
  transform:translateY(-4px);
  transition:all .2s ease;
  z-index:30;
}
.pd-token-pop b{ color:var(--green); }
.pd-token-pop.show{ opacity:1; visibility:visible; transform:none; }

.pd-iconbtn{
  width:40px;
  height:40px;
  flex:0 0 40px;
  border-radius:12px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  display:flex;
  align-items:center;
  justify-content:center;
  color:var(--text-primary);
  font-size:15px;
  cursor:pointer;
  transition:all .2s ease;
}
.pd-iconbtn:hover{ border-color:var(--green); transform:translateY(-1px); }
.pd-iconbtn.is-on{ color:var(--green); border-color:var(--green); }
/* دکمه لایک: در حالت فعال قرمز */
.pd-iconbtn.is-liked{ color:var(--red); border-color:var(--red); }
.pd-iconbtn.is-liked:hover{ border-color:var(--red); }

/* گالری‌ها — ۴ تصویر در هر ردیف */
.pd-gal h2{
  font-size:15px;
  font-weight:800;
  margin:0 0 12px;
}
.pd-gal-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:8px;
}
.pd-gal-grid img{
  width:100%;
  aspect-ratio:1/1;
  object-fit:cover;
  border-radius:12px;
  border:1px solid var(--border-subtle);
  cursor:pointer;
  transition:all .2s ease;
}
.pd-gal-grid img:hover{ border-color:var(--green); transform:translateY(-2px); }
.pd-gal-grid img.is-viewing{ border:2px solid var(--green); }

/* ═══════════ ستون چپ: نمایش بزرگ تصویر ═══════════ */
.pd-stage{
  flex:0 1 1200px;          /* عرض بخش تصاویر: ۱۲۰۰ */
  min-width:0;
  position:relative;
  background:var(--bg-page);
  display:flex;
  align-items:center;
  justify-content:center;
  padding:24px;
}

/* دکمه برگشت (ضربدر) — بالا سمت چپ بخش تصویر */
.pd-close{
  position:absolute;
  top:16px;
  inset-inline-end:20px;
  width:40px;
  height:40px;
  border-radius:12px;
  background:transparent;
  border:1px solid var(--border-subtle);
  color:var(--text-primary);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all .2s ease;
  z-index:4;
}
.pd-close:hover{ border-color:var(--green); box-shadow:0 0 0 3px rgba(207,254,0,.18); }

/* شمارنده بالا */
.pd-counter{
  position:absolute;
  top:20px;
  inset-inline-start:24px;
  font-size:15px;
  font-weight:700;
  color:var(--text-secondary);
  letter-spacing:1px;
  z-index:3;
}

/* تصویر اصلی */
.pd-main{
  width:100%;
  height:100%;
  display:flex;
  align-items:center;
  justify-content:center;
}
.pd-main img{
  max-width:100%;
  max-height:100%;
  border-radius:12px;
  object-fit:contain;
  background:var(--bg-card);
}

/* ═══════════ سکشن دوم: محصولات مشابه (اسلایدر) ═══════════ */
.pd-similar{
  background:var(--bg-page);
  color:var(--text-primary);
  padding:48px 0 64px;   /* حاشیه افقی از قانون --page-margin (کلاس .page-container) می‌آید */
  border-top:1px solid var(--border-subtle);
}
.pd-similar-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  margin-bottom:22px;
}
.pd-similar-head h2{
  font-size:22px;
  font-weight:800;
  margin:0;
}
.pd-similar-nav{ display:flex; gap:8px; }
.pd-similar-nav button{
  width:40px;
  height:40px;
  border-radius:12px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  color:var(--text-primary);
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  transition:all .2s ease;
}
.pd-similar-nav button:hover{ border-color:var(--green); }
.pd-similar-track{
  display:flex;
  gap:16px;
  overflow-x:auto;
  scroll-behavior:smooth;
  scrollbar-width:none;
  padding-bottom:6px;
}
.pd-similar-track::-webkit-scrollbar{ display:none; }
.pd-scard{
  flex:0 0 236px;
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  border-radius:12px;
  overflow:hidden;
  display:flex;
  flex-direction:column;
  cursor:pointer;
  transition:all .25s ease;
  text-decoration:none;
  color:var(--text-primary);
}
.pd-scard:hover{ transform:translateY(-4px); border-color:var(--green); }
.pd-scard img{
  width:100%;
  aspect-ratio:1/1;
  object-fit:cover;
}
.pd-scard-body{
  padding:12px 14px 14px;
  display:flex;
  flex-direction:column;
  gap:8px;
}
.pd-scard-cat{
  align-self:flex-start;
  height:22px;
  padding:0 9px;
  display:inline-flex;
  align-items:center;
  border-radius:8px;
  background:var(--bg-surface);
  border:1px solid var(--border-subtle);
  font-size:10.5px;
  font-weight:700;
  color:var(--text-secondary);
}
.pd-scard-title{
  font-size:14px;
  font-weight:800;
  margin:0;
  line-height:1.5;
  overflow:hidden;
  display:-webkit-box;
  -webkit-line-clamp:1;
  -webkit-box-orient:vertical;
}
.pd-scard-desc{
  font-size:11.5px;
  line-height:1.7;
  color:var(--text-secondary);
  margin:0;
  overflow:hidden;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
}

/* ═══════════ فقط موبایل (زیر 768px) — تبلت دقیقاً مثل دسکتاپ است ═══════════ */
@media (max-width:767px){
  .pd-shell{ flex-direction:column; }
  .pd-stage{
    order:-1;               /* تصاویر بالا */
    flex:none;
    width:100%;
    padding:16px;
    min-height:52vh;
  }
  .pd-main img{ max-height:50vh; }
  .pd-info{ border-inline-start:none; border-top:1px solid var(--border-subtle); }
  .pd-info-scroll{ padding:22px 18px 40px; }
  .pd-title{ font-size:20px; }
  .pd-similar{ padding:36px 0 110px; } /* جا برای نویگیشن پایین موبایل */
}
</style>
@endpush

@section('content')

{{-- ═══════════ سکشن ۱: توضیحات (راست) + تصویر بزرگ (چپ) — فول‌صفحه ═══════════ --}}
<div class="pd-shell" dir="rtl">

  {{-- ═══════ سمت راست: توضیحات محصول ═══════ --}}
  <aside class="pd-info">
    <div class="pd-info-scroll">

      {{-- ۱) نام محصول --}}
      <h1 class="pd-title">{{ $product->name_fa }}</h1>

      {{-- ۲) دسته‌بندی‌ها (باکس‌دار) + تگ‌ها (بدون باکس) --}}
      @if(count($__cats) || count($__tags))
      <div class="pd-meta">
        @if(count($__cats))
        <div class="pd-cats">
          @foreach($__cats as $__cat)
            <span class="pd-cat">{{ $__cat }}</span>
          @endforeach
        </div>
        @endif
        @if(count($__tags))
        <div class="pd-tags">
          @foreach($__tags as $__tag)
            <span class="pd-tag"># {{ $__tag }}</span>
          @endforeach
        </div>
        @endif
      </div>
      @endif

      {{-- ۳) توضیحات داخل باکس --}}
      @if($__desc !== '')
      <div class="pd-desc-box">
        <h2>توضیحات محصول</h2>
        <p class="pd-desc-text">{!! nl2br(e($__desc)) !!}</p>
      </div>
      @endif

      {{-- ۴) توکن مصرفی / سیو / انتشار / لایک --}}
      <div class="pd-actions">
        <div class="pd-token-wrap">
          <button type="button" class="pd-token" id="pdTokenBtn" title="میزان توکن مصرفی">
            <i class="fa-solid fa-bolt"></i>
            <b>{{ $__tokenLabel }}</b>
          </button>
          <div class="pd-token-pop" id="pdTokenPop">{{ $__tokenPopTxt }}</div>
        </div>
        <button type="button" class="pd-iconbtn {{ $isSaved ? 'is-on' : '' }}" id="btnBookmark" data-saved="{{ $isSaved ? '1' : '0' }}" title="ذخیره" aria-label="ذخیره">
          <i id="iconBkm" class="fa-{{ $isSaved ? 'solid' : 'regular' }} fa-bookmark"></i>
        </button>
        <button type="button" class="pd-iconbtn" id="btnShare" title="انتشار" aria-label="انتشار">
          <i class="fa-solid fa-arrow-up-from-bracket"></i>
        </button>
        <button type="button" class="pd-iconbtn {{ ($isLiked ?? false) ? 'is-liked' : '' }}" id="btnLike" data-liked="{{ ($isLiked ?? false) ? '1' : '0' }}" title="لایک" aria-label="لایک">
          <i id="iconLike" class="fa-{{ ($isLiked ?? false) ? 'solid' : 'regular' }} fa-heart"></i>
        </button>
      </div>

      {{-- ۵) تنظیمات داینامیک محصول + دکمه «بساز» (همان دکمه اصلی پروژه) --}}
      {{-- باکس «تنظیمات محصول» به دستور کاربر مخفی است (hideFields) — فقط دکمه «بساز» نمایش داده می‌شود --}}
      @include('app.partials.product-options', ['product' => $product, 'genButtonLabel' => 'بساز', 'hideFields' => true])

      {{-- ۶) تصاویر محصول --}}
      @if(count($__productImages))
      <div class="pd-gal">
        <h2>تصاویر محصول</h2>
        <div class="pd-gal-grid">
          @foreach($__productImages as $__img)
            <img src="{{ $__img }}" data-full="{{ $__img }}" alt="{{ $product->name_fa }}" loading="lazy">
          @endforeach
        </div>
      </div>
      @endif

      {{-- ۷) عکس‌های قبل --}}
      @if(count($__rawImages))
      <div class="pd-gal">
        <h2>عکس‌های قبل</h2>
        <div class="pd-gal-grid">
          @foreach($__rawImages as $__img)
            <img src="{{ $__img }}" data-full="{{ $__img }}" alt="عکس قبل — {{ $product->name_fa }}" loading="lazy">
          @endforeach
        </div>
      </div>
      @endif

    </div>
  </aside>

  {{-- ═══════ سمت چپ: نمایش بزرگ تصویر انتخاب‌شده ═══════ --}}
  <section class="pd-stage" aria-label="نمایش بزرگ تصویر محصول">

    {{-- شمارنده --}}
    <div class="pd-counter" id="pdCounter"></div>

    {{-- دکمه برگشت به صفحه قبل --}}
    <button type="button" class="pd-close" id="pdCloseBtn" title="برگشت" aria-label="برگشت به صفحه قبل">
      <i class="fa-solid fa-xmark"></i>
    </button>

    {{-- تصویر اصلی (خروجی تولید هوش مصنوعی هم روی همین تصویر می‌نشیند) --}}
    <div class="pd-main">
      <img id="pdpMainImage" src="{{ $__productImages[0] ?? $product->displayImageUrl() }}" alt="{{ $product->name_fa }}">
    </div>

  </section>

</div>

{{-- ═══════════ سکشن ۲: محصولات مشابه (اسلایدر افقی) ═══════════ --}}
@if($similar->count())
<section class="pd-similar" dir="rtl">
  <div class="page-container">

  <div class="pd-similar-head">
    <h2>محصولات مشابه</h2>
    <div class="pd-similar-nav">
      <button type="button" id="pdSimPrev" aria-label="قبلی">
        <i class="fa-solid fa-chevron-right text-[13px]"></i>
      </button>
      <button type="button" id="pdSimNext" aria-label="بعدی">
        <i class="fa-solid fa-chevron-left text-[13px]"></i>
      </button>
    </div>
  </div>

  <div class="pd-similar-track" id="pdSimTrack">
    @foreach($similar as $item)
    <a class="pd-scard" href="{{ route('app.product', $item->route_slug) }}">
      <img src="{{ $item->displayImageUrl() }}" alt="{{ $item->name_fa }}" loading="lazy">
      <div class="pd-scard-body">
        @if($item->category)
          <span class="pd-scard-cat">{{ $item->category }}</span>
        @endif
        <h3 class="pd-scard-title">{{ $item->name_fa }}</h3>
        @php $__sdesc = trim(strip_tags((string) ($item->description_fa ?: $item->description_en ?: ''))); @endphp
        @if($__sdesc !== '')
          <p class="pd-scard-desc">{{ \Illuminate\Support\Str::limit($__sdesc, 70) }}</p>
        @endif
      </div>
    </a>
    @endforeach
  </div>

  </div>
</section>
@endif

{{-- مودال میز کار هوش مصنوعی + مودال ورود --}}
@include('app.partials.product-workspace-modal', ['product' => $product])

@endsection

@push('scripts')
<script>
var GEN_URL = '{{ route('app.product.generate', $product->slug) }}';
var SAVE_URL = '{{ route('app.product.save', $product->slug) }}';
var LIKE_URL = '{{ route('app.product.like', $product->slug) }}';
var LOGIN_URL = '{{ route('login') }}';
var IS_AUTH = @json(auth()->check());
var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
var _modalTimers = [];
var _modalResultUrl = null;

/* ───── مدل‌های خروجی چندگانه (Output Variants) ───── */
var CREDIT_COST = {{ (int) ($product->credit_cost ?? 0) }};
var IS_PER_CREDIT = @json($product->pricing_model === 'per_credit');
var HAS_VARIANTS = @json(count($product->outputVariantList()) > 0);

function getSelectedVariantKeys() {
  return Array.prototype.map.call(
    document.querySelectorAll('#variantPickerGrid .variant-card.is-selected'),
    function (el) { return el.dataset.key; }
  );
}

function toggleVariantCard(el) {
  el.classList.toggle('is-selected');
  updateVariantTotal();
}

function updateVariantTotal() {
  if (!HAS_VARIANTS) return;
  var count = getSelectedVariantKeys().length;

  var countEl = document.getElementById('variantPickerCount');
  if (countEl) countEl.textContent = count ? (Number(count).toLocaleString('fa-IR') + ' مدل انتخاب شده') : 'هیچ مدلی انتخاب نشده';

  var numEl = document.getElementById('variantTokenTotalNum');
  if (numEl) {
    if (!IS_PER_CREDIT || CREDIT_COST <= 0) {
      numEl.textContent = 'رایگان';
    } else {
      numEl.textContent = Number(count * CREDIT_COST).toLocaleString('fa-IR') + ' توکن';
    }
  }
}

function renderOutputGrid(images) {
  var grid = document.getElementById('modalOutputGrid');
  var outImg = document.getElementById('modalOutputImage');
  var outPh = document.getElementById('outputPlaceholder');
  if (!grid) return;
  grid.innerHTML = '';
  images.forEach(function (img) {
    var card = document.createElement('button');
    card.type = 'button';
    card.className = 'relative rounded-xl overflow-hidden border border-white/[0.06] bg-white/[0.02] text-right group cursor-pointer';
    card.innerHTML =
      '<img src="' + img.url + '" alt="" class="w-full aspect-square object-cover">' +
      (img.title ? '<div class="px-1.5 py-1 text-[9px] font-bold text-gray-300 truncate">' + img.title + '</div>' : '');
    card.addEventListener('click', function () {
      _modalResultUrl = img.url;
      document.getElementById('pdpMainImage').src = img.url;
      document.getElementById('modalDlBtn').disabled = false;
    });
    grid.appendChild(card);
  });
  if (outImg) outImg.classList.add('hidden');
  if (outPh) outPh.classList.add('hidden');
  grid.classList.remove('hidden');
}

document.addEventListener('DOMContentLoaded', updateVariantTotal);

function openWorkspaceModal() {
  var modal = document.getElementById('workspaceModal');
  var content = document.getElementById('modalContent');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(function() {
    modal.classList.remove('opacity-0');
    content.classList.remove('scale-95');
  }, 20);
}

function closeWorkspaceModal() {
  var modal = document.getElementById('workspaceModal');
  var content = document.getElementById('modalContent');
  modal.classList.add('opacity-0');
  content.classList.add('scale-95');
  setTimeout(function() {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
  }, 300);
}

function handleModalUpload(inp) {
  if (!inp.files || !inp.files[0]) return;
  var r = new FileReader();
  r.onload = function(e) {
    var pv = document.getElementById('userImagePreview');
    var ph = document.getElementById('userImagePlaceholder');
    if(pv) {
      pv.src = e.target.result;
      pv.classList.remove('hidden');
    }
    if(ph) ph.classList.add('hidden');
  };
  r.readAsDataURL(inp.files[0]);
}

/* ───── جمع‌آوری مقادیر تنظیمات داینامیک محصول (input_schema) — رندرشده در product-options ─────
   خروجی: پیام خطا در صورت خالی بودن یک فیلد اجباری (رشته)، یا null در صورت معتبر بودن همه. */
function collectDynamicFields(fd) {
  var errorMsg = null;

  document.querySelectorAll('#pdpOptions .pdp-field').forEach(function (el) {
    var fid = el.dataset.fieldId;
    var type = el.dataset.fieldType;
    /* فیلدهای مخفی‌شده (باکس تنظیمات حذف‌شده) اجباری حساب نمی‌شوند تا جلوی ساخت را نگیرند */
    var required = el.dataset.required === '1' && el.offsetParent !== null;
    if (!fid) return;

    var labelEl = el.querySelector('label');
    var labelTxt = labelEl ? labelEl.textContent.replace('*', '').trim() : fid;

    if (type === 'radio') {
      var checkedRadio = el.querySelector('input[type="radio"]:checked');
      var val = checkedRadio ? checkedRadio.value : '';
      if (required && val === '' && !errorMsg) errorMsg = 'لطفاً «' + labelTxt + '» را انتخاب کنید.';
      fd.append('fields[' + fid + ']', val);
      return;
    }

    if (type === 'checkbox' && el.querySelectorAll('.pdp-checkbox-multi').length) {
      var vals = Array.prototype.map.call(el.querySelectorAll('.pdp-checkbox-multi:checked'), function (c) { return c.value; });
      if (required && vals.length === 0 && !errorMsg) errorMsg = 'لطفاً حداقل یک گزینه «' + labelTxt + '» را انتخاب کنید.';
      fd.append('fields[' + fid + ']', vals.join('، '));
      return;
    }

    if (type === 'checkbox' || type === 'switch') {
      var box = el.querySelector('.pdp-field-input');
      fd.append('fields[' + fid + ']', (box && box.checked) ? '1' : '0');
      return;
    }

    if (type === 'image_upload' || type === 'file_upload') {
      var fileInp = el.querySelector('.pdp-field-input');
      var file = fileInp && fileInp.files ? fileInp.files[0] : null;
      if (file) {
        fd.append('uploads[' + fid + ']', file);
      } else if (required && !errorMsg) {
        errorMsg = 'لطفاً «' + labelTxt + '» را انتخاب کنید.';
      }
      return;
    }

    // text / textarea / number / select / color / fallback
    var input = el.querySelector('.pdp-field-input');
    var value = input ? input.value : '';
    if (required && value === '' && !errorMsg) errorMsg = 'لطفاً «' + labelTxt + '» را تکمیل کنید.';
    fd.append('fields[' + fid + ']', value);
  });

  return errorMsg;
}

function triggerGeneration() {
  var errorBox = document.getElementById('modalFormError');
  var errorTxt = document.getElementById('modalFormErrorTxt');

  var fd = new FormData();
  fd.append('_token', CSRF);

  var dynErr = collectDynamicFields(fd);
  if (dynErr) {
    errorBox.classList.remove('hidden');
    errorTxt.textContent = dynErr;
    return;
  }

  // آپلود عمومی تصویر مرجع — فقط وقتی محصول فیلد آپلود اختصاصی در تنظیماتش تعریف نکرده باشد
  var fileInp = document.getElementById('modalFileInp');
  if (fileInp) {
    if (!fileInp.files || !fileInp.files[0]) {
      errorBox.classList.remove('hidden');
      errorTxt.textContent = 'لطفاً ابتدا تصویر ورودی خود را بارگذاری کنید.';
      return;
    }
    fd.append('uploads[photo]', fileInp.files[0]);
  }

  var selectedVariantKeys = HAS_VARIANTS ? getSelectedVariantKeys() : [];
  if (HAS_VARIANTS && selectedVariantKeys.length === 0) {
    errorBox.classList.remove('hidden');
    errorTxt.textContent = 'حداقل یک مدل خروجی را انتخاب کنید.';
    return;
  }
  errorBox.classList.add('hidden');

  selectedVariantKeys.forEach(function (k) { fd.append('variants[]', k); });

  var ratio = document.querySelector('input[name="modal_ratio"]:checked');
  fd.append('output[aspect_ratio]', ratio ? ratio.value : '1:1');
  fd.append('output[quality]', '1K');

  var overlay = document.getElementById('modalProgressOverlay');
  overlay.classList.remove('hidden');
  overlay.classList.add('flex');

  document.getElementById('btnModalSubmit').disabled = true;

  var steps = [
    {t: 'در حال آپلود تصویر...', s: 'ارسال امن اطلاعات به سرور پردازش', p: 20},
    {t: 'تحلیل ساختار الگو...', s: 'هوش مصنوعی در حال همگام‌سازی اجزا است', p: 45},
    {t: 'رندر و اعمال سبک...', s: 'طراحی لایه‌های نهایی تصویر', p: 75},
    {t: 'بهینه‌سازی خروجی...', s: 'شفاف‌سازی و آماده‌سازی جهت نمایش', p: 92},
  ];

  var stepIdx = 0;
  function processSteps() {
    if (stepIdx >= steps.length) return;
    var step = steps[stepIdx++];
    document.getElementById('modalPgTxt').textContent = step.t;
    document.getElementById('modalPgSub').textContent = step.s;
    document.getElementById('modalPgBar').style.width = step.p + '%';
    _modalTimers.push(setTimeout(processSteps, 3000));
  }
  processSteps();

  fetch(GEN_URL, {
    method: 'POST', body: fd,
    headers: {'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': CSRF}
  })
  .then(function(r){
    // ═══ مدیریت هوشمند خطای اتمام توکن (HTTP 402 یا عدم مجاز بودن) ═══
    if (r.status === 402) {
       _modalTimers.forEach(clearTimeout); _modalTimers = [];
       overlay.classList.add('hidden');
       document.getElementById('btnModalSubmit').disabled = false;

       // باز کردن پاپ‌آپ سراسری خرید بدون نیاز به رفرش
       if(typeof window.showTokenShortageModal === 'function') {
           window.showTokenShortageModal();
       }
       throw new Error('موجودی اعتبار توکن شما کافی نیست.');
    }
    return r.json();
  })
  .then(function(d){
    _modalTimers.forEach(clearTimeout); _modalTimers = [];
    overlay.classList.remove('flex');
    overlay.classList.add('hidden');
    document.getElementById('btnModalSubmit').disabled = false;

    if (d.success && d.image_url) {
      var outImg = document.getElementById('modalOutputImage');
      var outPh = document.getElementById('outputPlaceholder');
      var outGrid = document.getElementById('modalOutputGrid');

      if (d.images && d.images.length > 1) {
        // خروجی چندتایی — دقیقاً مدل‌هایی که کاربر تیک زده بود
        renderOutputGrid(d.images);
      } else {
        if (outGrid) outGrid.classList.add('hidden');
        outImg.src = d.image_url;
        outImg.classList.remove('hidden');
        if(outPh) outPh.classList.add('hidden');
      }

      _modalResultUrl = d.image_url;
      document.getElementById('modalDlBtn').disabled = false;

      document.getElementById('pdpMainImage').src = d.image_url;

      // اگر بعضی مدل‌ها ناموفق بودند، به کاربر اطلاع بده (بدون توقف نتیجه‌های موفق)
      if (d.failed_message) {
        errorBox.classList.remove('hidden');
        errorTxt.textContent = d.failed_message;
      }

      // ═══ به‌روزرسانی زنده و آنی مقدار توکن در دراپ‌داون پروفایل بدون رفرش ═══
      var tokenEl = document.getElementById('top-nav-tokens');
      if (tokenEl && d.remaining_tokens !== undefined) {
          tokenEl.textContent = Number(d.remaining_tokens).toLocaleString('fa-IR') + ' توکن';
      }
    } else {
      errorBox.classList.remove('hidden');
      errorTxt.textContent = d.message || 'پردازش هوش مصنوعی با خطا مواجه شد.';
    }
  })
  .catch(function(err){
    _modalTimers.forEach(clearTimeout); _modalTimers = [];
    overlay.classList.remove('flex');
    overlay.classList.add('hidden');
    document.getElementById('btnModalSubmit').disabled = false;

    if(err.message !== 'موجودی اعتبار توکن شما کافی نیست.') {
        errorBox.classList.remove('hidden');
        errorTxt.textContent = err.message || 'ارتباط با سرور برقرار نشد.';
    }
  });
}

function downloadGeneratedImage(){
  if (!_modalResultUrl) return;
  var a = document.createElement('a');
  a.href = _modalResultUrl; a.download = 'ai-product-result.png'; a.click();
}

/* ───── انتشار (اشتراک‌گذاری) ───── */
function doShare() {
  var h1 = document.querySelector('h1');
  var t = h1 ? h1.textContent.trim() : document.title;
  if (navigator.share) navigator.share({title:t, url:location.href}).catch(function(){});
  else if (navigator.clipboard) navigator.clipboard.writeText(location.href);
}
document.getElementById('btnShare')?.addEventListener('click', doShare);

/* ───── مودال «برای ادامه باید وارد شوید» ───── */
function openSaveLoginModal() {
  var modal = document.getElementById('saveLoginModal');
  var content = document.getElementById('saveLoginModalContent');
  if (!modal || !content) return;
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  setTimeout(function() {
    modal.classList.remove('opacity-0');
    content.classList.remove('scale-95');
  }, 20);
}

function closeSaveLoginModal() {
  var modal = document.getElementById('saveLoginModal');
  var content = document.getElementById('saveLoginModalContent');
  if (!modal || !content) return;
  modal.classList.add('opacity-0');
  content.classList.add('scale-95');
  setTimeout(function() {
    modal.classList.remove('flex');
    modal.classList.add('hidden');
  }, 300);
}

/* ───── دکمه سیو: مهمان → مودال ورود، لاگین‌کرده → درخواست واقعی به بک‌اند ───── */
var btnBookmark = document.getElementById('btnBookmark');
var iconBkm = document.getElementById('iconBkm');
var _saveBusy = false;

function setBookmarkUI(saved) {
  if (iconBkm) iconBkm.className = saved ? 'fa-solid fa-bookmark' : 'fa-regular fa-bookmark';
  if (btnBookmark) {
    btnBookmark.classList.toggle('is-on', saved);
    btnBookmark.dataset.saved = saved ? '1' : '0';
  }
}

btnBookmark?.addEventListener('click', function(){
  if (!IS_AUTH) {
    openSaveLoginModal();
    return;
  }
  if (_saveBusy) return;
  _saveBusy = true;

  fetch(SAVE_URL, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': CSRF,
      'Accept': 'application/json'
    }
  })
  .then(function(r){
    if (r.status === 401) { openSaveLoginModal(); throw new Error('unauthenticated'); }
    return r.json();
  })
  .then(function(d){
    if (d && d.success) setBookmarkUI(!!d.saved);
  })
  .catch(function(){})
  .finally(function(){ _saveBusy = false; });
});

/* ───── دکمه لایک: مهمان → مودال ورود، لاگین‌کرده → درخواست واقعی به بک‌اند — فعال = قرمز ───── */
var btnLike = document.getElementById('btnLike');
var iconLike = document.getElementById('iconLike');
var _likeBusy = false;

function setLikeUI(liked) {
  if (iconLike) iconLike.className = liked ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
  if (btnLike) {
    btnLike.classList.toggle('is-liked', liked);
    btnLike.dataset.liked = liked ? '1' : '0';
  }
}

btnLike?.addEventListener('click', function(){
  if (!IS_AUTH) {
    openSaveLoginModal();
    return;
  }
  if (_likeBusy) return;
  _likeBusy = true;

  fetch(LIKE_URL, {
    method: 'POST',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': CSRF,
      'Accept': 'application/json'
    }
  })
  .then(function(r){
    if (r.status === 401) { openSaveLoginModal(); throw new Error('unauthenticated'); }
    return r.json();
  })
  .then(function(d){
    if (d && d.success) setLikeUI(!!d.liked);
  })
  .catch(function(){})
  .finally(function(){ _likeBusy = false; });
});

/* ───── پاپ‌آپ توضیح توکن — با کلیک روی باکس توکن باز/بسته می‌شود ───── */
var tokenBtn = document.getElementById('pdTokenBtn');
var tokenPop = document.getElementById('pdTokenPop');
tokenBtn?.addEventListener('click', function (e) {
  e.stopPropagation();
  tokenPop.classList.toggle('show');
});
document.addEventListener('click', function (e) {
  if (tokenPop && !tokenPop.contains(e.target)) tokenPop.classList.remove('show');
});

/* ───── گالری: کلیک روی عکس‌های سمت راست → نمایش بزرگ در سمت چپ ───── */
(function () {
  function toFa(n){ return String(n).replace(/\d/g, function(d){ return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }

  var mainImg = document.getElementById('pdpMainImage');
  var counter = document.getElementById('pdCounter');
  var galleryImgs = Array.prototype.slice.call(document.querySelectorAll('.pd-gal-grid img'));

  function show(i){
    var img = galleryImgs[i];
    if (!img) return;
    mainImg.src = img.dataset.full || img.src;
    if (counter) counter.textContent = toFa(i + 1) + ' / ' + toFa(galleryImgs.length);
    galleryImgs.forEach(function (g, gi) {
      g.classList.toggle('is-viewing', gi === i);
    });
  }

  galleryImgs.forEach(function (img, i) {
    img.addEventListener('click', function(){ show(i); });
  });

  if (galleryImgs.length) show(0);
  else if (counter) counter.textContent = '';

  /* دکمه برگشت به صفحه قبل */
  document.getElementById('pdCloseBtn')?.addEventListener('click', function () {
    if (window.history.length > 1) {
      window.history.back();
    } else {
      window.location.href = '{{ route('app.home') }}';
    }
  });

  /* اسلایدر محصولات مشابه */
  var track = document.getElementById('pdSimTrack');
  if (track) {
    var step = 268; /* عرض کارت + فاصله */
    document.getElementById('pdSimNext')?.addEventListener('click', function () {
      track.scrollBy({ left: -step * 2, behavior: 'smooth' });
    });
    document.getElementById('pdSimPrev')?.addEventListener('click', function () {
      track.scrollBy({ left: step * 2, behavior: 'smooth' });
    });
  }
}());
</script>
@endpush
