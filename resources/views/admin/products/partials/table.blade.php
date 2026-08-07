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
      <button type="button" class="btn-pro btn-pro-ghost" onclick="openBulkAiModelDialog()"><i class="fa-solid fa-microchip text-[11px]"></i> تغییر مدل هوش مصنوعی <i class="fa-solid fa-circle-question text-[9px]"></i></button>
      <span class="pro-tooltip" style="width:270px;">مدل اصلی همه محصولات انتخاب‌شده را یکجا تغییر می‌دهد. پرامپت، ویژگی‌ها و مدل‌های جایگزین محصولات تغییری نمی‌کنند.</span>
    </span>
    <button type="button" class="btn-pro btn-pro-ghost" onclick="openBulkCreditDialog()"><i class="fa-solid fa-coins text-[11px]"></i> تغییر گروهی کردیت</button>
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
        <th>کد محصول</th>
        <th style="text-align:center;">دسته‌بندی</th>
        <th style="text-align:center;"><span class="block">ویژگی‌ها</span><span class="block">هوش مصنوعی</span></th>
        <th style="text-align:center;"><span class="block">توکن</span><span class="block">قیمت</span></th>
        <th style="text-align:center;"><span class="block">تعداد اجرا</span><span class="block">تعداد لایک</span></th>
        <th style="text-align:center;"><span class="block">زمان اجرا</span><span class="block">توکن مصرفی</span></th>
        <th style="text-align:center;">بهینه سازی آزمایش</th>
        <th style="text-align:center;"><span class="block">وضعیت</span><span class="block">لینک</span></th>
        <th>آخرین ویرایش</th>
        <th>عملیات</th>
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

          <td data-label="کد محصول">
            {{-- کد اصلی ۶ رقمی محصول (ستون product_code) — همان کدی که در لینک عمومی محصول استفاده می‌شود --}}
            @if($product->product_code)
              <span class="font-mono text-[11.5px] font-semibold" style="color:var(--text-main);" dir="ltr">{{ $product->product_code }}</span>
            @else
              <span class="text-[11px]" style="color:var(--text-soft);" title="این محصول قدیمی هنوز کد ندارد — با اولین ویرایش، خودکار ساخته می‌شود">—</span>
            @endif
          </td>

          <td data-label="دسته‌بندی" style="text-align:center;">
            <div class="flex flex-col items-center justify-center gap-1">
              <span class="badge-pro badge-primary">{{ $product->category }}</span>
              @if($product->subcategory)<div class="text-[10.5px]" style="color:var(--text-soft);">{{ $product->subcategory }}</div>@endif
            </div>
          </td>

          <td data-label="ویژگی‌ها / هوش مصنوعی" style="text-align:center;">
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
            @if($featureTitles->isNotEmpty())
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

          <td data-label="توکن / قیمت" id="product-credit-cell-{{ $product->id }}" style="text-align:center;">
            @php
              $labExperiment = $product->latestLabExperiment;
              $modelCostUsd = $assignedAiModel?->cost_per_generation_usd;
              $modelCostIrr = $modelCostUsd !== null ? (float) $modelCostUsd * (float) ($exchange['rate'] ?? 0) : null;
              $priceUsd = $labExperiment
                ? (float) ($labExperiment->total_cost_usd ?: $labExperiment->actual_cost_usd ?: $labExperiment->estimated_cost_usd)
                : ($modelCostUsd !== null ? (float) $modelCostUsd : null);
              $priceToman = $labExperiment
                ? (float) ($labExperiment->total_cost_toman ?: $labExperiment->actual_cost_toman ?: $labExperiment->estimated_cost_toman)
                : ($modelCostIrr !== null ? (float) $modelCostIrr / 10 : null);
            @endphp
            <div class="product-price-stack" title="توکن محصول و قیمت ثبت‌شده در آخرین آزمایش">
              <span><small>توکن</small><b>{{ number_format((int) ($product->pricing_model === 'free' ? 0 : $product->credit_cost)) }}</b></span>
              <span dir="ltr"><small>دلار</small><b>{{ $priceUsd !== null ? '$' . number_format($priceUsd, 4) : '—' }}</b></span>
              <span><small>تومان</small><b>{{ $priceToman !== null ? number_format($priceToman) : '—' }}</b></span>
            </div>
          </td>

          <td data-label="تعداد اجرا / تعداد لایک" style="text-align:center;">
            {{-- آمار واقعی اجرا از جدول generations (generations_count در کنترلر withCount شده)
                 نوار محبوبیت = نسبت اجرای این محصول به پراجراترین محصول پلتفرم ($maxRuns) --}}
            @php
              $runs = (int) ($product->generations_count ?? 0);
              $runsPct = ($maxRuns ?? 0) > 0 ? (int) round(($runs / $maxRuns) * 100) : 0;
            @endphp
            <div class="flex flex-col items-center justify-center gap-1.5 pro-tooltip-wrap w-full">
              <div class="flex flex-col items-center justify-center gap-1 w-full">
                <span class="font-bold text-[12.5px] text-center" style="color:var(--text-h);">{{ number_format($runs) }}</span>
                <div class="progress-track mx-auto">
                  <div class="progress-fill" style="width:{{ $runsPct }}%;"></div>
                </div>
              </div>
              <div class="flex items-center justify-center gap-1.5" dir="rtl" title="تعداد کل لایک">
                <i class="fa-solid fa-heart text-[9px]" style="color:var(--danger);"></i>
                <span class="font-bold text-[11.5px]" style="color:var(--text-main);">{{ number_format($product->displayed_likes_count) }}</span>
              </div>
              <div class="pro-tooltip" style="width:190px;">این محصول {{ number_format($runs) }} بار توسط کاربران اجرا شده — معادل {{ $runsPct }}٪ پراجراترین محصول پلتفرم</div>
            </div>
          </td>

          <td data-label="زمان اجرا / توکن مصرفی" style="text-align:center;">
            @php
              $labRuns = $labExperiment?->runs ?? collect();
              $labRunForStats = $labRuns->firstWhere('is_selected', true)
                ?? $labRuns->filter(fn ($run) => $run->rank !== null)->sortBy('rank')->first()
                ?? $labRuns->firstWhere('status', 'completed')
                ?? $labRuns->first();
              $labBuildSeconds = $labRunForStats?->build_seconds !== null
                ? (float) $labRunForStats->build_seconds
                : ($labRunForStats?->duration_ms !== null ? (float) $labRunForStats->duration_ms / 1000 : null);
              $labRunTokens = $labRunForStats?->tokens_used;
              $totalUserTokens = (int) ($product->completed_generations_count ?? 0) * max(0, (int) ($product->pricing_model === 'free' ? 0 : $product->credit_cost));
            @endphp
            <div class="product-run-token-stack flex flex-col items-center justify-center gap-1.5">
              <div><span>زمان اجرا</span><strong>{{ $labBuildSeconds !== null ? number_format($labBuildSeconds, 1) . ' ثانیه' : '—' }}</strong></div>
              <div><span>توکن مصرفی</span><strong>{{ $labRunTokens !== null ? number_format((int) $labRunTokens) : '—' }}</strong></div>
              <div><span>مجموع توکن</span><strong>{{ number_format($totalUserTokens) }}</strong></div>
            </div>
          </td>

          @php
            $hasScoredLabExperiment = (int) ($product->scored_lab_experiments_count ?? 0) > 0;
          @endphp
          <td data-label="بهینه سازی آزمایش" style="text-align:center;">
            <div class="flex items-center justify-center gap-1.5">
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
              <a href="{{ $hasScoredLabExperiment ? '#' : route('admin.lab.create', ['product_id' => $product->id]) }}"
                 @if($hasScoredLabExperiment) data-summary-url="{{ route('admin.lab.products.summary', $product) }}" onclick="openProductLabSummary(event, this)" @endif
                 class="icon-action-btn inline-flex items-center justify-center"
                 style="color:{{ $hasScoredLabExperiment ? 'var(--success)' : 'var(--danger)' }};"
                 title="{{ $hasScoredLabExperiment ? 'آزمایش انجام و امتیازدهی شده — مشاهده نتایج' : 'هنوز آزمایش امتیازدهی‌شده‌ای برای این محصول ثبت نشده — شروع آزمایش' }}"
                 aria-label="{{ $hasScoredLabExperiment ? 'مشاهده آزمایش‌های امتیازدهی‌شده' : 'شروع آزمایش محصول' }}">
                <i class="fa-solid fa-flask"></i>
              </a>
            </div>
          </td>

          <td data-label="وضعیت" style="text-align:center;">
            <div class="flex flex-col items-center justify-center">
            @php
              $statusMap = [
                'active'   => ['label' => 'فعال',      'class' => 'badge-success'],
                'draft'    => ['label' => 'پیش‌نویس',  'class' => 'badge-warning'],
                'inactive' => ['label' => 'غیرفعال',   'class' => 'badge-danger'],
              ];
              $st = $statusMap[$product->status] ?? $statusMap['draft'];
            @endphp
            @if($product->status === 'draft')
              <span class="badge-pro {{ $st['class'] }}" style="display:inline-flex;" title="برای انتشار، محصول را ویرایش کنید"><i class="fa-solid fa-circle"></i> {{ $st['label'] }}</span>
            @else
              <span class="badge-pro {{ $st['class'] }} is-clickable" style="display:inline-flex;" title="برای تغییر سریع وضعیت کلیک کنید" onclick="quickToggleStatus({{ $product->id }}, this)"><i class="fa-solid fa-circle"></i> {{ $st['label'] }}</span>
            @endif
            @php $publicProductUrl = route('app.product', $product->route_slug); @endphp
            <div class="mt-2 pro-tooltip-wrap" style="display:flex;width:max-content;">
              <button type="button" class="icon-action-btn"
                      style="width:27px;height:27px;"
                      aria-label="کپی لینک محصول"
                      data-product-url="{{ $publicProductUrl }}"
                      onclick="copyProductPublicLink(this)">
                <i class="fa-solid fa-link"></i>
              </button>
              <div class="pro-tooltip" dir="ltr" style="width:260px;overflow-wrap:anywhere;">{{ $publicProductUrl }}</div>
            </div>
            </div>
          </td>

          <td data-label="آخرین ویرایش">
            {{-- تاریخ و ساعت شمسی ثبت + آخرین ویرایش محصول (App\Support\Jalali::formatNumeric) --}}
            <div>
              <div class="text-[10px] font-bold" style="color:var(--text-soft);">تاریخ و ساعت ثبت</div>
              <div class="flex items-center gap-2 mb-2">
                <span class="text-[11.5px] font-semibold" style="color:var(--text-main);" dir="rtl">{{ \App\Support\Jalali::formatNumeric($product->created_at) }}</span>
                <button type="button" class="icon-action-btn" style="width:24px;height:24px;" title="نمایش ثبت‌کننده" onclick="showAdminActor('ثبت‌کننده محصول','مدیر سیستم')"><i class="fa-solid fa-user"></i></button>
              </div>
              <div class="text-[10px] font-bold" style="color:var(--text-soft);">تاریخ و ساعت آخرین ویرایش</div>
              @if($product->created_at?->equalTo($product->updated_at))
                <div class="text-[11px] font-semibold" style="color:var(--text-soft);">ویرایش نشده</div>
              @else
                <div class="flex items-center gap-2">
                  <span class="text-[11.5px] font-semibold" style="color:var(--text-main);" dir="rtl">{{ \App\Support\Jalali::formatNumeric($product->updated_at) }}</span>
                  <button type="button" class="icon-action-btn" style="width:24px;height:24px;" title="نمایش ویرایش‌کننده" onclick="showAdminActor('آخرین ویرایش‌کننده','مدیر سیستم')"><i class="fa-solid fa-user-pen"></i></button>
                </div>
              @endif
            </div>
          </td>

          <td data-label="عملیات">
            <div class="flex items-center gap-1 justify-end">
              <button type="button" class="icon-action-btn favorite" title="نشان‌کردن به‌عنوان مهم (نیاز به بررسی برنامه برای ذخیره‌سازی)" onclick="this.classList.toggle('is-active')">
                <i class="fa-solid fa-star"></i>
              </button>

              <div class="dropdown-pro">
                <button type="button" class="icon-action-btn" onclick="toggleRowDropdown(event, {{ $product->id }})">
                  <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <div class="dropdown-pro-menu" id="row-dropdown-{{ $product->id }}">
                  <button type="button" class="dropdown-pro-item" onclick="openDrawer({{ $product->id }})"><i class="fa-solid fa-eye"></i> مشاهده</button>
                  <a href="{{ route('admin.products.create', $product->id) }}" class="dropdown-pro-item"><i class="fa-solid fa-pen"></i> ویرایش</a>
                  <a href="{{ route('admin.lab.create', ['product_id' => $product->id]) }}" class="dropdown-pro-item"><i class="fa-solid fa-flask"></i> بازکردن در آزمایشگاه</a>
                  {{-- کپی محصول: به‌جای ذخیره‌ی مستقیم، وارد فرم «ثبت محصول» با تمام فیلدهای پرشده می‌شود
                       تا ادمین قبل از ثبت نهایی بتواند مقادیر را بازبینی/ویرایش کند (مسیر کوتاه‌تر). --}}
                  <a href="{{ route('admin.products.create') }}?duplicate={{ $product->id }}" class="dropdown-pro-item"><i class="fa-solid fa-copy"></i> کپی محصول</a>
                  <button type="button" class="dropdown-pro-item" onclick="quickToggleStatus({{ $product->id }}, null)">
                    <i class="fa-solid fa-toggle-on"></i> تغییر وضعیت سریع
                  </button>
                  <button type="button" class="dropdown-pro-item"
                          data-product-id="{{ $product->id }}"
                          data-product-name="{{ $product->name_fa }}"
                          data-ai-provider="{{ $product->ai_provider }}"
                          data-ai-model="{{ $product->primary_model }}"
                          data-ai-url="{{ route('admin.products.update_ai_model', $product) }}"
                          onclick="openProductAiModelDialog(this)" title="تغییر سریع مدل اصلی؛ بدون تغییر پرامپت و سایر تنظیمات محصول">
                    <i class="fa-solid fa-microchip"></i> هوش مصنوعی <i class="fa-solid fa-circle-question text-[9px] mr-auto"></i>
                  </button>
                  <button type="button" class="dropdown-pro-item"
                          data-product-id="{{ $product->id }}"
                          data-product-name="{{ $product->name_fa }}"
                          data-credit-cost="{{ (int) $product->credit_cost }}"
                          data-credit-url="{{ route('admin.products.update_credit', $product) }}"
                          onclick="editProductCredit(this)">
                    <i class="fa-solid fa-coins"></i> اصلاح تعداد کردیت
                  </button>
                  <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('این محصول حذف شود؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="dropdown-pro-item danger"><i class="fa-solid fa-trash"></i> حذف</button>
                  </form>
                </div>
              </div>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="13" class="td-empty">
            <div class="empty-state">
              <div class="empty-state-icon"><i class="fa-solid fa-box-open"></i></div>
              <div class="empty-state-title">هنوز محصولی ثبت نشده است.</div>
              <div class="empty-state-desc">بعد از ایجاد اولین محصول، این بخش اطلاعات کامل محصولات را نمایش خواهد داد.</div>
              <a href="{{ route('admin.products.create') }}" class="btn-pro btn-pro-primary" style="display:inline-flex;">
                <i class="fa-solid fa-plus text-[11px]"></i> ثبت اولین محصول
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
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div>
        <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">سرویس</label>
        <select id="product-ai-provider-select" class="input-pro w-full" onchange="renderProductAiModelOptions()"></select>
      </div>
      <div>
        <label class="text-[11px] font-bold block mb-1.5" style="color:var(--text-soft);">مدل</label>
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
    'providerFa' => ['liara' => 'لیارا', 'openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate'][$model->provider] ?? $model->provider_name,
    'providerEn' => ['liara' => 'Liara AI', 'openrouter' => 'OpenRouter', 'fal' => 'Fal.ai', 'replicate' => 'Replicate'][$model->provider] ?? $model->provider_name,
    'usage' => $model->taskLabel(),
    'grade' => $model->qualityGradeLabel(),
    'plan' => $model->liara_plan,
  ])->values()->all();
@endphp
window.PRODUCT_ASSIGNABLE_AI_MODELS = @json($assignableAiModelsForJs);
window.PRODUCT_BULK_AI_URL = @json(route('admin.products.bulk_update_ai_model'));
window.PRODUCT_MATCHING_IDS = @json($matchingProductIds ?? []);
</script>

<style>
#product-credit-dialog{position:fixed;inset:0;margin:auto;}
#product-credit-dialog::backdrop{background:color-mix(in srgb,var(--text-h) 55%,transparent);}
#product-ai-model-dialog{position:fixed;inset:0;margin:auto;}
#product-ai-model-dialog::backdrop{background:color-mix(in srgb,var(--text-h) 55%,transparent);}
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
@media (min-width:901px){#product-credit-dialog{transform:translateX(-147px);}}
@media (min-width:901px){#product-ai-model-dialog{transform:translateX(-147px);}}
</style>
<dialog id="product-credit-dialog" class="rounded-2xl p-0 w-[min(92vw,420px)]" style="background:var(--card-bg);color:var(--text-main);border:1px solid var(--border);box-shadow:var(--shadow-card);">
  <form method="dialog" id="product-credit-dialog-form" class="p-5">
    <div class="flex items-start justify-between gap-3 mb-5">
      <div>
        <div class="text-[14px] font-extrabold" style="color:var(--text-h);">اصلاح تعداد کردیت</div>
        <div id="product-credit-dialog-name" class="text-[11px] mt-1" style="color:var(--text-soft);"></div>
      </div>
      <button type="button" class="icon-action-btn" onclick="closeProductCreditDialog()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="grid grid-cols-2 gap-3 max-[480px]:grid-cols-1">
      <div>
        <label for="product-credit-dialog-current" class="block text-[11.5px] font-bold mb-2">کردیت فعلی محصول</label>
        <input id="product-credit-dialog-current" type="text" readonly
               class="input-pro w-full" style="height:44px;opacity:.72;" aria-readonly="true">
      </div>
      <div>
        <label for="product-credit-dialog-input" class="block text-[11.5px] font-bold mb-2">کردیت جدید</label>
        <input id="product-credit-dialog-input" type="number" min="0" max="1000000" step="1" required
               class="input-pro w-full" style="height:44px;" inputmode="numeric"
               placeholder="تعداد دلخواه را وارد کنید">
      </div>
    </div>
    <label for="product-credit-dialog-note" class="block text-[11.5px] font-bold mt-4 mb-2">توضیح تغییر <span class="font-normal" style="color:var(--text-soft);">(اختیاری)</span></label>
    <input id="product-credit-dialog-note" type="text" maxlength="255"
           class="input-pro w-full" style="height:44px;" placeholder="مثلاً اصلاح هزینه اجرای محصول">
    <div id="product-credit-dialog-error" class="hidden text-[10.5px] mt-2" style="color:var(--danger);"></div>
    <div class="flex items-center justify-end gap-2 mt-5">
      <button type="button" class="btn-pro btn-pro-ghost" onclick="closeProductCreditDialog()">انصراف</button>
      <button type="submit" class="btn-pro btn-pro-primary" id="product-credit-dialog-submit"><i class="fa-solid fa-check"></i> ذخیره کردیت</button>
    </div>
  </form>
</dialog>

<dialog id="product-bulk-credit-dialog" class="rounded-2xl p-0 w-[min(92vw,440px)]" style="background:var(--card-bg);color:var(--text-main);border:1px solid var(--border);box-shadow:var(--shadow-card);">
  <form method="dialog" id="product-bulk-credit-dialog-form" class="p-5">
    <div class="flex items-start justify-between gap-3 mb-5">
      <div>
        <div class="text-[14px] font-extrabold" style="color:var(--text-h);">تغییر گروهی هزینه‌ی کردیت</div>
        <div id="product-bulk-credit-dialog-count" class="text-[11px] mt-1" style="color:var(--text-soft);"></div>
      </div>
      <button type="button" class="icon-action-btn" onclick="closeBulkCreditDialog()" aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="p-3 rounded-xl text-[10.5px] leading-6 mb-4" style="background:var(--primary-l);border:1px solid var(--primary-m);color:var(--text-main);">
      هزینه‌ی محصول برای همه‌ی موارد انتخاب‌شده ثبت می‌شود و سابقه‌ی تغییر هر محصول نیز ذخیره خواهد شد.
    </div>
    <label for="product-bulk-credit-dialog-input" class="block text-[11.5px] font-bold mb-2">هزینه‌ی جدید برای هر اجرا</label>
    <div class="flex items-center gap-2">
      <input id="product-bulk-credit-dialog-input" type="number" min="0" max="1000000" step="1" required
             class="input-pro w-full" style="height:44px;" inputmode="numeric" value="10" placeholder="مثلاً ۱۰">
      <span class="text-[11px] whitespace-nowrap" style="color:var(--text-soft);">توکن</span>
    </div>
    <label for="product-bulk-credit-dialog-note" class="block text-[11.5px] font-bold mt-4 mb-2">توضیح تغییر <span class="font-normal" style="color:var(--text-soft);">(اختیاری)</span></label>
    <input id="product-bulk-credit-dialog-note" type="text" maxlength="255" class="input-pro w-full" style="height:44px;" placeholder="مثلاً یکسان‌سازی قیمت محصولات عکس">
    <div id="product-bulk-credit-dialog-error" class="hidden text-[10.5px] mt-2" style="color:var(--danger);"></div>
    <div class="flex items-center justify-end gap-2 mt-5">
      <button type="button" class="btn-pro btn-pro-ghost" onclick="closeBulkCreditDialog()">انصراف</button>
      <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-check"></i> ثبت برای همه</button>
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

var productCreditTrigger = null;
function editProductCredit(button) {
  productCreditTrigger = button;
  var dialog = document.getElementById('product-credit-dialog');
  document.getElementById('product-credit-dialog-name').textContent = button.dataset.productName;
  document.getElementById('product-credit-dialog-current').value = Number(button.dataset.creditCost || 0).toLocaleString('fa-IR') + ' کردیت';
  document.getElementById('product-credit-dialog-input').value = '';
  document.getElementById('product-credit-dialog-note').value = '';
  document.getElementById('product-credit-dialog-error').classList.add('hidden');
  dialog.showModal();
  setTimeout(function () { document.getElementById('product-credit-dialog-input').focus(); }, 30);
}
function closeProductCreditDialog() {
  document.getElementById('product-credit-dialog')?.close();
  productCreditTrigger = null;
}

document.getElementById('product-credit-dialog-form')?.addEventListener('submit', async function (event) {
  event.preventDefault();
  var button = productCreditTrigger;
  if (!button) return;
  var value = String(document.getElementById('product-credit-dialog-input').value).trim();
  var errorBox = document.getElementById('product-credit-dialog-error');
  if (!/^\d+$/.test(value) || Number(value) > 1000000) {
    errorBox.textContent = 'تعداد کردیت باید یک عدد صحیح بین صفر تا ۱٬۰۰۰٬۰۰۰ باشد.';
    errorBox.classList.remove('hidden');
    return;
  }
  var submit = document.getElementById('product-credit-dialog-submit');
  submit.disabled = true;
  try {
    var response = await fetch(button.dataset.creditUrl, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        credit_cost: Number(value),
        note: document.getElementById('product-credit-dialog-note').value.trim() || null,
      }),
    });
    var data = await response.json().catch(function () { return {}; });
    if (!response.ok) throw new Error(data.message || 'به‌روزرسانی کردیت انجام نشد.');

    button.dataset.creditCost = String(data.credit_cost);
    var cell = document.getElementById('product-credit-cell-' + button.dataset.productId);
    if (cell) {
      cell.innerHTML = data.pricing_model === 'free'
        ? '<span class="badge-pro badge-success">رایگان</span>'
        : '<div class="font-bold" style="color:var(--text-h);">' + Number(data.credit_cost).toLocaleString('fa-IR') + ' <span class="text-[10.5px] font-normal" style="color:var(--text-soft);">کردیت</span></div>';
    }
    closeProductCreditDialog();
    showProductCreditToast(data.message || 'کردیت محصول با موفقیت تغییر کرد.');
  } catch (error) {
    errorBox.textContent = error.message || 'خطا در به‌روزرسانی کردیت محصول.';
    errorBox.classList.remove('hidden');
  } finally {
    submit.disabled = false;
  }
});
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

function openBulkCreditDialog() {
  var ids = typeof getSelectedBulkProductIds === 'function' ? getSelectedBulkProductIds() : [];
  if (!ids.length) return;
  var dialog = document.getElementById('product-bulk-credit-dialog');
  document.getElementById('product-bulk-credit-dialog-count').textContent = ids.length.toLocaleString('fa-IR') + ' محصول برای تغییر انتخاب شده است.';
  document.getElementById('product-bulk-credit-dialog-input').value = '10';
  document.getElementById('product-bulk-credit-dialog-note').value = '';
  document.getElementById('product-bulk-credit-dialog-error').classList.add('hidden');
  dialog.showModal();
  setTimeout(function () { document.getElementById('product-bulk-credit-dialog-input').focus(); }, 30);
}

function closeBulkCreditDialog() {
  document.getElementById('product-bulk-credit-dialog')?.close();
}

document.getElementById('product-bulk-credit-dialog-form')?.addEventListener('submit', function (event) {
  event.preventDefault();
  var value = String(document.getElementById('product-bulk-credit-dialog-input').value).trim();
  var errorBox = document.getElementById('product-bulk-credit-dialog-error');
  if (!/^\d+$/.test(value) || Number(value) > 1000000) {
    errorBox.textContent = 'هزینه‌ی توکن باید یک عدد صحیح بین صفر تا ۱٬۰۰۰٬۰۰۰ باشد.';
    errorBox.classList.remove('hidden');
    return;
  }

  var ids = typeof getSelectedBulkProductIds === 'function' ? getSelectedBulkProductIds() : [];
  if (!ids.length) {
    closeBulkCreditDialog();
    return;
  }

  var form = document.getElementById('bulk-action-form');
  form.querySelectorAll('input[name="ids[]"], input[name="credit_cost"], input[name="note"], input[name="category_id"]').forEach(function (el) { el.remove(); });
  ids.forEach(function (id) {
    var input = document.createElement('input');
    input.type = 'hidden'; input.name = 'ids[]'; input.value = id;
    form.appendChild(input);
  });
  var creditInput = document.createElement('input');
  creditInput.type = 'hidden'; creditInput.name = 'credit_cost'; creditInput.value = Number(value);
  form.appendChild(creditInput);
  var noteInput = document.createElement('input');
  noteInput.type = 'hidden'; noteInput.name = 'note'; noteInput.value = document.getElementById('product-bulk-credit-dialog-note').value.trim();
  form.appendChild(noteInput);
  document.getElementById('bulk-action-input').value = 'set_credit';
  closeBulkCreditDialog();
  form.submit();
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
