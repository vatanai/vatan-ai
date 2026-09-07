@extends('layouts.app')

@section('page_title', 'پیش‌نمایش بساز محصول | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-workspace.css') }}?v={{ filemtime(public_path('css/create-workspace.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/create-product-preview.css') }}?v={{ filemtime(public_path('css/create-product-preview.css')) }}">
@endpush

@section('content')
<div class="create-product-preview-page" dir="rtl">
  <div class="create-product-preview-banner">
    <span class="create-product-preview-banner__eyebrow">پیش‌نمایش مستقل</span>
    <h1>طرح صفحه «بساز محصول»</h1>
    <p>این صفحه فقط برای دیدن و تأیید طراحی است. نسخه‌ی اصلی هنوز هیچ تغییری نکرده است.</p>
    <span class="create-product-preview-banner__product">{{ $product->name_fa ?: $product->name_en }}</span>
  </div>

  @include('app.partials.create-workspace', [
      'product' => $buildProduct,
      'previewMode' => true,
      'instance' => 'product-preview',
  ])
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/create-workspace.js') }}?v={{ filemtime(public_path('js/create-workspace.js')) }}"></script>
@endpush
