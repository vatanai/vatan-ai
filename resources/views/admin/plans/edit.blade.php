@extends('layouts.admin')
@section('title', 'ویرایش پلن — وطن استودیو')
@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/plan-builder.css') }}?v={{ filemtime(public_path('admin/css/plan-builder.css')) }}">
<link rel="stylesheet" href="{{ asset('css/plan-cards.css') }}?v={{ filemtime(public_path('css/plan-cards.css')) }}">
@endpush
@section('content')
<main class="flex-1 min-h-screen flex flex-col min-w-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto pb-page" id="content">
    @include('admin.plans.partials.form', ['editing' => true])
  </div>
</main>
@endsection
@section('scripts')@include('admin.plans.partials.form-scripts')@endsection
