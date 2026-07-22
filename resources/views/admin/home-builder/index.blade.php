@extends('layouts.admin')
@section('title', 'مدیریت صفحه هوم — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">

    @if(session('success'))
      <div class="mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--success-l);color:var(--success);border:1px solid var(--success-m);">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
      </div>
    @endif

    {{-- ── سربرگ صفحه ── --}}
    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-xl font-extrabold tracking-tight mb-1" style="color:var(--text-h);">مدیریت صفحه هوم</div>
        <div class="text-[13px]" style="color:var(--text-soft);">ساخت، ترتیب‌دهی و انتشار Sectionهای صفحه اصلی اپ — بدون نیاز به برنامه‌نویس</div>
      </div>
      <div class="flex items-center gap-2">
        <button type="button" class="btn-pro btn-pro-primary" onclick="HomeBuilder.openAddDrawer()">
          <i class="fa-solid fa-plus text-[11px]"></i> افزودن Section
        </button>
      </div>
    </div>

    {{-- ── لیست عمودی Sectionها ── --}}
    <div class="content-card" style="padding:14px;">
      <div id="hb-empty-state" class="empty-state" style="{{ $sections->isEmpty() ? '' : 'display:none;' }}">
        <div class="empty-state-icon"><i class="fa-solid fa-layer-group"></i></div>
        <div class="empty-state-title">هنوز هیچ Sectionی اضافه نشده</div>
        <div class="empty-state-desc">با دکمه‌ی «افزودن Section» اولین بلوک صفحه Home را بسازید — مثلاً یک اسلایدر محصولات یا یک بنر تبلیغاتی.</div>
        <button type="button" class="btn-pro btn-pro-primary" onclick="HomeBuilder.openAddDrawer()">
          <i class="fa-solid fa-plus text-[11px]"></i> افزودن اولین Section
        </button>
      </div>

      <div id="hb-section-list" class="flex flex-col gap-2" style="{{ $sections->isEmpty() ? 'display:none;' : '' }}">
        @foreach($sections as $section)
          @include('admin.home-builder.partials.section-row', ['section' => $section, 'typeRegistry' => $typeRegistry])
        @endforeach
      </div>
    </div>

  </div>
</main>

@include('admin.home-builder.partials.add-drawer')
@include('admin.home-builder.partials.edit-drawer')
@include('admin.home-builder.partials.scripts')

@endsection
