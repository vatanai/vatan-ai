{{-- اینستاگرام هوشمند — قالب مشترک صفحات (ساختار اجباری پنل + هدر مشترک) --}}
@extends('layouts.admin')
@section('title', ($siTitle ?? 'اینستاگرام هوشمند').' — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/smart-instagram.css') }}?v={{ @filemtime(public_path('admin/css/smart-instagram.css')) }}">
@endpush

@section('content')
@php($siCrumb = $siTitle ?? null)
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px] si" id="content" dir="rtl" style="background:var(--page-bg);">
    @foreach(['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $siKey => $siTone)
      @if(session($siKey))
        <div class="si-flash is-{{ $siTone }}" role="{{ $siKey === 'error' ? 'alert' : 'status' }}">
          <i class="fa-solid {{ $siTone === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
          <span>{{ session($siKey) }}</span>
          <button type="button" data-si-dismiss aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
        </div>
      @endif
    @endforeach
    @if($errors->any())
      <div class="si-flash is-danger" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <ul>@foreach($errors->all() as $siError)<li>{{ $siError }}</li>@endforeach</ul>
      </div>
    @endif

    @hasSection('si-head')
      @yield('si-head')
    @else
      <div class="si-head">
        <div>
          <h1 class="si-head-title">{{ $siTitle ?? 'اینستاگرام هوشمند' }}</h1>
          @isset($siSubtitle)<p class="si-head-sub">{{ $siSubtitle }}</p>@endisset
        </div>
        <div class="si-head-actions">@yield('si-actions')</div>
      </div>
    @endif

    @yield('si-page')
  </div>
</main>
@endsection

@section('scripts')
<script src="{{ asset('admin/js/smart-instagram.js') }}?v={{ @filemtime(public_path('admin/js/smart-instagram.js')) }}" defer></script>
@yield('si-scripts')
@endsection
