@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.monitor').'"><i class="fa-solid fa-gauge-high"></i> پایش کامل</a>'
    .'<a class="g-btn g-btn-primary" href="'.route('admin.growth.links.create').'"><i class="fa-solid fa-plus"></i> لینک جدید</a>';
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'نمای کلی رشد',
  'subtitle' => 'تصویر خلاصه از جذب چندکاناله و سلامت مسیر ورود کاربران در ۳۰ روز اخیر',
  'actions' => $actions,
])

<div class="g-stack">
  <section class="g-kpis">
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">نمایش محتوا</span><span class="g-kpi-icon"><i class="fa-solid fa-eye"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['impressions']) }}</div><div class="g-kpi-foot">آمار دستی کانال‌ها</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک</span><span class="g-kpi-icon info"><i class="fa-solid fa-arrow-pointer"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['clicks']) }}</div><div class="g-kpi-foot">۳۰ روز اخیر</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک یکتا</span><span class="g-kpi-icon"><i class="fa-solid fa-users"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['unique_clicks']) }}</div><div class="g-kpi-foot">بر پایه شناسه بازدیدکننده</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">بازشدن مقصد</span><span class="g-kpi-icon success"><i class="fa-solid fa-up-right-from-square"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['page_opens']) }}</div><div class="g-kpi-foot">رویداد مستقل</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">نرخ موفقیت</span><span class="g-kpi-icon warning"><i class="fa-solid fa-percent"></i></span></div><div class="g-kpi-value">{{ $metrics['success_rate'] }}٪</div><div class="g-kpi-foot">بازشدن به کلیک</div></article>
  </section>

  <section class="g-grid-main">
    <article class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-chart-area"></i> روند ۳۰ روزه</div><div class="g-section-sub">مقایسه کلیک با بازشدن موفق صفحه مقصد</div></div></div>
      <div class="g-chart-wrap"><canvas data-growth-chart data-labels='@json($trend['labels'])' data-clicks='@json($trend['clicks'])' data-opens='@json($trend['opens'])'></canvas></div>
    </article>
    <article class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-arrow-down-wide-short"></i> قیف رشد</div><div class="g-section-sub">از دیده‌شدن محتوا تا خرید</div></div></div>
      <div class="g-funnel">
        @foreach($funnel as $stage)
          <div class="g-funnel-row"><span class="g-funnel-label">{{ $stage['label'] }}</span><div class="g-funnel-bar"><span style="width:{{ $stage['width'] }}%"></span></div><span class="g-funnel-value">{{ number_format($stage['value']) }}</span></div>
        @endforeach
      </div>
    </article>
  </section>

  <section class="g-channel-grid">
    @foreach($channels as $item)
      @php
        $icon = match($item['key']) { 'instagram' => 'fa-instagram', 'telegram' => 'fa-telegram', 'youtube' => 'fa-youtube', default => 'fa-share-nodes' };
        $iconFamily = $item['key'] === 'other' ? 'fa-solid' : 'fa-brands';
      @endphp
      <a class="g-card g-channel" href="{{ route('admin.growth.channels', $item['key']) }}">
        <div class="g-channel-top"><span class="g-channel-name">{{ $item['label'] }}</span><span class="g-channel-icon"><i class="{{ $iconFamily }} {{ $icon }}"></i></span></div>
        <div class="g-channel-number">{{ number_format($item['clicks']) }}</div>
        <div class="g-channel-meta"><span>{{ $item['contents'] }} محتوا</span><strong>{{ $item['success_rate'] }}٪ بازشدن</strong></div>
        <div class="g-progress"><span style="width:{{ min(100, $item['success_rate']) }}%"></span></div>
      </a>
    @endforeach
  </section>
</div>
