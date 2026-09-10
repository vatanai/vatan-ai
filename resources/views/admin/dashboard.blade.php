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

      @if(empty($dashboardSection))
        @include('admin.partials.pages.dashboard-main')
      @else
        @include('admin.partials.pages.' . match($dashboardSection) {
          'crm' => 'crm', 'attendance' => 'misc', 'products' => 'products-dashboard',
          'productslist' => 'products-list', 'createproduct' => 'products-create',
          'categories' => 'products-categories', 'pricing' => 'products-pricing',
          'ai' => 'ai-hub', 'models' => 'ai-models', 'prompts' => 'ai-prompts', 'logs' => 'ai-logs',
          default => 'misc',
        })
      @endif

    </div>{{-- #content --}}

  </main>

</div>

@endsection

@section('scripts')
@include('admin.partials.scripts')
@endsection
