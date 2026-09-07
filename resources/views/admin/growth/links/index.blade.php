@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.links.analytics').'"><i class="fa-solid fa-chart-column"></i> آنالیز لینک‌ها</a>'
    .'<a class="g-btn g-btn-primary" href="'.route('admin.growth.links.create').'"><i class="fa-solid fa-plus"></i> لینک جدید</a>';
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'مدیریت لینک‌ها',
  'subtitle' => 'ساخت، کوتاه‌سازی و مشاهده عملکرد لینک‌های اختصاصی هر محتوا و کانال',
  'actions' => $actions,
])

<div class="g-stack">
  <section class="g-kpis">
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">لینک فعال</span><span class="g-kpi-icon"><i class="fa-solid fa-link"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['active_links']) }}</div><div class="g-kpi-foot">آماده دریافت ورودی</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک</span><span class="g-kpi-icon info"><i class="fa-solid fa-arrow-pointer"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['clicks']) }}</div><div class="g-kpi-foot">۳۰ روز اخیر</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">کلیک یکتا</span><span class="g-kpi-icon"><i class="fa-solid fa-user-check"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['unique_clicks']) }}</div><div class="g-kpi-foot">بازدیدکننده یکتا</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">بازشدن مقصد</span><span class="g-kpi-icon success"><i class="fa-solid fa-circle-check"></i></span></div><div class="g-kpi-value">{{ number_format($metrics['page_opens']) }}</div><div class="g-kpi-foot">بارگذاری موفق صفحه</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">نرخ موفقیت</span><span class="g-kpi-icon warning"><i class="fa-solid fa-percent"></i></span></div><div class="g-kpi-value">{{ $metrics['success_rate'] }}٪</div><div class="g-kpi-foot">بازشدن به کلیک</div></article>
  </section>

  <form class="g-card g-filterbar" method="GET" action="{{ route('admin.growth.links.index') }}">
    <div class="g-search"><i class="fa-solid fa-magnifying-glass"></i><input class="g-input" name="search" value="{{ request('search') }}" placeholder="جست‌وجوی عنوان یا نام کوتاه"></div>
    <select class="g-input" name="channel"><option value="">همه کانال‌ها</option>@foreach($channelLabels as $key => $label)<option value="{{ $key }}" @selected(request('channel') === $key)>{{ $label }}</option>@endforeach</select>
    <button class="g-btn g-btn-soft" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button>
  </form>

  <section class="g-card">
    @if(count($links) === 0)
      @include('admin.growth.partials.empty', ['icon' => 'fa-link', 'emptyTitle' => 'هنوز لینکی ساخته نشده است', 'emptyText' => 'برای هر پست یا محتوای منتشرشده یک لینک مستقل بسازید تا ورودی آن جداگانه قابل تحلیل باشد.', 'emptyAction' => route('admin.growth.links.create'), 'emptyActionLabel' => 'ساخت اولین لینک'])
    @else
      <div class="g-table-wrap"><table class="g-table"><thead><tr><th>عنوان</th><th>کانال</th><th>لینک کوتاه</th><th>کلیک</th><th>بازشدن</th><th>موفقیت</th><th>وضعیت</th><th></th></tr></thead><tbody>
        @foreach($links as $link)
          @php $rate = $link->clicks_count > 0 ? round(min(100, ($link->page_opens_count / $link->clicks_count) * 100), 1) : 0; @endphp
          <tr>
            <td><div class="g-table-title">{{ $link->title }}</div><div class="g-table-sub">{{ $link->campaign ?: 'بدون کمپین' }}</div></td>
            <td><span class="g-badge">{{ $channelLabels[$link->channel] ?? $link->channel }}</span></td>
            <td><div class="g-url">{{ $link->short_url }}</div><button class="g-btn g-btn-soft" type="button" data-copy-value="{{ $link->short_url }}"><i class="fa-regular fa-copy"></i> کپی</button></td>
            <td>{{ number_format($link->clicks_count) }}</td><td>{{ number_format($link->page_opens_count) }}</td><td>{{ $rate }}٪</td>
            <td><span class="g-badge {{ $link->is_active ? 'success' : 'danger' }}">{{ $link->is_active ? 'فعال' : 'غیرفعال' }}</span></td>
            <td><a class="g-btn g-btn-primary" href="{{ route('admin.growth.links.analytics', $link) }}"><i class="fa-solid fa-chart-column"></i> آنالیز لینک</a></td>
          </tr>
        @endforeach
      </tbody></table></div>
      @if(method_exists($links, 'links'))<div class="g-card-pad g-pagination">{{ $links->links() }}</div>@endif
    @endif
  </section>
</div>
