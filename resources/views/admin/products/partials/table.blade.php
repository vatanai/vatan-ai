{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت مستقل: جدول محصولات (Layer 3 / Products Table Component)
  ──────────────────────────────────────────────────────────────────
  ورودی مورد انتظار از View والد: $products (Paginator), $recentlyEdited
  ستون‌های «تعداد اجرا»، «مصرف کردیت»، «آخرین استفاده» و نوار محبوبیت داده‌ی
  واقعی ندارند و با بج «نیاز به بررسی برنامه» مشخص شده‌اند. «آخرین ویرایش»
  از ستون واقعی updated_at خوانده می‌شود (فقط نام ویرایش‌گر در دسترس نیست،
  چون فیلد updated_by در دیتابیس وجود ندارد — به همین دلیل برچسب
  «مدیر نامشخص + نیاز به بررسی برنامه» فقط برای بخش نام نمایش داده می‌شود).

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
          @if($r->thumbnail)<img src="{{ asset('storage/'.$r->thumbnail) }}" alt="">@else<i class="fa-solid fa-image text-[10px]"></i>@endif
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
  <table class="table-pro">
    <thead>
      <tr>
        <th style="width:40px;"><input type="checkbox" class="row-checkbox" id="select-all" onclick="toggleSelectAll(this)"></th>
        <th style="width:64px;"></th>
        <th>محصول</th>
        <th>کد محصول</th>
        <th>دسته‌بندی</th>
        <th>قیمت</th>
        <th>تعداد اجرا <span class="pending-badge" style="position:static;">نیاز به بررسی برنامه</span></th>
        <th>وضعیت</th>
        <th>آخرین ویرایش</th>
        <th>تاریخ ایجاد</th>
        <th>عملیات</th>
      </tr>
    </thead>
    <tbody>
      @forelse($products ?? [] as $product)
        <tr data-row-id="{{ $product->id }}">
          <td class="td-select"><input type="checkbox" class="row-checkbox bulk-check" value="{{ $product->id }}" onclick="onRowCheck()"></td>

          <td class="td-thumb">
            <div class="table-thumb cursor-pointer" onclick="openDrawer({{ $product->id }})">
              @if($product->thumbnail)
                <img src="{{ asset('storage/'.$product->thumbnail) }}" alt="">
              @else
                <i class="fa-solid fa-image"></i>
              @endif
            </div>
          </td>

          <td class="td-product">
            <div class="min-w-[220px] cursor-pointer" onclick="openDrawer({{ $product->id }})">
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
              </div>
            </div>
          </td>

          <td data-label="کد محصول">
            {{-- بند ۵۱: کد محصول از روی id واقعی ساخته می‌شود؛ بدون Migration/تغییر بک‌اند و از همین حالا با جستجوی عددی موجود قابل جستجوست. --}}
            <span class="font-mono text-[11.5px] font-semibold" style="color:var(--text-main);" dir="ltr">#{{ str_pad($product->id, 8, '0', STR_PAD_LEFT) }}</span>
          </td>

          <td data-label="دسته‌بندی">
            <span class="badge-pro badge-primary">{{ $product->category }}</span>
            @if($product->subcategory)<div class="text-[10.5px] mt-1" style="color:var(--text-soft);">{{ $product->subcategory }}</div>@endif
          </td>

          <td data-label="قیمت">
            @if($product->pricing_model === 'free')
              <span class="badge-pro badge-success">رایگان</span>
            @else
              <div class="font-bold" style="color:var(--text-h);">{{ $product->credit_cost }} <span class="text-[10.5px] font-normal" style="color:var(--text-soft);">کردیت</span></div>
            @endif
          </td>

          <td data-label="تعداد اجرا">
            <div class="flex items-center gap-2 pro-tooltip-wrap">
              <span style="color:var(--text-soft);">—</span>
              <div class="progress-track">
                <div class="progress-fill" style="width:0%;"></div>
              </div>
              <div class="pro-tooltip" style="width:170px;">نوار محبوبیت و تعداد اجرا — نیاز به بررسی برنامه (اتصال به آمار اجرا)</div>
            </div>
          </td>

          <td data-label="وضعیت">
            @php
              $statusMap = [
                'active'   => ['label' => 'فعال',      'class' => 'badge-success'],
                'draft'    => ['label' => 'پیش‌نویس',  'class' => 'badge-warning'],
                'inactive' => ['label' => 'غیرفعال',   'class' => 'badge-danger'],
              ];
              $st = $statusMap[$product->status] ?? $statusMap['draft'];
            @endphp
            @if($product->status === 'draft')
              <span class="badge-pro {{ $st['class'] }}" title="برای انتشار، محصول را ویرایش کنید"><i class="fa-solid fa-circle"></i> {{ $st['label'] }}</span>
            @else
              <span class="badge-pro {{ $st['class'] }} is-clickable" title="برای تغییر سریع وضعیت کلیک کنید" onclick="quickToggleStatus({{ $product->id }}, this)"><i class="fa-solid fa-circle"></i> {{ $st['label'] }}</span>
            @endif
          </td>

          <td data-label="آخرین ویرایش">
            <div class="flex items-center gap-1 pro-tooltip-wrap" style="width:max-content;">
              <span class="text-[10px] font-semibold is-pending" style="color:var(--text-soft);">مدیر نامشخص</span>
              <i class="fa-solid fa-circle-info text-[8px]" style="color:var(--warning);"></i>
              <div class="pro-tooltip" style="width:190px;">نام مدیری که آخرین ویرایش را انجام داده — نیاز به بررسی برنامه (ثبت ویرایش‌گر در دیتابیس)</div>
            </div>
            <div style="color:var(--text-main);">{{ $product->updated_at?->diffForHumans() }}</div>
          </td>

          <td data-label="تاریخ ایجاد" style="color:var(--text-soft);">{{ $product->created_at?->format('Y/m/d') }}</td>

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
                  <a href="{{ route('admin.products.edit', $product->id) }}" class="dropdown-pro-item"><i class="fa-solid fa-pen"></i> ویرایش</a>
                  {{-- کپی محصول: به‌جای ذخیره‌ی مستقیم، وارد فرم «ثبت محصول» با تمام فیلدهای پرشده می‌شود
                       تا ادمین قبل از ثبت نهایی بتواند مقادیر را بازبینی/ویرایش کند (مسیر کوتاه‌تر). --}}
                  <a href="{{ route('admin.products.create') }}?duplicate={{ $product->id }}" class="dropdown-pro-item"><i class="fa-solid fa-copy"></i> کپی محصول</a>
                  <button type="button" class="dropdown-pro-item" onclick="quickToggleStatus({{ $product->id }}, null)">
                    <i class="fa-solid fa-toggle-on"></i> تغییر وضعیت سریع
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
          <td colspan="11" class="td-empty">
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
