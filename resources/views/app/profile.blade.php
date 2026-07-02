@extends('layouts.app')

@push('styles')
  @include('app.profile._styles')
@endpush

@section('content')
<div class="profile-page" dir="rtl">

  {{-- ۱. هدر پروفایل: آواتار + اطلاعات + آمار + بنر --}}
  @include('app.profile._hero')

  {{-- ۲. تب‌ها + پنل‌ها --}}
  <section class="tabs-section">

    @include('app.profile._tabs')

    @include('app.profile._panel_content')
    @include('app.profile._panel_files')
    @include('app.profile._panel_referral')
    @include('app.profile._panel_saved')

  </section>

</div>
@endsection

@push('scripts')
  @include('app.profile._scripts')
@endpush
