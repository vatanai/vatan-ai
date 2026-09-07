@php
  $fullName = trim($user->name.' '.$user->last_name) ?: 'کاربر بدون نام';
  $actions = '<a class="g-btn" href="'.route('admin.growth.users.index').'"><i class="fa-solid fa-arrow-right"></i> بازگشت به کاربران</a>';
  $typeLabels = ['all' => 'همه فعالیت‌ها', 'auth' => 'ورود و ثبت‌نام', 'generation' => 'ساخت‌ها', 'order' => 'سفارش‌ها', 'purchase' => 'خریدها'];
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'سفر کاربر: '.$fullName,
  'subtitle' => 'خط زمانی یکپارچه از اولین ثبت‌نام تا آخرین فعالیت ثبت‌شده',
  'actions' => $actions,
])

<div class="g-stack">
  <section class="g-card g-user-summary">
    <div class="g-user-identity"><span class="g-user-avatar"><i class="fa-solid fa-user"></i></span><div><h2>{{ $fullName }}</h2><p>{{ $user->phone ?: 'بدون موبایل' }}{{ $user->email ? ' · '.$user->email : '' }}</p><span class="g-badge {{ $user->status === 'active' ? 'success' : 'warning' }}">{{ $user->status === 'active' ? 'فعال' : $user->status }}</span></div></div>
    <div class="g-user-meta-grid">
      <div><span>زمان ثبت‌نام</span><strong>{{ $user->registered_at?->format('Y/m/d H:i') ?? 'ثبت نشده' }}</strong></div>
      <div><span>آخرین ورود</span><strong>{{ $user->last_login_at?->format('Y/m/d H:i') ?? 'ثبت نشده' }}</strong></div>
      <div><span>دفعات ورود</span><strong>{{ number_format($user->login_count) }}</strong></div>
      <div><span>اعتبار فعلی</span><strong>{{ number_format($user->tokens) }}</strong></div>
      <div><span>منبع جذب</span><strong>{{ $source?->link?->title ?? 'مستقیم یا نامشخص' }}</strong></div>
      <div><span>کانال جذب</span><strong>{{ $source?->link?->channel ?? '—' }}</strong></div>
    </div>
  </section>

  <section class="g-kpis g-kpis-4">
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">ورود موفق</span><span class="g-kpi-icon info"><i class="fa-solid fa-right-to-bracket"></i></span></div><div class="g-kpi-value">{{ number_format($stats['logins']) }}</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">ساخت محتوا</span><span class="g-kpi-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span></div><div class="g-kpi-value">{{ number_format($stats['generations']) }}</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">سفارش</span><span class="g-kpi-icon warning"><i class="fa-solid fa-receipt"></i></span></div><div class="g-kpi-value">{{ number_format($stats['orders']) }}</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">خرید موفق</span><span class="g-kpi-icon success"><i class="fa-solid fa-bag-shopping"></i></span></div><div class="g-kpi-value">{{ number_format($stats['purchases']) }}</div><div class="g-kpi-foot">{{ number_format($stats['revenue']) }} تومان درآمد</div></article>
  </section>

  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-route"></i> خط زمانی فعالیت‌ها</div><div class="g-section-sub">اطلاعات ورود، ثبت‌نام، ساخت، سفارش و خرید به ترتیب زمان</div></div><div class="g-tabs">@foreach($typeLabels as $value => $label)<button type="button" class="g-tab {{ $value === 'all' ? 'active' : '' }}" data-timeline-filter="{{ $value }}">{{ $label }}</button>@endforeach</div></div>
    @if($timeline->isEmpty())
      @include('admin.growth.partials.empty', ['icon' => 'fa-route', 'emptyTitle' => 'هنوز فعالیتی برای این کاربر ثبت نشده است'])
    @else
      <div class="g-timeline g-card-pad">
        @foreach($timeline as $event)
          @php($icon = match($event['type']) { 'auth' => 'fa-key', 'generation' => 'fa-wand-magic-sparkles', 'order' => 'fa-receipt', 'purchase' => 'fa-bag-shopping', default => 'fa-circle' })
          <article class="g-timeline-item" data-timeline-type="{{ $event['type'] }}"><span class="g-timeline-icon {{ $event['type'] }}"><i class="fa-solid {{ $icon }}"></i></span><div class="g-timeline-body"><div class="g-timeline-title"><strong>{{ $event['title'] }}</strong><time>{{ $event['time']?->format('Y/m/d H:i:s') }}</time></div><p>{{ $event['detail'] }}</p>@if($event['meta'])<span>{{ $event['meta'] }}</span>@endif</div></article>
        @endforeach
      </div>
    @endif
  </section>
</div>
