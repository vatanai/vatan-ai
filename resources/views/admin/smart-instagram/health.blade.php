@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'سلامت و لاگ‌ها';
  $siSubtitle = 'دریافت رویداد، صف ارسال، رسانه و عملیات حساس — بدون نمایش محتوای پیام مشتری.';
  $s = $summary;
  $canFix = app(\App\Services\SmartInstagram\WorkspaceContext::class)->can(auth('admin')->user(), 'manage_settings');
@endphp

@section('si-page')
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-satellite-dish', 'tone' => 'info', 'value' => Ui::n($s['events_24h']), 'label' => 'رویداد ورودی (۲۴ ساعت)', 'hint' => 'آخرین: '.Ui::ago($s['last_event']), 'href' => route('admin.smart-instagram.health', ['tab' => 'events'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-triangle-exclamation', 'tone' => $s['events_failed'] || $s['events_waiting'] ? 'danger' : 'success', 'value' => Ui::n($s['events_failed']), 'label' => 'رویداد ناموفق', 'hint' => $s['events_waiting'] ? Ui::n($s['events_waiting']).' در انتظار بیش از ۵ دقیقه' : 'صف پردازش روان است', 'hintTone' => $s['events_waiting'] ? 'danger' : 'success', 'href' => route('admin.smart-instagram.health', ['tab' => 'events', 'status' => 'failed'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-paper-plane', 'tone' => ($s['outbound']['failed'] ?? 0) ? 'danger' : 'success', 'value' => Ui::n($s['outbound']['sent'] ?? 0), 'label' => 'ارسال موفق (۷ روز)', 'hint' => Ui::n($s['outbound']['failed'] ?? 0).' ناموفق · '.Ui::n($s['outbound']['blocked'] ?? 0).' مسدود با قانون', 'href' => route('admin.smart-instagram.health', ['tab' => 'outbound'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-layer-group', 'tone' => 'primary', 'value' => $s['jobs_pending'] === null ? '—' : Ui::n($s['jobs_pending']), 'label' => 'کار در صف ('.$queueMode.')', 'hint' => $s['jobs_failed'] ? Ui::n($s['jobs_failed']).' کار شکست‌خورده‌ی این ماژول' : null, 'hintTone' => 'danger'])
  </div>

  <nav class="si-tabs" aria-label="بخش‌های سلامت">
    @foreach($tabs as $key => $label)<a href="{{ route('admin.smart-instagram.health', ['tab' => $key]) }}" class="chip-filter {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>@endforeach
  </nav>

  @if($tab === 'overview')
    <div class="si-grid-even">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-heart-pulse"></i> وضعیت اجزا</div></div>
        @php
          $components = [
            ['دریافت رویداد', $s['events_failed'] === 0 && $s['events_waiting'] === 0, $s['events_waiting'] ? 'رویداد معطل در صف — ورکر صف را بررسی کنید' : 'سالم'],
            ['صف ارسال', ($s['outbound']['failed'] ?? 0) === 0, ($s['outbound']['failed'] ?? 0) ? 'ارسال ناموفق وجود دارد' : 'سالم'],
            ['دریافت رسانه', $s['media_failed'] === 0, $s['media_failed'] ? Ui::n($s['media_failed']).' فایل دریافت نشد' : 'سالم'],
            ['خطاهای ۲۴ ساعت', $s['errors_24h'] === 0, $s['errors_24h'] ? Ui::n($s['errors_24h']).' خطا ثبت شد' : 'بدون خطا'],
          ];
        @endphp
        @foreach($components as [$name, $ok, $desc])
          <div class="si-row"><span class="si-dot is-{{ $ok ? 'success' : 'danger' }}"></span><div class="si-row-main"><div class="si-row-title">{{ $name }}</div><div class="si-row-sub">{{ $desc }}</div></div></div>
        @endforeach
        <p class="si-help" style="margin-top:10px">بازیابی خودکار: هر ۱۰ دقیقه رویدادهای معطل و ارسال‌های گیرکرده دوباره به صف می‌روند (<span class="si-ltr">smart-instagram:maintenance</span>).</p>
      </section>
      <section class="content-card si-panel is-flush">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-scroll"></i> آخرین عملیات</div><a href="{{ route('admin.smart-instagram.health', ['tab' => 'logs']) }}" class="si-link">همه <i class="fa-solid fa-angle-left"></i></a></div>
        @include('admin.smart-instagram.partials.logs-table', ['logs' => $logs, 'compact' => true])
      </section>
    </div>
  @endif

  @if($tab === 'events')
    <section class="content-card si-panel is-flush">
      <div class="si-panel-head"><div class="si-chips">@foreach(['' => 'همه', 'received' => 'دریافت‌شده', 'processed' => 'پردازش‌شده', 'failed' => 'ناموفق', 'ignored' => 'نادیده', 'duplicate' => 'تکراری'] as $k => $l)<a href="{{ route('admin.smart-instagram.health', array_filter(['tab' => 'events', 'status' => $k])) }}" class="chip-filter {{ $status === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach</div></div>
      <div class="si-table-wrap" style="margin-top:10px">
        <table class="table-pro">
          <thead><tr><th>#</th><th>نوع</th><th>شناسه‌ی بیرونی</th><th>وضعیت</th><th>خطا</th><th>دریافت</th><th>پردازش</th><th></th></tr></thead>
          <tbody>
            @forelse($events as $event)
              <tr>
                <td class="si-num">{{ $event->id }}</td>
                <td class="si-mono">{{ $event->event_type }}</td>
                <td class="si-mono">{{ \Illuminate\Support\Str::limit((string) $event->external_id, 24) }}</td>
                <td><span class="badge-pro badge-{{ Ui::statusTone($event->processing_status) }}">{{ Ui::label('event', $event->processing_status) }}</span></td>
                <td class="si-muted" style="white-space:normal;max-width:260px">{{ \Illuminate\Support\Str::limit((string) $event->error_message, 120) ?: '—' }}</td>
                <td class="si-muted">{{ Ui::date($event->created_at, true) }}</td>
                <td class="si-muted">{{ $event->processed_at ? Ui::ago($event->processed_at) : '—' }}</td>
                <td>@if($canFix && in_array($event->processing_status, ['failed', 'received'], true))<form method="POST" action="{{ route('admin.smart-instagram.health.reprocess', $event) }}">@csrf<button class="icon-action-btn" title="پردازش دوباره"><i class="fa-solid fa-rotate-right"></i></button></form>@endif</td>
              </tr>
            @empty
              <tr><td colspan="8" class="si-table-empty">رویدادی ثبت نشده.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @include('admin.smart-instagram.partials.pagination', ['paginator' => $events])
    </section>
  @endif

  @if($tab === 'outbound')
    <section class="content-card si-panel is-flush">
      <div class="si-panel-head"><div class="si-chips">@foreach(['' => 'همه', 'pending' => 'در صف', 'retrying' => 'تلاش دوباره', 'sent' => 'ارسال‌شده', 'failed' => 'ناموفق', 'blocked' => 'مسدود', 'manual' => 'دستی'] as $k => $l)<a href="{{ route('admin.smart-instagram.health', array_filter(['tab' => 'outbound', 'status' => $k])) }}" class="chip-filter {{ $status === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach</div></div>
      <div class="si-table-wrap" style="margin-top:10px">
        <table class="table-pro">
          <thead><tr><th>#</th><th>مخاطب</th><th>نوع</th><th>منشأ</th><th>وضعیت</th><th>دلیل / خطا</th><th>تلاش</th><th>زمان</th><th></th></tr></thead>
          <tbody>
            @forelse($outbound as $out)
              <tr>
                <td class="si-num">{{ $out->id }}</td>
                <td>@if($out->contact)<a class="si-link" href="{{ route('admin.smart-instagram.inbox.show', $out->conversation_id) }}">{{ $out->contact->label() }}</a>@else — @endif</td>
                <td>{{ Ui::label('kind', $out->kind) }}</td>
                <td>{{ Ui::label('origin', $out->origin) }}@if($out->admin)<div class="si-muted">{{ $out->admin->name }}</div>@endif</td>
                <td><span class="badge-pro badge-{{ Ui::statusTone($out->status) }}">{{ Ui::label('outbound', $out->status) }}</span></td>
                <td class="si-muted" style="white-space:normal;max-width:280px">{{ \Illuminate\Support\Str::limit((string) ($out->policy_reason ?: $out->error), 140) ?: '—' }}</td>
                <td class="si-num">{{ Ui::n($out->attempts) }}</td>
                <td class="si-muted">{{ Ui::ago($out->created_at) }}</td>
                <td>@if($canFix && $out->status === 'failed')<form method="POST" action="{{ route('admin.smart-instagram.health.retry', $out) }}">@csrf<button class="icon-action-btn" title="ارسال دوباره"><i class="fa-solid fa-rotate-right"></i></button></form>@endif</td>
              </tr>
            @empty
              <tr><td colspan="9" class="si-table-empty">ارسالی ثبت نشده.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @include('admin.smart-instagram.partials.pagination', ['paginator' => $outbound])
    </section>
  @endif

  @if($tab === 'logs')
    <section class="content-card si-panel is-flush">
      <div class="si-panel-head"><div class="si-chips">@foreach(['' => 'همه', 'info' => 'اطلاعات', 'warning' => 'هشدار', 'error' => 'خطا'] as $k => $l)<a href="{{ route('admin.smart-instagram.health', array_filter(['tab' => 'logs', 'level' => $k])) }}" class="chip-filter {{ $level === $k ? 'active' : '' }}">{{ $l }}</a>@endforeach</div></div>
      @include('admin.smart-instagram.partials.logs-table', ['logs' => $logs, 'compact' => false])
      @include('admin.smart-instagram.partials.pagination', ['paginator' => $logs])
    </section>
  @endif
@endsection
