@extends('layouts.app')

@section('page_title', 'پیش‌نمایش صفحه بساز | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-workspace.css') }}">
@endpush

@section('content')
  @include('app.partials.create-workspace', ['product' => $previewProduct, 'previewMode' => true])
@endsection

@push('scripts')
  <script src="{{ asset('js/create-workspace.js') }}?v={{ filemtime(public_path('js/create-workspace.js')) }}"></script>
@endpush
