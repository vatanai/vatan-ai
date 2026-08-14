@extends('layouts.admin')
@section('title', 'داشبورد — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/admin.css') }}">
<style>
  body:has(#dashboard-page) .sidebar,
  body:has(#dashboard-page) .mini-rail { box-shadow: none; }
</style>
@endpush

@section('content')

<div class="flex min-h-screen" dir="rtl" style="background:var(--bg);">


  {{-- ══ MAIN ══ --}}
  <main class="mr-[294px] flex-1 h-screen min-h-0 flex flex-col min-w-0 max-[900px]:mr-0">

    {{-- ══ HEADER ══ --}}
    @include('admin.partials.header')

    {{-- ══ PAGE CONTENT ══ --}}
    <div class="flex-1 min-h-0 p-6 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]"
         id="content"
         style="scrollbar-width:none;-ms-overflow-style:none;">

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

  </main>

</div>

@endsection

@section('scripts')
@include('admin.partials.scripts')
@endsection
