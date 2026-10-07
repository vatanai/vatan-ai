@extends('layouts.admin')
@section('title', ($product ? 'ویرایش محصول پروداکتی' : 'ثبت محصول پروداکتی') . ' — وطن استودیو')

@push('styles')
<link rel="stylesheet" href="{{ asset('admin/css/products-create.css') }}">
<link rel="stylesheet" href="{{ asset('admin/css/product-shots.css') }}?v={{ filemtime(public_path('admin/css/product-shots.css')) }}">
@endpush

@php
  $selectedCategories = collect(old('category_ids', $product?->categories->pluck('id')->all() ?? []))->map(fn($id)=>(int)$id)->all();
  $categoryMap = $categories->keyBy('id');
  $categoryOptions = $categories->map(function ($category) use ($categoryMap) {
    $depth = 0;
    $parentId = $category->parent_id;
    while ($parentId && $depth < 10 && $categoryMap->has($parentId)) {
      $depth++;
      $parentId = $categoryMap->get($parentId)?->parent_id;
    }
    return ['id'=>(int)$category->id,'name'=>$category->name_fa ?: $category->name,'depth'=>$depth];
  })->values()->all();
  $selectedOccupations = collect(old('occupation_ids', $product?->occupations->pluck('id')->all() ?? ($settings['occupation_ids'] ?? [])))->map(fn($id)=>(int)$id)->all();
  $savedQualityModels = (array)($settings['quality_models'] ?? []);
  $savedPreflight = (array)($settings['preflight'] ?? []);
  $brandIdentityEnabled = (bool)old('brand_identity_enabled', $settings['brand_identity_enabled'] ?? true);
  $brandIdentityPrompt = old('brand_identity_prompt', $settings['brand_identity_prompt'] ?? \App\Services\ProductShots\ShotPromptBuilder::defaultBrandIdentityPrompt());
  $steps = [
    1 => ['هویت محصول','اطلاعات پایه و اصناف','fa-id-card'],
    2 => ['هوش مصنوعی','مدل، کیفیت و پرامپت','fa-wand-magic-sparkles'],
    3 => ['ویژگی‌های محصول','ورودی، کنترل کیفیت و شات‌ها','fa-camera-retro'],
    4 => ['خروجی و اعتبار','نمایش، واترمارک و انتشار','fa-file-export'],
    5 => ['بازبینی نهایی','مرور و پیش‌نمایش واقعی','fa-clipboard-check'],
  ];
@endphp

@section('content')
<main id="product-shot-wizard" class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0" dir="rtl">
  @include('admin.partials.header')
  <div class="admin-content p-6 flex-1 pb-24 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content">
    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-1.5 text-xs text-[var(--text-soft)]"><a href="{{ route('admin.products') }}" class="text-[var(--text-soft)] no-underline">محصولات</a><i class="fa-solid fa-chevron-left text-[9px]"></i><span class="font-bold text-[var(--text-h)]">{{ $product ? 'ویرایش محصول پروداکتی' : 'ثبت محصول پروداکتی' }}</span></div>
      <a href="{{ route('admin.product-shots.index', ['tab'=>'products']) }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-arrow-right"></i> بازگشت به استودیو محصول</a>
    </div>

    @if(session('success'))<div class="mb-4 p-3 rounded-xl border border-[var(--success)] bg-[var(--card-bg)] text-xs text-[var(--text-main)]"><i class="fa-solid fa-circle-check text-[var(--success)] ml-1"></i>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="mb-5 p-4 rounded-xl border border-[var(--danger)] bg-[var(--card-bg)] text-xs text-[var(--text-main)]"><strong class="block mb-2 text-[var(--danger)]">موارد زیر را اصلاح کنید:</strong><ul class="list-disc pr-5 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <div class="mb-6"><h1 class="text-xl font-extrabold text-[var(--text-h)] mb-1">{{ $product ? 'ویرایش «'.$product->name_fa.'»' : 'ثبت محصول پروداکتی' }}</h1><p class="text-xs text-[var(--text-soft)]">محصول را در ۵ مرحله تنظیم کنید — ساختار این مسیر با ثبت محصول پرتره‌ای یکسان و منطق آن کاملاً مستقل است.</p></div>

    <div class="mb-7 bg-[var(--card-bg)] border border-[var(--border)] rounded-xl p-2 md:p-1.5">
      <div class="flex flex-col md:flex-row md:items-center gap-1 md:gap-0">
        @foreach($steps as $number => [$title,$description,$icon])
          <button type="button" class="psw-step step-item flex-1 flex items-center gap-3 p-3 md:p-2.5 rounded-lg cursor-pointer transition-all border border-transparent text-right" data-step-tab="{{ $number }}">
            <span class="step-circle w-8 h-8 md:w-7 md:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 border-2" data-step-circle>{{ ['','۱','۲','۳','۴','۵'][$number] }}</span>
            <span class="flex-1 min-w-0"><span class="step-label block text-[11px] mb-0.5">گام {{ ['','اول','دوم','سوم','چهارم','پنجم'][$number] }}</span><strong class="step-title block text-xs">{{ $title }}</strong><small class="step-desc block text-[10px] text-[var(--text-soft)] mt-0.5">{{ $description }}</small></span>
            <i class="fa-solid {{ $icon }} text-[var(--text-soft)]" data-step-icon></i>
          </button>
          @if($number < 5)<span class="hidden md:block w-5 h-px bg-[var(--border)]"></span>@endif
        @endforeach
      </div>
    </div>

    <div id="psw-validation" class="hidden mb-5 p-3 rounded-xl border border-[var(--danger)] bg-[var(--card-bg)] text-xs text-[var(--danger)]" role="alert"></div>

    <form method="POST" enctype="multipart/form-data" id="shot-product-form" action="{{ $product ? route('admin.product-shots.products.update',$product) : route('admin.product-shots.products.store') }}">
      @csrf @if($product) @method('PUT') @endif
      <input type="hidden" name="status" id="psw-status" value="{{ old('status',$product?->status ?? 'draft') }}">
      <div data-step-panel="1">@include('admin.product-shots.product-steps.step-1')</div>
      <div data-step-panel="2" hidden>@include('admin.product-shots.product-steps.step-2')</div>
      <div data-step-panel="3" hidden>@include('admin.product-shots.product-steps.step-3')</div>
      <div data-step-panel="4" hidden>@include('admin.product-shots.product-steps.step-4')</div>
      <div data-step-panel="5" hidden>@include('admin.product-shots.product-steps.step-5')</div>
    </form>
  </div>

  <div class="sticky bottom-0 bg-[var(--card-bg)] border-t border-[var(--border)] p-3 md:p-4 flex items-center justify-between gap-2 flex-wrap z-40">
    <button type="button" class="btn-pro btn-pro-ghost" data-wizard-prev hidden><i class="fa-solid fa-arrow-right"></i> مرحله قبل</button>
    <div class="flex-1 min-w-[220px] max-w-xl"><div class="flex items-center justify-between text-[10px] text-[var(--text-soft)] mb-1"><span>پیشرفت ثبت محصول</span><strong data-wizard-progress-label>گام ۱ از ۵</strong></div><div class="h-1.5 rounded-full bg-[var(--input-bg)] overflow-hidden"><span class="block h-full bg-[var(--primary)] transition-all" data-wizard-progress style="width:20%"></span></div></div>
    <div class="flex gap-2"><button type="button" class="btn-pro btn-pro-primary" data-wizard-next>مرحله بعد <i class="fa-solid fa-arrow-left"></i></button><button type="button" class="btn-pro btn-pro-ghost" data-save-draft><i class="fa-solid fa-floppy-disk"></i> ذخیره پیش‌نویس</button><button type="button" class="btn-pro btn-pro-primary" data-save-active hidden><i class="fa-solid fa-rocket"></i> ثبت نهایی محصول</button></div>
  </div>
</main>
@endsection

@section('scripts')
<script>window.PRODUCT_SHOT_FORM_CONFIG={previewUrl:@json(route('admin.product-shots.preview')),qualityLevels:@json($qualityLevels),productId:@json($product?->id),productCode:@json($product?->product_code),categories:@json($categoryOptions),selectedCategoryIds:@json($selectedCategories)};</script>
<script src="{{ asset('admin/js/product-shots-form.js') }}?v={{ filemtime(public_path('admin/js/product-shots-form.js')) }}"></script>
@endsection
