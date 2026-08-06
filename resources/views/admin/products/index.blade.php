@extends('layouts.admin')
@section('title', 'لیست محصولات — وطن استودیو')

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')

  <div class="admin-content p-6 flex-1 overflow-y-auto max-[768px]:p-[18px] max-[480px]:p-[14px]" id="content" dir="rtl" style="background:var(--page-bg);">

    @if(session('success'))
      <div class="admin-toast mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--success-l);color:var(--success);border:1px solid var(--success-m);" role="status">
        <span class="admin-toast-icon" style="background:var(--success-m);"><i class="fa-solid fa-circle-check"></i></span>
        <span class="flex-1">{{ session('success') }}</span>
        <button type="button" onclick="this.closest('.admin-toast').remove()" aria-label="بستن پیام"><i class="fa-solid fa-xmark"></i></button>
      </div>
    @endif
    @if(session('error'))
      <div class="admin-toast mb-4 px-4 py-3 rounded-xl text-[12.5px] font-semibold" style="background:var(--danger-l);color:var(--danger);border:1px solid var(--danger-m);" role="alert">
        <span class="admin-toast-icon" style="background:var(--danger-m);"><i class="fa-solid fa-triangle-exclamation"></i></span>
        <span class="flex-1">{{ session('error') }}</span>
        <button type="button" onclick="this.closest('.admin-toast').remove()" aria-label="بستن پیام"><i class="fa-solid fa-xmark"></i></button>
      </div>
    @endif

    {{-- ── سربرگ صفحه ── --}}
    <div class="mb-5 flex items-center justify-between flex-wrap gap-3">
      <div>
        <div class="text-xl font-extrabold tracking-tight mb-1" style="color:var(--text-h);">لیست محصولات</div>
        <div class="text-[13px]" style="color:var(--text-soft);">مدیریت، ویرایش و پیکربندی تمام محصولات هوش مصنوعی پلتفرم</div>
      </div>
      <div class="flex items-center gap-2">
        <a href="{{ route('admin.products.create') }}" class="btn-pro btn-pro-primary">
          <i class="fa-solid fa-plus text-[11px]"></i> ثبت محصول جدید
        </a>
        <a href="{{ request()->fullUrl() }}" class="btn-pro btn-pro-ghost" id="btn-refresh-products" title="بروزرسانی لیست (با حفظ فیلترهای فعلی)">
          <i class="fa-solid fa-rotate-right text-[11px]"></i> بروزرسانی
        </a>
        <button type="button" class="btn-pro btn-pro-ghost is-disabled" title="نیاز به بررسی برنامه">
          <i class="fa-solid fa-file-export text-[11px]"></i> Export
          <span class="pending-badge" style="position:static;">Coming Soon</span>
        </button>
      </div>
    </div>

    {{-- ناحیه‌ی زنده‌ی محتوا: هنگام فیلتر/صفحه‌بندی/رفرش با اسکلت جایگزین می‌شود (فقط UI) --}}
    <div id="products-live-region">
      <!-- ══ Layer 1: Summary Cards ══ -->
      @include('admin.products.partials.stats-cards')

      <!-- ══ Layer 2: Filters & Search ══ -->
      @include('admin.products.partials.filters')

      <!-- ══ Layer 3: Products Table (+ Pagination) ══ -->
      @include('admin.products.partials.table')
    </div>

    <!-- ══ Skeleton Loading (فقط UI، حین ناوبری نمایش داده می‌شود) ══ -->
    @include('admin.products.partials.skeleton')

  </div>
</main>

<!-- ══ Drawer پیش‌نمایش محصول ══ -->
@include('admin.products.partials.drawer')

<script>
  /* ──────────────────────────────────────────────────────────────
     Skeleton Loading سمت کاربر: با ارسال فیلتر، کلیک روی چیپ‌های
     سریع، صفحه‌بندی، تغییر تعداد در صفحه یا دکمه‌ی بروزرسانی، بلافاصله
     ناحیه‌ی زنده مخفی و اسکلت نمایش داده می‌شود تا حس سرعت بالاتری در
     حین بارگذاری واقعی صفحه (از سرور Laravel) ایجاد شود. هیچ درخواست
     جدیدی به بک‌اند اضافه نمی‌شود؛ فقط جلوه‌ی بصری پیش از ناوبری است.
     ────────────────────────────────────────────────────────────── */
  (function () {
    const liveRegion = document.getElementById('products-live-region');
    const skeleton = document.getElementById('products-skeleton');
    if (!liveRegion || !skeleton) return;

    function showProductsSkeleton() {
      liveRegion.style.display = 'none';
      skeleton.style.display = '';
    }

    const filterForm = document.getElementById('products-filter-form');
    if (filterForm) filterForm.addEventListener('submit', showProductsSkeleton);

    const refreshBtn = document.getElementById('btn-refresh-products');
    if (refreshBtn) refreshBtn.addEventListener('click', showProductsSkeleton);

    document.addEventListener('click', function (e) {
      const trigger = e.target.closest('a.chip-filter, a.page-btn:not(.is-disabled)');
      if (trigger) showProductsSkeleton();
    });
  })();
</script>

@endsection
