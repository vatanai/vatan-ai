@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'گزارش‌ها';
  $siSubtitle = 'از سرعت پاسخ تا درآمد منتسب؛ هر عدد به فهرست زیرش باز می‌شود.';
  $k = $report['kpis'];
  $days = $report['days'];
  $maxDaily = max(1, collect($report['daily'])->max(fn ($d) => $d['in'] + $d['out']));
  $maxIntent = max(1, ...array_values($report['intents'] ?: [1]));
@endphp

@section('si-actions')
  <div class="si-chips">@foreach([7 => '۷ روز', 30 => '۳۰ روز', 90 => '۹۰ روز'] as $d => $l)<a href="{{ route('admin.smart-instagram.reports', ['days' => $d]) }}" class="chip-filter {{ $days === $d ? 'active' : '' }}">{{ $l }}</a>@endforeach</div>
@endsection

@section('si-page')
  <div class="si-panel-title" style="margin-bottom:10px"><i class="fa-solid fa-headset"></i> عملیات</div>
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-comments', 'tone' => 'primary', 'value' => Ui::n($k['conversations']), 'label' => 'گفتگوی فعال', 'hint' => Ui::n($k['new_contacts']).' مخاطب تازه', 'href' => route('admin.smart-instagram.inbox')])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-stopwatch', 'tone' => 'info', 'value' => Ui::duration($k['median_first_response']), 'label' => 'میانه‌ی زمان اولین پاسخ'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-inbox', 'tone' => 'warning', 'value' => Ui::pct($k['unanswered_pct']), 'label' => 'گفتگوی بی‌پاسخ', 'href' => route('admin.smart-instagram.inbox', ['filter' => 'unanswered'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-user-shield', 'tone' => 'danger', 'value' => Ui::pct($k['handoff_pct']), 'label' => 'واگذارشده به انسان', 'href' => route('admin.smart-instagram.inbox', ['filter' => 'attention'])])
  </div>

  <div class="si-panel-title" style="margin-bottom:10px"><i class="fa-solid fa-sack-dollar"></i> فروش</div>
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-arrow-right-arrow-left', 'tone' => 'info', 'value' => Ui::pct($k['comment_to_dm_pct']), 'label' => 'نرخ کامنت به دایرکت', 'tip' => 'از مخاطبانی که با کامنت وارد شدند، چند درصد دایرکت دادند'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-handshake', 'tone' => 'primary', 'value' => Ui::n($k['deals_created']), 'label' => 'فرصت فروش تازه', 'href' => route('admin.smart-instagram.pipeline')])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-trophy', 'tone' => 'success', 'value' => Ui::pct($k['win_rate']), 'label' => 'نرخ برد', 'hint' => Ui::n($k['won_count']).' فرصت برنده'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-coins', 'tone' => 'success', 'value' => Ui::money($k['won_value']), 'label' => 'فروش منتسب (تومان)'])
  </div>

  <div class="si-grid-2">
    <section class="content-card si-panel">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-chart-column"></i> حجم پیام روزانه</div><span class="si-muted">{{ Ui::n($k['inbound']) }} ورودی · {{ Ui::n($k['outbound']) }} خروجی</span></div>
      <div class="si-bars" role="img" aria-label="نمودار پیام‌های روزانه">
        @foreach($report['daily'] as $day)
          <div class="si-bar">
            <span class="is-out" style="height:{{ round($day['out'] * 100 / $maxDaily, 1) }}%"></span>
            <span class="is-in" style="height:{{ round($day['in'] * 100 / $maxDaily, 1) }}%"></span>
            <span class="si-bar-tip">{{ Ui::date($day['day']) }}: {{ Ui::n($day['in']) }} ورودی / {{ Ui::n($day['out']) }} خروجی</span>
          </div>
        @endforeach
      </div>
      <div class="si-legend"><span><i style="background:var(--primary)"></i>ورودی مشتری</span><span><i style="background:var(--info)"></i>پاسخ‌ها</span></div>
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-comment-dots"></i> موضوعات پرتکرار</div></div>
      @forelse($report['intents'] as $intent => $total)
        <div class="si-hbar"><span class="si-hbar-label">{{ Ui::label('intent', $intent) }}</span><span class="si-hbar-track"><span class="si-hbar-fill" style="width:{{ round($total * 100 / $maxIntent) }}%"></span></span><span class="si-hbar-value si-num">{{ Ui::n($total) }}</span></div>
      @empty
        <div class="si-table-empty">داده‌ای برای این بازه نیست.</div>
      @endforelse
    </section>
  </div>

  <section class="content-card si-panel is-flush" style="margin-bottom:14px">
    <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-route"></i> منبع ورود تا فروش</div></div>
    <div class="si-table-wrap" style="margin-top:10px">
      <table class="table-pro">
        <thead><tr><th>منبع</th><th>پیام ورودی</th><th>گفتگو</th><th>فرصت</th><th>برنده</th><th>فروش (تومان)</th></tr></thead>
        <tbody>
          @foreach($report['by_source'] as $row)
            <tr>
              <td><a class="si-td-strong" style="color:var(--text-h)" href="{{ route('admin.smart-instagram.inbox', ['source' => $row['key']]) }}">{{ $row['label'] }}</a></td>
              <td class="si-num">{{ Ui::n($row['messages']) }}</td><td class="si-num">{{ Ui::n($row['conversations']) }}</td>
              <td class="si-num">{{ Ui::n($row['deals']) }}</td><td class="si-num">{{ Ui::n($row['won']) }}</td>
              <td class="si-num si-td-strong">{{ Ui::money($row['value']) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>

  <div class="si-grid-even">
    <section class="content-card si-panel is-flush">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-people-group"></i> عملکرد تیم</div></div>
      <div class="si-table-wrap" style="margin-top:10px">
        <table class="table-pro">
          <thead><tr><th>فروشنده</th><th>پاسخ ارسالی</th><th>فرصت برنده</th><th>فروش (تومان)</th></tr></thead>
          <tbody>
            @forelse($report['team'] as $row)
              <tr><td class="si-td-strong">{{ $row['name'] }}</td><td class="si-num">{{ Ui::n($row['replies']) }}</td><td class="si-num">{{ Ui::n($row['won']) }}</td><td class="si-num">{{ Ui::money($row['value']) }}</td></tr>
            @empty
              <tr><td colspan="4" class="si-table-empty">هنوز پاسخی از تیم ثبت نشده.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-robot"></i> کیفیت هوش مصنوعی</div><a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'learning']) }}" class="si-link">یادگیری <i class="fa-solid fa-angle-left"></i></a></div>
      <dl class="si-kv">
        <dt>نرخ پذیرش پیشنهاد</dt><dd>{{ Ui::pct($k['ai_acceptance_pct']) }}</dd>
        <dt>پذیرفته با ویرایش</dt><dd>{{ Ui::pct($k['ai_edit_pct']) }}</dd>
        <dt>پذیرفته / ویرایش / رد</dt><dd>{{ Ui::n($report['ai']['accepted']) }} / {{ Ui::n($report['ai']['edited']) }} / {{ Ui::n($report['ai']['rejected']) }}</dd>
        <dt>اجرای تحلیل</dt><dd>{{ Ui::n($report['ai']['runs']) }} @if($report['ai']['failed'])<span style="color:var(--danger)">({{ Ui::n($report['ai']['failed']) }} ناموفق)</span>@endif</dd>
        <dt>میانگین اطمینان</dt><dd>{{ $report['ai']['avg_confidence'] ? Ui::pct($report['ai']['avg_confidence'] * 100) : '—' }}</dd>
        <dt>توکن مصرفی</dt><dd>{{ Ui::n($report['ai']['tokens']) }}</dd>
        <dt>هزینه (دلار)</dt><dd class="si-ltr">{{ $report['ai']['cost'] ? '$'.number_format($report['ai']['cost'], 4) : '—' }}</dd>
      </dl>
      @if($report['gaps'])
        <hr class="si-divider">
        <div class="si-label" style="margin-bottom:6px">سؤال‌های بدون دانش</div>
        <ul class="si-ul">@foreach(array_slice($report['gaps'], 0, 5) as $gap)<li>{{ $gap['label'] }} <span class="si-muted">({{ Ui::n($gap['total']) }})</span></li>@endforeach</ul>
      @endif
    </section>
  </div>

  <div class="si-grid-even">
    <section class="content-card si-panel is-flush">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-diagram-project"></i> اتومیشن‌ها</div></div>
      <div class="si-table-wrap" style="margin-top:10px">
        <table class="table-pro">
          <thead><tr><th>قانون</th><th>وضعیت</th><th>اجرا</th><th>موفق</th><th>ناموفق</th></tr></thead>
          <tbody>
            @forelse($report['automations'] as $rule)
              <tr><td><a class="si-link" href="{{ route('admin.smart-instagram.automations.show', $rule['id']) }}">{{ $rule['name'] }}</a></td><td><span class="badge-pro badge-{{ Ui::statusTone($rule['status']) }}">{{ \App\Http\Controllers\Admin\SmartInstagram\AutomationController::STATUSES[$rule['status']] ?? $rule['status'] }}</span></td><td class="si-num">{{ Ui::n($rule['runs_count']) }}</td><td class="si-num">{{ Ui::n($rule['success_count']) }}</td><td class="si-num">{{ Ui::n($rule['failure_count']) }}</td></tr>
            @empty
              <tr><td colspan="5" class="si-table-empty">قانونی ثبت نشده.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-user-xmark"></i> دلایل از دست رفتن مشتری</div></div>
      @php($maxLost = max(1, ...array_values($report['lost_reasons'] ?: [1])))
      @forelse($report['lost_reasons'] as $reason => $total)
        <div class="si-hbar"><span class="si-hbar-label" title="{{ $reason }}">{{ $reason }}</span><span class="si-hbar-track"><span class="si-hbar-fill" style="width:{{ round($total * 100 / $maxLost) }}%;background:var(--danger)"></span></span><span class="si-hbar-value si-num">{{ Ui::n($total) }}</span></div>
      @empty
        <div class="si-table-empty">دلیلی ثبت نشده؛ هنگام انتقال فرصت به «از دست‌رفته» دلیل را بنویسید.</div>
      @endforelse
    </section>
  </div>
@endsection
