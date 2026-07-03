{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت مستقل: کارت‌های آماری صفحه لیست محصولات (Layer 1 / Summary Cards)
  ──────────────────────────────────────────────────────────────────
  توکن‌های رنگی از public/admin/css/design-tokens.css (سازگار با تم روز/شب
  از طریق کلاس body.light — طبق doc/design-system.md).

  ورودی‌های مورد انتظار از View والد:
    - $products      : Paginator محصولات (برای total)
    - $activeCount    : تعداد محصولات فعال
    - $draftCount     : تعداد محصولات پیش‌نویس
    - $inactiveCount  : تعداد محصولات غیرفعال

  کارت‌های ۵ و ۶ و ۷ (کل اجراها / محبوب‌ترین محصول / کل مصرف کردیت) داده‌ی
  واقعی از بک‌اند ندارند و با بج «نیاز به بررسی برنامه» مشخص شده‌اند تا هم
  از فاز یک (موارد آماده) جدا باشند و هم توسعه‌دهنده‌ی بک‌اند بداند باید
  این فیلدها را به دیتابیس/API متصل کند. این کامپوننت هیچ Query یا
  وابستگی جدیدی به دیتابیس اضافه نمی‌کند.
  ══════════════════════════════════════════════════════════════════
--}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3.5 mb-5">

  {{-- کارت ۱: کل محصولات --}}
  <div class="stat-card pro-tooltip-wrap">
    <div class="stat-card-icon" style="background:var(--primary-l);color:var(--primary);">
      <i class="fa-solid fa-box-open"></i>
    </div>
    <div class="min-w-0">
      <div class="stat-card-value">{{ $products->total() ?? 0 }}</div>
      <div class="stat-card-label">کل محصولات</div>
    </div>
    <div class="pro-tooltip">مجموع تمام محصولات ثبت‌شده در پلتفرم</div>
  </div>

  {{-- کارت ۲: محصولات فعال --}}
  <div class="stat-card pro-tooltip-wrap">
    <div class="stat-card-icon" style="background:var(--success-l);color:var(--success);">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div class="min-w-0 flex-1">
      <div class="flex items-center gap-1.5">
        <div class="stat-card-value">{{ $activeCount ?? 0 }}</div>
        @php $activePct = ($products->total() ?? 0) > 0 ? round((($activeCount ?? 0) / $products->total()) * 100) : 0; @endphp
        @if(($products->total() ?? 0) > 0)
          <span class="badge-pro badge-success" style="padding:2px 6px;font-size:9.5px;">{{ $activePct }}%</span>
        @endif
      </div>
      <div class="stat-card-label">محصولات فعال</div>
    </div>
    <div class="pro-tooltip">محصولاتی که هم‌اکنون برای کاربران قابل استفاده هستند</div>
  </div>

  {{-- کارت ۳: پیش‌نویس‌ها --}}
  <div class="stat-card pro-tooltip-wrap">
    <div class="stat-card-icon" style="background:var(--warning-l);color:var(--warning);">
      <i class="fa-solid fa-pen"></i>
    </div>
    <div class="min-w-0">
      <div class="stat-card-value">{{ $draftCount ?? 0 }}</div>
      <div class="stat-card-label">پیش‌نویس‌ها</div>
    </div>
    <div class="pro-tooltip">محصولاتی که هنوز منتشر نشده و در حال آماده‌سازی هستند</div>
  </div>

  {{-- کارت ۴: غیرفعال --}}
  <div class="stat-card pro-tooltip-wrap">
    <div class="stat-card-icon" style="background:var(--danger-l);color:var(--danger);">
      <i class="fa-solid fa-circle-xmark"></i>
    </div>
    <div class="min-w-0">
      <div class="stat-card-value">{{ $inactiveCount ?? 0 }}</div>
      <div class="stat-card-label">غیرفعال</div>
    </div>
    <div class="pro-tooltip">محصولاتی که موقتاً از دسترس کاربران خارج شده‌اند</div>
  </div>

  {{-- کارت ۵: کل اجراها — آیتم جدید، نیاز به اتصال بک‌اند --}}
  <div class="stat-card pro-tooltip-wrap">
    <span class="pending-badge"><i class="fa-solid fa-triangle-exclamation"></i>نیاز به بررسی برنامه</span>
    <div class="stat-card-icon" style="background:var(--info-l);color:var(--info);">
      <i class="fa-solid fa-bolt"></i>
    </div>
    <div class="min-w-0">
      <div class="stat-card-value is-pending">—</div>
      <div class="stat-card-label">کل اجراها</div>
    </div>
    <div class="pro-tooltip">تعداد دفعات استفاده کاربران از تمام محصولات — این آیتم جدید است و باید توسط برنامه‌نویس بک‌اند به دیتابیس متصل شود.</div>
  </div>

  {{-- کارت ۶: محبوب‌ترین محصول — آیتم جدید، نیاز به اتصال بک‌اند --}}
  <div class="stat-card pro-tooltip-wrap">
    <span class="pending-badge"><i class="fa-solid fa-triangle-exclamation"></i>نیاز به بررسی برنامه</span>
    <div class="stat-card-icon" style="background:rgba(245,197,66,.1);color:#f5c542;">
      <i class="fa-solid fa-trophy"></i>
    </div>
    <div class="min-w-0">
      <div class="text-[13px] font-extrabold leading-tight is-pending truncate" style="color:var(--text-soft);">—</div>
      <div class="stat-card-label">محبوب‌ترین محصول</div>
    </div>
    <div class="pro-tooltip">محصولی با بیشترین تعداد اجرا — این آیتم جدید است و باید توسط برنامه‌نویس بک‌اند به دیتابیس متصل شود.</div>
  </div>

  {{-- کارت ۷: کل مصرف کردیت — آیتم جدید، نیاز به اتصال بک‌اند --}}
  <div class="stat-card pro-tooltip-wrap">
    <span class="pending-badge"><i class="fa-solid fa-triangle-exclamation"></i>نیاز به بررسی برنامه</span>
    <div class="stat-card-icon" style="background:var(--success-l);color:var(--success);">
      <i class="fa-solid fa-coins"></i>
    </div>
    <div class="min-w-0">
      <div class="stat-card-value is-pending">—</div>
      <div class="stat-card-label">مصرف کل کردیت</div>
    </div>
    <div class="pro-tooltip">مجموع کردیت مصرف‌شده توسط تمام محصولات — این آیتم جدید است و باید توسط برنامه‌نویس بک‌اند به دیتابیس متصل شود.</div>
  </div>

</div>
