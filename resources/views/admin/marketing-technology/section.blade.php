@extends('layouts.admin')

@section('title', $title.' — تکنولوژی مارکتینگ')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content flex-1 overflow-y-auto" id="content">
    <div class="mt-page-head"><div><div class="mt-eyebrow">تکنولوژی مارکتینگ</div><h1>{{ $title }}</h1><p>{{ $description }}</p></div><a class="mt-btn" href="{{ route('admin.marketing-technology.index') }}"><i class="fa-solid fa-arrow-right"></i> بازگشت به مرکز فرماندهی</a></div>
    <section class="mt-card mt-empty-section"><span class="mt-empty-icon"><i class="fa-solid fa-layer-group"></i></span><h2>این بخش در صف اجرای فاز بعدی است</h2><p>پوسته و مسیر این بخش آماده است. منطق عملیاتی آن بعد از تکمیل مدل داده مرکزی، بدون دست‌زدن به بخش فعلی رشد، اضافه می‌شود.</p><span class="mt-badge mt-badge-warn">بزودی</span></section>
    <section class="mt-card mt-card-pad mt-related"><div class="mt-section-head"><div><h2>مسیرهای مرتبط</h2><p>برای ادامه‌ی کار، یکی از ماژول‌های آماده‌سازی‌شده را انتخاب کنید.</p></div></div><div class="mt-related-grid">@foreach($modules as $module)<a class="mt-related-link" href="{{ route('admin.marketing-technology.'.$module['key']) }}"><i class="fa-solid {{ $module['icon'] }}"></i><span>{{ $module['title'] }}</span><i class="fa-solid fa-angle-left"></i></a>@endforeach</div></section>
  </div>
</main>
@endsection
