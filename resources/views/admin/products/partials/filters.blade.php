{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت مستقل: جستجو + فیلترها + عملیات گروهی (Layer 2 / Filters Component)
  ──────────────────────────────────────────────────────────────────
  ورودی‌های مورد انتظار از View والد:
    - $products      : Paginator محصولات (برای شمارش نتایج)
    - $categories     : لیست دسته‌بندی‌های واقعی موجود در دیتابیس
    - $subcategories  : لیست زیردسته‌های واقعی موجود در دیتابیس
    - $aiModels       : مدل‌های هوش مصنوعی فعال (برای سلکت «مدل AI»)

  همه‌ی فیلترها (جستجو، دسته، زیردسته، وضعیت، نوع قیمت، نوع رسانه، مدل AI،
  بازه‌ی تاریخ) روی ستون‌های واقعاً موجود در جدول products اعمال می‌شوند.
  فقط گزینه‌های مرتب‌سازی «بیشترین/کمترین استفاده» و «بیشترین درآمد» چون به
  آمار اجرا نیاز دارند غیرفعال هستند و با بج «نیاز به بررسی برنامه» مشخص شده‌اند.
  ══════════════════════════════════════════════════════════════════
--}}
@php
  $chipGroupKeys = ['status', 'featured', 'is_new', 'trending', 'pricing_model'];
  $baseQuery = request()->except($chipGroupKeys);
  $chipUrl = function ($key, $value) use ($baseQuery) {
      $q = $baseQuery;
      if ($value !== null) { $q[$key] = $value; } else { unset($q[$key]); }
      $qs = http_build_query($q);
      return request()->url() . ($qs ? '?' . $qs : '');
  };
  $isChipActive = function ($key, $value) {
      return $value === null
          ? !collect(['status','featured','is_new','trending','pricing_model'])->some(fn($k) => request()->filled($k))
          : request()->get($key) === $value;
  };
  $hasAdvancedFilters = request()->filled('subcategory') || request()->filled('media_type') || request()->filled('ai_model')
      || request()->filled('created_from') || request()->filled('created_to') || request()->filled('updated_from') || request()->filled('updated_to');
@endphp

<form method="GET" id="products-filter-form" class="content-card p-3.5 mb-4">

  <input type="hidden" name="per_page" value="{{ request('per_page') }}">

  {{-- ─── ردیف جستجوی اصلی ─── --}}
  <div class="flex items-center gap-2.5 flex-wrap">
    <div class="flex-1 min-w-[240px] relative">
      <i class="fa-solid fa-magnifying-glass absolute right-3.5 top-1/2 -translate-y-1/2 text-xs" style="color:var(--text-soft);"></i>
      <input type="text" name="search" placeholder="جستجو در نام فارسی، نام انگلیسی، Slug، تگ یا شناسه محصول..."
             value="{{ request('search') }}"
             class="input-pro is-lg w-full" dir="rtl">
    </div>

    <button type="submit" class="btn-pro btn-pro-primary">
      <i class="fa-solid fa-magnifying-glass text-[11px]"></i> جستجو
    </button>

    <button type="button" class="btn-pro btn-pro-ghost" onclick="toggleAdvancedFilters()" id="advanced-filter-toggle">
      <i class="fa-solid fa-sliders text-[11px]"></i> فیلتر پیشرفته
      @if($hasAdvancedFilters)<span class="badge-pro badge-primary" style="padding:1px 6px;font-size:9px;">فعال</span>@endif
      <i class="fa-solid fa-chevron-down text-[9px] transition-transform duration-200" id="advanced-filter-chevron"></i>
    </button>

    @if(request()->anyFilled(['search','category','subcategory','status','pricing_model','media_type','ai_model','created_from','created_to','updated_from','updated_to','featured','is_new','trending','sort']))
      <a href="{{ route('admin.products') }}" class="btn-pro btn-pro-ghost" title="پاک کردن همه فیلترها">
        <i class="fa-solid fa-xmark text-[11px]"></i> پاک کردن
      </a>
    @endif
  </div>

  <div class="text-[10.5px] mt-2" style="color:var(--text-soft);">
    جستجو بر اساس: نام فارسی، نام انگلیسی، Slug، تگ، شناسه محصول
  </div>

  {{-- ─── چیپ‌های فیلتر سریع ─── --}}
  <div class="flex items-center gap-2 flex-wrap mt-3.5">
    <a href="{{ $chipUrl(null, null) }}" class="chip-filter {{ $isChipActive(null, null) ? 'active' : '' }}">همه</a>
    <a href="{{ $chipUrl('status', 'active') }}" class="chip-filter {{ $isChipActive('status','active') ? 'active' : '' }}">فعال</a>
    <a href="{{ $chipUrl('status', 'draft') }}" class="chip-filter {{ $isChipActive('status','draft') ? 'active' : '' }}">پیش‌نویس</a>
    <a href="{{ $chipUrl('status', 'inactive') }}" class="chip-filter {{ $isChipActive('status','inactive') ? 'active' : '' }}">غیرفعال</a>
    <a href="{{ $chipUrl('featured', '1') }}" class="chip-filter {{ $isChipActive('featured','1') ? 'active' : '' }}">ویژه</a>
    <a href="{{ $chipUrl('is_new', '1') }}" class="chip-filter {{ $isChipActive('is_new','1') ? 'active' : '' }}">جدید</a>
    <a href="{{ $chipUrl('trending', '1') }}" class="chip-filter {{ $isChipActive('trending','1') ? 'active' : '' }}">ترند</a>
    <a href="{{ $chipUrl('pricing_model', 'free') }}" class="chip-filter {{ $isChipActive('pricing_model','free') ? 'active' : '' }}">رایگان</a>
    <a href="{{ $chipUrl('pricing_model', 'paid') }}" class="chip-filter {{ $isChipActive('pricing_model','paid') ? 'active' : '' }}">پولی</a>
  </div>

  {{-- ─── پنل فیلتر پیشرفته (جمع‌شونده) ─── --}}
  <div id="advanced-filter-panel" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4 pt-4" style="border-top:1px solid var(--divider); {{ $hasAdvancedFilters ? '' : 'display:none;' }}">

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">دسته‌بندی</label>
      <select name="category" class="input-pro w-full">
        <option value="">همه دسته‌بندی‌ها</option>
        @foreach(($categories ?? []) as $cat)
          <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">زیردسته</label>
      <select name="subcategory" class="input-pro w-full">
        <option value="">همه زیردسته‌ها</option>
        @foreach(($subcategories ?? []) as $sub)
          <option value="{{ $sub }}" {{ request('subcategory') == $sub ? 'selected' : '' }}>{{ $sub }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">وضعیت</label>
      <select name="status" class="input-pro w-full">
        <option value="">همه وضعیت‌ها</option>
        <option value="active" {{ request('status')=='active'?'selected':'' }}>فعال</option>
        <option value="draft" {{ request('status')=='draft'?'selected':'' }}>پیش‌نویس</option>
        <option value="inactive" {{ request('status')=='inactive'?'selected':'' }}>غیرفعال</option>
      </select>
    </div>

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">نوع قیمت‌گذاری</label>
      <select name="pricing_model" class="input-pro w-full">
        <option value="">همه</option>
        <option value="free" {{ request('pricing_model')=='free'?'selected':'' }}>رایگان</option>
        <option value="per_credit" {{ request('pricing_model')=='per_credit'?'selected':'' }}>کردیتی</option>
        <option value="subscription" {{ request('pricing_model')=='subscription'?'selected':'' }}>اشتراکی</option>
      </select>
    </div>

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">نوع رسانه</label>
      <select name="media_type" class="input-pro w-full">
        <option value="">همه</option>
        <option value="photo" {{ request('media_type')=='photo'?'selected':'' }}>عکس</option>
        <option value="video" {{ request('media_type')=='video'?'selected':'' }}>ویدیو</option>
        <option value="both" {{ request('media_type')=='both'?'selected':'' }}>هردو</option>
      </select>
    </div>

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">مدل هوش مصنوعی</label>
      <select name="ai_model" class="input-pro w-full">
        <option value="">همه مدل‌ها</option>
        @foreach(($aiModels ?? []) as $m)
          <option value="{{ $m->openrouter_model_id }}" {{ request('ai_model') == $m->openrouter_model_id ? 'selected' : '' }}>{{ $m->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">تاریخ ایجاد از</label>
      <input type="date" name="created_from" value="{{ request('created_from') }}" class="input-pro w-full">
    </div>
    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">تاریخ ایجاد تا</label>
      <input type="date" name="created_to" value="{{ request('created_to') }}" class="input-pro w-full">
    </div>

    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">تاریخ ویرایش از</label>
      <input type="date" name="updated_from" value="{{ request('updated_from') }}" class="input-pro w-full">
    </div>
    <div>
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">تاریخ ویرایش تا</label>
      <input type="date" name="updated_to" value="{{ request('updated_to') }}" class="input-pro w-full">
    </div>

    <div class="sm:col-span-2">
      <label class="text-[10.5px] font-bold block mb-1.5" style="color:var(--text-soft);">مرتب‌سازی</label>
      <select name="sort" class="input-pro w-full">
        <option value="newest" {{ request('sort','newest')=='newest'?'selected':'' }}>جدیدترین</option>
        <option value="oldest" {{ request('sort')=='oldest'?'selected':'' }}>قدیمی‌ترین</option>
        <option value="az" {{ request('sort')=='az'?'selected':'' }}>الفبا</option>
        <option value="most_used" disabled>بیشترین استفاده — نیاز به بررسی برنامه</option>
        <option value="least_used" disabled>کمترین استفاده — نیاز به بررسی برنامه</option>
        <option value="most_revenue" disabled>بیشترین درآمد — نیاز به بررسی برنامه</option>
      </select>
    </div>

    <div class="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 mt-1">
      <button type="submit" class="btn-pro btn-pro-primary">
        <i class="fa-solid fa-filter text-[11px]"></i> اعمال فیلترها
      </button>
    </div>

  </div>

  {{-- ─── تعداد نتایج ─── --}}
  <div class="text-[11.5px] mt-3.5" style="color:var(--text-soft);">
    نمایش {{ $products->firstItem() ?? 0 }} تا {{ $products->lastItem() ?? 0 }} از {{ $products->total() }} محصول
  </div>

</form>

<script>
  function toggleAdvancedFilters() {
    const panel = document.getElementById('advanced-filter-panel');
    const chevron = document.getElementById('advanced-filter-chevron');
    const isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : '';
    chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
  }
</script>
