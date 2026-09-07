@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.contents', ['channel' => $channel]).'"><i class="fa-solid fa-photo-film"></i> همه محتواها</a>'
    .'<a class="g-btn g-btn-primary" href="'.route('admin.growth.links.create').'"><i class="fa-solid fa-link"></i> ساخت لینک</a>';
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'کانال ' . $channelLabels[$channel],
  'subtitle' => 'مدیریت محتوا، لینک‌ها و عملکرد ورودی این کانال در یک نمای مستقل',
  'actions' => $actions,
])

<div class="g-stack">
  <nav class="g-tabs" aria-label="کانال‌های رشد">
    @foreach($channelLabels as $key => $label)
      <a class="g-tab {{ $channel === $key ? 'active' : '' }}" href="{{ route('admin.growth.channels', $key) }}">{{ $label }}</a>
    @endforeach
  </nav>

  <section class="g-kpis">
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">محتوا</span><span class="g-kpi-icon"><i class="fa-solid fa-photo-film"></i></span></div><div class="g-kpi-value">{{ number_format($summary['contents']) }}</div><div class="g-kpi-foot">ثبت‌شده در این کانال</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">لینک</span><span class="g-kpi-icon info"><i class="fa-solid fa-link"></i></span></div><div class="g-kpi-value">{{ number_format($summary['links']) }}</div><div class="g-kpi-foot">اختصاصی این کانال</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک</span><span class="g-kpi-icon"><i class="fa-solid fa-arrow-pointer"></i></span></div><div class="g-kpi-value">{{ number_format($summary['clicks']) }}</div><div class="g-kpi-foot">همه بازه‌ها</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">بازشدن مقصد</span><span class="g-kpi-icon success"><i class="fa-solid fa-circle-check"></i></span></div><div class="g-kpi-value">{{ number_format($summary['page_opens']) }}</div><div class="g-kpi-foot">ثبت واقعی بارگذاری</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">نرخ موفقیت</span><span class="g-kpi-icon warning"><i class="fa-solid fa-percent"></i></span></div><div class="g-kpi-value">{{ $summary['success_rate'] }}٪</div><div class="g-kpi-foot">بازشدن به کلیک</div></article>
  </section>

  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-layer-group"></i> محتواهای {{ $channelLabels[$channel] }}</div><div class="g-section-sub">نمای کارتی برای مقایسه سریع عملکرد محتواها</div></div><a class="g-btn g-btn-soft" href="{{ route('admin.growth.contents', ['channel' => $channel]) }}">مدیریت محتواها</a></div>
    <div class="g-card-pad">
      @if(count($contents) === 0)
        @include('admin.growth.partials.empty', ['icon' => 'fa-photo-film', 'emptyTitle' => 'برای این کانال محتوایی ثبت نشده است', 'emptyAction' => route('admin.growth.contents', ['channel' => $channel]), 'emptyActionLabel' => 'ثبت محتوا'])
      @else
        <div class="g-content-grid">
          @foreach($contents as $content)
            <article class="g-card g-content-card">
              <div class="g-content-cover"><i class="fa-solid fa-photo-film"></i><span class="g-badge {{ $content->status === 'active' ? 'success' : 'warning' }}">{{ $content->status === 'active' ? 'فعال' : ($content->status === 'draft' ? 'پیش‌نویس' : 'بایگانی') }}</span></div>
              <div class="g-content-body">
                <h3>{{ $content->title }}</h3><p>{{ $content->content_type ?: 'نوع محتوا ثبت نشده' }} · {{ $content->published_at?->format('Y/m/d H:i') ?? 'بدون زمان انتشار' }}</p>
                <div class="g-content-stats">
                  <div class="g-content-stat"><strong>{{ number_format($content->impressions) }}</strong><span>نمایش</span></div>
                  <div class="g-content-stat"><strong>{{ number_format($content->engagements) }}</strong><span>تعامل</span></div>
                  <div class="g-content-stat"><strong>{{ number_format($content->comments) }}</strong><span>کامنت</span></div>
                  <div class="g-content-stat"><strong>{{ number_format($content->shares) }}</strong><span>اشتراک</span></div>
                </div>
                <div class="g-content-actions">
                  @if($content->link)<a class="g-btn g-btn-soft" href="{{ route('admin.growth.links.analytics', $content->link) }}"><i class="fa-solid fa-chart-column"></i> آنالیز لینک</a>@endif
                  @if($content->external_url)<a class="g-btn g-icon-btn" href="{{ $content->external_url }}" target="_blank" rel="noopener" title="مشاهده محتوا"><i class="fa-solid fa-up-right-from-square"></i></a>@endif
                </div>
              </div>
            </article>
          @endforeach
        </div>
        @if(method_exists($contents, 'links'))<div class="g-pagination">{{ $contents->links() }}</div>@endif
      @endif
    </div>
  </section>

  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-link"></i> لینک‌های کانال</div><div class="g-section-sub">کلیک و بازشدن مقصد برای هر لینک</div></div></div>
    @if($links->isEmpty())
      @include('admin.growth.partials.empty', ['icon' => 'fa-link', 'emptyTitle' => 'هنوز لینکی برای این کانال ساخته نشده است', 'emptyAction' => route('admin.growth.links.create'), 'emptyActionLabel' => 'ساخت لینک'])
    @else
      <div class="g-table-wrap"><table class="g-table"><thead><tr><th>عنوان</th><th>لینک کوتاه</th><th>کلیک</th><th>بازشدن</th><th>وضعیت</th><th></th></tr></thead><tbody>
        @foreach($links as $link)
          <tr><td class="g-table-title">{{ $link->title }}</td><td><span class="g-url">{{ $link->short_url }}</span></td><td>{{ number_format($link->clicks_count) }}</td><td>{{ number_format($link->page_opens_count) }}</td><td><span class="g-badge {{ $link->is_active ? 'success' : 'danger' }}">{{ $link->is_active ? 'فعال' : 'غیرفعال' }}</span></td><td><a class="g-btn g-btn-soft" href="{{ route('admin.growth.links.analytics', $link) }}">آنالیز لینک</a></td></tr>
        @endforeach
      </tbody></table></div>
    @endif
  </section>
</div>
