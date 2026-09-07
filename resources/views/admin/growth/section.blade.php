@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.monitor').'"><i class="fa-solid fa-gauge-high"></i> پایش کامل</a>';
  $sectionMeta = [
    'attribution' => ['fa-route', 'اتریبیوشن چندمرحله‌ای', 'هویت ناشناس ورودی از زمان کلیک نگهداری می‌شود و منبع، دستگاه و جدید یا تکراری‌بودن بازدیدکننده قابل تحلیل است.'],
    'products' => ['fa-box-open', 'عملکرد محصول در مسیر رشد', 'آمار واقعی محصولات و ساخت‌های موفق یا ناموفق مستقیماً از هسته وطن خوانده می‌شود.'],
    'sales' => ['fa-cart-shopping', 'تبدیل به خرید', 'خریدهای موفق و اعتبار فروخته‌شده مستقیماً از سیستم سفارشات وطن خوانده می‌شوند.'],
    'retention' => ['fa-rotate', 'بازگشت و خرید مجدد', 'بازدیدکننده تکراری و خریدار تکراری از داده واقعی سیستم محاسبه می‌شود.'],
    'reports' => ['fa-file-lines', 'گزارش‌های مدیریتی', 'همه داده‌های رویدادی و بازه‌های ۲۴ ساعته نگهداری شده‌اند و مبنای گزارش‌های دوره‌ای هستند.'],
    'settings' => ['fa-sliders', 'تنظیم ثبت رویداد', 'برای تشخیص بازشدن واقعی صفحه مقصد، رهگیر زیر باید یک‌بار در صفحات مقصد قرار بگیرد.'],
  ][$section];
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => $title,
  'subtitle' => $description,
  'actions' => $actions,
])

<div class="g-stack">
  <section class="g-kpis g-kpis-3">
    @foreach($metrics as $index => $metric)
      <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">{{ $metric['label'] }}</span><span class="g-kpi-icon {{ $index === 1 ? 'success' : ($index === 2 ? 'warning' : '') }}"><i class="fa-solid {{ $sectionMeta[0] }}"></i></span></div><div class="g-kpi-value">{{ is_numeric($metric['value']) ? number_format($metric['value']) : $metric['value'] }}</div><div class="g-kpi-foot">داده فعلی وطن</div></article>
    @endforeach
  </section>

  <section class="g-grid-main">
    <article class="g-card g-card-pad">
      <div class="g-section-title"><i class="fa-solid {{ $sectionMeta[0] }}"></i> {{ $sectionMeta[1] }}</div>
      <p style="color:var(--text-soft);font-size:12px;line-height:2;margin:12px 0 0">{{ $sectionMeta[2] }}</p>

      @if($section === 'settings')
        <div class="g-source-cta">
          <div><strong>منابع داده و نگاشت شاخص‌ها</strong><span>اتصال‌های داخلی، ورودی دستی شبکه‌های اجتماعی، فایل و سلامت داده‌ها را در یک بخش مستقل مدیریت کنید.</span></div>
          <a class="g-btn g-btn-primary" href="{{ route('admin.growth.data-sources.index') }}"><i class="fa-solid fa-database"></i> مدیریت منابع داده</a>
        </div>
        <div class="g-note" style="margin-top:16px">این قطعه فقط ثبت رویداد «بازشدن صفحه» را فعال می‌کند و هیچ ظاهر یا رفتار صفحه را تغییر نمی‌دهد.</div>
        <code class="g-code" style="margin-top:12px">{{ $trackerSnippet }}</code>
        <button class="g-btn g-btn-soft" style="margin-top:10px" type="button" data-copy-value="{{ $trackerSnippet }}"><i class="fa-regular fa-copy"></i> کپی کد رهگیر</button>
      @elseif($section === 'reports')
        <div class="g-actions" style="margin-top:16px"><a class="g-btn g-btn-primary" href="{{ route('admin.growth.links.analytics') }}"><i class="fa-solid fa-clock-rotate-left"></i> گزارش بازه‌های ۲۴ ساعته</a><a class="g-btn" href="{{ route('admin.growth.contents') }}"><i class="fa-solid fa-table-list"></i> گزارش محتواها</a></div>
      @else
        <div class="g-actions" style="margin-top:16px"><a class="g-btn g-btn-primary" href="{{ route('admin.growth.overview') }}"><i class="fa-solid fa-chart-pie"></i> مشاهده نمای کلی</a><a class="g-btn" href="{{ route('admin.growth.links.analytics') }}"><i class="fa-solid fa-link"></i> بررسی ورودی لینک‌ها</a></div>
      @endif
    </article>

    <aside class="g-card g-card-pad">
      <div class="g-section-title"><i class="fa-solid fa-wand-magic-sparkles"></i> قابلیت‌های تکمیلی</div>
      @if($section === 'attribution')
        <div class="g-breakdown-row"><span>اتصال ساخت و خرید به ورودی</span><span class="g-badge success">فعال</span></div><div class="g-breakdown-row"><span>مدل آخرین کلیک موثر</span><span class="g-badge success">فعال</span></div><div class="g-breakdown-row"><span>مسیر چندلمسی</span><span class="g-badge warning">بزودی</span></div>
      @elseif($section === 'products')
        <div class="g-breakdown-row"><span>تبدیل لینک به ساخت محصول</span><span class="g-badge success">فعال</span></div><div class="g-breakdown-row"><span>رتبه‌بندی محصول بر اساس کانال</span><span class="g-badge warning">بزودی</span></div>
      @elseif($section === 'sales')
        <div class="g-breakdown-row"><span>اعتبار منتسب به لینک</span><span class="g-badge success">فعال</span></div><div class="g-breakdown-row"><span>هزینه جذب و بازگشت سرمایه</span><span class="g-badge warning">بزودی</span></div>
      @elseif($section === 'retention')
        <div class="g-breakdown-row"><span>خرید مجدد منتسب</span><span class="g-badge success">فعال</span></div><div class="g-breakdown-row"><span>گروه‌های بازگشتی</span><span class="g-badge warning">بزودی</span></div>
      @elseif($section === 'reports')
        <div class="g-breakdown-row"><span>خروجی اکسل</span><span class="g-badge warning">بزودی</span></div><div class="g-breakdown-row"><span>ارسال خودکار دوره‌ای</span><span class="g-badge warning">بزودی</span></div>
      @elseif($section === 'settings')
        <div class="g-breakdown-row"><span>منابع داخلی وطن</span><span class="g-badge success">فعال</span></div><div class="g-breakdown-row"><span>ورود دستی و فایل سی‌اس‌وی</span><span class="g-badge success">فعال</span></div><div class="g-breakdown-row"><span>پردازش فایل اکسل</span><span class="g-badge warning">بزودی</span></div>
      @else
        <div class="g-breakdown-row"><span>اتصال موقعیت جغرافیایی دقیق</span><span class="g-badge warning">بزودی</span></div><div class="g-breakdown-row"><span>قواعد اتریبیوشن قابل تنظیم</span><span class="g-badge warning">بزودی</span></div>
      @endif
    </aside>
  </section>
</div>
