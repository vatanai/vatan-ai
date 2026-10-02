@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'داشبورد اینستاگرام';
  $siSubtitle = 'امروز چه چیزی اقدام لازم دارد؟ گفتگوهای بی‌پاسخ، لیدهای داغ، وظایف و سلامت اتصال در یک نگاه.';
  $c = $cards;
  $channelTone = $channel ? Ui::statusTone($channel->status) : 'warning';
@endphp

@section('si-actions')
  <a href="{{ route('admin.smart-instagram.inbox', ['filter' => 'unanswered']) }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-inbox text-[11px]"></i> صندوق بی‌پاسخ</a>
  <a href="{{ route('admin.smart-instagram.reports') }}" class="btn-pro btn-pro-ghost"><i class="fa-solid fa-chart-column text-[11px]"></i> گزارش‌ها</a>
@endsection

@section('si-page')
  <div class="si-status-strip">
    <span class="badge-pro badge-{{ $channelTone }}"><i class="fa-solid fa-circle"></i> اتصال: {{ $channel ? Ui::label('channel', $channel->status).($channel->username ? ' · @'.$channel->username : '') : 'هنوز کانالی ثبت نشده' }}</span>
    <span class="badge-pro {{ $outboundEnabled ? 'badge-success' : 'badge-neutral' }}"><i class="fa-solid fa-circle"></i> ارسال خودکار: {{ $outboundEnabled ? 'روشن' : 'خاموش (فقط ثبت و پیشنهاد)' }}</span>
    <span class="badge-pro {{ $aiEnabled ? 'badge-primary' : 'badge-neutral' }}"><i class="fa-solid fa-circle"></i> دستیار هوشمند: {{ $aiEnabled ? 'پیشنهاد پاسخ با تأیید انسان' : 'خاموش' }}</span>
    @if($channel?->last_event_at)<span class="badge-pro badge-neutral"><i class="fa-solid fa-circle"></i> آخرین رویداد: {{ Ui::ago($channel->last_event_at) }}</span>@endif
  </div>

  <nav class="si-quick" aria-label="دسترسی سریع">
    <a href="{{ route('admin.smart-instagram.inbox') }}"><span class="si-quick-icon"><i class="fa-solid fa-comments"></i></span><span>صندوق گفتگو</span></a>
    <a href="{{ route('admin.smart-instagram.pipeline') }}"><span class="si-quick-icon"><i class="fa-solid fa-filter-circle-dollar"></i></span><span>قیف فروش</span></a>
    <a href="{{ route('admin.smart-instagram.automations.create', ['template' => 'price_comment']) }}"><span class="si-quick-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span><span>سناریوی «قیمت»</span></a>
    <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'sources']) }}"><span class="si-quick-icon"><i class="fa-solid fa-book-open"></i></span><span>افزودن دانش</span></a>
    <a href="{{ route('admin.smart-instagram.knowledge.index', ['tab' => 'playground']) }}"><span class="si-quick-icon"><i class="fa-solid fa-flask"></i></span><span>آزمایش دستیار</span></a>
  </nav>

  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-inbox', 'tone' => 'warning', 'value' => Ui::n($c['unanswered']), 'label' => 'گفتگوی بی‌پاسخ', 'hint' => $c['attention'] ? Ui::n($c['attention']).' مورد نیازمند انسان' : 'موردی نیازمند انسان نیست', 'hintTone' => $c['attention'] ? 'danger' : null, 'href' => route('admin.smart-instagram.inbox', ['filter' => 'unanswered'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-fire', 'tone' => 'danger', 'value' => Ui::n($c['hot_leads']), 'label' => 'لید داغ', 'hint' => 'امتیاز ۷۰ به بالا', 'href' => route('admin.smart-instagram.contacts.index', ['status' => 'hot'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-list-check', 'tone' => 'info', 'value' => Ui::n($c['tasks_today']), 'label' => 'وظایف امروز', 'hint' => $c['tasks_overdue'] ? Ui::n($c['tasks_overdue']).' مورد عقب‌افتاده' : 'بدون تأخیر', 'hintTone' => $c['tasks_overdue'] ? 'danger' : 'success', 'href' => route('admin.smart-instagram.pipeline', ['tab' => 'tasks', 'due' => 'today'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-sack-dollar', 'tone' => 'success', 'value' => Ui::money($c['won_value_30d']), 'label' => 'فروش منتسب (۳۰ روز، تومان)', 'hint' => Ui::n($c['won_count_30d']).' فرصت برنده', 'href' => route('admin.smart-instagram.reports')])
  </div>
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-stopwatch', 'tone' => 'primary', 'value' => Ui::duration($c['median_first_response']), 'label' => 'میانه‌ی زمان اولین پاسخ (۷ روز)', 'tip' => 'از اولین پیام مشتری تا اولین پاسخ ثبت‌شده'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-robot', 'tone' => 'info', 'value' => Ui::n($c['pending_suggestions']), 'label' => 'پیشنهاد هوشمند در انتظار', 'href' => route('admin.smart-instagram.inbox', ['filter' => 'ai'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-diagram-project', 'tone' => $c['automations_failing'] ? 'danger' : 'primary', 'value' => Ui::n($c['automations_active']), 'label' => 'اتومیشن فعال', 'hint' => ($c['automations_test'] ? Ui::n($c['automations_test']).' آزمایشی' : '').($c['automations_failing'] ? ' · '.Ui::n($c['automations_failing']).' دارای خطا' : ''), 'hintTone' => $c['automations_failing'] ? 'danger' : null, 'href' => route('admin.smart-instagram.automations.index')])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-paper-plane', 'tone' => $c['outbound_failed'] ? 'danger' : 'success', 'value' => Ui::n($c['outbound_failed']), 'label' => 'ارسال ناموفق (۷ روز)', 'href' => route('admin.smart-instagram.health', ['tab' => 'outbound', 'status' => 'failed'])])
  </div>

  <div class="si-grid-2">
    <section class="content-card si-panel">
      <div class="si-panel-head">
        <div><div class="si-panel-title"><i class="fa-solid fa-bolt"></i> گفتگوهای نیازمند اقدام</div><div class="si-panel-sub">مرتب‌شده بر اساس نیاز به انسان، اولویت و زمان انتظار</div></div>
        <a href="{{ route('admin.smart-instagram.inbox', ['filter' => 'unanswered']) }}" class="si-link">همه <i class="fa-solid fa-angle-left"></i></a>
      </div>
      @if($needs_action->isEmpty())
        <div class="empty-state" style="padding:30px 10px"><div class="empty-state-icon"><i class="fa-solid fa-mug-hot"></i></div><div class="empty-state-title">همه‌ی گفتگوها پاسخ گرفته‌اند</div><div class="empty-state-desc">پیام تازه‌ای منتظر پاسخ نیست.</div></div>
      @else
        <div class="si-rows">@foreach($needs_action as $conversation) @include('admin.smart-instagram.partials.conv-row') @endforeach</div>
      @endif
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head">
        <div class="si-panel-title"><i class="fa-solid fa-list-check"></i> وظایف پیش‌رو</div>
        <a href="{{ route('admin.smart-instagram.pipeline', ['tab' => 'tasks']) }}" class="si-link">همه <i class="fa-solid fa-angle-left"></i></a>
      </div>
      @forelse($tasks as $task)
        <div class="si-row">
          <span class="si-dot {{ $task->isOverdue() ? 'is-danger' : 'is-info' }}"></span>
          <div class="si-row-main">
            <div class="si-row-title">{{ $task->title }}</div>
            <div class="si-row-sub">{{ $task->contact?->label() ?? 'بدون مخاطب' }}</div>
          </div>
          <div class="si-row-meta"><span class="{{ $task->isOverdue() ? 'badge-pro badge-danger' : '' }}">{{ $task->due_at ? Ui::date($task->due_at, true) : 'بدون موعد' }}</span></div>
        </div>
      @empty
        <div class="si-table-empty">وظیفه‌ی بازی ندارید.</div>
      @endforelse
    </section>
  </div>

  <div class="si-grid-3">
    <section class="content-card si-panel">
      <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-comment-dots"></i> نیت‌های پرتکرار (۷ روز)</div></div>
      @php($siMax = max(1, ...array_values($top_intents ?: [1])))
      @forelse($top_intents as $intent => $total)
        <div class="si-hbar"><span class="si-hbar-label">{{ Ui::label('intent', $intent) }}</span><span class="si-hbar-track"><span class="si-hbar-fill" style="width:{{ round($total * 100 / $siMax) }}%"></span></span><span class="si-hbar-value si-num">{{ Ui::n($total) }}</span></div>
      @empty
        <div class="si-table-empty">بعد از اولین تحلیل‌های هوشمند، موضوعات پرتکرار این‌جا دیده می‌شوند.</div>
      @endforelse
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head">
        <div class="si-panel-title"><i class="fa-solid fa-user-clock"></i> مشتریان در خطر ریزش</div>
        <a href="{{ route('admin.smart-instagram.contacts.index', ['status' => 'hot']) }}" class="si-link">لیدها <i class="fa-solid fa-angle-left"></i></a>
      </div>
      @forelse($at_risk as $conversation)
        @include('admin.smart-instagram.partials.conv-row')
      @empty
        <div class="si-table-empty">لید واجدشرایطی بدون پیگیری نمانده است.</div>
      @endforelse
    </section>

    <section class="content-card si-panel">
      <div class="si-panel-head">
        <div class="si-panel-title"><i class="fa-solid fa-heart-pulse"></i> هشدارهای اخیر</div>
        <a href="{{ route('admin.smart-instagram.health') }}" class="si-link">سلامت <i class="fa-solid fa-angle-left"></i></a>
      </div>
      @forelse($alerts as $log)
        <div class="si-row">
          <span class="si-dot is-{{ $log->level === 'error' ? 'danger' : 'warning' }}"></span>
          <div class="si-row-main"><div class="si-row-title" title="{{ $log->message }}">{{ $log->message }}</div><div class="si-row-sub">{{ Ui::ago($log->created_at) }}</div></div>
        </div>
      @empty
        <div class="si-table-empty"><i class="fa-solid fa-circle-check" style="color:var(--success)"></i> خطایی ثبت نشده است.</div>
      @endforelse
    </section>
  </div>

  <section class="content-card si-panel is-flush">
    <div class="si-panel-head">
      <div><div class="si-panel-title"><i class="fa-solid fa-photo-film"></i> محتوای پربازده (۳۰ روز)</div><div class="si-panel-sub">کدام پست، ریلز، استوری یا تبلیغ گفتگو و فروش ساخت</div></div>
      <a href="{{ route('admin.smart-instagram.content') }}" class="si-link">همه‌ی محتوا <i class="fa-solid fa-angle-left"></i></a>
    </div>
    <div class="si-table-wrap" style="margin-top:12px">
      <table class="table-pro">
        <thead><tr><th>محتوا</th><th>نوع</th><th>تعامل</th><th>گفتگو</th><th>فرصت</th><th>فروش (تومان)</th></tr></thead>
        <tbody>
          @forelse($top_content as $row)
            <tr>
              <td class="si-mono">{{ \Illuminate\Support\Str::limit($row['ref'], 26) }}</td>
              <td><span class="si-source">{{ Ui::label('source', $row['type']) }}</span></td>
              <td class="si-num">{{ Ui::n($row['interactions']) }}</td>
              <td class="si-num">{{ Ui::n($row['conversations']) }}</td>
              <td class="si-num">{{ Ui::n($row['deals']) }}</td>
              <td class="si-num si-td-strong">{{ Ui::money($row['value']) }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="si-table-empty">هنوز تعاملی که به محتوای مشخصی وصل باشد ثبت نشده است.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection
