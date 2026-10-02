@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'قیف فروش و وظایف';
  $siSubtitle = 'مسیر هر مشتری از «ورودی جدید» تا «خرید» و کارهایی که برای حرکتش لازم است.';
  $openTotal = $summary->sum('total');
  $openValue = $summary->sum('value');
  $taskTypes = ['first_reply' => 'پاسخ اولیه', 'send_sample' => 'ارسال نمونه', 'send_link' => 'ارسال لینک', 'follow_up' => 'پیگیری زمان‌دار', 'call' => 'تماس انسانی', 'request_photo' => 'درخواست عکس محصول', 'record_result' => 'ثبت نتیجه', 'repurchase' => 'درخواست خرید مجدد'];
@endphp

@section('si-actions')
  <div class="si-chips">
    <a href="{{ route('admin.smart-instagram.pipeline') }}" class="chip-filter {{ $tab === 'board' ? 'active' : '' }}"><i class="fa-solid fa-table-columns"></i> قیف</a>
    <a href="{{ route('admin.smart-instagram.pipeline', ['tab' => 'tasks']) }}" class="chip-filter {{ $tab === 'tasks' ? 'active' : '' }}"><i class="fa-solid fa-list-check"></i> وظایف @if($taskCounts['overdue'])<span class="chip-count" style="color:var(--danger)">{{ Ui::n($taskCounts['overdue']) }}</span>@endif</a>
  </div>
@endsection

@section('si-page')
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-handshake', 'tone' => 'primary', 'value' => Ui::n($openTotal), 'label' => 'فرصت باز'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-coins', 'tone' => 'success', 'value' => Ui::money($openValue), 'label' => 'ارزش قیف باز (تومان)'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-calendar-day', 'tone' => 'info', 'value' => Ui::n($taskCounts['today']), 'label' => 'وظایف امروز', 'href' => route('admin.smart-instagram.pipeline', ['tab' => 'tasks', 'due' => 'today'])])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-triangle-exclamation', 'tone' => $taskCounts['overdue'] ? 'danger' : 'success', 'value' => Ui::n($taskCounts['overdue']), 'label' => 'وظایف عقب‌افتاده', 'href' => route('admin.smart-instagram.pipeline', ['tab' => 'tasks', 'due' => 'overdue'])])
  </div>

  @if($tab === 'board')
    <div class="si-kanban" aria-label="ستون‌های قیف فروش">
      @foreach($stages as $stageKey => $stageLabel)
        @php($stageDeals = $deals->get($stageKey, collect()))
        <section class="si-kanban-col">
          <div class="si-kanban-head">
            <span class="si-kanban-title">{{ $stageLabel }}</span>
            <span class="si-status-strip" style="margin:0"><span class="badge-pro badge-neutral">{{ Ui::n($stageDeals->count()) }}</span>@if(($summary[$stageKey]->value ?? 0) > 0)<span class="badge-pro badge-success">{{ Ui::money($summary[$stageKey]->value) }}</span>@endif</span>
          </div>
          <div class="si-kanban-body">
            @forelse($stageDeals as $deal)
              <article class="si-deal">
                <div class="si-deal-title">{{ $deal->title }}</div>
                <div class="si-deal-meta">
                  <a class="si-link" href="{{ route('admin.smart-instagram.contacts.show', $deal->contact_id) }}">{{ $deal->contact?->label() }}</a>
                  <span class="si-num">{{ $deal->value_toman ? Ui::money($deal->value_toman) : '—' }}</span>
                </div>
                <div class="si-deal-meta"><span>{{ $deal->owner?->name ?? 'بدون مسئول' }}</span><span>{{ Ui::ago($deal->stage_changed_at ?? $deal->updated_at) }}</span></div>
                <form method="POST" action="{{ route('admin.smart-instagram.deals.update', $deal) }}">@csrf @method('PATCH')
                  <select name="stage" class="input-pro" aria-label="انتقال به مرحله" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                    @foreach($stages as $k => $l)<option value="{{ $k }}" @selected($k === $deal->stage)>{{ $l }}</option>@endforeach
                  </select>
                </form>
                @if($deal->stage === 'lost')
                  <form method="POST" action="{{ route('admin.smart-instagram.deals.update', $deal) }}">@csrf @method('PATCH')
                    <input name="lost_reason" class="input-pro" style="height:30px;font-size:11px" maxlength="190" list="si-lost-reasons" value="{{ $deal->lost_reason }}" placeholder="دلیل از دست رفتن">
                    <button class="icon-action-btn" title="ثبت دلیل"><i class="fa-solid fa-check"></i></button>
                  </form>
                @endif
              </article>
            @empty
              <div class="si-muted" style="text-align:center;padding:14px 4px">خالی</div>
            @endforelse
          </div>
        </section>
      @endforeach
    </div>
    <datalist id="si-lost-reasons"><option value="قیمت بالا"><option value="پاسخ دیرهنگام"><option value="انتخاب رقیب"><option value="نیاز نداشت"><option value="بی‌پاسخ ماند"></datalist>
    <p class="si-help" style="margin-top:8px">فرصت تازه را از «صندوق گفتگو» یا اتومیشن بسازید؛ با تغییر مرحله به «برنده»، فروش به محتوا و منبع ورود همان مشتری منتسب می‌شود.</p>
  @else
    <div class="si-grid-2">
      <section class="content-card si-panel is-flush">
        <div class="si-panel-head">
          <div class="si-chips">
            @foreach(['open' => 'همه‌ی باز', 'today' => 'امروز', 'overdue' => 'عقب‌افتاده', 'upcoming' => 'آینده', 'done' => 'انجام‌شده'] as $k => $l)
              <a href="{{ route('admin.smart-instagram.pipeline', ['tab' => 'tasks', 'due' => $k === 'open' ? null : $k]) }}" class="chip-filter {{ $taskFilter === $k ? 'active' : '' }}">{{ $l }}</a>
            @endforeach
          </div>
        </div>
        <div class="si-table-wrap" style="margin-top:10px">
          <table class="table-pro">
            <thead><tr><th>وظیفه</th><th>مخاطب</th><th>نوع</th><th>موعد</th><th>مسئول</th><th>اقدام</th></tr></thead>
            <tbody>
              @forelse($tasks as $task)
                <tr>
                  <td class="si-td-strong">{{ $task->title }} @if($task->created_via === 'automation')<span class="badge-pro badge-info" style="margin-inline-start:4px">خودکار</span>@endif</td>
                  <td>@if($task->contact)<a class="si-link" href="{{ route('admin.smart-instagram.contacts.show', $task->contact) }}">{{ $task->contact->label() }}</a>@else — @endif</td>
                  <td>{{ $taskTypes[$task->type] ?? $task->type }}</td>
                  <td><span class="{{ $task->isOverdue() ? 'badge-pro badge-danger' : '' }}">{{ $task->due_at ? Ui::date($task->due_at, true) : '—' }}</span></td>
                  <td>{{ $task->assignee?->name ?? '—' }}</td>
                  <td>
                    @if($task->status === 'open')
                      <form method="POST" action="{{ route('admin.smart-instagram.tasks.update', $task) }}" style="display:flex;gap:4px;justify-content:flex-end">@csrf @method('PATCH')
                        <input type="hidden" name="status" value="done">
                        <button class="icon-action-btn" title="انجام شد"><i class="fa-solid fa-check"></i></button>
                      </form>
                    @else
                      <span class="badge-pro badge-success">انجام شد</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="si-table-empty">وظیفه‌ای در این فیلتر نیست.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @include('admin.smart-instagram.partials.pagination', ['paginator' => $tasks])
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-plus"></i> وظیفه‌ی تازه</div></div>
        <form method="POST" action="{{ route('admin.smart-instagram.tasks.store') }}" class="si-form is-1">@csrf
          <div class="si-field"><label for="t-title">عنوان</label><input id="t-title" name="title" class="input-pro" required maxlength="190" placeholder="مثلاً: پیگیری لیدهای پوشاک"></div>
          <div class="si-field"><label for="t-type">نوع</label><select id="t-type" name="type" class="input-pro">@foreach($taskTypes as $k => $l)<option value="{{ $k }}" @selected($k === 'follow_up')>{{ $l }}</option>@endforeach</select></div>
          <div class="si-field"><label for="t-due">موعد</label><input id="t-due" type="datetime-local" name="due_at" class="input-pro" value="{{ now('Asia/Tehran')->addDay()->format('Y-m-d\TH:i') }}"></div>
          <div class="si-field"><label for="t-assignee">مسئول</label><select id="t-assignee" name="assigned_admin_id" class="input-pro"><option value="">خودم</option>@foreach($admins as $admin)<option value="{{ $admin->id }}">{{ $admin->name }}</option>@endforeach</select></div>
          <div class="si-form-actions"><button class="btn-pro btn-pro-primary">ثبت وظیفه</button></div>
        </form>
      </section>
    </div>
  @endif
@endsection
