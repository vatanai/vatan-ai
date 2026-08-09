@extends('layouts.app')

@section('page_title', isset($sitePage) ? ($sitePage->meta_title ?: $sitePage->title) : 'بساز | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/create-ui.css') }}?v={{ filemtime(public_path('css/create-ui.css')) }}">
@endpush

@section('content')
<div class="vatan-create" dir="rtl" data-create-ui>
  @include('app.partials.create-ui.header')

  <main class="create-shell page-container">
    <section class="create-workspace" aria-label="فضای ساخت">
      @include('app.partials.create-ui.creator')
      @include('app.partials.create-ui.preview')
    </section>

    @include('app.partials.create-ui.chat')
    @include('app.partials.create-ui.recent')
  </main>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('js/create-ui.js') }}?v={{ filemtime(public_path('js/create-ui.js')) }}"></script>
@endpush
