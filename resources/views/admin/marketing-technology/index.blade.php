@extends('layouts.admin')

@section('title', 'تکنولوژی مارکتینگ — وطن استودیو')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content flex-1 overflow-y-auto" id="content">
    <div class="mt-page-head">
      <div>
        <div class="mt-eyebrow">مرکز مستقل بازاریابی و اتوماسیون وطن</div>
        <h1>تکنولوژی مارکتینگ</h1>
        <p>نسخه‌ی توسعه‌پذیر برای مدیریت محتوا، سناریو، گفتگو، اندازه‌گیری و هزینه؛ بدون تغییر در بخش فعلی رشد.</p>
      </div>
      <div class="mt-head-actions">
        <span class="mt-status"><span></span> حالت آماده‌سازی</span>
        <a class="mt-btn mt-btn-primary" href="{{ route('admin.marketing-technology.integrations') }}"><i class="fa-solid fa-plug"></i> بررسی اتصال‌ها</a>
      </div>
    </div>

    <section class="mt-hero mt-card">
      <div class="mt-hero-copy">
        <span class="mt-kicker"><i class="fa-solid fa-shield-halved"></i> هسته‌ی عملیاتی و تحلیلی</span>
        <h2>مرکز فرماندهی آماده‌ی توسعه است</h2>
        <p>ساختار جدید از بخش فعلی رشد جداست. داده‌های فعلی فقط خوانده می‌شوند و اتصال `Meta API` تا زمان دریافت کلید شما فعال نخواهد شد.</p>
        <div class="mt-actions"><a class="mt-btn mt-btn-primary" href="{{ route('admin.marketing-technology.content-calendar') }}">شروع با تقویم محتوا <i class="fa-solid fa-arrow-left"></i></a><a class="mt-btn" href="{{ route('admin.growth.monitor') }}">مشاهده رشد فعلی</a></div>
      </div>
      <div class="mt-hero-orbit"><div class="mt-orbit-ring"></div><div class="mt-orbit-core"><i class="fa-solid fa-network-wired"></i><span>مغز عملیات</span></div><span class="mt-orbit-node node-a"><i class="fa-solid fa-box"></i></span><span class="mt-orbit-node node-b"><i class="fa-solid fa-message"></i></span><span class="mt-orbit-node node-c"><i class="fa-solid fa-chart-column"></i></span></div>
    </section>

    <section class="mt-kpis">
      <article class="mt-card mt-kpi"><div class="mt-kpi-top"><span>محتواهای ثبت‌شده</span><i class="fa-solid fa-photo-film"></i></div><strong>{{ number_format($metrics['contents']) }}</strong><small>داده‌ی خوانده‌شده از رشد فعلی</small></article>
      <article class="mt-card mt-kpi"><div class="mt-kpi-top"><span>لینک‌های فعال</span><i class="fa-solid fa-link"></i></div><strong>{{ number_format($metrics['links']) }}</strong><small>قابل اتصال به کمپین و سناریو</small></article>
      <article class="mt-card mt-kpi"><div class="mt-kpi-top"><span>کلیک ثبت‌شده</span><i class="fa-solid fa-arrow-pointer"></i></div><strong>{{ number_format($metrics['clicks']) }}</strong><small>رویدادهای موجود در رهگیری وطن</small></article>
      <article class="mt-card mt-kpi"><div class="mt-kpi-top"><span>بازشدن مقصد</span><i class="fa-solid fa-door-open"></i></div><strong>{{ number_format($metrics['opens']) }}</strong><small>رویداد مستقل از کلیک</small></article>
    </section>

    <section class="mt-grid mt-grid-main">
      <article class="mt-card mt-card-pad">
        <div class="mt-section-head"><div><h2>ماژول‌های اجرایی</h2><p>هر بخش به‌صورت مستقل توسعه و تست می‌شود.</p></div><span class="mt-badge">نسخه مستقل</span></div>
        <div class="mt-module-grid">
          @foreach($modules as $module)
            <a class="mt-module" href="{{ route('admin.marketing-technology.'.$module['key']) }}"><span class="mt-module-icon"><i class="fa-solid {{ $module['icon'] }}"></i></span><span class="mt-module-copy"><strong>{{ $module['title'] }}</strong><small>{{ $module['description'] }}</small></span><span class="mt-module-status">{{ $module['status'] }}</span><i class="fa-solid fa-angle-left mt-module-arrow"></i></a>
          @endforeach
        </div>
      </article>

      <article class="mt-card mt-card-pad">
        <div class="mt-section-head"><div><h2>مسیر استاندارد عملیات</h2><p>شناسه‌ی مشترک، گزارش و هزینه در تمام مسیر حفظ می‌شود.</p></div></div>
        <div class="mt-pipeline">
          @foreach($pipeline as $step)
            <div class="mt-pipeline-step"><span><i class="fa-solid {{ $step['icon'] }}"></i></span><strong>{{ $step['title'] }}</strong><small>{{ $step['description'] }}</small></div>
            @if(!$loop->last)<i class="fa-solid fa-chevron-left mt-pipeline-arrow"></i>@endif
          @endforeach
        </div>
      </article>
    </section>

    <section class="mt-card mt-card-pad mt-roadmap">
      <div class="mt-section-head"><div><h2>وضعیت فازها</h2><p>اتصال به سرویس خارجی عمداً تا زمان دریافت کلید در حالت انتظار است.</p></div><span class="mt-badge mt-badge-warn">`Meta API` در انتظار کلید</span></div>
      <div class="mt-roadmap-grid"><div class="mt-roadmap-item done"><span>۱</span><div><strong>پوسته و منوی مستقل</strong><small>تکمیل‌شده</small></div></div><div class="mt-roadmap-item done"><span>۲</span><div><strong>محتوا و سناریوها</strong><small>تقویم، صف و نسخه‌بندی</small></div></div><div class="mt-roadmap-item done"><span>۳</span><div><strong>گزارش و مرکز هزینه</strong><small>قیف، کانال، روند و هزینه</small></div></div><div class="mt-roadmap-item active"><span>۴</span><div><strong>اتصال `Meta API`</strong><small>پس از دریافت کلید شما</small></div></div></div>
    </section>
  </div>
</main>
@endsection
