@extends('layouts.app')

@php
  $name = $product->name_fa ?: $product->name_en;
  $maxAngles = (int) $packConfig['max_extra_angles'];
  $maxPhotos = 1 + $maxAngles;
  $demo = fn ($n) => \App\Support\AppAsset::url('assets/img/product-shots/demo/' . $n . '.webp');
  $faNum = fn ($v) => strtr((string) $v, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
@endphp

@section('page_title', 'ساخت عکس برای ' . $name . ' | وطن AI')

@push('meta')
  <meta name="robots" content="noindex,nofollow">
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/productshot-common.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/product-create.css') }}">
@endpush

@section('content')
<div class="pc" dir="rtl" data-product-create>

  {{-- سربرگ --}}
  <header class="pc-top">
    @include('app.productshot-v2.partials.back', ['href' => $productUrl, 'label' => 'برگشت به صفحه‌ی محصول'])
    <img class="pc-top-thumb" src="{{ $packConfig['product']['cover'] }}" alt="" width="48" height="48" decoding="async">
    <div class="pc-top-title">
      <span class="pc-eyebrow">استودیو محصول</span>
      <h1>{{ $name }}</h1>
    </div>
    <a class="pc-top-link" href="{{ $productUrl }}">نمونه‌ها و جزئیات</a>
  </header>

  <div class="pc-grid">
    <div class="pc-main">

      {{-- نوار گام‌ها (هم‌عرض ستون اصلی) --}}
      <nav class="pc-steps" aria-label="مراحل ساخت">
        <ol>
          <li data-step-item="1" class="is-active"><span>۱</span>عکس محصول</li>
          <li data-step-item="2"><span>۲</span>صحنه‌ها</li>
          <li data-step-item="3"><span>۳</span>کیفیت</li>
          <li data-step-item="4"><span>۴</span>ساخت</li>
        </ol>
      </nav>

      {{-- ── گام ۱: عکس محصول (یک باکس، ۱ تا ۶ عکس) ── --}}
      <section class="pc-card" id="pc-s1" data-section="1" aria-labelledby="pc-h1">
        <header class="pc-card-head">
          <div>
            <h2 id="pc-h1">عکس محصولت را بگذار</h2>
            <p>@if($maxAngles > 0)یک عکس هم کافی است؛ بهترین نتیجه با <b>۳ عکس روی پس‌زمینه‌ی سفید</b> از ۳ زاویه است:<br> روبه‌رو، سه‌رخ چپ و سه‌رخ راست. تا {{ $faNum($maxPhotos) }} عکس می‌توانی بگذاری.@else یک عکس واضح از روبه‌روی محصول روی پس‌زمینه‌ی سفید بگذار.@endif</p>
          </div>
          <span class="pc-counter" data-upload-counter>{{ $faNum(0) }} از {{ $faNum($maxPhotos) }} عکس</span>
        </header>

        <input class="pc-sr" type="file" id="pc-up-input" accept="image/jpeg,image/png,image/webp" @if($maxPhotos > 1) multiple @endif data-upload-input aria-label="انتخاب عکس محصول">

        <div class="pc-up" data-upload-box>
          {{-- حالت خالی: موشن آموزشی + انتخاب عکس --}}
          <label class="pc-up-empty" for="pc-up-input" data-upload-empty>
            @if($maxAngles > 0)
              <span class="pc-demo" aria-hidden="true">
                <span class="pc-demo-tag"><b class="t-bad"><i class="fa-solid fa-xmark"></i> اشتباه</b><b class="t-ok"><i class="fa-solid fa-check"></i> درست</b></span>
                <span class="pc-demo-stage">
                  {{-- صحنه‌ی ۱: اشتباه — یک عکس کج، تاریک، بریده و روی پس‌زمینه‌ی شلوغ --}}
                  <span class="pc-demo-a">
                    <span class="pc-demo-vf pc-demo-vf-bad">
                      <span class="pc-demo-corners"></span>
                      <img class="pc-demo-wrong" src="{{ $demo('wrong') }}" alt="" decoding="async">
                    </span>
                    <span class="pc-demo-stamp"><i class="fa-solid fa-xmark"></i></span>
                    <span class="pc-demo-flags"><em class="f1">پس‌زمینه‌ی شلوغ</em><em class="f2">کج و بریده</em><em class="f3">تاریک</em></span>
                  </span>
                  {{-- صحنه‌ی ۲: درست — پس‌زمینه‌ی سفید و ۳ زاویه، یکی‌یکی عکس گرفته می‌شود --}}
                  <span class="pc-demo-b">
                    <span class="pc-demo-vf pc-demo-vf-ok">
                      <span class="pc-demo-corners"></span>
                      <img class="pc-demo-view v1" src="{{ $demo('front') }}" alt="" decoding="async">
                      <img class="pc-demo-view v2" src="{{ $demo('left') }}" alt="" decoding="async">
                      <img class="pc-demo-view v3" src="{{ $demo('right') }}" alt="" decoding="async">
                      <span class="pc-demo-flash"></span>
                      <span class="pc-demo-angle"><b class="a1">روبه‌رو</b><b class="a2">سه‌رخ چپ</b><b class="a3">سه‌رخ راست</b></span>
                    </span>
                    <span class="pc-demo-slots">
                      <span class="pc-demo-slot s1"><span class="pc-demo-shot"><img src="{{ $demo('front') }}" alt="" decoding="async"></span><i class="fa-solid fa-check"></i><small>روبه‌رو</small></span>
                      <span class="pc-demo-slot s2"><span class="pc-demo-shot"><img src="{{ $demo('left') }}" alt="" decoding="async"></span><i class="fa-solid fa-check"></i><small>سه‌رخ چپ</small></span>
                      <span class="pc-demo-slot s3"><span class="pc-demo-shot"><img src="{{ $demo('right') }}" alt="" decoding="async"></span><i class="fa-solid fa-check"></i><small>سه‌رخ راست</small></span>
                    </span>
                  </span>
                </span>
                <span class="pc-demo-captions">
                  <span class="pc-demo-c1"><strong>یک عکس کج، روی پس‌زمینه‌ی شلوغ</strong><small>محصول ناقص و تاریک دیده می‌شود و نتیجه دقیق نمی‌شود</small></span>
                  <span class="pc-demo-c2"><strong>پس‌زمینه‌ی سفید + ۳ زاویه</strong><small>روبه‌رو، سه‌رخ چپ و سه‌رخ راست؛ شکل و برچسب دقیق حفظ می‌شود</small></span>
                </span>
                <span class="pc-demo-dots"><span class="pc-demo-d1"></span><span class="pc-demo-d2"></span></span>
              </span>
            @endif
            <span class="pc-up-cta">
              <span class="pc-btn pc-btn-brand pc-up-btn"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> انتخاب عکس{{ $maxAngles > 0 ? '‌ها' : '' }}</span>
              <small>یا اینجا بکش و رها کن · JPG، PNG یا WEBP · حداکثر {{ $faNum($packConfig['max_upload_mb']) }} مگابایت{{ $maxAngles > 0 ? ' · تا ' . $faNum($maxPhotos) . ' عکس' : '' }}</small>
            </span>
          </label>

          {{-- حالت پر: عکس‌های انتخاب‌شده داخل همان باکس --}}
          <div class="pc-up-filled" data-upload-filled hidden>
            <div class="pc-up-list" data-upload-list></div>
            @if($maxAngles > 0)
              <div class="pc-up-angles" data-upload-angles aria-live="polite"></div>
            @endif
          </div>
        </div>

        {{-- عکس از لینک محصول (سایت خود کاربر یا هر فروشگاه دیگر) --}}
        <div class="pc-link" data-link>
          <div class="pc-link-head">
            <strong><i class="fa-solid fa-link" aria-hidden="true"></i> یا عکس‌ها را از لینک محصول بیاور</strong>
            <span>لینک صفحه‌ی محصول در سایت خودت یا هر فروشگاه دیگری را بگذار؛ عکس‌هایش را نشان می‌دهیم تا انتخاب کنی.</span>
          </div>
          <form class="pc-link-form" data-link-form novalidate>
            <input class="pc-link-input" type="url" inputmode="url" dir="ltr" autocomplete="url" placeholder="https://example.com/product/..." data-link-input aria-label="لینک صفحه‌ی محصول">
            <button type="submit" class="pc-btn pc-btn-ghost pc-link-go" data-link-go><span data-link-icon><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span> <span>دریافت عکس‌ها</span></button>
          </form>
          <p class="pc-link-msg" data-link-msg hidden role="status"></p>
          <div class="pc-link-result" data-link-result hidden>
            <div class="pc-link-meta"><span class="pc-link-title" data-link-title></span><span class="pc-link-count" data-link-count></span></div>
            <div class="pc-link-grid" data-link-grid role="group" aria-label="عکس‌های پیداشده"></div>
            <div class="pc-link-actions">
              <button type="button" class="pc-btn pc-btn-brand" data-link-add disabled><i class="fa-solid fa-plus" aria-hidden="true"></i> <span>افزودن عکس‌های انتخابی</span></button>
              <button type="button" class="pc-btn pc-btn-ghost" data-link-close>بستن</button>
            </div>
          </div>
        </div>

        {{-- بررسی هوشمند تصویر ورودی (اختیاری) --}}
        <div class="pc-smart">
          <div class="pc-smart-copy">
            <strong><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> بررسی هوشمند تصویر ورودی</strong>
            <span>اختیاری · هوش مصنوعی عکس‌هایت را چک می‌کند و کوتاه می‌گوید چه چیزی را بهتر کنی.</span>
          </div>
          <button type="button" class="pc-btn pc-btn-ghost pc-smart-btn" data-smart disabled><span data-smart-icon><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></span> <span data-smart-label>بررسی هوشمند</span></button>
        </div>

        <div class="pc-check" hidden data-check aria-live="polite"></div>

        <p class="pc-honest">شکل و رنگ محصول دقیق حفظ می‌شود؛ متن‌های خیلی ریز روی بسته ممکن است کامل حفظ نشوند.</p>
      </section>

      {{-- ── گام ۲: صحنه‌ها ── --}}
      <section class="pc-card" id="pc-s2" data-section="2" aria-labelledby="pc-h2">
        <header class="pc-card-head">
          <div>
            <h2 id="pc-h2">صحنه‌ها را انتخاب کن</h2>
            <p>هر صحنه‌ای را که می‌خواهی انتخاب کن.<br class="pc-br-m"> هر صحنه یک عکس جدا می‌سازد.</p>
          </div>
          <span class="pc-counter" data-shot-counter>—</span>
        </header>
        <div class="pc-shots" data-shots role="group" aria-label="صحنه‌ها"></div>
        <button type="button" class="pc-more-btn" data-more aria-expanded="false" hidden></button>
      </section>

      {{-- ── گام ۳: کیفیت ── --}}
      <section class="pc-card" id="pc-s3" data-section="3" aria-labelledby="pc-h3">
        <header class="pc-card-head">
          <div>
            <h2 id="pc-h3">کیفیت خروجی</h2>
            <p>هزینه‌ی هر عکس به کیفیت انتخابی بستگی دارد.</p>
          </div>
        </header>
        <div class="pc-qualities" role="radiogroup" aria-label="کیفیت خروجی" data-qualities></div>
      </section>

      {{-- ── باکس «بساز» موبایل: شناور تا وقتی جای طبیعی‌اش (زیر کیفیت) برسد ── --}}
      <div class="pc-dock-wrap" data-dock-wrap>
        <div class="pc-dock" data-dock>
          <div class="pc-dock-info"><b data-bar-summary>—</b><span data-bar-balance></span></div>
          <button type="button" class="pc-dock-cta" data-build disabled><i class="fa-solid fa-bolt" aria-hidden="true"></i> <span>بساز</span></button>
        </div>
      </div>

      {{-- ── گام ۴: نتیجه ── --}}
      <section class="pc-card" id="pc-s4" data-section="4" data-results hidden aria-labelledby="pc-h4">
        <header class="pc-results-head">
          <div>
            <h2 id="pc-h4">عکس‌های تو</h2>
            <p data-results-status aria-live="polite"></p>
          </div>
          <a class="pc-btn pc-btn-ghost" hidden data-download href="#"><i class="fa-solid fa-download" aria-hidden="true"></i> دانلود همه</a>
        </header>
        <div class="pc-tiles" data-tiles></div>
      </section>
    </div>

    {{-- ── خلاصه‌ی سفارش (دسکتاپ، ستون راست) ── --}}
    <aside class="pc-side" aria-label="خلاصه‌ی سفارش">
      <div class="pc-side-card">
        <h2>خلاصه‌ی سفارش</h2>
        <div class="pc-side-slider" data-sum-slider aria-roledescription="اسلایدر" aria-label="نمونه‌ی صحنه‌های انتخابی">
          <div class="pc-side-slides" data-sum-slides></div>
          <span class="pc-side-count" data-sum-count aria-hidden="true"></span>
          <span class="pc-side-cap" data-sum-cap aria-live="off"></span>
        </div>
        <dl class="pc-side-rows">
          <div><dt>صحنه‌ها</dt><dd data-sum-shots>—</dd></div>
          <div><dt>کیفیت</dt><dd data-sum-quality>—</dd></div>
          <div><dt>عکس محصول</dt><dd data-sum-uploads>هنوز آپلود نشده</dd></div>
          <div><dt>موجودی تو</dt><dd data-sum-balance>—</dd></div>
        </dl>
        <div class="pc-total">
          <div>
            <span>هزینه‌ی این ساخت</span>
            <strong data-bar-summary>—</strong>
          </div>
          <small data-bar-balance></small>
        </div>
        <button type="button" class="pc-btn pc-btn-brand pc-build" data-build disabled><i class="fa-solid fa-bolt" aria-hidden="true"></i> <span>بساز</span></button>
        <p class="pc-note">اگر عکسی ساخته نشود، اعتبارش خودکار برمی‌گردد.</p>
      </div>
    </aside>
  </div>

  {{-- ── هشدار اعتبار ── --}}
  <div class="pc-alert" data-credit-alert hidden role="dialog" aria-modal="true" aria-labelledby="pc-alert-title">
    <div class="pc-alert-backdrop" data-credit-alert-close></div>
    <div class="pc-alert-card">
      <span class="pc-alert-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span>
      <h2 id="pc-alert-title">اعتبار کافی نیست</h2>
      <p data-credit-alert-message></p>
      <div class="pc-alert-actions">
        <button type="button" class="pc-btn pc-btn-ghost" data-credit-alert-close>کم‌کردن صحنه‌ها</button>
        <a href="{{ $packConfig['pricing_url'] }}" class="pc-btn pc-btn-brand">افزایش اعتبار</a>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="product-pack-config">@json($packConfig)</script>
@endsection

@push('scripts')
  <script src="{{ \App\Support\AppAsset::url('js/product-create.js') }}" defer></script>
@endpush
