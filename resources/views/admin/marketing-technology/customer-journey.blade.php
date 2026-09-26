@extends('layouts.admin')

@section('title', 'مسیر کاربران — تکنولوژی مارکتینگ')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content cj-page flex-1 overflow-y-auto" id="content">
    @if(session('success'))
      <div class="mt-alert mt-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
      <div class="mt-alert mt-alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>@foreach($errors->all() as $error){{ $error }}@if(!$loop->last)، @endif @endforeach</span></div>
    @endif

    <div class="mt-page-head sp-page-head">
      <div>
        <div class="mt-eyebrow">موتور ساده و قابل کنترل سفر مشتری</div>
        <h1>مسیر کاربران</h1>
        <p>هر کاربر فقط یک گام فعال دارد؛ رفتار واقعی او گام و تسک بعدی را به‌روزرسانی می‌کند. ارسال بیرونی فعلاً دستی است.</p>
      </div>
      <div class="mt-head-actions">
        <span class="sp-manual-badge"><i class="fa-solid fa-hand"></i> اجرا و پیگیری دستی</span>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.partners') }}"><i class="fa-solid fa-route"></i> مسیر همکاران</a>
        <a class="mt-btn mt-btn-primary" href="{{ route('admin.marketing-technology.customer-journey', ['sync' => 1]) }}"><i class="fa-solid fa-rotate"></i> همگام‌سازی رفتار</a>
      </div>
    </div>

    @if(!$ready)
      <section class="mt-card mt-empty-section sp-empty"><span class="mt-empty-icon"><i class="fa-solid fa-route"></i></span><h2>موتور مسیر کاربران آماده اجرا نشده است</h2><p>ابتدا migrationهای مسیر کاربران روی این محیط اجرا شود.</p><span class="mt-badge mt-badge-warn">بزودی</span></section>
    @else
      <section class="sp-stat-grid cj-stat-grid" aria-label="خلاصه مسیر کاربران">
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-primary"><i class="fa-solid fa-users"></i></span><div><small>کاربران در مسیر</small><strong>{{ number_format($metrics['total']) }}</strong></div></article>
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-warning"><i class="fa-solid fa-list-check"></i></span><div><small>تسک‌های امروز</small><strong>{{ number_format($metrics['today_tasks']) }}</strong></div></article>
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-danger"><i class="fa-solid fa-user-clock"></i></span><div><small>نیازمند انسان</small><strong>{{ number_format($metrics['human_review']) }}</strong></div></article>
        <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-info"><i class="fa-solid fa-bolt"></i></span><div><small>میانگین آمادگی</small><strong>{{ number_format($metrics['average_score']) }}٪</strong></div></article>
      </section>

      @php($journeyChannelLabels = ['none' => 'بدون اقدام بیرونی', 'sms' => 'پیامک', 'call' => 'تماس تلفنی', 'direct' => 'دایرکت / پیام دستی', 'email' => 'ایمیل', 'in_app' => 'پیام داخل سایت', 'multi' => 'چندکاناله'])
      <section class="cj-stage-flow-panel mt-card mt-card-pad" aria-label="تنظیمات درختی مسیر کاربران">
        <div class="mt-section-head"><div><div class="mt-eyebrow">سناریوی هشت‌گامی قابل تنظیم</div><h2>درخت اجرای مسیر کاربران</h2><p>خط وسط، ترتیب واقعی سفر را نشان می‌دهد؛ سمت راست تعریف رفتار کاربر و سمت چپ اقدام، پیام و تسک قابل ویرایش هر گام است.</p></div><span class="sp-filter-result"><strong>۸</strong> گام · رفتارمحور</span></div>
        <div class="cj-stage-flow">
          @foreach($pointDefinitions as $point => $definition)
            @php($pointStat = $pointStats[$point] ?? ['total' => 0, 'active' => 0, 'human_review' => 0, 'paused' => 0, 'closed' => 0, 'samples' => []])
            <article class="cj-stage-row">
              <div class="cj-stage-explanation">
                <div class="cj-stage-explanation-head"><span class="mt-eyebrow">گام {{ $point }} · {{ $definition['short'] }}</span><strong>{{ number_format($pointStat['total']) }} کاربر</strong></div>
                <h3>{{ $definition['title'] }}</h3>
                <p>{{ $definition['description'] }}</p>
                <div class="cj-tree-statuses"><span>فعال {{ number_format($pointStat['active']) }}</span><span class="is-human">انسان {{ number_format($pointStat['human_review']) }}</span><span class="is-paused">متوقف {{ number_format($pointStat['paused']) }}</span></div>
                <a class="cj-stage-filter-link" href="{{ route('admin.marketing-technology.customer-journey', ['point' => $point, 'sync' => 0]) }}">مشاهده کارت‌های این گام <i class="fa-solid fa-arrow-left"></i></a>
              </div>
              <div class="cj-stage-center"><span class="cj-stage-node">{{ $point }}</span></div>
              <div class="cj-stage-settings">
                <div class="cj-stage-settings-head"><div><span class="mt-eyebrow">اقدامات و تسک‌های گام {{ $point }}</span><h3>{{ $definition['task_title'] }}</h3></div><span class="cj-stage-channel">{{ $journeyChannelLabels[$definition['channel']] ?? $definition['channel'] }}</span></div>
                <form method="POST" action="{{ route('admin.marketing-technology.customer-journey.stages.update', $point) }}" class="cj-stage-form" data-ajax-form>
                  @csrf @method('PUT')
                  <div class="cj-stage-form-grid">
                    <div class="mt-field"><label for="cj-stage-{{ $point }}-short">عنوان کوتاه</label><input class="mt-input" id="cj-stage-{{ $point }}-short" name="short" value="{{ $definition['short'] }}" required></div>
                    <div class="mt-field"><label for="cj-stage-{{ $point }}-title">عنوان گام</label><input class="mt-input" id="cj-stage-{{ $point }}-title" name="title" value="{{ $definition['title'] }}" required></div>
                    <div class="mt-field cj-stage-field-wide"><label for="cj-stage-{{ $point }}-description">توضیح رفتار این گام</label><textarea class="mt-input mt-textarea" id="cj-stage-{{ $point }}-description" name="description" rows="2">{{ $definition['description'] }}</textarea></div>
                    <div class="mt-field"><label for="cj-stage-{{ $point }}-task-title">عنوان تسک</label><input class="mt-input" id="cj-stage-{{ $point }}-task-title" name="task_title" value="{{ $definition['task_title'] }}" required></div>
                    <div class="mt-field"><label for="cj-stage-{{ $point }}-channel">کانال اقدام</label><select class="mt-input" id="cj-stage-{{ $point }}-channel" name="channel">@foreach($journeyChannelLabels as $channel => $channelLabel)<option value="{{ $channel }}" @selected($definition['channel'] === $channel)>{{ $channelLabel }}</option>@endforeach</select></div>
                    <div class="mt-field cj-stage-field-wide"><label for="cj-stage-{{ $point }}-task-body">جزئیات تسک برای تیم</label><textarea class="mt-input mt-textarea" id="cj-stage-{{ $point }}-task-body" name="task_body" rows="2">{{ $definition['task_body'] }}</textarea></div>
                    <div class="mt-field cj-stage-field-wide"><label for="cj-stage-{{ $point }}-message">متن پیام یا سناریوی تماس</label><textarea class="mt-input mt-textarea" id="cj-stage-{{ $point }}-message" name="message_template" rows="2">{{ $definition['message_template'] }}</textarea></div>
                    <div class="mt-field"><label for="cj-stage-{{ $point }}-delay">زمان اجرا پس از فعالیت <small>(دقیقه)</small></label><input class="mt-input" id="cj-stage-{{ $point }}-delay" name="delay_minutes" type="number" min="0" value="{{ $definition['delay_minutes'] }}" required></div>
                    <div class="mt-field cj-stage-toggle-field"><label>ورود انسان</label><label class="cj-switch"><input type="hidden" name="human_required" value="0"><input type="checkbox" name="human_required" value="1" @checked($definition['human_required'])><span>نیازمند بررسی انسانی</span></label></div>
                    <div class="mt-field cj-stage-field-wide"><label for="cj-stage-{{ $point }}-advance">شرط عبور خودکار</label><textarea class="mt-input mt-textarea" id="cj-stage-{{ $point }}-advance" name="advance_rule" rows="2">{{ $definition['advance_rule'] }}</textarea></div>
                    <div class="mt-field cj-stage-field-wide"><label for="cj-stage-{{ $point }}-stop">شرط توقف یا هشدار</label><textarea class="mt-input mt-textarea" id="cj-stage-{{ $point }}-stop" name="stop_rule" rows="2">{{ $definition['stop_rule'] }}</textarea></div>
                  </div>
                  <div class="cj-stage-form-actions"><label class="cj-switch"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked($definition['enabled'] ?? true)><span>فعال در موتور مسیر</span></label><button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره تنظیمات گام {{ $point }}</button></div>
                </form>
              </div>
            </article>
          @endforeach
        </div>
      </section>

      <section class="mt-card mt-card-pad cj-filter-card">
        <div class="mt-section-head"><div><div class="mt-eyebrow">صف اقدام</div><h2>کارت‌های کاربران و تسک بعدی</h2><p>هر کاربر یک کارت کوچک دارد؛ در هر صفحه ۲۰ کارت در ۵ ستون و ۴ ردیف نمایش داده می‌شود.</p></div><span class="sp-filter-result"><strong>{{ number_format($journeys?->total() ?? 0) }}</strong> مورد</span></div>
        <form method="GET" class="sp-filter-form">
          <input type="hidden" name="sync" value="0">
          <div class="mt-field"><label for="cj-search">جستجوی کاربر</label><input class="mt-input" id="cj-search" name="q" value="{{ $filters['q'] }}" placeholder="نام، ایمیل یا موبایل"></div>
          <div class="mt-field"><label for="cj-point">گام</label><select class="mt-input" id="cj-point" name="point"><option value="">همه گام‌ها</option>@foreach($pointDefinitions as $point => $definition)<option value="{{ $point }}" @selected((string) $filters['point'] === (string) $point)>گام {{ $point }} · {{ $definition['short'] }}</option>@endforeach</select></div>
          <div class="mt-field"><label for="cj-status">وضعیت</label><select class="mt-input" id="cj-status" name="status"><option value="">همه وضعیت‌ها</option><option value="active" @selected($filters['status'] === 'active')>فعال</option><option value="human_review" @selected($filters['status'] === 'human_review')>بررسی انسانی</option><option value="paused" @selected($filters['status'] === 'paused')>متوقف</option><option value="closed" @selected($filters['status'] === 'closed')>بسته</option></select></div>
          <div class="sp-filter-actions"><button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-filter"></i> نمایش</button><a class="mt-btn" href="{{ route('admin.marketing-technology.customer-journey') }}">پاک‌کردن</a></div>
        </form>
      </section>

      <section class="mt-card mt-card-pad cj-users-panel">
        <div class="mt-section-head cj-users-panel-head"><div><div class="mt-eyebrow">نمایش صفحه‌ای</div><h2>کارت‌های کاربران</h2><p>هر صفحه ۲۰ کاربر را در چیدمان ۵ ستون و ۴ ردیف نمایش می‌دهد.</p></div><span class="sp-filter-result"><strong>{{ number_format($journeys?->total() ?? 0) }}</strong> کاربر</span></div>
        <section class="cj-list">
          @forelse($journeys as $journey)
          @php($definition = $pointDefinitions[$journey->point] ?? $pointDefinitions[1])
          @php($pendingTask = $journey->tasks->first())
          <article class="mt-card mt-card-pad cj-user-card {{ $journey->status === 'human_review' ? 'is-human-review' : '' }}" data-journey-id="{{ $journey->id }}">
            <div class="cj-user-head">
              <div class="cj-user-identity"><div><strong>{{ trim(($journey->user?->name ?: '').' '.($journey->user?->last_name ?: '')) ?: 'کاربر بدون نام' }}</strong><small>{{ $journey->user?->email ?: ($journey->user?->phone ?: 'اطلاعات تماس ثبت نشده') }}</small></div></div>
              <div class="cj-user-badges"><span class="cj-point-badge">گام {{ $journey->point }} · {{ $definition['short'] }}</span><span class="cj-status-badge cj-status-{{ $journey->status }}">{{ ['active' => 'فعال', 'human_review' => 'بررسی انسانی', 'paused' => 'متوقف', 'closed' => 'بسته'][$journey->status] ?? $journey->status }}</span></div>
            </div>
            <div class="cj-user-metrics"><div><small>آمادگی خرید</small><strong>{{ number_format($journey->readiness_score) }}٪</strong></div><div><small>اعتبار باقی‌مانده</small><strong>{{ number_format((int) ($journey->user?->tokens ?? 0)) }}</strong></div><div><small>آخرین فعالیت</small><strong>{{ $journey->last_activity_at?->format('Y/m/d H:i') ?: 'ثبت نشده' }}</strong></div><div><small>قدم بعدی</small><strong>{{ $journey->next_action_at?->format('Y/m/d H:i') ?: 'بدون زمان' }}</strong></div></div>
            <div class="cj-user-task"><i class="fa-solid {{ $pendingTask ? 'fa-list-check' : 'fa-circle-check' }}"></i><span>{{ $pendingTask?->title ?: 'تسک باز در این لحظه ندارد' }}</span>@if($pendingTask)<small>{{ $pendingTask->body }}</small>@endif</div>
            <div class="cj-user-actions">
              <form method="POST" action="{{ route('admin.marketing-technology.customer-journey.point', $journey) }}" class="cj-move-form" data-ajax-form>@csrf @method('PATCH')<select class="mt-input" name="point" aria-label="انتقال گام کاربر">@foreach($pointDefinitions as $point => $pointDefinition)<option value="{{ $point }}" @selected($journey->point === $point)>گام {{ $point }} · {{ $pointDefinition['short'] }}</option>@endforeach</select><input class="mt-input" name="reason" value="بررسی دستی تیم فروش" aria-label="دلیل انتقال"><button class="mt-btn mt-btn-primary" type="submit">ثبت انتقال</button></form>
              <form method="POST" action="{{ route('admin.marketing-technology.customer-journey.review', $journey) }}" class="cj-inline-form" data-ajax-form>@csrf<input type="hidden" name="reason" value="توقف چرخه و نیاز به تصمیم انسانی"><button class="mt-btn" type="submit"><i class="fa-solid fa-user-clock"></i> ارسال به انسان</button></form>
              @if($pendingTask)<form method="POST" action="{{ route('admin.marketing-technology.customer-journey.tasks.complete', $pendingTask) }}" class="cj-inline-form" data-ajax-form>@csrf<button class="mt-btn" type="submit"><i class="fa-solid fa-check"></i> انجام شد</button></form>@endif
            </div>
          </article>
          @empty
            <div class="mt-card mt-card-pad sp-empty-inline"><i class="fa-solid fa-users-slash"></i><span>هنوز کاربری در موتور مسیر ثبت نشده است؛ دکمه‌ی همگام‌سازی رفتار را بزن.</span></div>
          @endforelse
        </section>
        @if($journeys && $journeys->hasPages())<div class="mt-pagination">{{ $journeys->onEachSide(1)->links() }}</div>@endif
      </section>

      <section class="cj-bottom-grid">
        <article class="mt-card mt-card-pad cj-settings-card">
          <div class="mt-section-head"><div><div class="mt-eyebrow">قابل تنظیم، بدون تغییر کد</div><h2>قواعد اعتبار و توقف</h2><p>این اعداد فعلاً برای موتور تصمیم‌گیری و صف انسانی ذخیره می‌شوند؛ شارژ خودکار تا تأیید مالی فعال نیست.</p></div></div>
          <form method="POST" action="{{ route('admin.marketing-technology.customer-journey.settings.update') }}" class="cj-settings-form" data-ajax-form>@csrf @method('PUT')
            @foreach($settings as $key => $setting)<div class="mt-field"><label for="cj-setting-{{ $key }}">{{ $setting['label'] }}</label><input class="mt-input" id="cj-setting-{{ $key }}" type="number" name="{{ $key }}" value="{{ $setting['value'] }}" required></div>@endforeach
            <button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> ذخیره قواعد</button>
          </form>
        </article>
        <article class="mt-card mt-card-pad cj-principles-card"><div class="mt-section-head"><div><div class="mt-eyebrow">قانون اجرای فعلی</div><h2>پیگیری امن و انسانی</h2></div></div><ul><li>هر کاربر فقط یک گام فعال دارد.</li><li>هر انتقال با دلیل و تاریخ در تاریخچه ثبت می‌شود.</li><li>هر گام فقط یک تسک باز دارد تا مزاحمت و تکرار ایجاد نشود.</li><li>کاربران متوقف‌شده به بررسی انسانی می‌روند، نه پیام‌های بی‌پایان.</li><li>ارسال بیرونی و اتصال کانال‌ها در این فاز فعال نیست.</li></ul></article>
      </section>
    @endif
  </div>
</main>
@endsection

@section('scripts')
<script>
(function () {
  function notice(message, error) {
    const page = document.querySelector('.cj-page');
    if (!page) return;
    const item = document.createElement('div');
    item.className = 'mt-alert ' + (error ? 'mt-alert-danger' : 'mt-alert-success');
    item.innerHTML = '<i class="fa-solid ' + (error ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span></span>';
    item.querySelector('span').textContent = message || (error ? 'ذخیره‌سازی انجام نشد.' : 'ذخیره شد.');
    page.prepend(item);
    window.setTimeout(function () { item.remove(); }, 3500);
  }
  document.querySelectorAll('[data-ajax-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      if (button) button.disabled = true;
      fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) })
        .then(function (response) { return response.json().then(function (payload) { if (!response.ok) throw new Error(payload.message || 'ذخیره‌سازی انجام نشد.'); return payload; }); })
        .then(function (payload) {
          notice(payload.message);
          const card = payload.journey_id ? document.querySelector('[data-journey-id="' + payload.journey_id + '"]') : null;
          if (card && payload.point) {
            const badge = card.querySelector('.cj-point-badge');
            if (badge) badge.textContent = 'گام ' + payload.point + ' · ' + (payload.point_label || '');
          }
          if (card && payload.status === 'human_review') {
            card.classList.add('is-human-review');
            const status = card.querySelector('.cj-status-badge');
            if (status) { status.className = 'cj-status-badge cj-status-human_review'; status.textContent = 'بررسی انسانی'; }
          }
          if (card && payload.task_id) {
            const completedForm = form;
            completedForm.remove();
            const taskTitle = card.querySelector('.cj-user-task span');
            if (taskTitle) taskTitle.textContent = 'تسک باز در این لحظه ندارد';
          }
          form.classList.add('is-saved'); window.setTimeout(function () { form.classList.remove('is-saved'); }, 900);
        })
        .catch(function (error) { notice(error.message, true); })
        .finally(function () { if (button) button.disabled = false; });
    });
  });
})();
</script>
@endsection
