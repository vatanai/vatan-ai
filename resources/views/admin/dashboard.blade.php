@extends('layouts.admin')
@section('title', 'داشبورد — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/admin.css') }}">
@endpush

@section('content')

{{-- ══ PAGE CONTENT (SPA sections) ══ --}}
<div id="content" style="padding:20px 24px; scrollbar-width:none; -ms-overflow-style:none;">

  @include('admin.partials.pages.dashboard-main')
  @include('admin.partials.pages.crm')
  @include('admin.partials.pages.misc')
  @include('admin.partials.pages.products-dashboard')
  @include('admin.partials.pages.products-list')
  @include('admin.partials.pages.products-create')
  @include('admin.partials.pages.products-categories')
  @include('admin.partials.pages.products-pricing')

  {{-- ══ AI PAGES ══ --}}
  @include('admin.partials.pages.ai-hub')
  @include('admin.partials.pages.ai-models')
  @include('admin.partials.pages.ai-prompts')
  @include('admin.partials.pages.ai-logs')

</div>{{-- #content --}}

@endsection

@section('scripts')
@include('admin.partials.scripts')
@endsection
