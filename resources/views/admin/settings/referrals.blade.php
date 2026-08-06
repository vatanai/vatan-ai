@extends('layouts.admin')
@section('title', $pageMeta['title'].' — همکاری در فروش وطن')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/referral-settings.css') }}?v={{ filemtime(public_path('admin/css/referral-settings.css')) }}">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="admin-content flex-1 overflow-y-auto" id="content">
    <div class="referral-settings-page">
      @include('admin.settings.referrals.partials.page-header')

      @if($page === 'overview')
        @include('admin.settings.referrals.partials.overview')
      @elseif($page === 'settings')
        @include('admin.settings.referrals.partials.dashboard')
      @elseif($page === 'reviews')
        @include('admin.settings.referrals.partials.reviews')
      @else
        @include('admin.settings.referrals.partials.reports')
      @endif
    </div>
  </div>
</main>
@endsection
