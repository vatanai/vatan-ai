@php
  $actions = '<a class="g-btn" href="'.route('admin.growth.monitor').'"><i class="fa-solid fa-gauge-high"></i> پایش کامل</a>'
    .'<a class="g-btn g-btn-primary" href="#add-source"><i class="fa-solid fa-plus"></i> افزودن منبع</a>';
  $healthLabels = ['healthy' => 'سالم', 'warning' => 'نیازمند توجه', 'idle' => 'منتظر داده', 'error' => 'خطا', 'inactive' => 'غیرفعال'];
  $connectionLabels = ['connected' => 'متصل', 'active' => 'فعال', 'idle' => 'منتظر اتصال', 'error' => 'خطای اتصال', 'inactive' => 'غیرفعال'];
  $sourceIcons = [
    'internal' => 'fa-database', 'internal_tracking' => 'fa-satellite-dish', 'manual' => 'fa-keyboard',
    'api' => 'fa-plug', 'webhook' => 'fa-bolt', 'import' => 'fa-file-arrow-up',
  ];
@endphp
@include('admin.growth.partials.page-header', [
  'heading' => 'مرکز منابع داده',
  'subtitle' => 'تعریف، پایش و نگاشت همه ورودی‌های مرکز رشد؛ بدون مخلوط‌کردن منبع داده با کانال انتشار',
  'actions' => $actions,
])

<div class="g-stack">
  <section class="g-kpis g-kpis-4">
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">منابع فعال</span><span class="g-kpi-icon"><i class="fa-solid fa-layer-group"></i></span></div><div class="g-kpi-value">{{ number_format(max(0, $sources->count() - $health['inactive'])) }}</div><div class="g-kpi-foot">از {{ number_format($sources->count()) }} منبع تعریف‌شده</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">اتصال سالم</span><span class="g-kpi-icon success"><i class="fa-solid fa-circle-check"></i></span></div><div class="g-kpi-value">{{ number_format($health['healthy']) }}</div><div class="g-kpi-foot">دریافت یا اتصال بدون خطا</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">نیازمند توجه</span><span class="g-kpi-icon warning"><i class="fa-solid fa-triangle-exclamation"></i></span></div><div class="g-kpi-value">{{ number_format($health['warning']) }}</div><div class="g-kpi-foot">داده دیررس یا منتظر اولین دریافت</div></article>
    <article class="g-card g-kpi"><div class="g-kpi-top"><span class="g-kpi-label">خطای فعال</span><span class="g-kpi-icon danger"><i class="fa-solid fa-circle-xmark"></i></span></div><div class="g-kpi-value">{{ number_format($health['error']) }}</div><div class="g-kpi-foot">نیازمند بررسی اتصال یا داده خام</div></article>
  </section>

  <section class="g-card">
    <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-diagram-project"></i> مسیر استاندارد داده رشد</div><div class="g-section-sub">داده خام همیشه قابل ردیابی می‌ماند و داشبورد فقط از خروجی نرمال‌شده می‌خواند</div></div></div>
    <div class="g-pipeline" dir="rtl">
      <div class="g-pipeline-step"><i class="fa-solid fa-cloud-arrow-down"></i><strong>منبع</strong><span>داخلی، دستی یا بیرونی</span></div>
      <i class="fa-solid fa-chevron-left g-pipeline-arrow"></i>
      <div class="g-pipeline-step"><i class="fa-solid fa-box-archive"></i><strong>داده خام</strong><span>ثبت بدون حذف سابقه</span></div>
      <i class="fa-solid fa-chevron-left g-pipeline-arrow"></i>
      <div class="g-pipeline-step"><i class="fa-solid fa-arrows-rotate"></i><strong>نرمال‌سازی</strong><span>نگاشت فیلدها</span></div>
      <i class="fa-solid fa-chevron-left g-pipeline-arrow"></i>
      <div class="g-pipeline-step"><i class="fa-solid fa-calculator"></i><strong>شاخص‌ها</strong><span>اولویت و جایگزین</span></div>
      <i class="fa-solid fa-chevron-left g-pipeline-arrow"></i>
      <div class="g-pipeline-step"><i class="fa-solid fa-chart-line"></i><strong>داشبورد</strong><span>پایش و تحلیل</span></div>
    </div>
  </section>

  <section>
    <div class="g-section-head g-section-head-plain"><div><div class="g-section-title"><i class="fa-solid fa-server"></i> منابع تعریف‌شده</div><div class="g-section-sub">هر کانال می‌تواند چند منبع داشته باشد؛ منبع اصلی و جایگزین در نگاشت تعیین می‌شوند</div></div></div>
    @if($sources->isEmpty())
      <div class="g-card">@include('admin.growth.partials.empty', ['icon' => 'fa-database', 'emptyTitle' => 'هنوز منبع داده‌ای وجود ندارد', 'emptyText' => 'پس از اجرای به‌روزرسانی دیتابیس، منابع داخلی وطن به‌صورت خودکار ساخته می‌شوند.'])</div>
    @else
      <div class="g-source-grid">
        @foreach($sources as $item)
          @php($source = $item['source'])
          <article class="g-card g-source-card {{ $selectedSource?->id === $source->id ? 'selected' : '' }}">
            <div class="g-source-heading">
              <div class="g-source-identity">
                <span class="g-source-icon"><i class="fa-solid {{ $sourceIcons[$source->source_type] ?? 'fa-database' }}"></i></span>
                <div><div class="g-source-name">{{ $source->name }}</div><div class="g-source-type">{{ $sourceTypes[$source->source_type] ?? $source->source_type }} · {{ $ingestionMethods[$source->ingestion_method] ?? $source->ingestion_method }}</div></div>
              </div>
              <span class="g-health {{ $item['health'] }}">{{ $healthLabels[$item['health']] ?? $item['health'] }}</span>
            </div>
            <div class="g-source-message">{{ $item['message'] }}</div>
            <div class="g-source-tags">
              <span class="g-badge">{{ $channelLabels[$source->channel] ?? $source->channel }}</span>
              <span class="g-badge {{ in_array($source->connection_status, ['connected', 'active'], true) ? 'success' : ($source->connection_status === 'error' ? 'danger' : 'warning') }}">{{ $connectionLabels[$source->connection_status] ?? $source->connection_status }}</span>
              @if($source->is_system)<span class="g-badge">سیستمی</span>@endif
              @foreach(array_slice($source->data_types ?? [], 0, 4) as $dataType)<span class="g-badge info">{{ $metricLabels[$dataType] ?? $dataType }}</span>@endforeach
              @if(count($source->data_types ?? []) > 4)<span class="g-badge">+{{ count($source->data_types) - 4 }}</span>@endif
            </div>
            <div class="g-source-footer">
              <span><i class="fa-regular fa-clock"></i> {{ $item['last_activity_human'] }}</span>
              <div class="g-actions">
                <a class="g-btn g-btn-soft" href="{{ route('admin.growth.data-sources.index', ['source' => $source->slug]) }}#manage-source">مدیریت</a>
                @if($source->is_system)
                  <span class="g-btn g-icon-btn g-btn-disabled" title="منبع سیستمی همیشه فعال است"><i class="fa-solid fa-lock"></i></span>
                @else
                  <form method="POST" action="{{ route('admin.growth.data-sources.toggle', $source) }}">@csrf @method('PATCH')<button class="g-btn g-icon-btn" type="submit" title="{{ $source->is_active ? 'غیرفعال‌کردن' : 'فعال‌کردن' }}"><i class="fa-solid {{ $source->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i></button></form>
                @endif
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </section>

  @if($selectedSource)
    @php($sourceConfig = $selectedSource->config ?? [])
    <section class="g-grid-main" id="manage-source">
      <article class="g-card">
        <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-sliders"></i> مدیریت {{ $selectedSource->name }}</div><div class="g-section-sub">تنظیمات عمومی و وضعیت اتصال این منبع</div></div><span class="g-health {{ $selectedHealth['health'] }}">{{ $healthLabels[$selectedHealth['health']] ?? $selectedHealth['health'] }}</span></div>
        <form class="g-form-grid g-card-pad" method="POST" action="{{ route('admin.growth.data-sources.update', $selectedSource) }}">
          @csrf @method('PUT')
          <div class="g-field"><label for="source-edit-name">نام نمایشی</label><input class="g-input" id="source-edit-name" name="name" value="{{ old('name', $selectedSource->name) }}" required></div>
          <div class="g-field"><label for="source-edit-priority">اولویت منبع</label><input class="g-input" id="source-edit-priority" type="number" min="1" max="999" name="priority" value="{{ old('priority', $selectedSource->priority) }}" required></div>
          <div class="g-field"><label>نوع و روش</label><div class="g-static-field">{{ $sourceTypes[$selectedSource->source_type] ?? $selectedSource->source_type }} · {{ $ingestionMethods[$selectedSource->ingestion_method] ?? $selectedSource->ingestion_method }}</div></div>
          <div class="g-field"><label>کانال مرتبط</label><div class="g-static-field">{{ $channelLabels[$selectedSource->channel] ?? $selectedSource->channel }}</div></div>
          @if(in_array($selectedSource->ingestion_method, ['api', 'webhook'], true))
            <div class="g-field g-form-full"><label for="source-edit-endpoint">نشانی اتصال</label><input class="g-input" id="source-edit-endpoint" dir="ltr" name="endpoint_url" value="{{ old('endpoint_url', $sourceConfig['endpoint_url'] ?? '') }}" placeholder="https://..."></div>
            <div class="g-field"><label for="source-edit-api-key">کلید دسترسی جدید</label><input class="g-input" id="source-edit-api-key" dir="ltr" type="password" name="api_key" autocomplete="new-password" placeholder="برای حفظ مقدار فعلی خالی بگذارید"></div>
            <div class="g-field"><label for="source-edit-event">نام رویداد وب‌هوک</label><input class="g-input" id="source-edit-event" dir="ltr" name="webhook_event" value="{{ old('webhook_event', $sourceConfig['webhook_event'] ?? '') }}"></div>
          @endif
          @if($selectedSource->ingestion_method === 'webhook')
            <div class="g-note g-form-full"><strong>نشانی دریافت وب‌هوک</strong><code class="g-code-line" dir="ltr">{{ route('webhooks.growth.receive', $selectedSource) }}</code><span>کلید ذخیره‌شدهٔ منبع را در هدر <span dir="ltr">X-Webhook-Secret</span> یا به‌صورت امضای <span dir="ltr">X-Signature-256</span> بفرستید.</span></div>
          @endif
          <div class="g-note g-form-full">شناسه داخلی: <span dir="ltr">{{ $selectedSource->slug }}</span> · تعداد نگاشت: {{ number_format($selectedSource->mappings_count) }} · داده خام واردشده: {{ number_format($selectedSource->raw_records_count) }}</div>
          <div class="g-form-actions">
            @if($selectedSource->last_error)<button class="g-btn" form="clear-source-error" type="submit"><i class="fa-solid fa-rotate"></i> پاک‌کردن خطا</button>@endif
            <button class="g-btn g-btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره تنظیمات</button>
          </div>
        </form>
        @if($selectedSource->last_error)<form id="clear-source-error" method="POST" action="{{ route('admin.growth.data-sources.clear-error', $selectedSource) }}">@csrf @method('PATCH')</form>@endif
      </article>

      <aside class="g-card">
        <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-right-left"></i> نگاشت فیلدها</div><div class="g-section-sub">فیلد ورودی را به شاخص استاندارد رشد وصل کنید</div></div></div>
        <div class="g-card-pad">
          @if($mappings->isEmpty())
            <div class="g-note">این منبع هنوز نگاشت اختصاصی ندارد؛ نام‌های استاندارد آمار محتوا مستقیماً پذیرفته می‌شوند.</div>
          @else
            <div class="g-mapping-list">
              @foreach($mappings as $mapping)
                <div class="g-mapping-row">
                  <code dir="ltr">{{ $mapping->source_field }}</code><i class="fa-solid fa-arrow-left"></i><strong>{{ $metricLabels[$mapping->growth_metric] ?? $mapping->growth_metric }}</strong>
                  <span class="g-badge {{ $mapping->is_primary ? 'success' : 'warning' }}">{{ $mapping->is_primary ? 'اصلی' : 'جایگزین '.$mapping->fallback_order }}</span>
                  <form method="POST" action="{{ route('admin.growth.data-sources.mappings.destroy', [$selectedSource, $mapping]) }}">@csrf @method('DELETE')<button class="g-btn g-icon-btn g-btn-danger" type="submit" title="حذف نگاشت"><i class="fa-regular fa-trash-can"></i></button></form>
                </div>
              @endforeach
            </div>
          @endif
          <form class="g-form-grid g-mapping-form" method="POST" action="{{ route('admin.growth.data-sources.mappings.store', $selectedSource) }}">
            @csrf
            <div class="g-field"><label for="mapping-source-field">نام فیلد ورودی</label><input class="g-input" id="mapping-source-field" dir="ltr" name="source_field" required placeholder="views_count"></div>
            <div class="g-field"><label for="mapping-metric">شاخص رشد</label><select class="g-input" id="mapping-metric" name="growth_metric" required>@foreach($metricLabels as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
            <div class="g-field"><label for="mapping-transform">تبدیل مقدار</label><select class="g-input" id="mapping-transform" name="transform"><option value="integer">عدد صحیح</option><option value="text">متن</option><option value="boolean">بله یا خیر</option><option value="lowercase">متن یکدست</option></select></div>
            <div class="g-field"><label for="mapping-order">ترتیب جایگزین</label><input class="g-input" id="mapping-order" type="number" min="1" max="99" name="fallback_order" value="1" required></div>
            <label class="g-check g-form-full"><input type="checkbox" name="is_primary" value="1"> این منبع برای شاخص انتخاب‌شده، منبع اصلی باشد</label>
            <div class="g-form-actions"><button class="g-btn g-btn-soft" type="submit"><i class="fa-solid fa-plus"></i> افزودن نگاشت</button></div>
          </form>
        </div>
      </aside>
    </section>

    <section class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-box-archive"></i> آخرین داده‌های خام</div><div class="g-section-sub">برای بررسی خطا و ردیابی تبدیل داده؛ سابقه حذف یا بازنشانی نمی‌شود</div></div></div>
      @if($rawRecords->isEmpty())
        <div class="g-empty g-empty-compact"><div class="g-empty-icon"><i class="fa-solid fa-inbox"></i></div><h3>داده خامی در این لایه ثبت نشده است</h3><p>@if($selectedSource->is_system)رویدادهای این منبع در جداول عملیاتی فعلی وطن نگهداری می‌شوند و برای جلوگیری از تکثیر داده دوباره کپی نشده‌اند.@else پس از اولین ورود دستی، فایل یا اتصال بیرونی، رکورد خام و نتیجه نرمال‌سازی اینجا دیده می‌شود.@endif</p></div>
      @else
        <div class="g-table-wrap"><table class="g-table"><thead><tr><th>زمان دریافت</th><th>نوع</th><th>شناسه بیرونی</th><th>وضعیت</th><th>خلاصه داده</th></tr></thead><tbody>
          @foreach($rawRecords as $record)
            <tr>
              <td>{{ $record->received_at?->format('Y/m/d H:i:s') }}</td><td>{{ $record->record_type === 'content_metrics' ? 'آمار محتوا' : $record->record_type }}</td><td dir="ltr">{{ $record->external_id ?: '—' }}</td>
              <td><span class="g-badge {{ $record->normalization_status === 'normalized' ? 'success' : ($record->normalization_status === 'failed' ? 'danger' : 'warning') }}">{{ $record->normalization_status === 'normalized' ? 'نرمال‌شده' : ($record->normalization_status === 'failed' ? 'خطا' : 'در انتظار') }}</span></td>
              <td><code class="g-raw-preview" dir="ltr">{{ \Illuminate\Support\Str::limit(json_encode($record->payload, JSON_UNESCAPED_UNICODE), 120) }}</code>@if($record->error_message)<div class="g-error-text">{{ $record->error_message }}</div>@endif</td>
            </tr>
          @endforeach
        </tbody></table></div>
      @endif
    </section>
  @endif

  <section class="g-grid-main">
    <article class="g-card" id="add-source">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-circle-plus"></i> افزودن منبع داده</div><div class="g-section-sub">برای هر اتصال یا روش ورود، یک منبع مستقل و قابل پایش بسازید</div></div></div>
      <form class="g-form-grid g-card-pad" method="POST" action="{{ route('admin.growth.data-sources.store') }}">
        @csrf
        <div class="g-field"><label for="new-source-name">نام منبع</label><input class="g-input" id="new-source-name" name="name" value="{{ old('name') }}" required placeholder="مثلاً گزارش هفتگی اینستاگرام"></div>
        <div class="g-field"><label for="new-source-type">نوع منبع</label><select class="g-input" id="new-source-type" name="source_type" required>@foreach($sourceTypes as $key => $label)<option value="{{ $key }}" @selected(old('source_type', 'manual') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="g-field"><label for="new-source-channel">کانال مرتبط</label><select class="g-input" id="new-source-channel" name="channel">@foreach($channelLabels as $key => $label)<option value="{{ $key }}" @selected(old('channel', 'instagram') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="g-field"><label for="new-source-method">روش دریافت</label><select class="g-input" id="new-source-method" name="ingestion_method" required>@foreach($ingestionMethods as $key => $label)<option value="{{ $key }}" @selected(old('ingestion_method', 'manual') === $key)>{{ $label }}@if($key === 'excel') — بزودی@endif</option>@endforeach</select></div>
        <div class="g-field"><label for="new-source-priority">اولویت</label><input class="g-input" id="new-source-priority" type="number" min="1" max="999" name="priority" value="{{ old('priority', 100) }}"></div>
        <label class="g-check g-field-end"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> منبع از همین حالا فعال باشد</label>
        <div class="g-field g-form-full"><label>داده‌های دریافتی</label><div class="g-checkbox-grid">@foreach($metricLabels as $key => $label)<label class="g-check-tile"><input type="checkbox" name="data_types[]" value="{{ $key }}" @checked(in_array($key, old('data_types', ['views', 'comments', 'shares']), true))>{{ $label }}</label>@endforeach</div></div>
        <details class="g-advanced g-form-full"><summary>تنظیمات اتصال بیرونی</summary><div class="g-form-grid"><div class="g-field g-form-full"><label for="new-source-endpoint">نشانی اتصال</label><input class="g-input" id="new-source-endpoint" dir="ltr" name="endpoint_url" value="{{ old('endpoint_url') }}" placeholder="https://..."></div><div class="g-field"><label for="new-source-key">کلید دسترسی</label><input class="g-input" id="new-source-key" dir="ltr" type="password" name="api_key" autocomplete="new-password"></div><div class="g-field"><label for="new-source-event">نام رویداد وب‌هوک</label><input class="g-input" id="new-source-event" dir="ltr" name="webhook_event" value="{{ old('webhook_event') }}"></div></div></details>
        <div class="g-form-actions"><button class="g-btn g-btn-primary" type="submit"><i class="fa-solid fa-plus"></i> ساخت منبع</button></div>
      </form>
    </article>

    <aside class="g-card">
      <div class="g-section-head"><div><div class="g-section-title"><i class="fa-solid fa-file-csv"></i> ورود گروهی آمار محتوا</div><div class="g-section-sub">ورود نیمه‌خودکار با حفظ فایل خام و گزارش خطا</div></div></div>
      <div class="g-card-pad">
        @if($importSources->isEmpty())
          <div class="g-note">ابتدا یک منبع با روش «ورود فایل سی‌اس‌وی» بسازید. پشتیبانی فایل اکسل <span class="g-badge warning">بزودی</span></div>
        @else
          <form class="g-form-grid" method="POST" enctype="multipart/form-data" action="{{ route('admin.growth.data-sources.import.csv') }}">
            @csrf
            <div class="g-field g-form-full"><label for="import-source">منبع فایل</label><select class="g-input" id="import-source" name="growth_data_source_id" required>@foreach($importSources as $source)<option value="{{ $source->id }}">{{ $source->name }} · {{ $channelLabels[$source->channel] ?? $source->channel }}</option>@endforeach</select></div>
            <div class="g-field"><label for="import-date">تاریخ پیش‌فرض آمار</label><input class="g-input" id="import-date" type="date" name="metric_date" value="{{ today()->toDateString() }}"></div>
            <div class="g-field"><label for="import-file">فایل سی‌اس‌وی</label><input class="g-input" id="import-file" type="file" name="file" accept=".csv,text/csv" required></div>
            <div class="g-form-actions"><button class="g-btn g-btn-soft" type="submit"><i class="fa-solid fa-file-arrow-up"></i> دریافت و پردازش</button></div>
          </form>
        @endif
        <div class="g-import-guide"><strong>ستون‌های قابل استفاده</strong><code dir="ltr">content_id, external_id, metric_date, views, engagements, comments, shares, likes, saves</code><p>وجود یکی از ستون‌های <span dir="ltr">content_id</span> یا <span dir="ltr">external_id</span> برای شناسایی محتوا الزامی است.</p></div>
      </div>
    </aside>
  </section>
</div>
