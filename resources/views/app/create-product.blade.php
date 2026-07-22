@extends('layouts.app')

@section('page_title', 'ساخت ' . ($product->name_fa ?: $product->name_en) . ' | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-workspace.css') }}">
@endpush

@section('content')
  @include('app.partials.create-workspace', ['product' => $buildProduct, 'previewMode' => false])
@endsection

@push('scripts')
  <script src="{{ asset('js/create-workspace.js') }}"></script>
@endpush
