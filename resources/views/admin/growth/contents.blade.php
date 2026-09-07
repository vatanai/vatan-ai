@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.links.index').'"><i class="fa-solid fa-link"></i> مدیریت لینک‌ها</a>'
    .'<a class="g-btn g-btn-soft" href="'.route('admin.growth.data-sources.index').'#add-source"><i class="fa-solid fa-database"></i> منابع و ورود فایل</a>';
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'مدیریت محتواها',
  'subtitle' => 'ثبت و مقایسه محتوای اینستاگرام، تلگرام، یوتیوب و سایر کانال‌ها به‌شکل کارتی و جدولی',
  'actions' => $actions,
])

<div class="g-stack">
  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-plus"></i> ثبت محتوای جدید</div><div class="g-section-sub">آمار شبکه اجتماعی دستی ثبت می‌شود؛ کلیک و بازشدن لینک خودکار است</div></div></div>
    <form class="g-form-grid g-card-pad" method="POST" action="{{ route('admin.growth.contents.store') }}">
      @csrf
      <div class="g-field"><label for="content-title">عنوان محتوا</label><input class="g-input" id="content-title" name="title" value="{{ old('title') }}" required placeholder="مثلاً معرفی محصول پرتره"></div>
      <div class="g-field"><label for="content-channel">کانال</label><select class="g-input" id="content-channel" name="channel" required>@foreach($channelLabels as $key => $label)<option value="{{ $key }}" @selected(old('channel', request('channel', 'instagram')) === $key)>{{ $label }}</option>@endforeach</select></div>
      <div class="g-field"><label for="content-type">نوع محتوا</label><input class="g-input" id="content-type" name="content_type" value="{{ old('content_type') }}" placeholder="پست، ریلز، استوری، ویدیو یا پیام"></div>
      <div class="g-field"><label for="content-link">لینک اختصاصی متصل</label><select class="g-input" id="content-link" name="growth_link_id"><option value="">بدون لینک</option>@foreach($links as $link)<option value="{{ $link->id }}" @selected((string) old('growth_link_id') === (string) $link->id)>{{ $link->title }}</option>@endforeach</select></div>
      <div class="g-field"><label for="content-external-id">شناسه محتوا در کانال</label><input class="g-input" id="content-external-id" name="external_id" value="{{ old('external_id') }}" placeholder="شناسه پست یا ویدیو"></div>
      <div class="g-field"><label for="content-external-url">نشانی محتوای اصلی</label><input class="g-input" id="content-external-url" name="external_url" value="{{ old('external_url') }}" inputmode="url" dir="ltr" placeholder="https://..."></div>
      <div class="g-field"><label for="content-published">زمان انتشار</label><input class="g-input" id="content-published" type="datetime-local" name="published_at" value="{{ old('published_at') }}"></div>
      <div class="g-field"><label for="content-status">وضعیت</label><select class="g-input" id="content-status" name="status"><option value="active">فعال</option><option value="draft">پیش‌نویس</option><option value="archived">بایگانی</option></select></div>
      <div class="g-field"><label for="content-source">منبع آمار</label><select class="g-input" id="content-source" name="data_source_id"><option value="">انتخاب خودکار بر اساس کانال</option>@foreach($dataSources as $source)<option value="{{ $source->id }}" data-channel="{{ $source->channel }}" @selected((string) old('data_source_id') === (string) $source->id)>{{ $source->name }} · {{ $channelLabels[$source->channel] ?? $source->channel }}</option>@endforeach</select></div>
      <div class="g-field"><label for="content-metric-date">تاریخ این آمار</label><input class="g-input" id="content-metric-date" type="date" name="metric_date" value="{{ old('metric_date', today()->toDateString()) }}"></div>
      <div class="g-field"><label for="content-impressions">نمایش</label><input class="g-input" id="content-impressions" type="number" min="0" name="impressions" value="{{ old('impressions', 0) }}"></div>
      <div class="g-field"><label for="content-engagements">تعامل</label><input class="g-input" id="content-engagements" type="number" min="0" name="engagements" value="{{ old('engagements', 0) }}"></div>
      <div class="g-field"><label for="content-comments">کامنت</label><input class="g-input" id="content-comments" type="number" min="0" name="comments" value="{{ old('comments', 0) }}"></div>
      <div class="g-field"><label for="content-shares">اشتراک‌گذاری</label><input class="g-input" id="content-shares" type="number" min="0" name="shares" value="{{ old('shares', 0) }}"></div>
      <div class="g-field"><label for="content-likes">پسند</label><input class="g-input" id="content-likes" type="number" min="0" name="likes" value="{{ old('likes', 0) }}"></div>
      <div class="g-field"><label for="content-saves">ذخیره</label><input class="g-input" id="content-saves" type="number" min="0" name="saves" value="{{ old('saves', 0) }}"></div>
      <div class="g-form-actions"><button class="g-btn g-btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ثبت محتوا</button></div>
    </form>
  </section>

  <form class="g-card g-filterbar" method="GET" action="{{ route('admin.growth.contents') }}">
    <div class="g-search"><i class="fa-solid fa-magnifying-glass"></i><input class="g-input" name="search" value="{{ request('search') }}" placeholder="جست‌وجوی عنوان محتوا"></div>
    <select class="g-input" name="channel"><option value="">همه کانال‌ها</option>@foreach($channelLabels as $key => $label)<option value="{{ $key }}" @selected(request('channel') === $key)>{{ $label }}</option>@endforeach</select>
    <select class="g-input" name="status"><option value="">همه وضعیت‌ها</option><option value="active" @selected(request('status') === 'active')>فعال</option><option value="draft" @selected(request('status') === 'draft')>پیش‌نویس</option><option value="archived" @selected(request('status') === 'archived')>بایگانی</option></select>
    <button class="g-btn g-btn-soft" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button>
  </form>

  @if(count($contents) === 0)
    <section class="g-card">@include('admin.growth.partials.empty', ['icon' => 'fa-photo-film', 'emptyTitle' => 'محتوایی با این فیلتر پیدا نشد', 'emptyText' => 'محتوای هر کانال را ثبت کنید تا مقایسه کارت‌ها و آمار کل در دسترس باشد.'])</section>
  @else
    <section>
      <div class="g-content-grid">
        @foreach($contents as $content)
          <article class="g-card g-content-card">
            <div class="g-content-cover"><i class="fa-solid fa-photo-film"></i><span class="g-badge {{ $content->status === 'active' ? 'success' : 'warning' }}">{{ $content->status === 'active' ? 'فعال' : ($content->status === 'draft' ? 'پیش‌نویس' : 'بایگانی') }}</span></div>
            <div class="g-content-body">
              <h3>{{ $content->title }}</h3><p>{{ $channelLabels[$content->channel] ?? $content->channel }} · {{ $content->content_type ?: 'نوع نامشخص' }}</p>
              <div class="g-content-stats g-six">
                <div class="g-content-stat"><strong>{{ number_format($content->impressions) }}</strong><span>نمایش</span></div>
                <div class="g-content-stat"><strong>{{ number_format($content->engagements) }}</strong><span>تعامل</span></div>
                <div class="g-content-stat"><strong>{{ number_format($content->comments) }}</strong><span>کامنت</span></div>
                <div class="g-content-stat"><strong>{{ number_format($content->shares) }}</strong><span>اشتراک</span></div>
                <div class="g-content-stat"><strong>{{ number_format($content->likes ?? 0) }}</strong><span>پسند</span></div>
                <div class="g-content-stat"><strong>{{ number_format($content->saves ?? 0) }}</strong><span>ذخیره</span></div>
              </div>
              <div class="g-content-actions">
                @if($content->link)<a class="g-btn g-btn-soft" href="{{ route('admin.growth.links.analytics', $content->link) }}"><i class="fa-solid fa-chart-column"></i> آنالیز لینک</a>@endif
                @if($content->external_url)<a class="g-btn g-icon-btn" href="{{ $content->external_url }}" target="_blank" rel="noopener" title="مشاهده"><i class="fa-solid fa-up-right-from-square"></i></a>@endif
              </div>
            </div>
          </article>
        @endforeach
      </div>
    </section>

    <section class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-table-list"></i> ورود سریع آمار روزانه</div><div class="g-section-sub">هر ذخیره یک رکورد تاریخ‌دار و قابل ردیابی از منبع انتخاب‌شده می‌سازد</div></div><a class="g-btn" href="{{ route('admin.growth.data-sources.index') }}#add-source"><i class="fa-solid fa-file-csv"></i> ورود گروهی</a></div>
      <div class="g-table-wrap"><table class="g-table g-table-daily"><thead><tr><th>محتوا</th><th>تاریخ</th><th>منبع</th><th>نمایش</th><th>تعامل</th><th>کامنت</th><th>اشتراک</th><th>پسند</th><th>ذخیره</th><th>وضعیت</th><th></th></tr></thead><tbody>
        @foreach($contents as $content)
          <tr>
            <td><form id="content-metrics-{{ $content->id }}" method="POST" action="{{ route('admin.growth.contents.update', $content) }}">@csrf @method('PATCH')</form><div class="g-table-title">{{ $content->title }}</div><div class="g-table-sub">{{ $channelLabels[$content->channel] ?? $content->channel }}</div></td>
            <td><input class="g-input g-date-compact" form="content-metrics-{{ $content->id }}" type="date" name="metric_date" value="{{ today()->toDateString() }}" aria-label="تاریخ آمار"></td>
            <td><select class="g-input g-source-select" form="content-metrics-{{ $content->id }}" name="data_source_id" aria-label="منبع آمار"><option value="">منبع پیش‌فرض</option>@foreach($dataSources->filter(fn ($source) => in_array($source->channel, ['all', $content->channel], true)) as $source)<option value="{{ $source->id }}">{{ $source->name }}</option>@endforeach</select></td>
            <td><input class="g-input g-compact" form="content-metrics-{{ $content->id }}" type="number" min="0" name="impressions" value="{{ $content->impressions }}" aria-label="نمایش"></td>
            <td><input class="g-input g-compact" form="content-metrics-{{ $content->id }}" type="number" min="0" name="engagements" value="{{ $content->engagements }}" aria-label="تعامل"></td>
            <td><input class="g-input g-compact" form="content-metrics-{{ $content->id }}" type="number" min="0" name="comments" value="{{ $content->comments }}" aria-label="کامنت"></td>
            <td><input class="g-input g-compact" form="content-metrics-{{ $content->id }}" type="number" min="0" name="shares" value="{{ $content->shares }}" aria-label="اشتراک"></td>
            <td><input class="g-input g-compact" form="content-metrics-{{ $content->id }}" type="number" min="0" name="likes" value="{{ $content->likes ?? 0 }}" aria-label="پسند"></td>
            <td><input class="g-input g-compact" form="content-metrics-{{ $content->id }}" type="number" min="0" name="saves" value="{{ $content->saves ?? 0 }}" aria-label="ذخیره"></td>
            <td><select class="g-input" form="content-metrics-{{ $content->id }}" name="status"><option value="active" @selected($content->status === 'active')>فعال</option><option value="draft" @selected($content->status === 'draft')>پیش‌نویس</option><option value="archived" @selected($content->status === 'archived')>بایگانی</option></select></td>
            <td><button class="g-btn g-btn-soft" form="content-metrics-{{ $content->id }}" type="submit"><i class="fa-solid fa-check"></i> ذخیره</button></td>
          </tr>
        @endforeach
      </tbody></table></div>
    </section>
    @if(method_exists($contents, 'links'))<div class="g-pagination">{{ $contents->links() }}</div>@endif
  @endif
</div>
