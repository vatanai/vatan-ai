@extends('layouts.app')

@section('content')
<div class="profile-page" dir="rtl">

  {{-- ۱) هدر و نمایش بالا: آواتار، اطلاعات، آمار، اکشن‌ها --}}
  @include('app.profile.header')

  <section class="tabs-section">

    {{-- ۲) گرید و مارک‌ها: تب‌ها + محتوای ساخته‌شده + ذخیره‌شده‌ها --}}
    @include('app.profile.content')

    {{-- ۳) فایل‌های تو --}}
    @include('app.profile.files')

    {{-- ۴) همکاری در فروش --}}
    @include('app.profile.referral')

  </section>

</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/profile.js') }}" defer></script>
@endpush
