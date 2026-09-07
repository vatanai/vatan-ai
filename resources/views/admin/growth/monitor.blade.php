@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.users.index').'"><i class="fa-solid fa-users"></i> کاربران و سفر مشتری</a>'
    .'<a class="g-btn" href="'.route('admin.growth.overview').'"><i class="fa-solid fa-chart-pie"></i> نمای کلی</a>'
    .'<a class="g-btn g-btn-primary" href="'.route('admin.growth.links.create').'"><i class="fa-solid fa-link"></i> ساخت لینک</a>';
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'پایش کامل رشد',
  'subtitle' => 'مرکز فرماندهی برای دیدن وضعیت کانال‌ها، قیف جذب، لینک‌ها و نقاط احتمالی افت در یک نگاه',
  'actions' => $actions,
])

<div class="g-stack">
  <section class="g-card g-command">
    <div class="g-command-row">
      <div>
        <span class="g-live">پایش فعال</span>
        <h2>وضعیت کل مسیر جذب تا خرید</h2>
        <p>تمام اعداد این صفحه از داده‌های واقعی مرکز رشد و بخش‌های فعلی وطن خوانده می‌شوند. برای جزئیات هر بخش می‌توانید مستقیماً وارد زیرمنوی مربوط شوید.</p>
      </div>
      <div class="g-insight">
        <strong><i class="fa-solid fa-wand-magic-sparkles"></i> بینش خودکار</strong>
        <span>
          @if($metrics['clicks'] === 0)
            برای دریافت اولین بینش، یک لینک بسازید و انتشار محتوا را آغاز کنید.
          @elseif($metrics['success_rate'] < 70)
            نرخ بازشدن مقصد نیازمند بررسی است؛ فاصله کلیک تا بارگذاری صفحه بیش از حد معمول است.
          @else
            مسیر کلیک تا بازشدن مقصد پایدار است؛ تمرکز بعدی می‌تواند روی بهبود تبدیل به ساخت و خرید باشد.
          @endif
        </span>
      </div>
    </div>
  </section>

  <section class="g-kpis">
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک ۳۰ روز اخیر</span><span class="g-kpi-icon"><i class="fa-solid fa-arrow-pointer"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['clicks']) }}</div><div class="g-kpi-foot"><span class="{{ $metrics['clicks_delta'] >= 0 ? 'g-up' : 'g-down' }}">{{ $metrics['clicks_delta'] >= 0 ? '+' : '' }}{{ $metrics['clicks_delta'] }}٪</span> نسبت به بازه قبل</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک یکتا</span><span class="g-kpi-icon info"><i class="fa-solid fa-user-check"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['unique_clicks']) }}</div><div class="g-kpi-foot">شناسه بازدیدکننده یکتا</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">بازشدن موفق مقصد</span><span class="g-kpi-icon success"><i class="fa-solid fa-circle-check"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['page_opens']) }}</div><div class="g-kpi-foot">رویدادی مستقل از کلیک</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">نرخ بازشدن</span><span class="g-kpi-icon warning"><i class="fa-solid fa-percent"></i></span></div><div class="g-kpi-value">{{ $metrics['success_rate'] }}٪</div><div class="g-kpi-foot">کلیک منجر به بارگذاری صفحه</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">لینک فعال</span><span class="g-kpi-icon"><i class="fa-solid fa-link"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['active_links']) }}</div><div class="g-kpi-foot">در {{ number_format($metrics['contents']) }} محتوای ثبت‌شده</div></article>
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
        <div class="g-channel-meta"><span>{{ $item['contents'] }} محتوا · {{ $item['links'] }} لینک</span><strong>{{ $item['success_rate'] }}٪ موفق</strong></div>
        <div class="g-progress"><span style="width:{{ min(100, $item['success_rate']) }}%"></span></div>
      </a>
    @endforeach
  </section>

  <section class="g-card">
    <div class="g-section-head">
      <div><div class="g-section-title"><i class="fa-solid fa-heart-pulse"></i> سلامت منابع داده</div><div class="g-section-sub">وضعیت دریافت اطلاعات داخلی، رهگیری لینک‌ها و آمار شبکه‌های اجتماعی</div></div>
      <div class="g-actions">
        @if($sourceHealth['error'] > 0)<span class="g-badge danger">{{ $sourceHealth['error'] }} خطا</span>@endif
        @if($sourceHealth['warning'] > 0)<span class="g-badge warning">{{ $sourceHealth['warning'] }} نیازمند توجه</span>@endif
        <a class="g-btn g-btn-soft" href="{{ route('admin.growth.data-sources.index') }}">مدیریت منابع</a>
      </div>
    </div>
    @if($sourceHealth['sources']->isEmpty())
      <div class="g-card-pad"><div class="g-note">پس از اجرای به‌روزرسانی دیتابیس، منابع داخلی وطن و ورودی‌های شبکه‌های اجتماعی اینجا پایش می‌شوند.</div></div>
    @else
      @php($healthLabels = ['healthy' => 'سالم', 'warning' => 'نیازمند توجه', 'idle' => 'منتظر داده', 'error' => 'خطا', 'inactive' => 'غیرفعال'])
      <div class="g-health-list g-card-pad">
        @foreach($sourceHealth['sources']->take(6) as $sourceItem)
          <a class="g-health-item" href="{{ route('admin.growth.data-sources.index', ['source' => $sourceItem['source']->slug]) }}#manage-source">
            <span class="g-health-item-name">{{ $sourceItem['source']->name }}</span>
            <span class="g-health {{ $sourceItem['health'] }}">{{ $healthLabels[$sourceItem['health']] ?? $sourceItem['health'] }}</span>
            <span class="g-health-item-text">{{ $sourceItem['message'] }}</span>
            <span class="g-health-item-text">{{ $sourceItem['last_activity_human'] }}</span>
          </a>
        @endforeach
      </div>
    @endif
  </section>

  <section class="g-grid-main">
    <article class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-chart-line"></i> روند کلیک و بازشدن مقصد</div><div class="g-section-sub">۱۴ روز اخیر، تفکیک دو رویداد مستقل</div></div><a class="g-btn g-btn-soft" href="{{ route('admin.growth.links.analytics') }}">جزئیات</a></div>
      <div class="g-chart-wrap"><canvas data-growth-chart data-labels='@json($trend['labels'])' data-clicks='@json($trend['clicks'])' data-opens='@json($trend['opens'])'></canvas></div>
    </article>
    <article class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-filter"></i> قیف کامل رشد</div><div class="g-section-sub">داده رشد و آمار عملیاتی وطن</div></div></div>
      <div class="g-funnel">
        @foreach($funnel as $stage)
          <div class="g-funnel-row"><span class="g-funnel-label">{{ $stage['label'] }}</span><div class="g-funnel-bar"><span style="width:{{ $stage['width'] }}%"></span></div><span class="g-funnel-value">{{ number_format($stage['value']) }}</span></div>
        @endforeach
      </div>
    </article>
  </section>

  <section class="g-grid-equal">
    <article class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-bolt"></i> لینک‌های فعال</div><div class="g-section-sub">میانبر آنالیز عملکرد</div></div><a href="{{ route('admin.growth.links.index') }}" class="g-btn">همه لینک‌ها</a></div>
      @if($activeLinks->isEmpty())
        @include('admin.growth.partials.empty', ['icon' => 'fa-link', 'emptyTitle' => 'هنوز لینک فعالی ندارید', 'emptyAction' => route('admin.growth.links.create'), 'emptyActionLabel' => 'ساخت اولین لینک'])
      @else
        <div class="g-table-wrap"><table class="g-table"><thead><tr><th>لینک</th><th>کلیک</th><th>بازشدن</th><th></th></tr></thead><tbody>
          @foreach($activeLinks as $link)
            <tr><td><div class="g-table-title">{{ $link->title }}</div><div class="g-table-sub">{{ $link->short_url }}</div></td><td>{{ number_format($link->clicks_count) }}</td><td>{{ number_format($link->page_opens_count) }}</td><td><a class="g-btn g-icon-btn" title="آنالیز لینک" href="{{ route('admin.growth.links.analytics', $link) }}"><i class="fa-solid fa-chart-column"></i></a></td></tr>
          @endforeach
        </tbody></table></div>
      @endif
    </article>

    <article class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-ranking-star"></i> محتواهای برتر</div><div class="g-section-sub">مرتب‌شده بر اساس تعامل ثبت‌شده</div></div><a href="{{ route('admin.growth.contents') }}" class="g-btn">همه محتواها</a></div>
      @if($topContents->isEmpty())
        @include('admin.growth.partials.empty', ['icon' => 'fa-photo-film', 'emptyTitle' => 'هنوز محتوایی ثبت نشده است', 'emptyAction' => route('admin.growth.contents'), 'emptyActionLabel' => 'ثبت محتوا'])
      @else
        <div class="g-table-wrap"><table class="g-table"><thead><tr><th>محتوا</th><th>تعامل</th><th>کلیک</th><th>کانال</th></tr></thead><tbody>
          @foreach($topContents as $content)
            <tr><td><div class="g-table-title">{{ $content->title }}</div><div class="g-table-sub">{{ $content->published_at?->format('Y/m/d H:i') ?? 'بدون زمان انتشار' }}</div></td><td>{{ number_format($content->engagements) }}</td><td>{{ number_format($content->clicks_count) }}</td><td><span class="g-badge">{{ $channels->firstWhere('key', $content->channel)['label'] ?? $content->channel }}</span></td></tr>
          @endforeach
        </tbody></table></div>
      @endif
    </article>
  </section>
</div>
