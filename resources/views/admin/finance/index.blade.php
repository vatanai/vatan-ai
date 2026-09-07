@extends('layouts.admin')
@section('title', ($sections[$section] ?? 'مالی') . ' — وطن استودیو')
@push('styles')
  <link rel="stylesheet" href="{{ asset('admin/css/finance.css') }}?v={{ filemtime(public_path('admin/css/finance.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content finance-page flex-1 overflow-y-auto" id="content" dir="rtl">
    @include('admin.finance.partials.messages')
    @include('admin.finance.partials.page-header')
    @include('admin.finance.partials.nav')
    @include('admin.finance.sections.' . (in_array($section, ['expenses', 'income'], true) ? 'transactions' : $section))
  </div>
</main>
@endsection
