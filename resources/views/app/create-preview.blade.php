@extends('layouts.app')

@section('page_title', 'پیش‌نمایش صفحه بساز | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/create-workspace.css') }}">
@endpush

@section('content')
  @include('app.partials.create-workspace', ['product' => $previewProduct, 'previewMode' => true])
@endsection

@push('scripts')
  <script src="{{ \App\Support\AppAsset::url('js/create-workspace.js') }}"></script>
@endpush
