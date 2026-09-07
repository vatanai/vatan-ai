@extends('layouts.app')

@section('page_title', 'صفحه های بساز نمونه | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-samples-workspace.css') }}?v={{ filemtime(public_path('css/create-samples-workspace.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/create-samples.css') }}?v={{ filemtime(public_path('css/create-samples.css')) }}">
@endpush

@section('content')
<div class="create-samples-page" dir="rtl">
  <header class="create-samples-header">
    <div>
      <span class="create-samples-eyebrow">مقایسه نسخه‌های بکاپ</span>
      <h1>صفحه های بساز نمونه</h1>
      <p>دو نسخه‌ی قبلی را جداگانه و پشت‌سرهم ببینید و مشخص کنید کدام نسخه باید حفظ شود.</p>
    </div>
    <span class="create-samples-badge"><i class="fa-solid fa-lock"></i> فقط نمایش</span>
  </header>

  <a class="create-product-preview-link" href="{{ route('app.create.product.preview', ['product' => $product->route_slug]) }}">
    <span class="create-product-preview-link__icon"><i class="fa-solid fa-eye"></i></span>
    <span><strong>پیش‌نمایش مستقل «بساز محصول»</strong><small>این نسخه برای تأیید شماست و هنوز به لینک اصلی وصل نشده.</small></span>
    <i class="fa-solid fa-arrow-left"></i>
  </a>

  <section class="create-sample-card create-sample-card--product" aria-labelledby="sample-product-title">
    <div class="create-sample-label">
      <div>
        <span>نسخه‌ی اول</span>
        <h2 id="sample-product-title">بساز محصول — نسخه‌ی بکاپ</h2>
        <p>محتوای `create-product.blade.php` و فضای ساخت محصول از بکاپ.</p>
      </div>
      <span class="create-sample-source">`create-product.blade.php`</span>
    </div>

    @include('app.partials.create-samples-workspace', [
        'product' => $buildProduct,
        'previewMode' => false,
        'instance' => 'backup-product',
    ])
  </section>

  <section class="create-sample-card create-sample-card--create" aria-labelledby="sample-create-title">
    <div class="create-sample-label">
      <div>
        <span>نسخه‌ی دوم</span>
        <h2 id="sample-create-title">بساز — نسخه‌ی بکاپ</h2>
        <p>نسخه‌ی عمومی `create.blade.php` از بکاپ، با چیدمان بازطراحی‌شده.</p>
      </div>
      <span class="create-sample-source">`create.blade.php`</span>
    </div>

    @include('app.partials.create-samples-workspace', [
        'product' => $buildProduct,
        'previewMode' => false,
        'instance' => 'redesign',
    ])
  </section>

  <section class="create-sample-card create-sample-card--product" aria-labelledby="sample-product-2-title">
    <div class="create-sample-label">
      <div>
        <span>نسخه‌ی سوم</span>
        <h2 id="sample-product-2-title">بساز محصول — بکاپ دوم</h2>
        <p>همان صفحه‌ی `create-product.blade.php` از مسیر بکاپ دوم.</p>
      </div>
      <span class="create-sample-source">`app 2/resources/views/app/create-product.blade.php`</span>
    </div>

    @include('app.partials.create-samples-workspace', [
        'product' => $buildProduct,
        'previewMode' => false,
        'instance' => 'backup2-product',
    ])
  </section>

  <section class="create-sample-card create-sample-card--create" aria-labelledby="sample-create-2-title">
    <div class="create-sample-label">
      <div>
        <span>نسخه‌ی چهارم</span>
        <h2 id="sample-create-2-title">بساز — بکاپ دوم</h2>
        <p>همان صفحه‌ی `create.blade.php` از مسیر بکاپ دوم؛ همان نسخه‌ای که کلیک از محصول به آن می‌رسید.</p>
      </div>
      <span class="create-sample-source">`app 2/resources/views/app/create.blade.php`</span>
    </div>

    @include('app.partials.create-samples-workspace', [
        'product' => $buildProduct,
        'previewMode' => false,
        'instance' => 'backup2-redesign',
    ])
  </section>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/create-samples-workspace.js') }}?v={{ filemtime(public_path('js/create-samples-workspace.js')) }}"></script>
@endpush
