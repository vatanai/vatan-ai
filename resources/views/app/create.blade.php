@extends('layouts.app')

@section('page_title', isset($sitePage) ? ($sitePage->meta_title ?: $sitePage->title) : 'بساز | وطن AI')

@push('styles')
  <link rel="stylesheet" href="{{ \App\Support\AppAsset::url('css/create-ui.css') }}">
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
  <script src="{{ \App\Support\AppAsset::url('js/create-ui.js') }}"></script>
@endpush
