@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.links.index').'"><i class="fa-solid fa-list"></i> مدیریت لینک‌ها</a>'
    .'<a class="g-btn g-btn-primary" href="'.route('admin.growth.links.create').'"><i class="fa-solid fa-plus"></i> لینک جدید</a>';
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'آنالیز ۲۴ ساعته لینک',
  'subtitle' => 'آمار دائمی پنجره‌های متوالی ۲۴ ساعته، روند عملکرد و جزئیات هر رویداد',
  'actions' => $actions,
])

<div class="g-stack">
  @if(!$growthLink)
    <section class="g-card">@include('admin.growth.partials.empty', ['icon' => 'fa-chart-column', 'emptyTitle' => 'لینکی برای تحلیل وجود ندارد', 'emptyText' => 'پس از ساخت اولین لینک، همه بازه‌های ۲۴ ساعته و جزئیات رویدادها در این صفحه نمایش داده می‌شوند.', 'emptyAction' => route('admin.growth.links.create'), 'emptyActionLabel' => 'ساخت اولین لینک'])</section>
  @else
    <section class="g-card g-filterbar">
      <div class="g-field" style="flex:1;min-width:240px"><label for="analytics-link">انتخاب لینک</label><select class="g-input" id="analytics-link" data-link-select>@foreach($links as $link)<option value="{{ route('admin.growth.links.analytics', $link) }}" @selected($link->is($growthLink))>{{ $link->title }} — {{ $link->slug }}</option>@endforeach</select></div>
      <div style="flex:1;min-width:240px"><div class="g-url">{{ $growthLink->short_url }}</div><div class="g-actions"><button class="g-btn g-btn-soft" type="button" data-copy-value="{{ $growthLink->short_url }}"><i class="fa-regular fa-copy"></i> کپی لینک</button><a class="g-btn" href="{{ $growthLink->destination_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-up-right-from-square"></i> مقصد</a></div></div>
      <span class="g-badge {{ $growthLink->is_active ? 'success' : 'danger' }}">{{ $growthLink->is_active ? 'فعال' : 'غیرفعال' }}</span>
    </section>

    <section class="g-kpis">
      <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کل کلیک</span><span class="g-kpi-icon"><i class="fa-solid fa-arrow-pointer"></i></span></div><div class="g-kpi-value">{{ number_format($summary['total_clicks']) }}</div><div class="g-kpi-foot">در بازه انتخاب‌شده</div></article>
      <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک یکتا</span><span class="g-kpi-icon info"><i class="fa-solid fa-user-check"></i></span></div><div class="g-kpi-value">{{ number_format($summary['unique_clicks']) }}</div><div class="g-kpi-foot">بازدیدکننده یکتا</div></article>
      <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">بازشدن موفق</span><span class="g-kpi-icon success"><i class="fa-solid fa-circle-check"></i></span></div><div class="g-kpi-value">{{ number_format($summary['page_opens']) }}</div><div class="g-kpi-foot">رویداد بارگذاری مقصد</div></article>
      <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">بدون بازشدن</span><span class="g-kpi-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></span></div><div class="g-kpi-value">{{ number_format($summary['failed_opens']) }}</div><div class="g-kpi-foot">کلیک فاقد رویداد مقصد</div></article>
      <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">نرخ موفقیت</span><span class="g-kpi-icon success"><i class="fa-solid fa-percent"></i></span></div><div class="g-kpi-value">{{ $summary['success_rate'] }}٪</div><div class="g-kpi-foot">بازشدن واقعی به کلیک</div></article>
    </section>

    <section class="g-grid-main">
      <article class="g-card">
        <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-chart-line"></i> روند عملکرد لینک</div><div class="g-section-sub">۱۴ روز اخیر</div></div></div>
        <div class="g-chart-wrap"><canvas data-growth-chart data-labels='@json($trend['labels'])' data-clicks='@json($trend['clicks'])' data-opens='@json($trend['opens'])'></canvas></div>
      </article>
      <article class="g-card">
        <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-mobile-screen"></i> نوع کاربر و دستگاه</div><div class="g-section-sub">بازه انتخاب‌شده</div></div></div>
        <div class="g-card-pad">
          <div class="g-breakdown-row"><span>موبایل</span><strong>{{ number_format($summary['mobile_clicks']) }}</strong></div>
          <div class="g-breakdown-row"><span>دسکتاپ</span><strong>{{ number_format($summary['desktop_clicks']) }}</strong></div>
          <div class="g-breakdown-row"><span>کاربر جدید</span><strong>{{ number_format($summary['new_visitors']) }}</strong></div>
          <div class="g-breakdown-row"><span>کاربر تکراری</span><strong>{{ number_format($summary['repeat_visitors']) }}</strong></div>
        </div>
      </article>
    </section>

    <section class="g-breakdowns">
      @foreach([
        'operating_systems' => ['سیستم‌عامل', 'fa-laptop'],
        'browsers' => ['مرورگر', 'fa-window-maximize'],
        'sources' => ['منبع و ارجاع', 'fa-share-nodes'],
        'countries' => ['کشور', 'fa-earth-asia'],
        'cities' => ['شهر', 'fa-location-dot'],
      ] as $breakdownKey => [$breakdownLabel, $breakdownIcon])
        <article class="g-card g-breakdown">
          <h3><i class="fa-solid {{ $breakdownIcon }}"></i> {{ $breakdownLabel }}</h3>
          @forelse(($summary['breakdowns'][$breakdownKey] ?? []) as $name => $count)
            <div class="g-breakdown-row"><span>{{ $name }}</span><strong>{{ number_format($count) }}</strong></div>
          @empty
            <div class="g-breakdown-row"><span>داده‌ای ثبت نشده</span><strong>۰</strong></div>
          @endforelse
        </article>
      @endforeach
    </section>

    <section class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-clock-rotate-left"></i> آرشیو بازه‌های ۲۴ ساعته</div><div class="g-section-sub">هیچ بازه‌ای پس از پایان روز حذف یا صفر نمی‌شود</div></div></div>
      <div class="g-table-wrap"><table class="g-table"><thead><tr><th>بازه</th><th>کل کلیک</th><th>یکتا</th><th>بازشدن</th><th>بدون بازشدن</th><th>موفقیت</th><th>موبایل / دسکتاپ</th><th>جدید / تکراری</th><th></th></tr></thead><tbody>
        @foreach($windows as $window)
          @php
            $start = \Carbon\Carbon::parse($window['window_start'])->setTimezone('Asia/Tehran');
            $end = \Carbon\Carbon::parse($window['window_end'])->setTimezone('Asia/Tehran');
            $isSelected = ($selectedWindow['is_live'] && $window['is_live']) || (!$selectedWindow['is_live'] && (int) $selectedWindow['id'] === (int) ($window['id'] ?? 0));
          @endphp
          <tr>
            <td><div class="g-table-title">{{ $start->format('Y/m/d H:i') }}</div><div class="g-table-sub">تا {{ $end->format('Y/m/d H:i') }}</div></td>
            <td>{{ number_format($window['total_clicks']) }}</td><td>{{ number_format($window['unique_clicks']) }}</td><td>{{ number_format($window['page_opens']) }}</td><td>{{ number_format($window['failed_opens']) }}</td><td>{{ $window['success_rate'] }}٪</td>
            <td>{{ number_format($window['mobile_clicks']) }} / {{ number_format($window['desktop_clicks']) }}</td><td>{{ number_format($window['new_visitors']) }} / {{ number_format($window['repeat_visitors']) }}</td>
            <td>
              @if($window['is_live'])<a class="g-btn {{ $isSelected ? 'g-btn-primary' : 'g-btn-soft' }}" href="{{ route('admin.growth.links.analytics', $growthLink) }}"><i class="fa-solid fa-bolt"></i> زنده</a>
              @else<a class="g-btn {{ $isSelected ? 'g-btn-primary' : 'g-btn-soft' }}" href="{{ route('admin.growth.links.analytics', ['growthLink' => $growthLink, 'window' => $window['id']]) }}"><i class="fa-solid fa-magnifying-glass"></i> جزئیات</a>@endif
            </td>
          </tr>
        @endforeach
      </tbody></table></div>
    </section>

    <section class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-list-check"></i> جزئیات رویدادها</div><div class="g-section-sub">هر کلیک و بازشدن مقصد به‌صورت مستقل</div></div><span class="g-badge {{ $selectedWindow['is_live'] ? 'success' : 'info' }}">{{ $selectedWindow['is_live'] ? 'بازه زنده' : 'بازه آرشیوی' }}</span></div>
      @if(count($events) === 0)
        @include('admin.growth.partials.empty', ['icon' => 'fa-list-check', 'emptyTitle' => 'در این بازه رویدادی ثبت نشده است', 'emptyText' => 'جزئیات زمان، دستگاه، مرورگر، منبع و موقعیت رویدادها پس از اولین ورودی نمایش داده می‌شود.'])
      @else
        <div class="g-table-wrap"><table class="g-table"><thead><tr><th>رویداد</th><th>زمان</th><th>منبع</th><th>دستگاه</th><th>سیستم‌عامل / مرورگر</th><th>کشور / شهر</th><th>کاربر</th><th>شناسه</th></tr></thead><tbody>
          @foreach($events as $event)
            <tr>
              <td><span class="g-badge {{ $event->event_type === \App\Models\GrowthEvent::TYPE_PAGE_OPEN ? 'success' : 'info' }}">{{ $event->event_type === \App\Models\GrowthEvent::TYPE_PAGE_OPEN ? 'بازشدن صفحه' : 'کلیک' }}</span></td>
              <td>{{ $event->occurred_at->format('Y/m/d H:i:s') }}</td><td>{{ $event->source ?: 'نامشخص' }}<div class="g-table-sub">{{ $event->referrer ?: 'بدون ارجاع‌دهنده' }}</div></td>
              <td>{{ $event->device_type === 'mobile' ? 'موبایل' : ($event->device_type === 'desktop' ? 'دسکتاپ' : 'نامشخص') }}</td><td>{{ $event->operating_system ?: 'نامشخص' }} / {{ $event->browser ?: 'نامشخص' }}</td>
              <td>{{ $event->country ?: 'نامشخص' }} / {{ $event->city ?: 'نامشخص' }}</td><td><span class="g-badge {{ $event->is_new_visitor ? 'success' : '' }}">{{ $event->is_new_visitor ? 'جدید' : 'تکراری' }}</span></td>
              <td><span class="g-table-sub">{{ \Illuminate\Support\Str::limit($event->event_uuid, 12) }}</span></td>
            </tr>
          @endforeach
        </tbody></table></div>
        @if(method_exists($events, 'links'))<div class="g-card-pad g-pagination">{{ $events->links() }}</div>@endif
      @endif
    </section>
  @endif
</div>
