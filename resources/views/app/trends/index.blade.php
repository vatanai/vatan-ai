@extends('layouts.app')

@section('page_title', 'ترندز | وطن AI')

@section('content')
  @include('app.trends.partials.page', [
    'trendProducts' => $trendProducts,
    'trendBanners' => $trendBanners,
  ])
@endsection

@push('styles')
  @include('app.trends.partials.styles')
@endpush

@push('scripts')
  @include('app.trends.partials.scripts')
@endpush
