@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.monitor').'"><i class="fa-solid fa-chart-line"></i> مرکز پایش رشد</a>';
  $periodLabels = ['today' => 'امروز', '7' => '۷ روز اخیر', '30' => '۳۰ روز اخیر', '90' => '۹۰ روز اخیر', 'custom' => 'بازه دلخواه'];
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'کاربران و سفر مشتری',
  'subtitle' => 'پایش ثبت‌نام، ورود، فعالیت، خرید و منبع جذب هر کاربر از یک نمای واحد',
  'actions' => $actions,
])

<div class="g-stack">
  <form class="g-card g-filterbar" method="GET" action="{{ route('admin.growth.users.index') }}">
    <div class="g-field">
      <label for="period">بازه گزارش</label>
      <select class="g-input" id="period" name="period">
        @foreach($periodLabels as $value => $label)<option value="{{ $value }}" @selected($period === $value)>{{ $label }}</option>@endforeach
      </select>
    </div>
    <div class="g-field"><label for="from">از تاریخ</label><input class="g-input" id="from" name="from" type="date" value="{{ request('from', $from->format('Y-m-d')) }}"></div>
    <div class="g-field"><label for="to">تا تاریخ</label><input class="g-input" id="to" name="to" type="date" value="{{ request('to', $to->format('Y-m-d')) }}"></div>
    <div class="g-field">
      <label for="activity">نوع فعالیت کاربر</label>
      <select class="g-input" id="activity" name="activity">
        <option value="all" @selected($activity === 'all')>همه کاربران</option>
        <option value="registered" @selected($activity === 'registered')>ثبت‌نام‌شده در بازه</option>
        <option value="logged_in" @selected($activity === 'logged_in')>واردشده در بازه</option>
        <option value="generated" @selected($activity === 'generated')>دارای ساخت در بازه</option>
        <option value="purchased" @selected($activity === 'purchased')>دارای خرید در بازه</option>
      </select>
    </div>
    <div class="g-field g-search"><label for="search">جست‌وجوی کاربر</label><i class="fa-solid fa-magnifying-glass"></i><input class="g-input" id="search" name="search" value="{{ $search }}" placeholder="نام، موبایل یا ایمیل"></div>
    <div class="g-actions g-filter-actions"><button class="g-btn g-btn-primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button><a class="g-btn" href="{{ route('admin.growth.users.index') }}">پاک‌کردن</a></div>
  </form>

  <section class="g-kpis g-kpis-4">
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">ثبت‌نام جدید</span><span class="g-kpi-icon"><i class="fa-solid fa-user-plus"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['signups']) }}</div><div class="g-kpi-foot">در بازه انتخاب‌شده</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">ورود موفق</span><span class="g-kpi-icon info"><i class="fa-solid fa-right-to-bracket"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['logins']) }}</div><div class="g-kpi-foot">تعداد دفعات ورود</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کاربر فعال</span><span class="g-kpi-icon success"><i class="fa-solid fa-user-check"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['activeUsers']) }}</div><div class="g-kpi-foot">کاربر یکتای احراز هویت‌شده</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">خریدار</span><span class="g-kpi-icon warning"><i class="fa-solid fa-bag-shopping"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['buyers']) }}</div><div class="g-kpi-foot">خریدار یکتا در بازه</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">درآمد</span><span class="g-kpi-icon success"><i class="fa-solid fa-coins"></i></span></div><div class="g-kpi-value g-kpi-money">{{ number_format($metrics['revenue']) }}</div><div class="g-kpi-foot">تومان از خریدهای تکمیل‌شده</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">سفارش</span><span class="g-kpi-icon"><i class="fa-solid fa-receipt"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['orders']) }}</div><div class="g-kpi-foot">تمام سفارش‌های ثبت‌شده</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">تبدیل ثبت‌نام به خرید</span><span class="g-kpi-icon info"><i class="fa-solid fa-percent"></i></span></div><div class="g-kpi-value">{{ $metrics['conversionRate'] }}٪</div><div class="g-kpi-foot">خرید کاربران ثبت‌نامی همین بازه</div></article>
  </section>

  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-chart-line"></i> روند جذب و تبدیل</div><div class="g-section-sub">مقایسه روزانه ثبت‌نام، ورود و خرید در {{ $periodLabels[$period] }}</div></div></div>
    <div class="g-chart-wrap"><canvas data-user-growth-chart data-labels='@json($trend['labels'])' data-signups='@json($trend['signups'])' data-logins='@json($trend['logins'])' data-purchases='@json($trend['purchases'])'></canvas></div>
  </section>

  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-link"></i> عملکرد لینک‌های ورود و جذب</div><div class="g-section-sub">از کلیک و بازشدن صفحه تا شناسایی کاربر و خرید</div></div><a class="g-btn" href="{{ route('admin.growth.links.index') }}">مدیریت لینک‌ها</a></div>
    @if($links->isEmpty())
      @include('admin.growth.partials.empty', ['icon' => 'fa-link', 'emptyTitle' => 'هنوز داده‌ای برای لینک‌ها ثبت نشده است', 'emptyAction' => route('admin.growth.links.create'), 'emptyActionLabel' => 'ساخت لینک'])
    @else
      <div class="g-table-wrap"><table class="g-table"><thead><tr><th>لینک</th><th>کانال</th><th>کلیک</th><th>بازشدن</th><th>کاربر منتسب</th><th>خرید</th><th>تبدیل کلیک به کاربر</th></tr></thead><tbody>
        @foreach($links as $link)
          <tr><td><div class="g-table-title">{{ $link->title }}</div><div class="g-table-sub">{{ $link->short_url }}</div></td><td><span class="g-badge">{{ $link->channel }}</span></td><td>{{ number_format($link->clicks_count) }}</td><td>{{ number_format($link->opens_count) }}</td><td>{{ number_format($link->attributed_users) }}</td><td>{{ number_format($link->purchases_count) }}</td><td>{{ $link->clicks_count > 0 ? round(($link->attributed_users / $link->clicks_count) * 100, 1) : 0 }}٪</td></tr>
        @endforeach
      </tbody></table></div>
    @endif
  </section>

  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-users"></i> فهرست کاربران</div><div class="g-section-sub">{{ number_format($users->total()) }} کاربر مطابق فیلتر؛ برای مشاهده مسیر کامل روی «سفر کاربر» بزنید</div></div></div>
    @if($users->isEmpty())
      @include('admin.growth.partials.empty', ['icon' => 'fa-users', 'emptyTitle' => 'کاربری مطابق این فیلتر پیدا نشد'])
    @else
      <div class="g-table-wrap"><table class="g-table g-user-table"><thead><tr><th>کاربر</th><th>ثبت‌نام</th><th>آخرین ورود</th><th>دفعات ورود</th><th>ساخت‌ها</th><th>خریدها</th><th>اعتبار</th><th></th></tr></thead><tbody>
        @foreach($users as $item)
          <tr><td><div class="g-table-title">{{ trim($item->name.' '.$item->last_name) ?: 'بدون نام' }}</div><div class="g-table-sub">{{ $item->phone ?: ($item->email ?: 'بدون راه ارتباطی') }}</div></td><td>{{ $item->registered_at?->format('Y/m/d H:i') ?? '—' }}</td><td>{{ $item->last_login_at?->format('Y/m/d H:i') ?? '—' }}</td><td>{{ number_format($item->login_count) }}</td><td>{{ number_format($item->generated_images_count) }}</td><td>{{ number_format($item->plan_purchases_count) }}</td><td>{{ number_format($item->tokens) }}</td><td><a class="g-btn g-btn-soft" href="{{ route('admin.growth.users.show', $item) }}"><i class="fa-solid fa-route"></i> سفر کاربر</a></td></tr>
        @endforeach
      </tbody></table></div>
      <div class="g-card-pad g-pagination">{{ $users->links() }}</div>
    @endif
  </section>
</div>
