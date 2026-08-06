@extends('layouts.admin')

@section('title', 'مدیریت صفحه ترند — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">
    @if(session('success'))
      <div class="mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--success-l);color:var(--success);border:1px solid var(--success-m);">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
      </div>
    @endif

    @if($errors->any())
      <div class="mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--danger-l);color:var(--danger);border:1px solid var(--danger-m);">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}
      </div>
    @endif

    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-xl font-extrabold tracking-tight mb-1" style="color:var(--text-h);">مدیریت صفحه ترند</div>
        <div class="text-[13px]" style="color:var(--text-soft);">محصولات، وضعیت نمایش، گزارش عملکرد و بنرهای صفحه ترندز را از اینجا کنترل کنید.</div>
      </div>
      <a href="{{ route('app.trends') }}" target="_blank" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-up-left-from-circle text-[11px]"></i> مشاهده صفحه زنده</a>
    </div>

    <div class="grid grid-cols-4 max-[1100px]:grid-cols-2 max-[560px]:grid-cols-1 gap-3 mb-5">
      <div class="stat-card"><div class="stat-card-icon" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid fa-fire"></i></div><div><div class="stat-card-value">{{ number_format($activeTrendingCount) }}</div><div class="stat-card-label">محصول فعال در ترند</div></div></div>
      <div class="stat-card"><div class="stat-card-icon" style="background:var(--info-l);color:var(--info);"><i class="fa-solid fa-eye"></i></div><div><div class="stat-card-value">{{ number_format($viewsCount) }}</div><div class="stat-card-label">بازدید محصول</div></div></div>
      <div class="stat-card"><div class="stat-card-icon" style="background:var(--warning-l);color:var(--warning);"><i class="fa-solid fa-arrow-up-right-from-square"></i></div><div><div class="stat-card-value">{{ number_format($opensCount) }}</div><div class="stat-card-label">بازکردن از ترند</div></div></div>
      <div class="stat-card"><div class="stat-card-icon" style="background:var(--success-l);color:var(--success);"><i class="fa-solid fa-image"></i></div><div><div class="stat-card-value">{{ number_format($banners->where('is_active', true)->count()) }}</div><div class="stat-card-label">بنر فعال</div></div></div>
    </div>

    @include('admin.trends.partials.product-toolbar', ['search' => $search, 'availableProducts' => $availableProducts])
    @include('admin.trends.partials.product-list', ['products' => $trendingProducts])
    @include('admin.trends.partials.banner-form', ['banner' => $editingBanner])
    @include('admin.trends.partials.banner-list', ['banners' => $banners])
  </div>
</main>
@endsection
