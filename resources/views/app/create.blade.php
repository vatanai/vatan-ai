@extends('layouts.app')

@section('page_title', ($product?->name_fa ?: $product?->name_en ?: 'بساز') . ' | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-workspace.css') }}?v={{ filemtime(public_path('css/create-workspace.css')) }}">
@endpush

@section('content')
<div class="create-page-compare" dir="rtl">
  @if($buildProduct)
    <section class="create-section create-section--redesign" data-create-section="redesign" aria-label="صفحه‌ی بساز">
      @include('app.partials.create-workspace', ['product' => $buildProduct, 'previewMode' => false, 'instance' => 'redesign'])
    </section>
  @else
    <div class="create-empty-state">در حال حاضر محصول فعالی برای ساخت وجود ندارد.</div>
  @endif
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/create-workspace.js') }}?v={{ filemtime(public_path('js/create-workspace.js')) }}"></script>
@endpush
