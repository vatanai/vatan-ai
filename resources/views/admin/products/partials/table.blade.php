{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت مستقل: جدول محصولات (Layer 3 / Products Table Component)
  ──────────────────────────────────────────────────────────────────
  ورودی مورد انتظار از View والد: $products (Paginator), $recentlyEdited, $maxRuns
  ستون «تعداد اجرا» و نوار محبوبیت به آمار واقعی جدول generations متصل هستند
  (generations_count از withCount کنترلر). ستون «کد محصول» کد اصلی ۶ رقمی
  (product_code) را نشان می‌دهد و ستون «آخرین ویرایش» تاریخ/ساعت شمسی ثبت و
  آخرین ویرایش را از created_at / updated_at می‌خواند.

  data-label روی هر td برای نمایش «جدول → لیست کارتی» در حالت موبایل استفاده
  می‌شود (فقط CSS در design-tokens.css، بدون تغییر منطق). ستون‌های چک‌باکس/
  تصویر/محصول با کلاس‌های td-select / td-thumb / td-product سرِ کارت موبایل
  را می‌سازند و بقیه‌ی ستون‌ها به‌صورت ردیف برچسب:مقدار زیر آن نمایش داده می‌شوند.
  ══════════════════════════════════════════════════════════════════
--}}

@if($recentlyEdited->isNotEmpty())
<div class="content-card p-3.5 mb-4">
  <div class="flex items-center gap-2 mb-2.5">
    <i class="fa-solid fa-clock-rotate-left text-[11px]" style="color:var(--primary);"></i>
    <span class="text-[11.5px] font-bold" style="color:var(--text-h);">آخرین محصولات ویرایش‌شده</span>
  </div>
  <div class="flex items-center gap-2.5 flex-wrap">
    @foreach($recentlyEdited as $r)
      <div class="flex items-center gap-2 pl-3 pr-2 py-1.5 rounded-lg" style="background:var(--input-bg);border:1px solid var(--border);">
        <div class="table-thumb" style="width:26px;height:26px;border-radius:6px;">
          <img src="{{ $r->displayImageUrl() }}" alt="">
        </div>
        <span class="text-[11.5px] font-semibold" style="color:var(--text-h);">{{ $r->name_fa }}</span>
        <span class="text-[10px]" style="color:var(--text-soft);">{{ $r->updated_at?->diffForHumans() }}</span>
      </div>
    @endforeach
  </div>
</div>
@endif

<style>
  .products-table-compact thead th:nth-child(7),
  .products-table-compact thead th:nth-child(10) { line-height:1.35; }

  @media (min-width: 768px) {
    .products-table-compact thead th {
      text-align: center;
      vertical-align: middle;
    }
    .products-table-compact tbody td {
      text-align: center;
      vertical-align: middle;
    }
    .products-table-compact thead th:nth-child(3),
    .products-table-compact tbody td.td-product {
      text-align: right;
    }
    .products-table-compact tbody td[data-label="آخرین ویرایش"] > div {
      width: max-content;
      max-width: 100%;
      margin-inline: auto;
      text-align: center;
    }
    .products-table-compact tbody td[data-label="آخرین ویرایش"] .flex,
    .products-table-compact tbody td[data-label^="عملیات"] > div {
      justify-content: center !important;
    }
    .products-table-compact thead th:last-child,
    .products-table-compact tbody td[data-label^="عملیات"] {
      text-align: center;
    }
  }

  .products-table-compact .td-product .badge-success[title*="آزمایشگاه"] { display:none; }
  .products-table-compact td[data-label*="بهینه"] > div { display:grid !important; grid-template-columns:1fr; gap:5px; justify-items:center; }
  .products-table-compact td[data-label*="بهینه"] > div > button,
  .products-table-compact td[data-label*="بهینه"] > div > a { width:34px; height:34px; min-height:34px; border:1px solid var(--border); border-radius:8px; background:var(--input-bg); }
  .products-table-compact .product-price-stack { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:3px; text-align:center; }
  .products-table-compact .product-price-stack span,
  .products-table-compact .product-run-token-stack > div { display:flex; align-items:center; justify-content:center; gap:5px; white-space:nowrap; }
  .products-table-compact .product-price-stack small,
  .products-table-compact .product-run-token-stack span { color:var(--text-soft); font-size:9px; font-weight:700; }
  .products-table-compact .product-price-stack b,
  .products-table-compact .product-run-token-stack strong { color:var(--text-main); font-size:11px; font-weight:800; }
  .products-table-compact .product-lab-cost-stack { display:flex; flex-direction:column; align-items:stretch; gap:4px; min-width:116px; }
  .products-table-compact .product-lab-cost-row { display:flex; align-items:center; justify-content:space-between; gap:7px; white-space:nowrap; }
  .products-table-compact .product-lab-cost-row > span:first-child { color:var(--text-soft); font-size:9px; font-weight:800; }
  .products-table-compact .product-lab-cost-value { display:inline-flex; align-items:center; gap:3px; direction:ltr; color:var(--text-main); font-size:9.5px; font-weight:800; }
  .products-table-compact .product-lab-cost-source { position:relative; cursor:help; outline:none; }
  .products-table-compact .product-lab-cost-source--actual { color:var(--success); }
  .products-table-compact .product-lab-cost-source--verified { color:var(--success); }
  body.light .products-table-compact .product-lab-cost-source--actual,
  body.light .products-table-compact .product-lab-cost-source--verified { color:var(--primary); }
  .products-table-compact .product-lab-cost-source--estimated,
  .products-table-compact .product-lab-cost-source--model { color:var(--warning); }
  .products-table-compact .product-lab-cost-source--unavailable { color:var(--text-soft); }
  .products-table-compact .product-lab-cost-tooltip { position:absolute; z-index:40; right:0; bottom:calc(100% + 7px); width:220px; padding:8px 9px; border:1px solid var(--b1); border-radius:9px; background:var(--s2); box-shadow:var(--shadow-card); color:var(--text-main); direction:rtl; text-align:right; white-space:normal; opacity:0; visibility:hidden; transform:translateY(3px); pointer-events:none; transition:opacity .15s ease,visibility .15s ease,transform .15s ease; }
  .products-table-compact .product-lab-cost-source:hover .product-lab-cost-tooltip,
  .products-table-compact .product-lab-cost-source:focus .product-lab-cost-tooltip,
  .products-table-compact .product-lab-cost-source:focus-within .product-lab-cost-tooltip { opacity:1; visibility:visible; transform:translateY(0); }
  .products-table-compact .product-lab-cost-tooltip strong { display:block; margin-bottom:3px; color:var(--text-main); font-size:9.5px; line-height:1.6; }
  .products-table-compact .product-lab-cost-tooltip small { display:block; color:var(--text-soft); font-size:8.5px; line-height:1.65; }
  .products-table-compact .product-lab-cost-value small { color:var(--text-soft); font-size:8px; font-weight:700; }
  .products-table-compact .product-lab-cost-meta { display:block; margin-top:2px; color:var(--text-soft); font-size:8px; line-height:1.4; }
  .products-table-compact .product-code-category-stack,
  .products-table-compact .product-credit-cost-stack { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:5px; min-width:128px; }
  .products-table-compact .product-credit-quality-stack { display:flex; flex-direction:column; align-items:stretch; justify-content:center; gap:4px; min-width:168px; }
  .products-table-compact .product-credit-quality-row { display:grid; grid-template-columns:minmax(58px, 1fr) auto minmax(0, 1.35fr); align-items:center; gap:5px; min-width:0; white-space:nowrap; }
  .products-table-compact .product-quality-label { min-width:0; overflow:hidden; text-overflow:ellipsis; color:var(--text-soft); font-size:9px; font-weight:800; }
  .products-table-compact .product-quality-credits { color:var(--text-main); font-size:10px; font-weight:900; }
  .products-table-compact .product-quality-cost { min-width:0; justify-self:end; color:var(--text-main); font-size:9px; font-weight:800; direction:ltr; }
  .products-table-compact .product-code-category-stack > div { display:flex; align-items:center; justify-content:center; gap:5px; flex-wrap:wrap; }
  .products-table-compact .product-code-category-stack > div:nth-child(2) { flex-direction:row; align-items:center; flex-wrap:nowrap; gap:5px; max-width:100%; white-space:nowrap; direction:rtl; }
  .products-table-compact .product-category-values { display:flex; flex-wrap:wrap; justify-content:center; align-items:center; gap:4px; max-width:100%; }
  .products-table-compact .product-category-values .badge-pro { white-space:normal; overflow-wrap:anywhere; text-align:center; }
  .products-table-compact .product-code-category-stack .stack-label { color:var(--text-soft); font-size:9px; font-weight:700; }
  .products-table-compact .product-code-category-stack .stack-value { color:var(--text-main); font-size:10.5px; font-weight:800; }
  .products-table-compact .product-audit-stack { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; min-width:0; }
  .products-table-compact .product-audit-entry { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px; min-width:0; line-height:1.35; }
  .products-table-compact .product-audit-date { color:var(--text-main); font-size:10.5px; font-weight:800; white-space:nowrap; }
  .products-table-compact .product-audit-actor { max-width:130px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text-soft); font-size:9.5px; font-weight:700; }
  .products-table-compact .product-actions-stack { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:4px; }
  .products-table-compact .product-actions-grid { display:grid; grid-template-columns:repeat(2, 44px); grid-template-rows:repeat(2, 44px); align-items:center; justify-items:center; gap:4px 8px; margin-inline:auto; }
  .products-table-compact .product-actions-grid > .product-like-box { grid-column:1; grid-row:1; }
  .products-table-compact .product-actions-grid > .dropdown-pro { grid-column:2; grid-row:1; }
  .products-table-compact .product-actions-grid > .favorite { grid-column:1; grid-row:2; }
  .products-table-compact .product-actions-grid > .product-action-link { grid-column:2; grid-row:2; }
  .products-table-compact .product-actions-grid > .product-like-box,
  .products-table-compact .product-actions-grid > .dropdown-pro,
  .products-table-compact .product-actions-grid > .favorite,
  .products-table-compact .product-actions-grid > .product-action-link,
  .products-table-compact .product-actions-grid > .dropdown-pro > .icon-action-btn,
  .products-table-compact .product-actions-grid > .product-action-link > .icon-action-btn {
    width:44px;
    min-width:44px;
    height:44px;
    min-height:44px;
    box-sizing:border-box;
  }
  .products-table-compact .product-like-box { display:inline-flex; align-items:center; justify-content:center; gap:5px; width:44px; min-width:44px; height:44px; min-height:44px; padding:3px 6px; border:1px solid var(--border); border-radius:9px; background:var(--input-bg); direction:rtl; }
  .products-table-compact .product-like-count { color:var(--text-main); font-size:10.5px; font-weight:800; line-height:1; }
  .products-table-compact .product-like-icon { color:var(--danger); font-size:11px; line-height:1; }
  .products-table-compact .product-actions-stack .dropdown-pro { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0; }
  .products-table-compact .product-action-link { display:flex; align-items:center; justify-content:center; width:44px; min-width:44px; height:44px; min-height:44px; margin-top:0; padding-top:0; border-top:0; }
  .products-table-compact td[data-label*="بهینه"] > .product-status-optimization-stack { display:flex !important; flex-direction:column; align-items:center; justify-content:center; gap:6px; }
  .products-table-compact .product-status-line { display:flex; align-items:center; justify-content:center; min-height:24px; }
  .products-table-compact .product-status-optimization-stack .icon-action-btn { width:31px; height:31px; min-height:31px; }
  .products-table-compact .product-credit-cost-stack .product-lab-cost-stack { width:100%; min-width:0; gap:3px; }
  .products-table-compact .product-credit-cost-stack .product-lab-cost-row { gap:5px; }
  .products-table-compact .product-credit-cost-stack .product-lab-cost-row > span:first-child { font-size:8.5px; }
  .products-table-compact .product-credit-cost-stack .product-lab-cost-value { font-size:9px; }
  .products-table-compact .product-credit-cost-stack,
  .products-table-compact .product-credit-cost-stack .product-lab-cost-stack { overflow:visible; }

  @media (max-width: 767px) {
    .products-table-compact tbody td[data-label^="عملیات"] {
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:8px;
    }
    .products-table-compact tbody td[data-label^="عملیات"]::before {
      width:100%;
      text-align:center;
    }
    .products-table-compact tbody td[data-label^="عملیات"] > .product-actions-stack {
      width:100%;
      align-items:center;
      margin-inline:auto;
    }
  }

  /*
   * جدول محصولات در دسکتاپ فضای ثابتی دارد؛ ستون‌های عملیاتی نباید فضای
   * توضیحات محصول را بگیرند. محتوای طولانی نیز باید داخل سلول خودش کنترل شود.
   */
  @media (min-width: 768px) {
    .products-table-compact th:nth-child(4) { width: 138px !important; }
    .products-table-compact th:nth-child(5) { width: 118px !important; }
    .products-table-compact th:nth-child(6) { width: 270px !important; min-width: 270px !important; }
    .products-table-compact th:nth-child(9) { width: 84px !important; }
    .products-table-compact th:nth-child(10) { width: 112px !important; }
    .products-table-compact th:nth-child(11) { width: 92px !important; }
    .products-table-compact thead th:nth-child(n+9):nth-child(-n+11),
    .products-table-compact tbody td:nth-child(n+9):nth-child(-n+11) { padding-inline: 6px; }

    .products-table-compact td[data-label^="کد محصول"],
    .products-table-compact td[data-label^="ویژگی‌ها"] {
      min-width: 0;
      overflow: hidden;
    }

    .products-table-compact .product-code-category-stack {
      width: 100%;
      min-width: 0;
      max-width: 100%;
      overflow: hidden;
    }

    /* popup قیمت باید از ارتفاع سلول بیرون بیاید و بریده نشود. */
    .products-table-compact .product-credit-cost-stack {
      width: 100%;
      min-width: 0;
      max-width: 100%;
      overflow: visible;
    }

    .products-table-compact .product-code-category-stack > div,
    .products-table-compact .product-category-values {
      width: 100%;
      min-width: 0;
      max-width: 100%;
    }

    .products-table-compact td[data-label^="ویژگی‌ها"] > div {
      width: 100%;
      min-width: 0;
      max-width: 100%;
      overflow: hidden;
    }

    .products-table-compact td[data-label^="ویژگی‌ها"] .product-ai-status,
    .products-table-compact td[data-label^="ویژگی‌ها"] > div:last-child {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .products-table-compact td[data-label^="ویژگی‌ها"] .badge-pro {
      min-width: 0;
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      font-size: clamp(8px, 0.52vw, 9px);
      line-height: 1.25;
    }

    .products-table-compact td[data-label^="ویژگی‌ها"] .product-ai-status .badge-pro {
      max-width: 100%;
    }

    .products-table-compact .product-category-values .badge-pro {
      max-width: 100%;
      white-space: normal;
      overflow-wrap: anywhere;
      line-height: 1.25;
    }

    .products-table-compact .product-audit-stack,
    .products-table-compact .product-actions-stack,
    .products-table-compact .product-status-optimization-stack {
      min-width: 0;
      max-width: 100%;
    }

    .products-table-compact .product-audit-date,
    .products-table-compact .product-audit-actor {
      max-width: 100%;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }
</style>

{{-- ─── نوار عملیات گروهی (Bulk Action) — فقط وقتی چند ردیف انتخاب شود نمایش داده می‌شود ─── --}}
<form id="bulk-action-form" method="POST" action="{{ route('admin.products.bulk_action') }}">
  @csrf
  <input type="hidden" name="action" id="bulk-action-input">
  <div id="bulk-toolbar" class="bulk-toolbar" style="display:none;">
    <span class="text-[12px] font-bold" style="color:var(--primary);"><span id="bulk-count">0</span> محصول انتخاب شده</span>
    <button type="button" id="bulk-select-all-matching" class="btn-pro btn-pro-ghost" style="display:none;" onclick="selectAllMatchingProducts()">انتخاب همه‌ی نتایج فیلترشده</button>
    <div class="w-px h-4" style="background:var(--border);"></div>
    <button type="button" class="btn-pro btn-pro-ghost" onclick="submitBulk('activate')"><i class="fa-solid fa-circle-check text-[11px]"></i> فعال کردن</button>
    <button type="button" class="btn-pro btn-pro-ghost" onclick="submitBulk('deactivate')"><i class="fa-solid fa-circle-xmark text-[11px]"></i> غیرفعال کردن</button>
    <span class="pro-tooltip-wrap inline-flex">
      <button type="button" class="btn-pro btn-pro-ghost" onclick="openBulkQualityConfigurationDialog()"><i class="fa-solid fa-layer-group text-[11px]"></i> تنظیم مدل‌های هوش مصنوعی <i class="fa-solid fa-circle-question text-[9px]"></i></button>
      <span class="pro-tooltip" style="width:295px;">مدل اصلی و جایگزینِ کیفیت‌های استاندارد، حرفه‌ای و بهترین خروجی را یکجا برای محصولات انتخاب‌شده تنظیم می‌کند.</span>
    </span>
    <button type="button" class="btn-pro btn-pro-ghost" onclick="openBulkQualityCreditPresetDialog()"><i class="fa-solid fa-bolt text-[11px]"></i> تنظیم مصرف اعتبار</button>
    <select class="input-pro" style="width:150px;height:34px;" onchange="submitBulkCategory(this.value)">
      <option value="">تغییر دسته به...</option>
      @foreach(($categories ?? []) as $cat)
        <option value="{{ $cat }}">{{ $cat }}</option>
      @endforeach
    </select>
    <button type="button" class="btn-pro btn-pro-ghost is-disabled" title="نیاز به بررسی برنامه"><i class="fa-solid fa-copy text-[11px]"></i> کپی <span class="pending-badge" style="position:static;">نیاز به بررسی برنامه</span></button>
    <button type="button" class="btn-pro btn-pro-ghost is-disabled" title="نیاز به بررسی برنامه"><i class="fa-solid fa-file-export text-[11px]"></i> Export <span class="pending-badge" style="position:static;">نیاز به بررسی برنامه</span></button>
    <button type="button" class="btn-pro btn-pro-danger" onclick="submitBulk('delete')"><i class="fa-solid fa-trash text-[11px]"></i> حذف</button>
  </div>
</form>

<div class="content-card overflow-hidden">
  <div class="overflow-x-auto">
  <table class="table-pro products-table-compact">
    <thead>
      <tr>
        <th style="width:40px;"><input type="checkbox" class="row-checkbox" id="select-all" onclick="toggleSelectAll(this)"></th>
        <th style="width:64px;"></th>
        <th>محصول</th>
        <th style="text-align:center;"><span class="block">نوع محصول</span><span class="block">کد محصول</span><span class="block">دسته‌بندی</span></th>
        <th style="text-align:center;"><span class="block">{{ ($isVideoList ?? false) ? 'سناریو' : 'ویژگی‌ها' }}</span><span class="block">هوش مصنوعی</span></th>
        <th style="text-align:center;"><span class="block">اعتبار اجرا</span><span class="block">قیمت محصول</span></th>
        <th style="text-align:center;"><span class="block">مالک</span><span class="block">تعداد اجرا</span></th>
        <th style="text-align:center;"><span class="block">{{ ($isVideoList ?? false) ? 'مدت / قاب' : 'زمان اجرا' }}</span><span class="block">اعتبار مصرفی</span></th>
        <th style="text-align:center;"><span class="block">فعال</span><span class="block">{{ ($isVideoList ?? false) ? 'پیش‌نمایش' : 'بهینه سازی' }}</span><span class="block">آزمایشگاه</span></th>
        <th>آخرین ویرایش</th>
        <th><span class="block">عملیات</span><span class="block">لینک</span><span class="block">تعداد لایک</span></th>
      </tr>
    </thead>
    <tbody>
      @forelse($products ?? [] as $product)
        <tr data-row-id="{{ $product->id }}">
          <td class="td-select"><input type="checkbox" class="row-checkbox bulk-check" value="{{ $product->id }}" onclick="onRowCheck()"></td>

          <td class="td-thumb">
            <div class="table-thumb cursor-pointer" onclick="openDrawer({{ $product->id }})">
              <img src="{{ $product->displayImageUrl() }}" alt="">
            </div>
          </td>

          <td class="td-product">
            <div class="min-w-0 cursor-pointer" onclick="openDrawer({{ $product->id }})">
              <div class="text-[13px] font-bold" style="color:var(--text-h);">{{ $product->name_fa }}</div>
              <div class="text-[10.5px] font-mono" style="color:var(--text-soft);" dir="ltr">{{ $product->name_en }} · {{ $product->slug }}</div>
              <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                <span class="badge-pro badge-info" style="padding:2px 6px;font-size:9px;">{{ $product->media_type === 'video' ? 'Video' : ($product->media_type === 'both' ? 'Photo+Video' : 'Photo') }}</span>
                @if($product->pricing_model !== 'free')
                  <span class="badge-pro badge-primary" style="padding:2px 6px;font-size:9px;">Premium</span>
                @endif
                @if($product->primary_model)
                  <span class="badge-pro badge-neutral" style="padding:2px 6px;font-size:9px;font-family:monospace;">{{ \Illuminate\Support\Str::limit($product->primary_model, 14) }}</span>
                @endif
                @if($product->is_featured)<span class="badge-pro badge-warning" style="padding:2px 6px;font-size:9px;"><i class="fa-solid fa-star"></i> ویژه</span>@endif
                @if($product->is_trending)<span class="badge-pro badge-danger" style="padding:2px 6px;font-size:9px;"><i class="fa-solid fa-fire"></i> ترند</span>@endif
                @if(($product->lab_experiments_count ?? 0) > 0 || ($product->legacy_test_runs_count ?? 0) > 0)<span class="badge-pro badge-success" style="padding:2px 6px;font-size:9px;" title="این محصول در آزمایشگاه آزمایش شده"><i class="fa-solid fa-flask"></i> آزمایش‌شده</span>@endif
              </div>
            </div>
          </td>

          <td data-label="نوع محصول / کد محصول / دسته‌بندی" style="text-align:center;">
            @php
              $categoryLabels = collect($product->categories ?? [])
                ->map(fn ($category) => $category->name_fa ?? $category->name ?? null)
                ->filter()->values();
              if ($categoryLabels->isEmpty()) {
                $categoryLabels = collect([$product->category, $product->subcategory])->filter()->values();
              }
            @endphp
            <div class="product-code-category-stack">
              <div><span class="stack-label">نوع</span><span class="stack-value">{{ $product->media_type === 'video' ? 'محصول ویدیو' : ($product->media_type === 'both' ? 'محصول عکس و ویدیو' : 'محصول عکس') }}</span></div>
              <div>
                <span class="stack-label">کد</span>
                @if($product->product_code)
                  <span class="stack-value font-mono" dir="ltr">{{ $product->product_code }}</span>
                @else
                  <span class="stack-value" title="این محصول قدیمی هنوز کد ندارد — با اولین ویرایش، خودکار ساخته می‌شود">—</span>
                @endif
              </div>
              <div>
                <div class="product-category-values">
                  @foreach($categoryLabels as $categoryLabel)
                    <span class="badge-pro badge-primary">{{ $categoryLabel }}</span>
                  @endforeach
                </div>
              </div>
            </div>
          </td>

          <td data-label="{{ ($isVideoList ?? false) ? 'سناریو / هوش مصنوعی' : 'ویژگی‌ها / هوش مصنوعی' }}" style="text-align:center;">
            @php
              $featureTitles = collect((array) $product->input_schema)
                ->map(fn ($feature) => is_array($feature) ? ($feature['label_fa'] ?? $feature['label'] ?? null) : null)
                ->filter()->values();
              $aiKey = ($product->ai_provider ?? '') . '|' . ($product->primary_model ?? '');
              $aiModelIsValid = isset($validAiModelKeys[$aiKey]);
              $assignedAiModel = $aiModelIsValid
                ? $assignableAiModels->first(fn ($model) => $model->provider === $product->ai_provider && $model->openrouter_model_id === $product->primary_model)
                : null;
            @endphp
            @if(($isVideoList ?? false))
              @php $videoListConfig = $product->videoConfiguration(); @endphp
              <div class="flex flex-wrap items-center justify-center gap-1 max-w-[180px] mx-auto">
                <span class="badge-pro badge-neutral">{{ ['image_to_video' => 'عکس به ویدیو', 'text_to_video' => 'متن به ویدیو', 'video_to_video' => 'ویدیو به ویدیو'][$videoListConfig['workflow'] ?? ''] ?? 'ویدیو' }}</span>
                @if(!empty($videoListConfig['preserve_source_aspect_ratio']))<span class="badge-pro badge-success">نسبت اصلی</span>@endif
                <span class="badge-pro badge-info">{{ (int) ($product->source_photo_products_count ?? 0) }} اتصال عکس</span>
              </div>
            @elseif($featureTitles->isNotEmpty())
              <div class="flex flex-wrap items-center justify-center gap-1 max-w-[180px] mx-auto">
                @foreach($featureTitles->take(3) as $featureTitle)
                  <span class="badge-pro badge-neutral">{{ $featureTitle }}</span>
                @endforeach
                @if($featureTitles->count() > 3)<span class="badge-pro badge-info">+{{ $featureTitles->count() - 3 }}</span>@endif
              </div>
            @else
              <span style="color:var(--text-soft);">ــ</span>
            @endif
            <div class="mt-1.5 flex flex-wrap items-center justify-center gap-1" id="product-ai-status-{{ $product->id }}">
              @if(!$product->primary_model || !$product->ai_provider)
                <span class="badge-pro badge-warning"><i class="fa-solid fa-circle-exclamation"></i> مدل تعیین نشده</span>
              @elseif(!$aiModelIsValid)
                <span class="badge-pro badge-danger"><i class="fa-solid fa-triangle-exclamation"></i> مدل نامعتبر</span>
                <span class="badge-pro badge-neutral" dir="ltr">{{ Illuminate\Support\Str::limit($product->primary_model, 18) }}</span>
              @else
                <span class="badge-pro badge-success" dir="ltr" title="{{ $assignedAiModel?->name }}"><i class="fa-solid fa-circle-check"></i> {{ $assignedAiModel?->shortDisplayName() }}</span>
              @endif
            </div>
          </td>

          <td data-label="اعتبار اجرا / قیمت محصول" id="product-credit-cell-{{ $product->id }}" style="text-align:center;">
            @php
              $productLabCosts = collect((array) ($product->lab_cost_summary ?? []));
              $productLabCostsByQuality = $productLabCosts->keyBy(fn ($cost) => (string) ($cost['key'] ?? ''));
              $qualityCreditCosts = $product->qualityCreditCosts();
              $qualityRows = [
                'standard' => 'استاندارد',
                'professional' => 'حرفه‌ای',
                'best' => 'بهترین خروجی',
              ];
            @endphp
            <div class="product-credit-quality-stack" aria-label="اعتبار مصرفی و هزینه ساخت هر کیفیت">
              @foreach($qualityRows as $qualityKey => $qualityLabel)
                @php
                  $labCost = (array) $productLabCostsByQuality->get($qualityKey);
                  $costTone = in_array(($labCost['tone'] ?? 'unavailable'), ['actual', 'verified', 'estimated', 'model', 'unavailable'], true)
                    ? $labCost['tone']
                    : 'unavailable';
                  $costSourceLabel = $labCost['source_label'] ?? (($labCost['source'] ?? null) === 'actual' ? 'هزینه واقعی' : 'هزینه تخمینی');
                  $costSourceDetail = $labCost['source_detail'] ?? 'جزئیات منبع قیمت ثبت نشده است.';
                @endphp
                <div class="product-credit-quality-row">
                  <span class="product-quality-label">{{ $qualityLabel }}</span>
                  <span class="product-quality-credits">{{ number_format((int) ($product->pricing_model === 'free' ? 0 : ($qualityCreditCosts[$qualityKey] ?? 0))) }} اعتبار</span>
                  @if(($labCost['status'] ?? null) === 'available')
                    <span class="product-quality-cost product-lab-cost-source product-lab-cost-source--{{ $costTone }}" tabindex="0" aria-label="{{ $costSourceLabel }}">
                      ${{ number_format((float) $labCost['usd'], 4) }}
                      <small>·</small>
                      {{ number_format((int) $labCost['toman']) }} تومان
                      <span class="product-lab-cost-tooltip" role="tooltip">
                        <strong>{{ $costSourceLabel }}</strong>
                        <small>{{ $costSourceDetail }}</small>
                        @if(!empty($labCost['model']))<small>مدل: {{ $labCost['model'] }}</small>@endif
                        @if(!empty($labCost['provider']))<small>پرووایدر: <b dir="ltr">{{ $labCost['provider'] }}</b></small>@endif
                      </span>
                    </span>
                  @else
                    <span class="product-quality-cost product-lab-cost-source product-lab-cost-source--unavailable" tabindex="0" aria-label="{{ $costSourceLabel }}">
                      —
                      <span class="product-lab-cost-tooltip" role="tooltip">
                        <strong>{{ $costSourceLabel }}</strong>
                        <small>{{ $costSourceDetail }}</small>
                      </span>
                    </span>
                  @endif
                </div>
              @endforeach
            </div>
          </td>

          <td data-label="مالک / تعداد اجرا" style="text-align:center;">
            {{-- آمار واقعی اجرا از جدول generations (generations_count در کنترلر withCount شده)
                 نوار محبوبیت = نسبت اجرای این محصول به پراجراترین محصول پلتفرم ($maxRuns) --}}
            @php
            $runs = (int) ($product->generations_count ?? 0) + (int) ($product->generated_videos_count ?? 0);
              $runsPct = ($maxRuns ?? 0) > 0 ? (int) round(($runs / $maxRuns) * 100) : 0;
            @endphp
            <div class="flex flex-col items-center justify-center gap-1.5 pro-tooltip-wrap w-full">
              <div class="flex items-center justify-center gap-1.5 max-w-full" title="مالک محصول">
                <i class="fa-solid fa-user-tag text-[9px]" style="color:var(--primary);"></i>
                <span class="text-[9px] font-bold truncate max-w-[110px]" style="color:var(--text-soft);">{{ $product->creatorRewardOwner ? trim($product->creatorRewardOwner->name.' '.($product->creatorRewardOwner->last_name ?? '')) : 'مالک تعیین نشده' }}</span>
              </div>
              <div class="flex flex-col items-center justify-center gap-1 w-full">
                <span class="font-bold text-[12.5px] text-center" style="color:var(--text-h);">{{ number_format($runs) }}</span>
                <div class="progress-track mx-auto">
                  <div class="progress-fill" style="width:{{ $runsPct }}%;"></div>
                </div>
              </div>
              <div class="pro-tooltip" style="width:190px;">این محصول {{ number_format($runs) }} بار توسط کاربران اجرا شده — معادل {{ $runsPct }}٪ پراجراترین محصول پلتفرم</div>
            </div>
          </td>

          <td data-label="{{ ($isVideoList ?? false) ? 'مدت / قاب / اعتبار مصرفی' : 'زمان اجرا / اعتبار مصرفی' }}" style="text-align:center;">
            @php
              $labExperiment = $product->latestLabExperiment;
              $labRuns = $labExperiment?->runs ?? collect();
              $labRunForStats = $labRuns->firstWhere('is_selected', true)
                ?? $labRuns->filter(fn ($run) => $run->rank !== null)->sortBy('rank')->first()
                ?? $labRuns->firstWhere('status', 'completed')
                ?? $labRuns->first();
              $labBuildSeconds = $labRunForStats?->build_seconds !== null
                ? (float) $labRunForStats->build_seconds
                : ($labRunForStats?->duration_ms !== null ? (float) $labRunForStats->duration_ms / 1000 : null);
              $labRunTokens = $labRunForStats?->tokens_used;
              $totalUserTokens = (int) ($product->completed_generations_count ?? 0) * max(0, (int) ($product->pricing_model === 'free' ? 0 : $product->qualityCreditCost('standard')));
            @endphp
            <div class="product-run-token-stack flex flex-col items-center justify-center gap-1.5">
              @if($isVideoList ?? false)
                @php $videoListConfig = $videoListConfig ?? $product->videoConfiguration(); @endphp
                <div><span>مدت</span><strong>{{ implode('، ', array_map(fn ($item) => $item . ' ثانیه', (array) ($videoListConfig['durations'] ?? []))) ?: '—' }}</strong></div>
                <div><span>قاب</span><strong dir="ltr">{{ implode(' · ', (array) ($videoListConfig['aspect_ratios'] ?? [])) ?: '—' }}</strong></div>
              @else
                <div><span>زمان اجرا</span><strong>{{ $labBuildSeconds !== null ? number_format($labBuildSeconds, 1) . ' ثانیه' : '—' }}</strong></div>
              @endif
              <div><span>اعتبار مصرفی</span><strong>{{ $labRunTokens !== null ? number_format((int) $labRunTokens) : '—' }}</strong></div>
              <div><span>مجموع اعتبار</span><strong>{{ number_format($totalUserTokens) }}</strong></div>
              <div><span>اعتبار پاداش داده‌شده</span><strong class="text-[var(--primary)]">{{ number_format((int) ($product->creator_reward_credits_sum ?? 0)) }}</strong></div>
            </div>
          </td>

          @php
            $hasScoredLabExperiment = (int) ($product->scored_lab_experiments_count ?? 0) > 0;
          @endphp
          <td data-label="فعال / {{ ($isVideoList ?? false) ? 'پیش‌نمایش / آزمایشگاه' : 'بهینه سازی / آزمایشگاه' }}" style="text-align:center;">
            @php
              $statusMap = [
                'active'   => ['label' => 'فعال',      'class' => 'badge-success'],
                'draft'    => ['label' => 'پیش‌نویس',  'class' => 'badge-warning'],
                'inactive' => ['label' => 'غیرفعال',   'class' => 'badge-danger'],
              ];
              $st = $statusMap[$product->status] ?? $statusMap['draft'];
            @endphp
            <div class="product-status-optimization-stack">
              <div class="product-status-line">
                @if($product->status === 'draft')
                  <span class="badge-pro {{ $st['class'] }}" style="display:inline-flex;" title="برای انتشار، محصول را ویرایش کنید"><i class="fa-solid fa-circle"></i> {{ $st['label'] }}</span>
                @else
                  <span class="badge-pro {{ $st['class'] }} is-clickable" style="display:inline-flex;" title="برای تغییر سریع وضعیت کلیک کنید" onclick="quickToggleStatus({{ $product->id }}, this)"><i class="fa-solid fa-circle"></i> {{ $st['label'] }}</span>
                @endif
              </div>
              <div class="flex items-center justify-center gap-1.5">
              @if($isVideoList ?? false)
                <a href="{{ $product->previewVideoUrl() ?: '#' }}" class="icon-action-btn inline-flex items-center justify-center" style="color:{{ $product->previewVideoUrl() ? 'var(--success)' : 'var(--text-soft)' }};" {{ $product->previewVideoUrl() ? 'target=_blank rel=noopener' : '' }} title="{{ $product->previewVideoUrl() ? 'مشاهده ویدیوی پیش‌نمایش' : 'پیش‌نمایش ثبت نشده' }}"><i class="fa-solid fa-video"></i></a>
              @else
              <button type="button"
                      class="product-image-optimize-btn icon-action-btn {{ $product->images_optimized_at ? 'is-optimized' : '' }}"
                      data-product-id="{{ $product->id }}"
                      data-state="{{ $product->images_optimized_at ? 'done' : 'idle' }}"
                      data-url="{{ route('admin.products.optimize_images', $product) }}"
                      title="{{ $product->images_optimized_at ? 'بهینه‌سازی انجام شده — آخرین بررسی: '.\App\Support\Jalali::formatNumeric($product->images_optimized_at) : 'بهینه‌سازی استاندارد تمام عکس‌های این محصول' }}"
                      aria-label="بهینه‌سازی تصاویر محصول"
                      @if($product->images_optimized_at) style="color:var(--success);" @endif
                      onclick="optimizeProductImagesFromTable(this)">
                <i class="fa-solid {{ $product->images_optimized_at ? 'fa-circle-check' : 'fa-wand-magic-sparkles' }}"></i>
              </button>
              @endif
              <a href="{{ $hasScoredLabExperiment ? '#' : route('admin.lab.create', ['product_id' => $product->id]) }}"
                 @if($hasScoredLabExperiment) data-summary-url="{{ route('admin.lab.products.summary', $product) }}" onclick="openProductLabSummary(event, this)" @endif
                 class="icon-action-btn inline-flex items-center justify-center"
                 style="color:{{ $hasScoredLabExperiment ? 'var(--success)' : 'var(--danger)' }};"
                 title="{{ $hasScoredLabExperiment ? 'آزمایش انجام و امتیازدهی شده — مشاهده نتایج' : 'هنوز آزمایش امتیازدهی‌شده‌ای برای این محصول ثبت نشده — شروع آزمایش' }}"
                 aria-label="{{ $hasScoredLabExperiment ? 'مشاهده آزمایش‌های امتیازدهی‌شده' : 'شروع آزمایش محصول' }}">
                <i class="fa-solid fa-flask"></i>
              </a>
            </div>
            </div>
          </td>

          <td data-label="آخرین ویرایش">
            {{-- تاریخ و ساعت شمسی ثبت + آخرین ویرایش محصول (App\Support\Jalali::formatNumeric) --}}
            <div class="product-audit-stack">
              <div class="product-audit-entry">
                <span class="product-audit-date" dir="rtl">{{ \App\Support\Jalali::formatNumeric($product->created_at) }}</span>
                <span class="product-audit-actor" title="{{ $product->creator?->name ?: 'ثبت‌کننده ثبت نشده' }}">{{ $product->creator?->name ?: 'ثبت‌کننده ثبت نشده' }}</span>
              </div>
              <div class="product-audit-entry">
                <span class="product-audit-date" dir="rtl">{{ \App\Support\Jalali::formatNumeric($product->updated_at) }}</span>
                <span class="product-audit-actor" title="{{ $product->editor?->name ?: 'ویرایش‌کننده ثبت نشده' }}">{{ $product->editor?->name ?: 'ویرایش‌کننده ثبت نشده' }}</span>
              </div>
            </div>
          </td>

          <td data-label="عملیات / لینک / تعداد لایک">
            <div class="product-actions-stack">
              <div class="product-actions-grid">
                <div class="product-like-box" title="تعداد کل لایک" aria-label="تعداد کل لایک">
                  <span class="product-like-count">{{ number_format($product->displayed_likes_count) }}</span>
                  <i class="fa-solid fa-heart product-like-icon" aria-hidden="true"></i>
                </div>
                <button type="button" class="icon-action-btn favorite" title="نشان‌کردن به‌عنوان مهم (نیاز به بررسی برنامه برای ذخیره‌سازی)" onclick="this.classList.toggle('is-active')">
                  <i class="fa-solid fa-star"></i>
                </button>

                <div class="dropdown-pro">
                  <button type="button" class="icon-action-btn" onclick="toggleRowDropdown(event, {{ $product->id }})">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                  </button>
                  <div class="dropdown-pro-menu" id="row-dropdown-{{ $product->id }}">
                    <a href="{{ $product->isVideoProduct() ? route('admin.products.video.create', $product) : route('admin.products.create', $product) }}" class="dropdown-pro-item"><i class="fa-solid fa-pen"></i> ویرایش</a>
                    {{-- تکثیر محصول: به‌جای ذخیره‌ی مستقیم، وارد فرم «ثبت محصول» با تمام فیلدهای پرشده می‌شود
                         تا ادمین قبل از ثبت نهایی بتواند مقادیر را بازبینی/ویرایش کند (مسیر کوتاه‌تر). --}}
                    @unless($product->isVideoProduct())
                      <a href="{{ route('admin.products.create') }}?duplicate={{ $product->id }}" class="dropdown-pro-item"><i class="fa-solid fa-copy"></i> تکثیر محصول</a>
                    @endunless
                    <button type="button" class="dropdown-pro-item" onclick="quickToggleStatus({{ $product->id }}, null)">
                      <i class="fa-solid fa-toggle-on"></i> {{ $product->status === 'active' ? 'تغییر وضعیت به غیرفعال' : 'تغییر وضعیت به فعال' }}
                    </button>
                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('این محصول حذف شود؟')">
                      @csrf @method('DELETE')
                      <button type="submit" class="dropdown-pro-item danger"><i class="fa-solid fa-trash"></i> حذف</button>
                    </form>
                  </div>
                </div>
                @php $publicProductUrl = route('app.product', $product->route_slug); @endphp
                <div class="pro-tooltip-wrap product-action-link">
                  <button type="button" class="icon-action-btn" aria-label="کپی لینک محصول" data-product-url="{{ $publicProductUrl }}" onclick="copyProductPublicLink(this)">
                    <i class="fa-solid fa-link"></i>
                  </button>
                  <div class="pro-tooltip" dir="ltr" style="width:260px;overflow-wrap:anywhere;">{{ $publicProductUrl }}</div>
                </div>
              </div>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="11" class="td-empty">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-box-open"></i></div>
              <div class="empty-state-title">هنوز محصولی ثبت نشده است.</div>
              <div class="empty-state-desc">بعد از ایجاد اولین محصول، این بخش اطلاعات کامل محصولات را نمایش خواهد داد.</div>
              <a href="{{ ($isVideoList ?? false) ? route('admin.products.video.create') : route('admin.products.create') }}" class="btn-pro btn-pro-primary" style="display:inline-flex;">
                <i class="fa-solid {{ ($isVideoList ?? false) ? 'fa-video' : 'fa-plus' }} text-[11px]"></i> ثبت اولین محصول {{ ($isVideoList ?? false) ? 'ویدیو' : 'عکس' }}
              </a>
            </div>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
  </div>

  @include('admin.products.partials.pagination')

</div>

<dialog id="product-ai-model-dialog" class="rounded-2xl p-0 w-[min(92vw,460px)]" style="background:var(--card-bg);color:var(--text-main);border:1px solid var(--border);box-shadow:var(--shadow-card);">
  <div class="p-5">
    <div class="flex items-start justify-between gap-3 mb-5">
      <div>
        <div id="product-ai-dialog-title" class="text-[14px] font-extrabold" style="color:var(--text-h);">مدل هوش مصنوعی</div>
        <div id="product-ai-dialog-subtitle" class="text-[11px] mt-1" style="color:var(--text-soft);"></div>
      </div>
      <button type="button" class="icon-action-btn" onclick="closeProductAiModelDialog()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <div>
        <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">سرویس</label>
        <select id="product-ai-provider-select" class="input-pro w-full" onchange="renderProductAiModelOptions()"></select>
      </div>
      <div>
        <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">نوع مدل</label>
        <select id="product-ai-task-select" class="input-pro w-full" onchange="renderProductAiModelOptions()">
          <option value="product_image">متن + عکس → عکس</option>
          <option value="text_to_image">متن به عکس</option>
          <option value="image_to_image">عکس به عکس</option>
          <option value="face_consistency">حفظ هویت چهره</option>
          <option value="all">همه مدل‌های منتخب</option>
        </select>
      </div>
      <div>
        <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">جست‌وجوی مدل</label>
        <input id="product-ai-model-search" class="input-pro w-full" type="search" placeholder="نام یا شناسه مدل..." oninput="renderProductAiModelOptions()" autocomplete="off">
        <select id="product-ai-model-select" class="hidden" onchange="saveProductAiModelSelection()"></select>
      </div>
    </div>
    <div class="product-ai-model-table-wrap mt-3">
      <div class="product-ai-model-table-head"><span dir="ltr">اسم انگلیسی مدل</span><span>اسم فارسی</span><span>پرووایدر</span><span>کاربری</span><span>گرید</span></div>
      <div id="product-ai-model-options" class="product-ai-model-options"></div>
    </div>
    <div class="mt-3 p-3 rounded-xl text-[10.5px] leading-6" style="background:var(--primary-l);border:1px solid var(--primary-m);color:var(--text-main);">
      <i class="fa-solid fa-circle-info ml-1" style="color:var(--primary);"></i>
      فقط مدل اصلی و سرویس هوش مصنوعی ذخیره می‌شود؛ پرامپت، ویژگی‌ها، تصاویر و مدل‌های جایگزین محصول دست‌نخورده می‌مانند.
    </div>
    <div id="product-ai-dialog-state" class="text-[10.5px] mt-3 min-h-5" style="color:var(--text-soft);">با انتخاب مدل، تغییر به‌صورت خودکار ذخیره می‌شود.</div>
  </div>
</dialog>

<script>
@php
$assignableAiModelsForJs = ($assignableAiModels ?? collect())->map(fn ($model) => [
    'id' => $model->openrouter_model_id,
    'name' => $model->name,
    'englishName' => $model->englishDisplayName(),
    'persianName' => $model->name,
    'provider' => $model->provider,
    'providerFa' => ['openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate'][$model->provider] ?? $model->provider_name,
    'providerEn' => ['openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate'][$model->provider] ?? $model->provider_name,
    'usage' => $model->productWorkflowLabel(),
    'workflow' => $model->supportsProductImageWorkflow() ? 'product_image' : $model->task_type,
    'task' => $model->task_type,
    'useCases' => $model->recommendedUseCaseKeys(),
    'grade' => $model->qualityGradeLabel(),
    'gradeNumber' => $model->pricingGrade(),
    'usd' => data_get($model->lab_pricing ?? [], 'usd'),
    'toman' => is_numeric(data_get($model->lab_pricing ?? [], 'usd'))
        ? (int) round(((float) data_get($model->lab_pricing ?? [], 'usd') * (float) data_get($exchange ?? [], 'rate', 0)) / 10)
        : null,
  ])->values()->all();
$modelTierDefaultsForJs = ($modelTierDefaults ?? collect())->mapWithKeys(fn ($item) => [$item->tier_key => [
    'name' => $item->name,
    'grade' => $item->grade,
    'primary' => ['model_id' => $item->primary_model_id, 'provider' => $item->primary_provider],
    'fallback' => ['model_id' => $item->fallback_model_id, 'provider' => $item->fallback_provider],
]])->all();
$modelTierDefinitionsForJs = \App\Services\ModelTierService::DEFINITIONS;
$modelQualityPresetsForJs = ($modelQualityPresets ?? collect())->mapWithKeys(fn ($preset) => [$preset->preset_key => [
    'name' => $preset->name,
    'configuration' => $preset->configuration ?: [],
    'url' => route('admin.model-quality-presets.update', $preset),
]])->all();
$qualityCreditPresetsForJs = ($qualityCreditPresets ?? collect())->mapWithKeys(fn ($preset) => [$preset->preset_key => [
    'name' => $preset->name,
    'costs' => $preset->costs(),
    'is_default_for_product_creation' => (bool) $preset->is_default_for_product_creation,
]])->all();
$modelQualityDefaultPresetKey = ($modelQualityPresets ?? collect())->first(fn ($preset) => (bool) ($preset->is_default_for_product_creation ?? false))?->preset_key
    ?: ($modelQualityPresets ?? collect())->first()?->preset_key;
@endphp
window.PRODUCT_ASSIGNABLE_AI_MODELS = @json($assignableAiModelsForJs);
window.PRODUCT_BULK_AI_URL = @json(route('admin.products.bulk_update_ai_model'));
window.PRODUCT_BULK_TIER_URL = @json(route('admin.products.bulk_apply_model_tier_preset'));
window.PRODUCT_MATCHING_IDS = @json($matchingProductIds ?? []);
window.PRODUCT_MODEL_TIER_DEFAULTS = @json($modelTierDefaultsForJs);
window.PRODUCT_MODEL_TIER_DEFINITIONS = @json($modelTierDefinitionsForJs);
window.PRODUCT_MODEL_QUALITY_PRESETS = @json($modelQualityPresetsForJs);
window.PRODUCT_QUALITY_CREDIT_PRESETS = @json($qualityCreditPresetsForJs);
window.PRODUCT_MODEL_QUALITY_DEFAULT_KEY = @json($modelQualityDefaultPresetKey);
window.PRODUCT_BULK_MODEL_QUALITY_URL = @json(route('admin.products.bulk_update_model_quality_configuration'));
window.PRODUCT_BULK_QUALITY_CREDIT_URL = @json(route('admin.products.bulk_apply_quality_credit_preset'));
window.PRODUCT_QUALITY_CREDIT_PRESET_STORE_URL = @json(route('admin.product-credit-presets.store'));
</script>

<style>
#product-ai-model-dialog{position:fixed;inset:0;margin:auto;}
#product-ai-model-dialog::backdrop{background:color-mix(in srgb,var(--text-h) 55%,transparent);}
#product-tier-configuration-dialog{position:fixed;inset:0;margin:auto;}
#product-tier-configuration-dialog::backdrop{background:color-mix(in srgb,var(--text-h) 55%,transparent);}
#product-quality-configuration-dialog{position:fixed;inset:0;margin:auto;}
#product-quality-credit-preset-dialog{position:fixed;inset:0;margin:auto;}
#product-quality-configuration-dialog::backdrop{background:color-mix(in srgb,var(--text-h) 55%,transparent);}
.product-ai-model-table-wrap{max-height:270px;overflow:auto;border:1px solid var(--border);border-radius:10px;background:var(--input-bg);}
.product-ai-model-table-head,.product-ai-model-row{display:grid;grid-template-columns:1.45fr 1.35fr .95fr 1.15fr .62fr;align-items:center;gap:8px;min-width:690px;padding:8px 9px;}
.product-ai-model-table-head{color:var(--text-soft);font-size:8px;font-weight:800;border-bottom:1px solid var(--border);}
.product-ai-model-row{width:100%;border:0;border-bottom:1px solid var(--border);background:transparent;color:var(--text-main);font-size:8.5px;text-align:right;cursor:pointer;}
.product-ai-model-row:last-child{border-bottom:0;}
.product-ai-model-row:hover,.product-ai-model-row.is-selected{background:var(--primary-l);}
.product-ai-model-row>span{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.product-ai-model-row>span:nth-child(3){display:flex;flex-direction:column;gap:1px;}
.product-ai-model-row>span:nth-child(3) small{color:var(--text-soft);font-size:7.5px;}
.product-ai-model-row .model-quality-grade{color:var(--warning);font-weight:800;}
@media (min-width:901px){#product-ai-model-dialog{transform:translateX(-147px);}}
@media (min-width:901px){#product-tier-configuration-dialog{transform:translateX(-147px);}}
@media (min-width:901px){#product-quality-configuration-dialog{transform:translateX(-147px);}}
@media (min-width:901px){#product-quality-credit-preset-dialog{transform:translateX(-147px);}}
</style>
<dialog id="product-tier-configuration-dialog" class="rounded-2xl p-0 w-[min(96vw,930px)] max-h-[90vh]" style="background:var(--card-bg);color:var(--text-main);border:1px solid var(--border);box-shadow:var(--shadow-card);">
  <div class="p-5 overflow-y-auto max-h-[90vh]">
    <div class="flex items-start justify-between gap-4 mb-4">
      <div>
        <div id="product-tier-configuration-title" class="text-[14px] font-extrabold" style="color:var(--text-h);">مدل‌های چهار سطحی</div>
        <div id="product-tier-configuration-subtitle" class="text-[11px] mt-1" style="color:var(--text-soft);"></div>
      </div>
      <button type="button" class="icon-action-btn" onclick="closeProductTierConfigurationDialog()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div id="product-tier-configuration-hint" class="text-[10.5px] leading-6 p-3 rounded-xl mb-4" style="background:var(--primary-l);color:var(--text-main);border:1px solid var(--primary-m);"></div>
    <div id="product-tier-configuration-content" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
    <div id="product-tier-configuration-state" class="text-[10.5px] mt-3 min-h-5" style="color:var(--text-soft);"></div>
    <div class="flex items-center justify-between gap-3 flex-wrap mt-5 pt-4" style="border-top:1px solid var(--border);">
      <button type="button" class="btn-pro btn-pro-ghost" onclick="applyAllTierDefaultsInDialog()"><i class="fa-solid fa-wand-magic-sparkles text-[11px]"></i> انتخاب چهار پیش‌فرض</button>
      <div class="flex items-center gap-2"><button type="button" class="btn-pro btn-pro-ghost" onclick="closeProductTierConfigurationDialog()">انصراف</button><button type="button" class="btn-pro btn-pro-primary" id="product-tier-configuration-submit" onclick="saveProductTierConfiguration()">ذخیره چهار سطح</button></div>
    </div>
  </div>
</dialog>
<dialog id="product-quality-configuration-dialog" class="rounded-2xl p-0 w-[min(96vw,1040px)]" style="background:var(--card-bg);color:var(--text-main);border:1px solid var(--border);box-shadow:var(--shadow-card);">
  <div class="p-5" dir="rtl">
    <div class="flex items-start justify-between gap-4 mb-4">
      <div>
        <div id="product-quality-configuration-title" class="text-[14px] font-extrabold" style="color:var(--text-h);">معماری کیفیت خروجی مدل</div>
        <div id="product-quality-configuration-subtitle" class="text-[11px] mt-1" style="color:var(--text-soft);"></div>
      </div>
      <div class="flex items-center gap-2 flex-wrap shrink-0">
        <button type="button" class="btn-pro btn-pro-ghost" onclick="closeProductQualityConfigurationDialog()">انصراف</button>
        <button type="button" class="btn-pro btn-pro-primary" id="product-quality-configuration-submit" onclick="saveProductQualityConfiguration()"><i class="fa-solid fa-check text-[11px]"></i> ذخیره تنظیمات</button>
        <button type="button" class="icon-action-btn" onclick="closeProductQualityConfigurationDialog()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
      </div>
    </div>
    <section class="rounded-xl overflow-hidden mb-4" style="background:var(--input-bg);border:1px solid var(--border);">
      <div class="flex items-center justify-between gap-3 flex-wrap p-4" style="border-bottom:1px solid var(--border);">
        <div class="flex items-center gap-2 min-w-0"><span class="w-8 h-8 grid place-items-center rounded-lg" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid fa-layer-group"></i></span><div><b class="text-[12px]" style="color:var(--text-h);">معماری کیفیت خروجی مدل</b><p class="text-[10px] mt-1" style="color:var(--text-soft);">برای هر کیفیت، مدل اصلی و مسیر جایگزین متفاوت را مشخص کنید.</p></div></div>
        <div class="flex items-center gap-2 flex-wrap">
          <label class="sr-only" for="product-quality-preset">پیش‌فرض‌ها</label>
          <select id="product-quality-preset" class="input-pro" style="height:36px;min-width:120px;" onchange="applyProductQualityPreset(this.value)"></select>
          <button type="button" class="btn-pro btn-pro-ghost" style="height:36px;" onclick="fixProductQualityPreset()"><i class="fa-solid fa-thumbtack text-[10px]"></i> فیکس کردن تنظیمات</button>
          <button type="button" id="product-quality-architecture-toggle" class="btn-pro btn-pro-ghost" style="height:36px;" onclick="toggleProductQualityArchitecture()"><i class="fa-solid fa-toggle-on text-[12px]"></i> <span>روشن</span></button>
        </div>
      </div>
      <div id="product-quality-configuration-hint" class="text-[10.5px] leading-6 p-3 mx-4 mt-4 rounded-xl" style="background:var(--primary-l);color:var(--text-main);border:1px solid var(--primary-m);"></div>
      <div id="product-quality-architecture-content" class="p-4 pt-0">
        <div class="rounded-xl p-4 mb-3" style="background:var(--card-bg);border:1px solid var(--border);">
          <div class="flex items-center gap-2 mb-4"><span class="w-7 h-7 grid place-items-center rounded-lg" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid fa-sliders"></i></span><div><b class="text-xs" style="color:var(--text-h);">مدل‌های انتخابی برای کاربر:</b><p class="text-[10px] mt-0.5" style="color:var(--text-soft);">مدل هر سه کیفیتِ قابل انتخاب پس از خرید پلن.</p></div></div>
          <div id="product-quality-paid-cards" class="grid grid-cols-1 xl:grid-cols-3 gap-3"></div>
        </div>
        <div class="rounded-xl p-4" style="background:var(--card-bg);border:1px solid var(--border);">
          <div class="flex items-center gap-2 mb-4"><span class="w-7 h-7 grid place-items-center rounded-lg" style="background:var(--primary-l);color:var(--orange);"><i class="fa-solid fa-user-clock"></i></span><div><b class="text-xs" style="color:var(--text-h);">کاربران بدون پلن خریداری‌شده</b><p class="text-[10px] mt-0.5" style="color:var(--text-soft);">کاربر رایگان انتخاب کیفیت نمی‌بیند و با مسیر استاندارد هدیه می‌سازد.</p></div></div>
          <div id="product-quality-free-cards" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
        </div>
      </div>
    </section>
    <div id="product-quality-configuration-state" class="text-[10.5px] mt-3 min-h-5" style="color:var(--text-soft);"></div>
  </div>
</dialog>
<dialog id="product-quality-credit-preset-dialog" class="rounded-2xl p-0 w-[min(92vw,520px)]" style="background:var(--card-bg);color:var(--text-main);border:1px solid var(--border);box-shadow:var(--shadow-card);">
  <form method="dialog" id="product-quality-credit-preset-form" class="p-5">
    <div class="flex items-start justify-between gap-3 mb-5">
      <div>
        <div class="text-[14px] font-extrabold" style="color:var(--text-h);">تنظیم مصرف اعتبار</div>
        <div id="product-quality-credit-preset-count" class="text-[11px] mt-1" style="color:var(--text-soft);"></div>
      </div>
      <button type="button" class="icon-action-btn" onclick="closeBulkQualityCreditPresetDialog()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="p-3 rounded-xl text-[10.5px] leading-6 mb-4" style="background:var(--primary-l);border:1px solid var(--primary-m);color:var(--text-main);">
      فقط سه مقدار مصرف اعتبار تغییر می‌کند؛ مدل‌های اصلی، جایگزین و سایر تنظیمات محصولات دست‌نخورده می‌مانند.
    </div>
    <label for="product-quality-credit-preset-select" class="block text-[11.5px] font-bold mb-2">پیش‌فرض مصرف اعتبار</label>
    <select id="product-quality-credit-preset-select" class="input-pro w-full" style="height:44px;"></select>
    <div id="product-quality-credit-preset-values" class="grid grid-cols-3 gap-2 mt-3"></div>
    <div class="flex items-center gap-2 mt-4 mb-2 text-[10.5px]">
      <button type="button" class="btn-pro btn-pro-primary" id="bulk-credit-use-preset">استفاده از پیش‌فرض</button>
      <button type="button" class="btn-pro btn-pro-ghost" id="bulk-credit-use-manual">ورود دستی</button>
    </div>
    <div id="product-quality-credit-manual" class="hidden grid grid-cols-3 gap-2 mt-2">
      @foreach(['standard' => 'استاندارد', 'professional' => 'حرفه‌ای', 'best' => 'بهترین خروجی'] as $quality => $label)
        <label class="text-[10px] text-[var(--text-soft)]">{{ $label }}
          <input type="number" min="1" max="1000000" step="1" data-bulk-credit-cost="{{ $quality }}" class="input-pro w-full mt-1" style="height:40px;">
        </label>
      @endforeach
    </div>
    <div class="flex items-center gap-2 mt-3">
      <input id="product-quality-credit-new-preset-name" type="text" maxlength="100" class="input-pro flex-1" style="height:40px;" placeholder="نام پیش‌فرض جدید برای این اعداد">
      <button type="button" class="btn-pro btn-pro-ghost" id="product-quality-credit-save-preset"><i class="fa-solid fa-plus"></i> ساخت پیش‌فرض</button>
    </div>
    <div id="product-quality-credit-preset-error" class="hidden text-[10.5px] mt-3" style="color:var(--danger);"></div>
    <div class="flex items-center justify-end gap-2 mt-5">
      <button type="button" class="btn-pro btn-pro-ghost" onclick="closeBulkQualityCreditPresetDialog()">انصراف</button>
      <button type="submit" class="btn-pro btn-pro-primary" id="product-quality-credit-preset-submit"><i class="fa-solid fa-check"></i> اعمال برای محصولات</button>
    </div>
  </form>
</dialog>
<script>
function showAdminActor(role, name) {
  const old = document.getElementById('admin-actor-toast');
  if (old) old.remove();
  const box = document.createElement('div');
  box.id = 'admin-actor-toast';
  box.className = 'admin-toast fixed left-5 bottom-5 z-[120] px-4 py-3 rounded-xl text-[12px]';
  box.style.cssText += 'background:var(--card-bg);color:var(--text-main);border:1px solid var(--border);';
  box.innerHTML = '<span class="admin-toast-icon" style="background:var(--primary-l);color:var(--primary);"><i class="fa-solid fa-user-shield"></i></span><span><b>'+role+'</b><br><span style="color:var(--text-soft);">'+name+' · نمایش موقت رابط کاربری</span></span>';
  document.body.appendChild(box);
  setTimeout(function(){ box.remove(); }, 3500);
}
async function copyProductPublicLink(button) {
  var url = button?.dataset.productUrl || '';
  if (!url) return;
  try {
    await navigator.clipboard.writeText(url);
  } catch (error) {
    var helper = document.createElement('textarea');
    helper.value = url;
    helper.style.position = 'fixed';
    helper.style.opacity = '0';
    document.body.appendChild(helper);
    helper.select();
    document.execCommand('copy');
    helper.remove();
  }
  var icon = button.querySelector('i');
  var oldClass = icon?.className;
  if (icon) icon.className = 'fa-solid fa-check';
  button.style.color = 'var(--success)';
  button.title = 'لینک محصول کپی شد';
  setTimeout(function () {
    if (icon) icon.className = oldClass;
    button.style.color = '';
    button.title = '';
  }, 1800);
}

function showProductCreditToast(message) {
  document.getElementById('product-credit-success-toast')?.remove();
  var toast = document.createElement('div');
  toast.id = 'product-credit-success-toast';
  toast.className = 'admin-toast fixed left-5 bottom-5 z-[130] px-4 py-3 rounded-xl text-[12.5px] font-semibold';
  toast.style.cssText += 'font-family:YekanBakh,sans-serif;background:var(--success-l);color:var(--success);border:1px solid var(--success-m);box-shadow:var(--shadow-card);';
  toast.setAttribute('role', 'status');
  toast.innerHTML = '<span class="admin-toast-icon" style="background:var(--success-m);"><i class="fa-solid fa-circle-check"></i></span><span class="flex-1"></span><button type="button" aria-label="بستن پیام"><i class="fa-solid fa-xmark"></i></button>';
  toast.querySelector('.flex-1').textContent = message;
  toast.querySelector('button').addEventListener('click', function () { toast.remove(); });
  document.body.appendChild(toast);
  setTimeout(function () { toast.remove(); }, 4500);
}

function renderBulkQualityCreditPresetOptions() {
  var select = document.getElementById('product-quality-credit-preset-select');
  var values = document.getElementById('product-quality-credit-preset-values');
  if (!select || !values) return;
  var presets = window.PRODUCT_QUALITY_CREDIT_PRESETS || {};
  var esc = function (value) { return String(value ?? '').replace(/[&<>'"]/g, function (character) { return ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'})[character]; }); };
  select.innerHTML = Object.entries(presets).map(function (entry) {
    return '<option value="' + esc(entry[0]) + '">' + esc(entry[1].name || entry[0]) + '</option>';
  }).join('');
  var draw = function () {
    var costs = presets[select.value]?.costs || {};
    values.innerHTML = [['standard','استاندارد'],['professional','حرفه‌ای'],['best','بهترین خروجی']].map(function (pair) {
      return '<div class="p-2 rounded-lg" style="background:var(--input-bg);border:1px solid var(--border);"><small style="display:block;color:var(--text-soft);font-size:9px;">' + pair[1] + '</small><b style="display:block;color:var(--text-h);font-size:13px;margin-top:3px;">' + Number(costs[pair[0]] || 0).toLocaleString('fa-IR') + ' <small style="font-size:9px;font-weight:400;color:var(--text-soft);">اعتبار</small></b></div>';
    }).join('');
  };
  select.onchange = draw;
  document.getElementById('bulk-credit-use-preset')?.addEventListener('click', function () {
    document.getElementById('product-quality-credit-manual')?.classList.add('hidden');
    select.disabled = false;
    draw();
  });
  document.getElementById('bulk-credit-use-manual')?.addEventListener('click', function () {
    document.getElementById('product-quality-credit-manual')?.classList.remove('hidden');
    select.disabled = true;
    var costs = presets[select.value]?.costs || {};
    document.querySelectorAll('[data-bulk-credit-cost]').forEach(function (input) { input.value = costs[input.dataset.bulkCreditCost] || ''; });
    values.innerHTML = '<div class="col-span-3 text-[10px]" style="color:var(--text-soft);">سه مقدار دستی را وارد کنید و سپس اعمال را بزنید.</div>';
  });
  draw();
}

function openBulkQualityCreditPresetDialog() {
  var ids = typeof requireBulkSelection === 'function' ? requireBulkSelection() : [];
  if (!ids) return;
  var dialog = document.getElementById('product-quality-credit-preset-dialog');
  if (!dialog || !Object.keys(window.PRODUCT_QUALITY_CREDIT_PRESETS || {}).length) {
    if (typeof showProductNotice === 'function') showProductNotice('پیش‌فرض‌های مصرف اعتبار از سرور دریافت نشد.', 'error');
    return;
  }
  document.getElementById('product-quality-credit-preset-count').textContent = ids.length.toLocaleString('fa-IR') + ' محصول برای تغییر انتخاب شده است.';
  document.getElementById('product-quality-credit-preset-error').classList.add('hidden');
  renderBulkQualityCreditPresetOptions();
  dialog.showModal();
}

function closeBulkQualityCreditPresetDialog() {
  document.getElementById('product-quality-credit-preset-dialog')?.close();
}

document.getElementById('product-quality-credit-preset-form')?.addEventListener('submit', async function (event) {
  event.preventDefault();
  var ids = typeof getSelectedBulkProductIds === 'function' ? getSelectedBulkProductIds() : [];
  var presetKey = document.getElementById('product-quality-credit-preset-select')?.value || '';
  var manual = !document.getElementById('product-quality-credit-manual')?.classList.contains('hidden');
  var costs = {};
  document.querySelectorAll('[data-bulk-credit-cost]').forEach(function (input) { costs[input.dataset.bulkCreditCost] = Number(input.value || 0); });
  var errorBox = document.getElementById('product-quality-credit-preset-error');
  var submit = document.getElementById('product-quality-credit-preset-submit');
  if (!ids.length || (!presetKey && !manual)) {
    errorBox.textContent = 'حداقل یک محصول و یک پیش‌فرض یا مقادیر دستی را انتخاب کنید.';
    errorBox.classList.remove('hidden');
    return;
  }
  if (manual && Object.keys(costs).some(function (key) { return !Number.isInteger(costs[key]) || costs[key] < 1 || costs[key] > 1000000; })) {
    errorBox.textContent = 'هر سه مقدار دستی باید عدد صحیح بین ۱ تا ۱٬۰۰۰٬۰۰۰ باشند.';
    errorBox.classList.remove('hidden');
    return;
  }
  submit.disabled = true;
  try {
    var response = await fetch(window.PRODUCT_BULK_QUALITY_CREDIT_URL, {
      method: 'PATCH',
      headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''},
      credentials: 'same-origin',
      body: JSON.stringify({ids: ids, preset_key: manual ? null : presetKey, costs: manual ? costs : null}),
    });
    var data = await response.json().catch(function () { return {}; });
    if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'اعمال پیش‌فرض مصرف اعتبار انجام نشد.');
    closeBulkQualityCreditPresetDialog();
    showProductCreditToast(data.message || 'پیش‌فرض مصرف اعتبار اعمال شد.');
    setTimeout(function () { window.location.reload(); }, 650);
  } catch (error) {
    errorBox.textContent = error.message || 'خطا در اعمال پیش‌فرض مصرف اعتبار.';
    errorBox.classList.remove('hidden');
  } finally { submit.disabled = false; }
});

document.getElementById('product-quality-credit-save-preset')?.addEventListener('click', async function () {
  var nameInput = document.getElementById('product-quality-credit-new-preset-name');
  var name = String(nameInput?.value || '').trim();
  var errorBox = document.getElementById('product-quality-credit-preset-error');
  var select = document.getElementById('product-quality-credit-preset-select');
  var manual = !document.getElementById('product-quality-credit-manual')?.classList.contains('hidden');
  var presets = window.PRODUCT_QUALITY_CREDIT_PRESETS || {};
  var costs = manual ? {} : (presets[select?.value]?.costs || {});
  if (manual) document.querySelectorAll('[data-bulk-credit-cost]').forEach(function (input) { costs[input.dataset.bulkCreditCost] = Number(input.value || 0); });
  if (!name) { errorBox.textContent = 'نام پیش‌فرض جدید را وارد کنید.'; errorBox.classList.remove('hidden'); return; }
  if (Object.keys(costs).some(function (key) { return !Number.isInteger(Number(costs[key])) || Number(costs[key]) < 1 || Number(costs[key]) > 1000000; })) {
    errorBox.textContent = 'برای ساخت پیش‌فرض، هر سه مقدار اعتبار باید معتبر باشد.'; errorBox.classList.remove('hidden'); return;
  }
  this.disabled = true;
  try {
    var response = await fetch(window.PRODUCT_QUALITY_CREDIT_PRESET_STORE_URL, {
      method: 'POST', headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''}, credentials: 'same-origin',
      body: JSON.stringify({name: name, costs: costs}),
    });
    var data = await response.json().catch(function () { return {}; });
    if (!response.ok) throw new Error(Object.values(data.errors || {})[0]?.[0] || data.message || 'ساخت پیش‌فرض انجام نشد.');
    var key = data.preset.preset_key;
    presets[key] = {name: data.preset.name, costs: data.costs, is_default_for_product_creation: !!data.preset.is_default_for_product_creation};
    window.PRODUCT_QUALITY_CREDIT_PRESETS = presets;
    renderBulkQualityCreditPresetOptions();
    if (select) select.value = key;
    if (nameInput) nameInput.value = '';
    errorBox.textContent = data.message || 'پیش‌فرض جدید ساخته شد.';
    errorBox.style.color = 'var(--success)';
    errorBox.classList.remove('hidden');
  } catch (error) {
    errorBox.textContent = error.message || 'ساخت پیش‌فرض انجام نشد.';
    errorBox.style.color = 'var(--danger)';
    errorBox.classList.remove('hidden');
  } finally { this.disabled = false; }
});

function formatProductImageBytes(bytes) {
  if (!Number.isFinite(Number(bytes))) return '—';
  if (Number(bytes) < 1024 * 1024) return Math.max(1, Math.round(Number(bytes) / 1024)).toLocaleString('fa-IR') + ' کیلوبایت';
  return (Number(bytes) / (1024 * 1024)).toLocaleString('fa-IR', { maximumFractionDigits: 2 }) + ' مگابایت';
}

async function optimizeProductImagesFromTable(button) {
  if (!button || button.dataset.state === 'processing') return;
  const icon = button.querySelector('i');
  button.dataset.state = 'processing';
  button.disabled = true;
  if (icon) icon.className = 'fa-solid fa-spinner fa-spin';
  button.style.color = 'var(--warning)';
  button.title = 'در حال بهینه‌سازی تمام تصاویر محصول…';

  try {
    const response = await fetch(button.dataset.url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      credentials: 'same-origin',
    });
    const data = await response.json().catch(function () { return {}; });
    if (!response.ok) throw new Error(data.message || 'بهینه‌سازی تصاویر انجام نشد.');

    button.dataset.state = 'done';
    if (icon) icon.className = 'fa-solid fa-circle-check';
    button.style.color = 'var(--success)';
    button.title = (data.message || 'بهینه‌سازی انجام شد.') + ' حجم قبل: ' + formatProductImageBytes(data.before_bytes) + ' — حجم بعد: ' + formatProductImageBytes(data.after_bytes);
    const row = button.closest('tr');
    const cover = row?.querySelector('.td-thumb img');
    if (cover && data.cover_url) cover.src = data.cover_url + (data.cover_url.includes('?') ? '&' : '?') + 'v=' + Date.now();
  } catch (error) {
    button.dataset.state = 'failed';
    if (icon) icon.className = 'fa-solid fa-rotate-right';
    button.style.color = 'var(--danger)';
    button.title = error.message || 'خطا در بهینه‌سازی؛ برای تلاش مجدد کلیک کنید.';
    alert(button.title);
  } finally {
    button.disabled = false;
  }
}

async function openProductLabSummary(event, link) {
  event.preventDefault();
  let dialog = document.getElementById('product-lab-summary-dialog');
  if (!dialog) { dialog = document.createElement('dialog'); dialog.id='product-lab-summary-dialog'; dialog.style.cssText='width:min(980px,94vw);max-height:88vh;border:1px solid var(--border);border-radius:14px;background:var(--surface);color:var(--text-main);padding:0;'; document.body.appendChild(dialog); }
  dialog.innerHTML='<div style="padding:18px"><div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><strong>جزئیات کامل آزمایش</strong><button type="button" class="icon-action-btn" onclick="this.closest(\'dialog\').close()"><i class="fa-solid fa-xmark"></i></button></div><div style="padding:28px;text-align:center;color:var(--text-soft)">در حال دریافت اطلاعات…</div></div>'; dialog.showModal();
  try { const response=await fetch(link.dataset.summaryUrl,{headers:{Accept:'application/json'}}); const data=await response.json(); if(!response.ok) throw new Error(data.message); const rows=(data.runs||[]).map(run=>`<tr><td>${run.model||'—'}</td><td>${run.provider||'—'}</td><td>${run.quality||'—'}</td><td>${run.size||'—'}</td><td>${run.seconds??'—'} ثانیه</td><td>${run.score??'—'}</td><td>${run.rank??'—'}</td><td>${run.cost_usd?('$'+Number(run.cost_usd).toFixed(4)):'—'}</td></tr>`).join(''); dialog.querySelector('div').innerHTML=`<div style="display:flex;justify-content:space-between;align-items:center;gap:10px"><div><strong>${data.product?.name||'آزمایش محصول'}</strong><small style="display:block;color:var(--text-soft)">${data.product?.code||''} · ${data.report_code||''}</small></div><button type="button" class="icon-action-btn" onclick="this.closest('dialog').close()"><i class="fa-solid fa-xmark"></i></button></div><div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:16px 0;font-size:11px"><span>مدل‌ها: <b>${data.models_count||0}</b></span><span>نمره: <b>${data.overall_score||'—'}</b></span><span>دلار: <b>${Number(data.cost?.usd||0).toFixed(4)}</b></span><span>تومان: <b>${Number(data.cost?.toman||0).toLocaleString('fa-IR')}</b></span></div><div style="overflow:auto"><table class="table-pro"><thead><tr><th>مدل</th><th>پرووایدر</th><th>کیفیت</th><th>نسبت</th><th>زمان ساخت</th><th>امتیاز</th><th>رتبه</th><th>هزینه</th></tr></thead><tbody>${rows}</tbody></table></div>`; } catch(error) { dialog.querySelector('div').innerHTML='<div style="padding:28px;color:var(--danger)">'+(error.message||'دریافت اطلاعات انجام نشد.')+'</div>'; }
}
</script>
