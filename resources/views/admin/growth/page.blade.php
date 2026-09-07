@extends('layouts.admin')

@section('title', $title . ' — وطن استودیو')

@push('styles')
<link href="{{ asset('admin/css/growth.css') }}?v={{ filemtime(public_path('admin/css/growth.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="growth-page admin-content flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    @if(session('success'))
      <div class="g-alert g-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
      <div class="g-alert g-alert-danger"><i class="fa-solid fa-circle-exclamation"></i><div>@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div></div>
    @endif
    @include($partial)
  </div>
</main>
@endsection

@section('scripts')
  @include('admin.growth.partials.scripts')
@endsection
