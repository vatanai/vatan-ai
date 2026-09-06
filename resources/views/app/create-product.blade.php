@extends('layouts.app')

@section('page_title', 'ساخت ' . ($product->name_fa ?: $product->name_en) . ' | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-samples-workspace.css') }}?v={{ filemtime(public_path('css/create-samples-workspace.css')) }}">
@endpush

@section('content')
  <div class="create-page-compare" dir="rtl">
    <button type="button" class="create-page-close" data-create-page-close title="برگشت" aria-label="برگشت به صفحه قبل">
      <i class="fa-solid fa-xmark" aria-hidden="true"></i>
    </button>
    <section class="create-section create-section--redesign" data-create-section="redesign" aria-label="صفحه‌ی بساز محصول">
      @include('app.partials.create-samples-workspace', [
          'product' => $buildProduct,
          'previewMode' => false,
          'instance' => 'redesign',
          'galleryItem' => $galleryItem ?? null,
      ])
    </section>
  </div>
@endsection

@push('scripts')
  <script src="{{ asset('js/create-samples-workspace.js') }}?v={{ filemtime(public_path('js/create-samples-workspace.js')) }}"></script>
  <script>
    document.querySelector('[data-create-page-close]')?.addEventListener('click', function () {
      if (window.history.length > 1) window.history.back();
      else window.location.href = @json(route('app.home'));
    });
  </script>
@endpush
