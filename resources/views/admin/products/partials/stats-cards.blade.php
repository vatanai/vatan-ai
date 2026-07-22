{{--
  ══════════════════════════════════════════════════════════════════
  کامپوننت مستقل: کارت‌های آماری صفحه لیست محصولات (Layer 1 / Summary Cards)
  ──────────────────────────────────────────────────────────────────
  توکن‌های رنگی از public/admin/css/design-tokens.css (سازگار با تم روز/شب
  از طریق کلاس body.light — طبق doc/design-system.md).

  ورودی‌های مورد انتظار از View والد:
    - $products      : Paginator محصولات (برای total)
    - $activeCount    : تعداد محصولات فعال (کوئری واقعی از دیتابیس)
    - $draftCount     : تعداد محصولات پیش‌نویس
    - $inactiveCount  : تعداد محصولات غیرفعال (کوئری واقعی از دیتابیس)
    - $totalRuns      : کل اجراهای واقعی همه محصولات (count جدول generations)
    - $topProduct     : محبوب‌ترین محصول (بیشترین اجرا) یا null

  «محصولات فعال» و «غیرفعال» طبق درخواست مدیر در یک کارت واحد ادغام شده‌اند.
  فقط کارت «مصرف کل کردیت» هنوز داده‌ی واقعی ندارد و با بج مشخص است.
  ══════════════════════════════════════════════════════════════════
--}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-6 gap-3.5 mb-5">

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

  {{-- کارت ۲: محصولات فعال / غیرفعال (ادغام‌شده در یک کارت — هر دو عدد واقعی از دیتابیس) --}}
  <div class="stat-card pro-tooltip-wrap">
    <div class="stat-card-icon" style="background:var(--success-l);color:var(--success);">
      <i class="fa-solid fa-toggle-on"></i>
    </div>
    <div class="min-w-0 flex-1">
      <div class="flex items-baseline gap-1.5 flex-wrap">
        <span class="stat-card-value" style="color:var(--success);">{{ $activeCount ?? 0 }}</span>
        <span class="text-[11px] font-bold" style="color:var(--text-soft);">/</span>
        <span class="stat-card-value" style="color:var(--danger);">{{ $inactiveCount ?? 0 }}</span>
        @php $activePct = ($products->total() ?? 0) > 0 ? round((($activeCount ?? 0) / $products->total()) * 100) : 0; @endphp
        @if(($products->total() ?? 0) > 0)
          <span class="badge-pro badge-success" style="padding:2px 6px;font-size:9.5px;">{{ $activePct }}% فعال</span>
        @endif
      </div>
      <div class="stat-card-label">فعال / غیرفعال</div>
    </div>
    <div class="pro-tooltip">{{ $activeCount ?? 0 }} محصول فعال (قابل استفاده برای کاربران) و {{ $inactiveCount ?? 0 }} محصول غیرفعال (خارج از دسترس)</div>
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

  {{-- کارت ۴: کل اجراها — متصل به دیتابیس واقعی (count جدول generations) --}}
  <div class="stat-card pro-tooltip-wrap">
    <div class="stat-card-icon" style="background:var(--info-l);color:var(--info);">
      <i class="fa-solid fa-bolt"></i>
    </div>
    <div class="min-w-0">
      <div class="stat-card-value">{{ number_format($totalRuns ?? 0) }}</div>
      <div class="stat-card-label">کل اجراها</div>
    </div>
    <div class="pro-tooltip">تعداد کل دفعاتی که کاربران محصولات پلتفرم را اجرا کرده‌اند — مستقیم از جدول اجراها (generations) محاسبه می‌شود</div>
  </div>

  {{-- کارت ۵: محبوب‌ترین محصول — بیشترین تعداد اجرای واقعی --}}
  <div class="stat-card pro-tooltip-wrap">
    <div class="stat-card-icon" style="background:var(--warning-l);color:var(--warning);">
      <i class="fa-solid fa-trophy"></i>
    </div>
    <div class="min-w-0">
      @if(!empty($topProduct))
        <div class="text-[13px] font-extrabold leading-tight truncate" style="color:var(--text-h);" title="{{ $topProduct->name_fa }}">{{ $topProduct->name_fa }}</div>
        <div class="stat-card-label">محبوب‌ترین ({{ number_format($topProduct->generations_count ?? 0) }} اجرا)</div>
      @else
        <div class="text-[13px] font-extrabold leading-tight truncate" style="color:var(--text-soft);">—</div>
        <div class="stat-card-label">محبوب‌ترین محصول</div>
      @endif
    </div>
    <div class="pro-tooltip">{{ !empty($topProduct) ? 'محصولی که بیشترین تعداد اجرا را در کل پلتفرم دارد' : 'هنوز هیچ اجرایی برای محصولات ثبت نشده است' }}</div>
  </div>

  {{-- کارت ۶: کل مصرف کردیت — آیتم جدید، نیاز به اتصال بک‌اند --}}
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
