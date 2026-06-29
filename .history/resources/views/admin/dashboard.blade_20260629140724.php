@extends('layouts.admin')
@section('title', 'داشبورد — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/admin.css') }}">
@endpush

@section('content')

<div class="flex min-h-screen" dir="rtl" style="background:var(--bg);">

  {{-- ══ SIDEBAR ══ --}}

  {{-- ══ SIDEBAR OVERLAY (mobile) ══ --}}
  <div class="sidebar-overlay hidden max-[900px]:block fixed inset-0 z-[99] bg-black/[0.55] opacity-0 pointer-events-none transition-opacity duration-[250ms]"
       id="sidebar-overlay" onclick="toggleSidebar()"></div>

  {{-- ══ MAIN ══ --}}
  <main class="mr-64 flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">

    {{-- ══ HEADER ══ --}}
    @include('admin.partials.header')

    {{-- ══ PAGE CONTENT ══ --}}
    <div class="flex-1 p-6 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]"
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
