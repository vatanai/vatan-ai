@extends('layouts.app')

@section('page_title', 'بساز | انتخاب محصول | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/create-products.css') }}">
@endpush

@section('content')
<div class="create-products-page" dir="rtl">
  <header class="create-products-header page-container">
    <div>
      <span class="create-products-eyebrow">استودیوی ساخت وطن</span>
      <h1>با چی شروع کنیم؟</h1>
      <p>محصول موردنظرت را انتخاب کن و مستقیم وارد فضای ساخت شو.</p>
    </div>
  </header>

  <section class="create-products-section page-container" aria-label="محصولات">
    @if($products->isNotEmpty())
      <div class="create-products-list" aria-label="فهرست کامل محصولات">
        <div class="create-products-track">
          @foreach($products as $product)
            <a class="create-product-card" href="{{ route('app.product', $product->route_slug) }}" aria-label="{{ $product->name_fa ?: $product->name_en }}">
              <div class="create-product-card-media">
                <img src="{{ $product->displayImageUrl() }}" alt="{{ $product->name_fa ?: $product->name_en }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                <div class="create-product-card-overlay"></div>
                <div class="create-product-card-info">
                  <h3>{{ $product->name_fa ?: $product->name_en }}</h3>
                  <div class="create-product-card-details">
                    <span class="create-product-card-category">{{ $product->subcategory ?: ($product->category ?: 'عمومی') }}</span>
                    <span class="create-product-card-credit"><i class="fa-solid fa-bolt"></i> {{ (int) $product->credit_cost === 0 ? 'رایگان' : number_format((int) $product->credit_cost) . ' اعتبار' }}</span>
                  </div>
                </div>
              </div>
            </a>
          @endforeach
        </div>
      </div>
      <div class="create-products-scroll-hint"><i class="fa-solid fa-arrows-up-down"></i><span>همه‌ی محصولات در همین صفحه و به‌صورت عمودی نمایش داده می‌شوند</span></div>
    @else
      <div class="create-products-empty"><i class="fa-regular fa-folder-open"></i><strong>هنوز محصولی آماده نیست</strong><span>محصولات فعال سایت اینجا نمایش داده می‌شوند.</span></div>
    @endif
  </section>
</div>
@endsection
