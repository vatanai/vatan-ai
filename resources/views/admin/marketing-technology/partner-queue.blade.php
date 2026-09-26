@extends('layouts.admin')

@section('title', 'صف عملیاتی امروز — مسیر همکاران فروش')

@push('styles')
<link href="{{ asset('admin/css/marketing-technology.css') }}?v={{ filemtime(public_path('admin/css/marketing-technology.css')) }}" rel="stylesheet">
@endpush

@section('content')
<main class="mr-[294px] flex-1 min-h-screen flex flex-col min-w-0 max-[900px]:mr-0">
  @include('admin.partials.header')
  <div class="marketing-tech-page admin-content sp-page sp-queue-page flex-1 overflow-y-auto" id="content">
    @if(session('success'))
      <div class="mt-alert mt-alert-success"><i class="fa-solid fa-circle-check"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
      <div class="mt-alert mt-alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>@foreach($errors->all() as $error){{ $error }}@if(!$loop->last)، @endif @endforeach</span></div>
    @endif

    <div class="mt-page-head sp-page-head">
      <div>
        <div class="mt-eyebrow">اجرای روزانه‌ی تیم فروش</div>
        <h1>صف عملیاتی امروز</h1>
        <p>اینجا فقط کارهای قابل انجام امروز را می‌بینی؛ هر کارت را باز کن، اسکریپت مرحله را اجرا کن و نتیجه را ثبت کن.</p>
      </div>
      <div class="mt-head-actions">
        <span class="sp-manual-badge"><i class="fa-solid fa-hand"></i> ارتباط اول دستی و انسانی</span>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.partners') }}"><i class="fa-solid fa-route"></i> برد مسیر</a>
        <a class="mt-btn" href="{{ route('admin.marketing-technology.partners.team') }}"><i class="fa-solid fa-chart-simple"></i> عملکرد تیم</a>
      </div>
    </div>

    @if(!$ready)
      <section class="mt-card mt-empty-section sp-empty"><span class="mt-empty-icon"><i class="fa-solid fa-list-check"></i></span><h2>صف عملیاتی آماده اجرا نشده است</h2><p>ابتدا migration مسیر همکاران فروش باید روی این محیط اجرا شود.</p><span class="mt-badge mt-badge-warn">بزودی</span></section>
    @else
      <section class="sp-queue-summary">
        <article class="mt-card mt-card-pad sp-queue-progress-card">
          <div class="sp-queue-progress-head"><div><div class="mt-eyebrow">هدف امروز {{ $admin?->name ?: 'تیم فروش' }}</div><h2>امروز چند ارتباط را جلو برده‌ای؟</h2><p>هدف فقط ارسال پیام نیست؛ هر ارتباط باید با نتیجه و قدم بعدی ثبت شود.</p></div><strong>{{ number_format($todayContacts) }} <small>از {{ number_format($target) }}</small></strong></div>
          <div class="sp-team-progress sp-queue-progress"><span style="width: {{ $progress }}%"></span></div>
          <div class="sp-queue-progress-foot"><span>{{ $progress }}٪ از هدف روزانه</span><span>{{ number_format($queue->count()) }} کارت در صف · {{ number_format($overdue) }} عقب‌افتاده</span></div>
        </article>
        <article class="mt-card mt-card-pad sp-queue-rule-card">
          <span class="sp-queue-rule-icon"><i class="fa-solid fa-bullseye"></i></span>
          <div><strong>قاعده‌ی ساده‌ی اجرا</strong><p>هر کارت = یک اقدام، یک نتیجه، یک زمان برای قدم بعدی.</p><small>بعد از ثبت نتیجه، کارت از صف امروز خارج یا برای پیگیری بعدی زمان‌بندی می‌شود.</small></div>
        </article>
      </section>

      <section class="mt-card mt-card-pad sp-queue-filter-card">
        <div class="sp-filter-head"><div><div class="mt-eyebrow">تمرکز کاری</div><h2>فقط کارت‌هایی که باید الان انجام شوند</h2><p>برای شروع از کارت‌های خودت استفاده کن؛ مدیر می‌تواند صف کل تیم را هم ببیند.</p></div><span class="sp-filter-result"><strong>{{ number_format($queue->count()) }}</strong> کارت</span></div>
        <div class="sp-queue-tabs">
          <a class="sp-filter-tab {{ $scope === 'mine' ? 'is-active' : '' }}" href="{{ route('admin.marketing-technology.partners.queue', array_filter(['scope' => 'mine', 'stage' => $stageFilter])) }}"><i class="fa-solid fa-user"></i> کارت‌های من</a>
          <a class="sp-filter-tab {{ $scope === 'all' ? 'is-active' : '' }}" href="{{ route('admin.marketing-technology.partners.queue', array_filter(['scope' => 'all', 'stage' => $stageFilter])) }}"><i class="fa-solid fa-users"></i> صف کل تیم</a>
        </div>
        <form method="GET" class="sp-queue-filter-form">
          <input type="hidden" name="scope" value="{{ $scope }}">
          <div class="mt-field"><label for="spq-stage">تمرکز روی مرحله</label><select class="mt-input" id="spq-stage" name="stage"><option value="">همه مراحل</option>@foreach($stages as $stage)<option value="{{ $stage['key'] }}" @selected((string) $stageFilter === (string) $stage['key'])>{{ $stage['key'] }} · {{ $stage['title'] }}</option>@endforeach</select></div>
          <div class="sp-filter-actions"><button class="mt-btn mt-btn-primary" type="submit"><i class="fa-solid fa-filter"></i> نمایش صف</button><a class="mt-btn" href="{{ route('admin.marketing-technology.partners.queue', ['scope' => $scope]) }}">پاک‌کردن</a></div>
        </form>
      </section>

      <section class="sp-queue-layout">
        <article class="mt-card mt-card-pad sp-queue-list-card">
          <div class="mt-section-head"><div><h2>کارت‌های امروز</h2><p>از بالا به پایین جلو برو؛ موارد عقب‌افتاده و اولویت‌بالا زودتر نمایش داده می‌شوند.</p></div><span class="mt-badge {{ $overdue > 0 ? 'mt-badge-warn' : 'mt-badge-success' }}">{{ $overdue > 0 ? number_format($overdue).' عقب‌افتاده' : 'صف مرتب است' }}</span></div>
          @if($queue->isEmpty())
            <div class="sp-queue-empty"><span><i class="fa-solid fa-circle-check"></i></span><strong>صف امروز خالی است</strong><p>کارت جدیدی برای این نما وجود ندارد؛ می‌توانی از برد مسیر سرنخ جدید اضافه کنی یا صف کل تیم را ببینی.</p><a class="mt-btn mt-btn-primary" href="{{ route('admin.marketing-technology.partners') }}#sp-add-lead"><i class="fa-solid fa-plus"></i> افزودن سرنخ</a></div>
          @else
            <div class="sp-queue-list">
              @foreach($queue as $lead)
                @php
                  $leadStage = $stageMap->get($lead->stage);
                  $isOverdue = $lead->next_follow_up_at?->lt(today()->startOfDay()) ?? false;
                  $contactType = $lead->stage <= 2 ? 'voice' : 'message';
                @endphp
                <article class="sp-queue-item {{ $isOverdue ? 'is-overdue' : '' }}">
                  <div class="sp-queue-item-index">{{ $loop->iteration }}</div>
                  <div class="sp-avatar">{{ mb_substr($lead->name ?: ltrim($lead->handle, '@'), 0, 1) }}</div>
                  <div class="sp-queue-item-main"><div class="sp-queue-item-title"><strong>{{ $lead->name ?: 'بدون نام' }}</strong><span class="sp-channel sp-channel-{{ $lead->channel }}">{{ $lead->channel === 'instagram' ? 'اینستاگرام' : ($lead->channel === 'telegram' ? 'تلگرام' : ($lead->channel === 'both' ? 'چندکاناله' : 'ارتباط دستی')) }}</span></div>@if($lead->contact_url)<a class="sp-queue-contact-link" href="{{ $lead->contact_url }}" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> بازکردن {{ $lead->channel === 'telegram' ? 'تلگرام' : ($lead->channel === 'instagram' ? 'اینستاگرام' : 'کانال') }} · {{ $lead->handle }}</a>@else<span class="sp-queue-no-link">{{ $lead->handle }} · ارتباط دستی</span>@endif<div class="sp-queue-item-meta"><span><i class="fa-solid fa-route"></i> مرحله {{ $lead->stage }} · {{ $leadStage['title'] ?? 'بدون عنوان' }}</span><span><i class="fa-regular fa-clock"></i> {{ $isOverdue ? 'عقب‌افتاده' : 'امروز' }} · {{ $lead->next_follow_up_at?->format('H:i') }}</span><span><i class="fa-solid fa-paper-plane"></i> {{ number_format($lead->contact_count) }} ارتباط</span></div></div>
                  <div class="sp-queue-item-actions"><button class="mt-btn mt-btn-primary mt-btn-small sp-open-queue-contact" type="button" data-lead-id="{{ $lead->id }}" data-lead-name="{{ $lead->name ?: $lead->handle }}" data-contact-type="{{ $contactType }}"><i class="fa-solid fa-check"></i> ثبت نتیجه</button><button class="sp-queue-script-button" type="button" data-queue-stage="{{ $lead->stage }}"><i class="fa-solid fa-comment-dots"></i> اسکریپت</button></div>
                </article>
              @endforeach
            </div>
          @endif
        </article>

        <aside class="mt-card mt-card-pad sp-queue-playbook-card">
          <div class="mt-section-head"><div><div class="mt-eyebrow">راهنمای لحظه‌ای</div><h2 id="spq-playbook-title">اسکریپت مرحله</h2><p id="spq-playbook-subtitle">با انتخاب هر کارت، متن و اقدام همان مرحله را ببین.</p></div><span class="mt-badge" id="spq-playbook-badge">مرحله —</span></div>
          <div class="sp-queue-playbook-content"><div class="sp-queue-playbook-block"><span><i class="fa-solid fa-bullseye"></i> هدف</span><strong id="spq-playbook-goal">—</strong></div><div class="sp-queue-playbook-block"><span><i class="fa-solid fa-list-check"></i> اقدام</span><strong id="spq-playbook-task">—</strong></div><div class="sp-queue-playbook-block sp-queue-playbook-script"><span><i class="fa-solid fa-comment-dots"></i> متن پیشنهادی</span><strong id="spq-playbook-script">—</strong><button type="button" class="sp-copy-script" id="spq-copy-script"><i class="fa-regular fa-copy"></i> کپی متن</button></div><div class="sp-queue-playbook-block"><span><i class="fa-regular fa-clock"></i> قدم بعدی</span><strong id="spq-playbook-followup">—</strong></div></div>
        </aside>
      </section>
    @endif
  </div>
</main>

<div class="sp-modal" id="sp-queue-contact-modal" hidden>
  <div class="sp-modal-backdrop" data-close-queue-contact></div>
  <section class="sp-modal-card" role="dialog" aria-modal="true" aria-labelledby="spq-contact-title">
    <div class="sp-modal-head"><div><div class="mt-eyebrow">ثبت فعالیت روزانه</div><h2 id="spq-contact-title">نتیجه‌ی ارتباط</h2><p id="spq-contact-lead-name">—</p></div><button type="button" class="sp-modal-close" data-close-queue-contact aria-label="بستن"><i class="fa-solid fa-xmark"></i></button></div>
    <form method="POST" id="spq-contact-form" class="sp-modal-form">
      @csrf
      <input type="hidden" name="contact_type" id="spq-contact-type" value="message">
      <div class="mt-field"><label for="spq-contact-result">نتیجه ارتباط</label><select class="mt-input" id="spq-contact-result" name="result"><option value="no_response">پاسخ نداد</option><option value="positive">پاسخ مثبت</option><option value="follow_up">نیازمند پیگیری</option><option value="negative">پاسخ منفی</option></select></div>
      <div class="mt-field"><label for="spq-contact-next">پیگیری بعدی</label><input class="mt-input" id="spq-contact-next" name="next_follow_up_at" type="datetime-local"></div>
      <div class="mt-field"><label for="spq-contact-note">یادداشت نتیجه</label><textarea class="mt-input mt-textarea" id="spq-contact-note" name="note" placeholder="چه گفت؟ قدم بعدی چیست؟"></textarea></div>
      <div class="sp-modal-actions"><button type="button" class="mt-btn" data-close-queue-contact>انصراف</button><button type="submit" class="mt-btn mt-btn-primary"><i class="fa-solid fa-check"></i> ثبت و ادامه صف</button></div>
    </form>
  </section>
</div>
@endsection

@section('scripts')
<script>
(function () {
  const stageData = @json($stages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  const modal = document.getElementById('sp-queue-contact-modal');
  const form = document.getElementById('spq-contact-form');
  const copyButton = document.getElementById('spq-copy-script');
  const scriptElement = document.getElementById('spq-playbook-script');
  const contactUrlTemplate = '{{ url('/admin/marketing-technology/partners') }}/__ID__/contacted';

  function stageByKey(key) {
    return stageData.find(function (stage) { return String(stage.key) === String(key); }) || stageData[0];
  }

  function renderPlaybook(key) {
    const stage = stageByKey(key);
    if (!stage) return;
    document.getElementById('spq-playbook-title').textContent = stage.title || 'اسکریپت مرحله';
    document.getElementById('spq-playbook-subtitle').textContent = stage.description || 'راهنمای اجرای این مرحله';
    document.getElementById('spq-playbook-badge').textContent = 'مرحله ' + stage.key;
    document.getElementById('spq-playbook-goal').textContent = stage.goal || '—';
    document.getElementById('spq-playbook-task').textContent = stage.task || '—';
    document.getElementById('spq-playbook-script').textContent = stage.script || '—';
    document.getElementById('spq-playbook-followup').textContent = stage.follow_up || '—';
  }

  document.querySelectorAll('[data-queue-stage]').forEach(function (button) {
    button.addEventListener('click', function () { renderPlaybook(button.dataset.queueStage); });
  });
  const firstStage = document.querySelector('[data-queue-stage]');
  renderPlaybook(firstStage ? firstStage.dataset.queueStage : 0);

  if (copyButton) {
    copyButton.addEventListener('click', function () {
      if (!scriptElement) return;
      navigator.clipboard?.writeText(scriptElement.textContent || '').then(function () {
        copyButton.innerHTML = '<i class="fa-solid fa-check"></i> کپی شد';
        window.setTimeout(function () { copyButton.innerHTML = '<i class="fa-regular fa-copy"></i> کپی متن'; }, 1500);
      });
    });
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove('sp-modal-open');
  }

  document.querySelectorAll('[data-close-queue-contact]').forEach(function (button) { button.addEventListener('click', closeModal); });
  document.querySelectorAll('.sp-open-queue-contact').forEach(function (button) {
    button.addEventListener('click', function () {
      if (!modal || !form) return;
      form.action = contactUrlTemplate.replace('__ID__', button.dataset.leadId);
      form.dataset.leadId = button.dataset.leadId;
      document.getElementById('spq-contact-lead-name').textContent = button.dataset.leadName || 'همکار فروش';
      document.getElementById('spq-contact-type').value = button.dataset.contactType || 'message';
      document.getElementById('spq-contact-result').value = 'no_response';
      document.getElementById('spq-contact-next').value = '';
      document.getElementById('spq-contact-note').value = '';
      modal.hidden = false;
      document.body.classList.add('sp-modal-open');
    });
  });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closeModal(); });
  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) submitButton.disabled = true;
      fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) })
        .then(function (response) { return response.json().then(function (payload) { if (!response.ok) throw new Error(payload.message || 'ثبت نتیجه انجام نشد.'); return payload; }); })
        .then(function (payload) {
          closeModal();
          const page = document.querySelector('.sp-queue-page');
          if (page) {
            const notice = document.createElement('div');
            notice.className = 'mt-alert mt-alert-success';
            notice.innerHTML = '<i class="fa-solid fa-circle-check"></i><span></span>';
            notice.querySelector('span').textContent = payload.message || 'نتیجه ثبت شد.';
            page.prepend(notice);
            window.setTimeout(function () { notice.remove(); }, 3500);
          }
        })
        .catch(function (error) { window.alert(error.message); })
        .finally(function () { if (submitButton) submitButton.disabled = false; });
    });
  }
})();
</script>
@endsection
