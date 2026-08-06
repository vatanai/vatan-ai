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

{{-- ─── نوار عملیات گروهی (Bulk Action) — فقط وقتی چند ردیف انتخاب شود نمایش داده می‌شود ─── --}}
<form id="bulk-action-form" method="POST" action="{{ route('admin.products.bulk_action') }}">
  @csrf
  <input type="hidden" name="action" id="bulk-action-input">
  <div id="bulk-toolbar" class="bulk-toolbar" style="display:none;">
    <span class="text-[12px] font-bold" style="color:var(--primary);"><span id="bulk-count">0</span> محصول انتخاب شده</span>
    <div class="w-px h-4" style="background:var(--border);"></div>
    <button type="button" class="btn-pro btn-pro-ghost" onclick="submitBulk('activate')"><i class="fa-solid fa-circle-check text-[11px]"></i> فعال کردن</button>
    <button type="button" class="btn-pro btn-pro-ghost" onclick="submitBulk('deactivate')"><i class="fa-solid fa-circle-xmark text-[11px]"></i> غیرفعال کردن</button>
    <span class="pro-tooltip-wrap inline-flex">
      <button type="button" class="btn-pro btn-pro-ghost" onclick="openBulkAiModelDialog()"><i class="fa-solid fa-microchip text-[11px]"></i> تغییر مدل هوش مصنوعی <i class="fa-solid fa-circle-question text-[9px]"></i></button>
      <span class="pro-tooltip" style="width:270px;">مدل اصلی همه محصولات انتخاب‌شده را یکجا تغییر می‌دهد. پرامپت، ویژگی‌ها و مدل‌های جایگزین محصولات تغییری نمی‌کنند.</span>
    </span>
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
        <th>قیمت</th>
        <th style="text-align:center;"><span class="block">تعداد اجرا</span><span class="block">تعداد لایک</span></th>
        <th style="text-align:center;"><span class="block">زمان اجرا</span><span class="block">توکن مصرفی</span></th>
        <th style="text-align:center;">بهینه‌سازی و آزمایش</th>
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

          <td data-label="قیمت" id="product-credit-cell-{{ $product->id }}">
            @if($product->pricing_model === 'free')
              <span class="badge-pro badge-success">رایگان</span>
            @else
              <div class="font-bold" style="color:var(--text-h);">{{ $product->credit_cost }} <span class="text-[10.5px] font-normal" style="color:var(--text-soft);">کردیت</span></div>
            @endif
            @php
              $modelCostUsd = $assignedAiModel?->cost_per_generation_usd;
              $modelCostIrr = $modelCostUsd !== null ? (float) $modelCostUsd * (float) ($exchange['rate'] ?? 0) : null;
            @endphp
            <div class="mt-1.5 text-[10px]" style="color:var(--text-soft);" title="هزینه هر خروجی مدل اصلی با نرخ روز دلار">
              @if($modelCostUsd !== null)<span dir="ltr">${{ number_format((float)$modelCostUsd, 4) }}</span><span class="mx-1">·</span>{{ number_format($modelCostIrr / 10) }} تومان @else قیمت مدل ثبت نشده @endif
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
            <div class="flex flex-col items-center justify-center gap-2">
              <div class="text-center">
                <div class="text-[10px] font-bold" style="color:var(--text-soft);">آخرین زمان اجرای تست</div>
                <div class="text-[11.5px] font-semibold" style="color:var(--text-main);">
                @if($product->last_test_duration_ms !== null)
                  {{ $product->last_test_duration_ms < 1000 ? number_format($product->last_test_duration_ms) . ' میلی‌ثانیه' : number_format($product->last_test_duration_ms / 1000, 1) . ' ثانیه' }}
                @else — @endif
                </div>
              </div>
              <div class="text-center">
                <div class="text-[10px] font-bold" style="color:var(--text-soft);">مجموع توکن تست</div>
                <div class="text-[11.5px] font-semibold" style="color:var(--text-main);">{{ number_format((int) $product->total_test_tokens) }}</div>
              </div>
            </div>
          </td>

          @php
            $hasScoredLabExperiment = (int) ($product->scored_lab_experiments_count ?? 0) > 0;
          @endphp
          <td data-label="بهینه‌سازی و آزمایش" style="text-align:center;">
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
              <a href="{{ $hasScoredLabExperiment ? route('admin.lab.index', ['product_id' => $product->id]) : route('admin.lab.create', ['product_id' => $product->id]) }}"
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
        <select id="product-ai-model-select" class="input-pro w-full" onchange="saveProductAiModelSelection()"></select>
      </div>
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
    'provider' => $model->provider,
    'plan' => $model->liara_plan,
  ])->values()->all();
@endphp
window.PRODUCT_ASSIGNABLE_AI_MODELS = @json($assignableAiModelsForJs);
window.PRODUCT_BULK_AI_URL = @json(route('admin.products.bulk_update_ai_model'));
</script>

<style>
#product-credit-dialog{position:fixed;inset:0;margin:auto;}
#product-credit-dialog::backdrop{background:color-mix(in srgb,var(--text-h) 55%,transparent);}
#product-ai-model-dialog{position:fixed;inset:0;margin:auto;}
#product-ai-model-dialog::backdrop{background:color-mix(in srgb,var(--text-h) 55%,transparent);}
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
</script>
