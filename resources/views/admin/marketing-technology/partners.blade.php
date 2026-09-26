@extends('layouts.admin')

@section('title', 'مسیر همکاران فروش — تکنولوژی مارکتینگ')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content sp-page flex-1 overflow-y-auto" id="content">
    @if(session('success'))
      <div class="mt-alert mt-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
      <div class="mt-alert mt-alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
      <div class="mt-alert mt-alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>@foreach($errors->all() as $error){{ $error }}@if(!$loop->last)، @endif @endforeach</span></div>
    @endif

    <div class="mt-page-head sp-page-head">
      <div>
        <div class="mt-eyebrow">مرکز رشد و ارتباط وطن</div>
        <h1>مسیر همکاران فروش</h1>
        <p>یک مسیر ساده و کارت‌محور برای ثبت سرنخ، پیگیری روزانه و رساندن هر همکار از تماس اول تا همکاری فعال.</p>
      </div>
      <div class="mt-head-actions">
        <span class="sp-manual-badge"><i class="fa-solid fa-hand"></i> اجرای دستی · آماده توسعه اتصال</span>
        <a class="mt-btn mt-btn-primary" href="{{ route('admin.marketing-technology.partners.queue') }}"><i class="fa-solid fa-list-check"></i> صف عملیاتی امروز</a>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.partners.settings') }}"><i class="fa-solid fa-sliders"></i> تنظیمات مراحل</a>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.partners.team') }}"><i class="fa-solid fa-chart-simple"></i> عملکرد تیم</a>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.reports') }}"><i class="fa-solid fa-chart-line"></i> گزارش و تحلیل</a>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.integrations') }}"><i class="fa-solid fa-plug"></i> اتصال‌های آینده</a>
        <a class="mt-btn mt-btn-primary" href="#sp-add-lead"><i class="fa-solid fa-plus"></i> افزودن سرنخ</a>
      </div>
    </div>

    <section class="sp-stat-grid" aria-label="خلاصه مسیر همکاران فروش">
      <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-primary"><i class="fa-solid fa-users-viewfinder"></i></span><div><small>کل سرنخ‌های فعال</small><strong>{{ number_format($metrics['total']) }}</strong></div></article>
      <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-warning"><i class="fa-solid fa-calendar-check"></i></span><div><small>تسک‌های امروز</small><strong>{{ number_format($metrics['due']) }}</strong></div></article>
      <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-info"><i class="fa-solid fa-message"></i></span><div><small>پاسخ مثبت ثبت‌شده</small><strong>{{ number_format($metrics['positive']) }}</strong></div></article>
      <article class="sp-stat-card"><span class="sp-stat-icon sp-stat-icon-success"><i class="fa-solid fa-flag-checkered"></i></span><div><small>همکاران فعال</small><strong>{{ number_format($metrics['active']) }}</strong></div></article>
    </section>

    @if($ready)
      @php($activeQuery = request()->query())
      @php($acquisitionSourceLabels = ['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'google' => 'گوگل', 'website' => 'سایت وطن', 'referral' => 'معرفی و رفرال', 'manual' => 'پیدا شده دستی', 'other' => 'سایر'])
      <section class="mt-card mt-card-pad sp-filter-card" aria-label="فیلتر صف پیگیری">
        <div class="sp-filter-head"><div><div class="mt-eyebrow">کنترل روزانه تیم</div><h2>صف پیگیری همکاران</h2><p>اول صف کار را مشخص کن، بعد فقط همان کارت‌ها را بررسی و بین اعضای تیم تقسیم کن.</p></div><span class="sp-filter-result"><strong>{{ number_format($leads->count()) }}</strong> کارت در این نما</span></div>
        <div class="sp-filter-tabs" role="tablist" aria-label="نمایش صف">
          @foreach(['all' => 'همه', 'today' => 'امروز و عقب‌افتاده', 'overdue' => 'فقط عقب‌افتاده', 'no_follow_up' => 'بدون پیگیری'] as $key => $label)
            @php($tabQuery = array_merge($activeQuery, ['view' => $key]))
            <a class="sp-filter-tab {{ $filters['view'] === $key ? 'is-active' : '' }}" href="{{ route('admin.marketing-technology.partners', $tabQuery) }}">{{ $label }}@if($key === 'overdue' && $metrics['overdue'] > 0)<span>{{ number_format($metrics['overdue']) }}</span>@endif</a>
          @endforeach
        </div>
        <form method="GET" class="sp-filter-form">
          <input type="hidden" name="view" value="{{ $filters['view'] }}">
          <div class="mt-field"><label for="sp-filter-search">جستجوی نام یا آیدی</label><input class="mt-input" id="sp-filter-search" name="q" value="{{ $filters['q'] }}" placeholder="مثلاً @roya.studio"></div>
          <div class="mt-field"><label for="sp-filter-stage">مرحله</label><select class="mt-input" id="sp-filter-stage" name="stage"><option value="">همه مراحل</option>@foreach($stages as $stage)<option value="{{ $stage['key'] }}" @selected((string) $filters['stage'] === (string) $stage['key'])>{{ $stage['key'] }} · {{ $stage['title'] }}</option>@endforeach</select></div>
          <div class="mt-field"><label for="sp-filter-channel">کانال ارتباط</label><select class="mt-input" id="sp-filter-channel" name="channel"><option value="">همه کانال‌ها</option><option value="other" @selected($filters['channel'] === 'other')>ارتباط دستی</option><option value="instagram" @selected($filters['channel'] === 'instagram')>اینستاگرام · آینده</option><option value="telegram" @selected($filters['channel'] === 'telegram')>تلگرام · آینده</option><option value="both" @selected($filters['channel'] === 'both')>چندکاناله · آینده</option></select></div>
          <div class="mt-field"><label for="sp-filter-priority">اولویت</label><select class="mt-input" id="sp-filter-priority" name="priority"><option value="">همه اولویت‌ها</option><option value="high" @selected($filters['priority'] === 'high')>مهم</option><option value="normal" @selected($filters['priority'] === 'normal')>عادی</option><option value="low" @selected($filters['priority'] === 'low')>کم</option></select></div>
          <div class="mt-field"><label for="sp-filter-assignee">مسئول پیگیری</label><select class="mt-input" id="sp-filter-assignee" name="assigned_to"><option value="">همه مسئولان</option><option value="unassigned" @selected($filters['assigned_to'] === 'unassigned')>بدون مسئول</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected((string) $filters['assigned_to'] === (string) $admin->id)>{{ $admin->name }}</option>@endforeach</select></div>
          <div class="sp-filter-actions"><button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-filter"></i> اعمال فیلتر</button><a class="mt-btn" href="{{ route('admin.marketing-technology.partners') }}">پاک‌کردن</a></div>
        </form>
        <div class="sp-filter-insights"><span><i class="fa-solid fa-triangle-exclamation"></i> {{ number_format($metrics['overdue']) }} عقب‌افتاده</span><span><i class="fa-solid fa-user-slash"></i> {{ number_format($metrics['unassigned']) }} بدون مسئول</span><span><i class="fa-solid fa-calendar-day"></i> {{ number_format($todayFollowUps->count()) }} مورد در صف امروز</span><span><i class="fa-solid fa-bullseye"></i> ارتباط امروز {{ number_format($metrics['daily_contacts']) }}/{{ number_format($metrics['daily_target']) }}</span></div>
      </section>
    @endif

    @if(!$ready)
      <section class="mt-card mt-empty-section sp-empty"><span class="mt-empty-icon"><i class="fa-solid fa-route"></i></span><h2>زیرساخت مسیر آماده اجرا نشده است</h2><p>پوسته این بخش آماده است. با اجرای مهاجرت پایگاه داده، ثبت سرنخ‌ها و جابه‌جایی واقعی کارت‌ها فعال می‌شود.</p><span class="mt-badge mt-badge-warn">بزودی</span></section>
    @else
      <section class="sp-command-grid">
        <article class="mt-card mt-card-pad sp-today-card">
          <div class="mt-section-head">
            <div><h2>صف کارهای امروز</h2><p>هر ردیف یک اقدام مشخص دارد: ارتباط، ثبت نتیجه و تعیین قدم بعدی.</p></div>
            <span class="mt-badge mt-badge-warn">{{ number_format($todayFollowUps->count()) }} مورد · {{ number_format($metrics['overdue']) }} عقب‌افتاده</span>
          </div>
          @if($todayFollowUps->isEmpty())
            <div class="sp-empty-inline"><i class="fa-solid fa-circle-check"></i><span>برای امروز تسک معوقی ثبت نشده است.</span></div>
          @else
            <div class="sp-task-list">
              @foreach($todayFollowUps->take(20) as $lead)
                @php($isOverdue = $lead->next_follow_up_at?->lt(today()->startOfDay()) ?? false)
                <div class="sp-task-row {{ $isOverdue ? 'is-overdue' : '' }}">
                  <div class="sp-avatar">{{ mb_substr($lead->name ?: ltrim($lead->handle, '@'), 0, 1) }}</div>
                  <div class="sp-task-copy"><strong>{{ $lead->name ?: $lead->handle }}</strong><small>{{ $lead->handle }} · {{ $stages[$lead->stage]['title'] ?? 'مرحله نامشخص' }} · {{ $isOverdue ? 'عقب‌افتاده' : 'امروز' }}</small></div>
                  <span class="sp-channel sp-channel-{{ $lead->channel }}">{{ $lead->channel === 'instagram' ? 'اینستاگرام' : ($lead->channel === 'telegram' ? 'تلگرام' : ($lead->channel === 'both' ? 'چندکاناله' : 'ارتباط دستی')) }}</span>
                  <form method="POST" action="{{ route('admin.marketing-technology.partners.assignee', $lead) }}" class="sp-assignee-form" title="تعیین مسئول پیگیری">
                    @csrf
                    @method('PATCH')
                    <select class="sp-owner-select" name="assigned_to" aria-label="مسئول پیگیری {{ $lead->name ?: $lead->handle }}" onchange="this.form.requestSubmit()">
                      <option value="">بدون مسئول</option>
                      @foreach($admins as $admin)<option value="{{ $admin->id }}" @selected((int) $lead->assigned_to === (int) $admin->id)>{{ $admin->name }}</option>@endforeach
                    </select>
                  </form>
                  <form method="POST" action="{{ route('admin.marketing-technology.partners.contacted', $lead) }}" class="sp-contact-form">
                    @csrf
                    <input type="hidden" name="contact_type" value="{{ $lead->stage <= 2 ? 'voice' : 'message' }}">
                    <button type="button" class="mt-btn mt-btn-small sp-open-contact" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name ?: $lead->handle }}" data-contact-type="{{ $lead->stage <= 2 ? 'voice' : 'message' }}" title="ثبت نتیجه تماس"><i class="fa-solid fa-check"></i> ثبت نتیجه</button>
                  </form>
                </div>
              @endforeach
            </div>
          @endif
        </article>

        <article class="mt-card mt-card-pad sp-connection-card">
          <div class="mt-section-head"><div><h2>کانال‌های ارتباطی</h2><p>فعلاً پیام اول دستی است؛ اتصال‌ها برای مرحله بعد آماده شده‌اند.</p></div><i class="fa-solid fa-signal sp-section-icon"></i></div>
          <div class="sp-connection-list">
            <div class="sp-connection-row"><span class="sp-connection-icon sp-manual"><i class="fa-solid fa-hand"></i></span><div><strong>ارتباط دستی</strong><small>وویس، پیام یا تماس انسانی؛ مسیر فعلی پایلوت</small></div><span class="sp-connection-state sp-connection-state-active">فعال</span></div>
            <div class="sp-connection-row"><span class="sp-connection-icon sp-future"><i class="fa-solid fa-plug"></i></span><div><strong>اتصال اینستاگرام و تلگرام</strong><small>در فاز بعدی، بعد از تثبیت مسیر دستی</small></div><span class="sp-connection-state">بعداً</span></div>
          </div>
          <a class="sp-light-link" href="{{ route('admin.marketing-technology.integrations') }}">مشاهده تنظیمات اتصال‌ها <i class="fa-solid fa-arrow-left"></i></a>
        </article>
      </section>

      <article class="sp-playbook-card" id="sp-playbook-card">
        <div class="sp-playbook-head"><div><div class="mt-eyebrow">راهنمای اجرای مرحله</div><h3 id="sp-playbook-title">—</h3></div><span class="mt-badge" id="sp-playbook-badge">مرحله —</span></div>
        <div class="sp-playbook-grid">
          <div class="sp-playbook-item"><span><i class="fa-solid fa-bullseye"></i> هدف</span><strong id="sp-playbook-goal">—</strong></div>
          <div class="sp-playbook-item"><span><i class="fa-solid fa-list-check"></i> تسک اصلی</span><strong id="sp-playbook-task">—</strong></div>
          <div class="sp-playbook-item sp-playbook-wide"><span><i class="fa-solid fa-comment-dots"></i> اسکریپت پیشنهادی</span><strong id="sp-playbook-script">—</strong></div>
          <div class="sp-playbook-item"><span><i class="fa-regular fa-clock"></i> زمان پیگیری</span><strong id="sp-playbook-followup">—</strong></div>
          <div class="sp-playbook-item"><span><i class="fa-solid fa-stopwatch"></i> زمان پیش‌فرض</span><strong id="sp-playbook-default-hours">—</strong></div>
          <div class="sp-playbook-item"><span><i class="fa-solid fa-bullhorn"></i> نوع ارتباط</span><strong id="sp-playbook-message-type">—</strong></div>
          <div class="sp-playbook-item"><span><i class="fa-solid fa-arrow-left"></i> شرط عبور</span><strong id="sp-playbook-advance">—</strong></div>
          <div class="sp-playbook-item"><span><i class="fa-solid fa-ban"></i> شرط توقف</span><strong id="sp-playbook-stop">—</strong></div>
        </div>
      </article>

      <section class="mt-card mt-card-pad sp-add-card" id="sp-add-lead">
        <div class="mt-section-head"><div><h2>افزودن سرنخ جدید</h2><p>برای شروع، فقط اطلاعات ضروری را وارد کن؛ جزئیات بعداً تکمیل می‌شود.</p></div><span class="mt-badge">ثبت دستی</span></div>
        <form method="POST" action="{{ route('admin.marketing-technology.partners.store') }}" class="sp-add-form" id="sp-add-lead-form">
          @csrf
          <div class="mt-field"><label for="sp-name">نام یا نام برند</label><input class="mt-input" id="sp-name" name="name" value="{{ old('name') }}" placeholder="مثلاً استودیو رویا"></div>
          <div class="mt-field"><label for="sp-handle">آیدی یا شناسه <span>*</span></label><input class="mt-input" id="sp-handle" name="handle" value="{{ old('handle') }}" required placeholder="مثلاً @roya.studio"></div>
          <div class="mt-field"><label for="sp-channel">روش ارتباط فعلی</label><select class="mt-input" id="sp-channel" name="channel"><option value="other">ارتباط دستی</option><option value="instagram">اینستاگرام · آینده</option><option value="telegram">تلگرام · آینده</option><option value="both">چندکاناله · آینده</option></select></div>
          <div class="mt-field"><label for="sp-acquisition-source">منبع پیدا کردن همکار</label><select class="mt-input" id="sp-acquisition-source" name="acquisition_source"><option value="manual" @selected(old('acquisition_source', 'manual') === 'manual')>پیدا شده دستی</option><option value="instagram" @selected(old('acquisition_source') === 'instagram')>اینستاگرام</option><option value="telegram" @selected(old('acquisition_source') === 'telegram')>تلگرام</option><option value="google" @selected(old('acquisition_source') === 'google')>گوگل</option><option value="website" @selected(old('acquisition_source') === 'website')>سایت وطن</option><option value="referral" @selected(old('acquisition_source') === 'referral')>معرفی و رفرال</option><option value="other" @selected(old('acquisition_source') === 'other')>سایر</option></select></div>
          <div class="mt-field"><label for="sp-profile-url">لینک پروفایل</label><input class="mt-input" id="sp-profile-url" name="profile_url" value="{{ old('profile_url') }}" type="url" placeholder="https://..."></div>
          <div class="mt-field"><label for="sp-stage">شروع از مرحله</label><select class="mt-input" id="sp-stage" name="stage">@foreach($stages as $stage)<option value="{{ $stage['key'] }}">{{ $stage['key'] }} · {{ $stage['title'] }}</option>@endforeach</select></div>
          <div class="mt-field"><label for="sp-priority">اولویت</label><select class="mt-input" id="sp-priority" name="priority"><option value="normal">عادی</option><option value="high">مهم</option><option value="low">کم</option></select></div>
          <div class="mt-field"><label for="sp-next-follow-up">اولین پیگیری</label><input class="mt-input" id="sp-next-follow-up" name="next_follow_up_at" type="datetime-local"></div>
          <div class="mt-field mt-field-wide"><label for="sp-notes">یادداشت اولیه</label><textarea class="mt-input mt-textarea" id="sp-notes" name="notes" placeholder="چرا فکر می‌کنیم این پیج برای همکاری مناسب است؟">{{ old('notes') }}</textarea></div>
          <div class="sp-add-actions"><button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-plus"></i> افزودن به مرحله صفر</button></div>
        </form>
      </section>


      <section class="mt-card mt-card-pad sp-pipeline-card">
        <div class="mt-section-head sp-pipeline-head"><div><h2>برد مسیر همکاران فروش</h2><p>هر کارت را با کشیدن به مرحله بعد منتقل کن. مرحله‌ها فعلاً دستی هستند تا اسکریپت واقعی تیم روی سیستم تثبیت شود.</p></div><div class="sp-board-hint"><i class="fa-solid fa-arrows-up-down-left-right"></i> کشیدن و رهاکردن کارت</div></div>
        <div class="sp-board" id="sp-board">
          @foreach($stages as $stage)
            <section class="sp-stage" data-stage="{{ $stage['key'] }}" aria-label="مرحله {{ $stage['key'] }}: {{ $stage['title'] }}">
              <div class="sp-stage-head"><span class="sp-stage-number">{{ $stage['key'] }}</span><div><strong>{{ $stage['title'] }}</strong><small>{{ $stage['description'] }}</small></div><button type="button" class="sp-stage-info" data-stage-info="{{ $stage['key'] }}" title="راهنمای این مرحله" aria-label="راهنمای مرحله {{ $stage['key'] }}"><i class="fa-solid fa-circle-info"></i></button><span class="sp-stage-count">{{ $leadsByStage[$stage['key']]->count() }}</span></div>
              <div class="sp-stage-body" data-stage-body="{{ $stage['key'] }}">
                @forelse($leadsByStage[$stage['key']] as $lead)
                  @include('admin.marketing-technology.partials.partner-lead-card', ['lead' => $lead, 'acquisitionSourceLabels' => $acquisitionSourceLabels])
                @empty
                  <div class="sp-stage-empty"><i class="fa-solid {{ $stage['icon'] }}"></i><span>هنوز کارتی نیست</span></div>
                @endforelse
              </div>
            </section>
          @endforeach
        </div>
      </section>
    @endif
  </div>
</main>

<div class="sp-modal" id="sp-contact-modal" hidden>
  <div class="sp-modal-backdrop" data-close-contact></div>
  <section class="sp-modal-card" role="dialog" aria-modal="true" aria-labelledby="sp-contact-title">
    <div class="sp-modal-head"><div><div class="mt-eyebrow">ثبت فعالیت</div><h2 id="sp-contact-title">ثبت نتیجه تماس</h2><p id="sp-contact-lead-name">—</p></div><button type="button" class="sp-modal-close" data-close-contact aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST" id="sp-contact-form" class="sp-modal-form">
      @csrf
      <input type="hidden" name="contact_type" id="sp-contact-type" value="message">
      <div class="mt-field"><label for="sp-contact-result">نتیجه ارتباط</label><select class="mt-input" id="sp-contact-result" name="result"><option value="no_response">پاسخ نداد</option><option value="positive">پاسخ مثبت</option><option value="follow_up">نیازمند پیگیری</option><option value="negative">پاسخ منفی</option></select></div>
      <div class="mt-field"><label for="sp-contact-next">پیگیری بعدی</label><input class="mt-input" id="sp-contact-next" name="next_follow_up_at" type="datetime-local"></div>
      <div class="mt-field"><label for="sp-contact-note">یادداشت نتیجه</label><textarea class="mt-input mt-textarea" id="sp-contact-note" name="note" placeholder="چه گفت؟ قدم بعدی چیست؟"></textarea></div>
      <div class="sp-modal-actions"><button type="button" class="mt-btn" data-close-contact>انصراف</button><button type="submit" class="mt-btn mt-btn-primary"><i class="fa-solid fa-check"></i> ذخیره نتیجه</button></div>
    </form>
  </section>
</div>
@endsection

@section('scripts')
<script>
(function () {
  const board = document.getElementById('sp-board');
  const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const stageData = @json($stages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  const modal = document.getElementById('sp-contact-modal');
  const contactForm = document.getElementById('sp-contact-form');
  const contactUrlTemplate = '{{ url('/admin/marketing-technology/partners') }}/__ID__/contacted';

  function showNotice(message, type) {
    const page = document.querySelector('.sp-page');
    if (!page || !message) return;
    const notice = document.createElement('div');
    notice.className = 'mt-alert ' + (type === 'error' ? 'mt-alert-danger' : 'mt-alert-success');
    notice.innerHTML = '<i class="fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check') + '"></i><span></span>';
    notice.querySelector('span').textContent = message;
    page.prepend(notice);
    window.setTimeout(function () { notice.remove(); }, 3600);
  }

  async function submitAjax(form) {
    const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) });
    const payload = await response.json().catch(function () { return {}; });
    if (!response.ok) throw new Error(payload.message || 'ذخیره‌سازی انجام نشد.');
    return payload;
  }

  function setBusy(form, busy) {
    form.classList.toggle('is-saving', busy);
    form.querySelectorAll('button, select, input, textarea').forEach(function (field) { field.disabled = busy; });
  }

  function formatIranDateTime(value) {
    if (!value) return 'بدون پیگیری';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return 'بدون پیگیری';
    const day = new Intl.DateTimeFormat('fa-IR-u-ca-persian', { timeZone: 'Asia/Tehran', year: 'numeric', month: '2-digit', day: '2-digit' }).format(date).replace(/\u200e|\u200f/g, '');
    const time = new Intl.DateTimeFormat('fa-IR', { timeZone: 'Asia/Tehran', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).format(date);
    return day + ' ' + time;
  }

  function hydrateFollowUp(root) {
    (root || document).querySelectorAll('[data-follow-up-at]').forEach(function (element) {
      const value = element.dataset.followUpAt;
      const text = element.querySelector('strong');
      if (text) text.textContent = formatIranDateTime(value);
    });
  }

  function renderPlaybook(stageKey) {
    const stage = stageData.find(function (item) { return String(item.key) === String(stageKey); });
    if (!stage) return;
    document.querySelectorAll('[data-stage-info]').forEach(function (button) { button.classList.toggle('is-selected', String(button.dataset.stageInfo) === String(stageKey)); });
    const typeLabels = { none: 'بدون پیام', voice: 'وویس', message: 'پیام متنی', both: 'وویس یا پیام' };
    const values = { title: stage.title, badge: 'مرحله ' + stage.key, goal: stage.goal, task: stage.task, script: stage.script, followup: stage.follow_up, 'default-hours': stage.default_follow_up_hours === null ? 'بدون زمان‌بندی خودکار' : stage.default_follow_up_hours + ' ساعت بعد', 'message-type': typeLabels[stage.message_type] || stage.message_type, advance: stage.advance_when, stop: stage.stop_when };
    Object.keys(values).forEach(function (key) { const element = document.getElementById('sp-playbook-' + key); if (element) element.textContent = values[key] || '—'; });
  }

  document.querySelectorAll('[data-stage-info]').forEach(function (button) { button.addEventListener('click', function () { renderPlaybook(button.dataset.stageInfo); }); });
  renderPlaybook(0);
  hydrateFollowUp(document);

  function closeContactModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('sp-modal-open');
  }

  document.querySelectorAll('[data-close-contact]').forEach(function (button) { button.addEventListener('click', closeContactModal); });
  document.addEventListener('click', function (event) {
    const button = event.target.closest('.sp-open-contact');
    if (!button || !modal || !contactForm) return;
    contactForm.action = contactUrlTemplate.replace('__ID__', button.dataset.leadId);
    contactForm.dataset.leadId = button.dataset.leadId;
    document.getElementById('sp-contact-lead-name').textContent = button.dataset.leadName || 'همکار فروش';
    document.getElementById('sp-contact-type').value = button.dataset.contactType || 'message';
    document.getElementById('sp-contact-result').value = 'no_response';
    document.getElementById('sp-contact-next').value = '';
    document.getElementById('sp-contact-note').value = '';
    modal.hidden = false;
    document.body.classList.add('sp-modal-open');
  });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeContactModal(); });

  document.addEventListener('submit', function (event) {
    const form = event.target;
    if (form.id === 'sp-add-lead-form') {
      event.preventDefault();
      setBusy(form, true);
      submitAjax(form).then(function (payload) {
        const body = board?.querySelector('[data-stage-body="' + payload.lead.stage + '"]');
        if (body && payload.html) {
          body.querySelector('.sp-stage-empty')?.remove();
          body.insertAdjacentHTML('afterbegin', payload.html);
          hydrateFollowUp(body);
          const count = body.parentElement.querySelector('.sp-stage-count');
          if (count) count.textContent = Number(count.textContent) + 1;
        }
        form.reset();
        showNotice(payload.message);
      }).catch(function (error) { showNotice(error.message, 'error'); }).finally(function () { setBusy(form, false); });
      return;
    }
    if (form === contactForm) {
      event.preventDefault();
      setBusy(form, true);
      submitAjax(form).then(function (payload) {
        const card = board?.querySelector('[data-lead-id="' + form.dataset.leadId + '"]');
        if (card && payload.lead) {
          const count = card.querySelector('[data-contact-count]');
          const followUp = card.querySelector('.sp-lead-follow-up');
          if (count) count.textContent = Number(payload.lead.contact_count).toLocaleString('fa-IR');
          if (followUp) {
            followUp.dataset.followUpAt = payload.lead.next_follow_up_at || '';
            const followUpText = followUp.querySelector('strong');
            if (followUpText) followUpText.textContent = formatIranDateTime(payload.lead.next_follow_up_at);
          }
        }
        closeContactModal();
        showNotice(payload.message);
      }).catch(function (error) { showNotice(error.message, 'error'); }).finally(function () { setBusy(form, false); });
      return;
    }
    if (form.classList.contains('sp-assignee-form')) {
      event.preventDefault();
      setBusy(form, true);
      submitAjax(form).then(function (payload) { showNotice(payload.message); }).catch(function (error) { showNotice(error.message, 'error'); }).finally(function () { setBusy(form, false); });
    }
  });

  if (!board) return;
  let dragged = null;
  function bindCard(card) {
    if (!card || card.dataset.dragBound === '1') return;
    card.dataset.dragBound = '1';
    card.addEventListener('dragstart', function () { dragged = card; card.classList.add('is-dragging'); });
    card.addEventListener('dragend', function () { card.classList.remove('is-dragging'); dragged = null; board.querySelectorAll('.sp-stage-body').forEach(function (body) { body.classList.remove('is-over'); }); });
  }
  board.querySelectorAll('.sp-lead-card').forEach(bindCard);

  board.querySelectorAll('.sp-stage-body').forEach(function (body) {
    body.addEventListener('dragover', function (event) { event.preventDefault(); body.classList.add('is-over'); });
    body.addEventListener('dragleave', function () { body.classList.remove('is-over'); });
    body.addEventListener('drop', function (event) {
      event.preventDefault();
      body.classList.remove('is-over');
      if (!dragged) return;
      const card = dragged;
      const stage = body.dataset.stageBody;
      const sourceBody = card.parentElement;
      const leadId = card.dataset.leadId;
      const currentStage = card.dataset.stage;
      if (stage === currentStage) return;
      const form = new FormData();
      form.append('_token', token || '');
      form.append('_method', 'PATCH');
      form.append('stage', stage);
      const sourceCount = sourceBody.parentElement.querySelector('.sp-stage-count');
      const targetCount = body.parentElement.querySelector('.sp-stage-count');
      body.querySelector('.sp-stage-empty')?.remove();
      body.appendChild(card);
      card.dataset.stage = stage;
      if (sourceCount) sourceCount.textContent = Math.max(0, Number(sourceCount.textContent) - 1);
      if (targetCount) targetCount.textContent = Number(targetCount.textContent) + 1;
      card.classList.add('is-saving');
      fetch('{{ url('/admin/marketing-technology/partners') }}/' + leadId + '/stage', { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: form })
        .then(function (response) { if (!response.ok) throw new Error('تغییر مرحله ذخیره نشد.'); return response.json(); })
        .then(function (payload) { card.classList.remove('is-saving'); card.classList.add('is-saved'); window.setTimeout(function () { card.classList.remove('is-saved'); }, 1100); showNotice(payload.message); })
        .catch(function (error) { sourceBody.appendChild(card); card.dataset.stage = currentStage; if (sourceCount) sourceCount.textContent = Number(sourceCount.textContent) + 1; if (targetCount) targetCount.textContent = Math.max(0, Number(targetCount.textContent) - 1); card.classList.remove('is-saving'); showNotice(error.message, 'error'); });
    });
  });
})();
</script>
@endsection
