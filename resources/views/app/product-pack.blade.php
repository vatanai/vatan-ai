@extends('layouts.app')

@section('page_title', 'ساخت ' . ($product->name_fa ?: $product->name_en) . ' | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/product-pack.css') }}">
@endpush

@section('content')
@php
  $collage = app(\App\Services\ProductShots\ShotPackService::class)->collage($product);
@endphp
<div class="pp" dir="rtl" data-product-pack>
  <section class="pp-hero" aria-label="معرفی پک">
    <div class="pp-hero-collage pp-collage-{{ count($collage) }}" aria-hidden="true">
      @foreach($collage as $url)<img src="{{ $url }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}">@endforeach
    </div>
    <div class="pp-hero-copy">
      <span class="pp-kicker"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> پک شات محصول</span>
      <h1>{{ $product->name_fa ?: $product->name_en }}</h1>
      <p>{{ $product->description_fa ?: 'یک عکس از محصولت بده؛ چند عکس تبلیغاتی سینمایی با همان شکل و رنگ محصول بگیر.' }}</p>
    </div>
  </section>

  <div class="pp-layout">
    {{-- ── گام ۱: عکس محصول ── --}}
    <section class="pp-step" aria-labelledby="pp-step1-title">
      <header class="pp-step-head"><span class="pp-step-num">۱</span><div><h2 id="pp-step1-title">عکس‌های محصولت</h2><p>یک عکس واضح از روبه‌رو الزامی است؛ تا سه زاویه‌ی مکمل هم می‌توانی اضافه کنی.</p></div></header>

      <div class="pp-uploads">
        <label class="pp-drop pp-drop-main" data-slot="0">
          <input type="file" accept="image/jpeg,image/png,image/webp" data-slot-input="0">
          <span class="pp-drop-empty"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i><strong>انتخاب عکس محصول</strong><small>JPG، PNG یا WEBP · حداکثر {{ $packConfig['max_upload_mb'] }} مگابایت</small></span>
          <img alt="عکس محصول" hidden data-slot-img>
          <span class="pp-slot-badge" hidden data-slot-badge></span>
        </label>
        @for($i = 1; $i <= $packConfig['max_extra_angles']; $i++)
          <label class="pp-drop pp-drop-angle" data-slot="{{ $i }}">
            <input type="file" accept="image/jpeg,image/png,image/webp" data-slot-input="{{ $i }}">
            <span class="pp-drop-empty"><i class="fa-solid fa-plus" aria-hidden="true"></i><small>زاویه‌ی {{ [1=>'دوم',2=>'سوم',3=>'چهارم'][$i] ?? $i }} (اختیاری)</small></span>
            <img alt="زاویه‌ی دیگر محصول" hidden data-slot-img>
            <span class="pp-slot-badge" hidden data-slot-badge></span>
            <button type="button" class="pp-slot-remove" hidden data-slot-remove aria-label="حذف این عکس"><i class="fa-solid fa-xmark"></i></button>
          </label>
        @endfor
      </div>

      <div class="pp-check" hidden data-check aria-live="polite"></div>
      <div class="pp-sheet-check" data-sheet-check>
        <div><strong>کنترل مجموعه و پروداکت‌شیت</strong><span>هوش مصنوعی یکسان‌بودن محصول، پوشش زاویه‌ها و کیفیت کل مجموعه را بررسی می‌کند.</span></div>
        <button type="button" class="pp-btn pp-btn-ghost" data-check-set disabled><i class="fa-solid fa-magnifying-glass-chart"></i> بررسی مجموعه</button>
      </div>
      <div class="pp-product-sheet" data-product-sheet hidden><img alt="پروداکت‌شیت چندزاویه‌ای" data-product-sheet-img><div data-product-sheet-copy></div></div>

      <ul class="pp-tips">
        <li><i class="fa-regular fa-sun" aria-hidden="true"></i> نور روز، کنار پنجره</li>
        <li><i class="fa-solid fa-square" aria-hidden="true"></i> پس‌زمینه‌ی ساده</li>
        <li><i class="fa-solid fa-expand" aria-hidden="true"></i> کل محصول داخل کادر</li>
      </ul>
      <p class="pp-honest"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> شکل و رنگ محصول را دقیق حفظ می‌کنیم؛ متن‌های خیلی ریز روی بسته ممکن است کامل حفظ نشود. برای بهترین نتیجه، عکس واضح از جلوی محصول بده.</p>
    </section>

    {{-- ── گام ۲: بسته‌ی شات ── --}}
    <section class="pp-step" aria-labelledby="pp-step2-title">
      <header class="pp-step-head"><span class="pp-step-num">۲</span><div><h2 id="pp-step2-title">بسته‌ی شات</h2><p>بسته‌ی آماده از قبل انتخاب شده؛ اگر بخواهی شات‌ها را کم و زیاد کن.</p></div></header>
      <div class="pp-shots" data-shots role="group" aria-label="شات‌ها"></div>
      <button type="button" class="pp-more" data-more aria-expanded="false" hidden><i class="fa-solid fa-sliders" aria-hidden="true"></i> شات‌ها را شخصی‌سازی کن</button>
    </section>

    {{-- ── گام ۳: کیفیت ── --}}
    <section class="pp-step" aria-labelledby="pp-step3-title">
      <header class="pp-step-head"><span class="pp-step-num">۳</span><div><h2 id="pp-step3-title">کیفیت خروجی</h2><p>مدل و اعتبار هر سطح از قبل برای همین محصول تنظیم شده است.</p></div></header>
      <div class="pp-ratios" role="radiogroup" aria-label="کیفیت خروجی" data-qualities></div>
    </section>

    {{-- ── نتیجه ── --}}
    <section class="pp-step pp-results" hidden data-results aria-labelledby="pp-results-title">
      <header class="pp-step-head pp-results-head">
        <div><h2 id="pp-results-title">پک تو</h2><p data-results-status aria-live="polite"></p></div>
        <a class="pp-btn pp-btn-ghost" hidden data-download href="#"><i class="fa-solid fa-download" aria-hidden="true"></i> دانلود همه</a>
      </header>
      <div class="pp-slider" data-slider>
        <button type="button" class="pp-slider-arrow pp-slider-prev" data-slider-prev aria-label="اسلاید قبلی" hidden><i class="fa-solid fa-chevron-right"></i></button>
        <div class="pp-slider-viewport" data-slider-viewport>
          <div class="pp-tiles" data-tiles></div>
        </div>
        <button type="button" class="pp-slider-arrow pp-slider-next" data-slider-next aria-label="اسلاید بعدی" hidden><i class="fa-solid fa-chevron-left"></i></button>
        <div class="pp-slider-footer" data-slider-footer hidden>
          <div class="pp-slider-dots" data-slider-dots aria-label="انتخاب اسلاید"></div>
          <span class="pp-slider-counter" data-slider-counter></span>
        </div>
      </div>
    </section>
  </div>

  <div class="pp-bar" data-bar>
    <div class="pp-bar-info">
      <strong data-bar-summary>—</strong>
      <span data-bar-balance></span>
    </div>
    <button type="button" class="pp-btn pp-btn-brand" data-build disabled><i class="fa-solid fa-bolt" aria-hidden="true"></i> <span>بساز</span></button>
  </div>

  <div class="pp-alert" data-credit-alert hidden role="dialog" aria-modal="true" aria-labelledby="pp-credit-alert-title">
    <div class="pp-alert-backdrop" data-credit-alert-close></div>
    <div class="pp-alert-card">
      <span class="pp-alert-icon"><i class="fa-solid fa-wallet"></i></span>
      <h2 id="pp-credit-alert-title">اعتبار کافی نیست</h2>
      <p data-credit-alert-message></p>
      <div class="pp-alert-actions">
        <button type="button" class="pp-btn pp-btn-ghost" data-credit-alert-close>کم‌کردن شات‌ها</button>
        <a href="{{ $packConfig['pricing_url'] }}" class="pp-btn pp-btn-brand">افزایش اعتبار</a>
      </div>
    </div>
  </div>
</div>

<script type="application/json" id="product-pack-config">@json($packConfig)</script>
@endsection

@push('scripts')
  <script src="{{ \App\Support\AppAsset::url('js/product-pack.js') }}" defer></script>
@endpush
