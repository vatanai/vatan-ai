@extends('layouts.admin')
@section('title', 'دسترسی سریع — پنل مدیریت')

@php
  $quickAccessPriorityPrefetchUrls = [
    route('admin.users.index'),
    route('admin.products'),
    route('admin.plans.index'),
  ];
@endphp

@push('styles')
@foreach($quickAccessPriorityPrefetchUrls as $prefetchUrl)
<link rel="prefetch" href="{{ $prefetchUrl }}" as="document">
@endforeach
<link rel="stylesheet" href="{{ asset('admin/css/quick-access.css') }}?v={{ filemtime(public_path('admin/css/quick-access.css')) }}">
@endpush

@section('content')
<div class="admin-quick-access-view" dir="rtl">
  <main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
    @include('admin.partials.header')

    <div class="admin-content quick-access-content flex-1 overflow-y-auto" id="content">
      <section class="quick-access-page" aria-labelledby="quick-access-title">
        <header class="quick-access-hero">
          <div class="quick-access-hero-copy">
            <span class="quick-access-eyebrow"><i class="fa-solid fa-bolt-lightning"></i> مرکز فرماندهی پنل</span>
            <h1 id="quick-access-title">دسترسی سریع</h1>
            <p>همه‌ی بخش‌های مهم داشبورد در یک نگاه؛ با انتخاب هر کارت یا میانبر، مستقیم وارد همان بخش شوید.</p>
          </div>
          <a class="quick-access-center-link" href="{{ route('admin.dashboard', ['center' => 1]) }}">
            <i class="fa-solid fa-gauge-high"></i>
            <span>مرکز فرماندهی</span>
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </a>
        </header>

        <div class="quick-access-grid" aria-label="میانبرهای بخش‌های داشبورد">
          @foreach($quickAccessCards as $card)
            <article class="quick-access-card {{ !empty($card['priority']) ? 'is-priority' : '' }}">
              <a class="quick-access-card-main" href="{{ $card['url'] }}">
                <span class="quick-access-card-icon"><i class="fa-solid {{ $card['icon'] }}" aria-hidden="true"></i></span>
                <span class="quick-access-card-copy">
                  <strong>{{ $card['title'] }}</strong>
                  <small>{{ $card['description'] }}</small>
                </span>
                <i class="fa-solid fa-arrow-left quick-access-card-arrow" aria-hidden="true"></i>
              </a>

              <div class="quick-access-shortcuts" aria-label="میانبرهای {{ $card['title'] }}">
                @foreach($card['shortcuts'] as $shortcut)
                  <a class="quick-access-shortcut" href="{{ $shortcut['url'] }}" title="{{ $shortcut['title'] }}">
                    <i class="fa-solid {{ $shortcut['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $shortcut['title'] }}</span>
                  </a>
                @endforeach
              </div>
            </article>
          @endforeach
        </div>
      </section>
    </div>
  </main>
</div>
@endsection

@section('scripts')
@include('admin.partials.scripts')
@endsection
