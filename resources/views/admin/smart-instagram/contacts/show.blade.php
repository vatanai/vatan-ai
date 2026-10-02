@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'پرونده‌ی '.$contact->label();
  $openDeals = $contact->deals->where('outcome', 'open');
  $wonValue = $contact->deals->where('outcome', 'won')->sum('value_toman');
@endphp

@section('si-head')
  <div class="si-head">
    <div style="display:flex;gap:12px;align-items:center;min-width:0">
      <div class="si-avatar is-lg">{{ $contact->initials() }}</div>
      <div style="min-width:0">
        <h1 class="si-head-title">{{ $contact->label() }}</h1>
        <div class="si-status-strip" style="margin:4px 0 0">
          @if($contact->username)<span class="badge-pro badge-neutral si-ltr">{{ '@'.$contact->username }}</span>@endif
          <span class="badge-pro badge-{{ ['hot' => 'danger', 'customer' => 'success', 'qualified' => 'primary', 'lost' => 'warning'][$contact->lead_status] ?? 'info' }}">{{ Ui::label('lead', $contact->lead_status) }}</span>
          <span class="badge-pro badge-neutral">ورود از {{ Ui::label('source', $contact->first_source) }}</span>
          @if($contact->opted_out)<span class="badge-pro badge-danger">در فهرست توقف</span>@endif
        </div>
      </div>
    </div>
    <div class="si-head-actions">
      @if($conversations->first())<a href="{{ route('admin.smart-instagram.inbox.show', $conversations->first()) }}" class="btn-pro btn-pro-primary"><i class="fa-solid fa-comments text-[11px]"></i> باز کردن گفتگو</a>@endif
      <a href="{{ route('admin.smart-instagram.contacts.index') }}" class="btn-pro btn-pro-ghost">بازگشت به فهرست</a>
    </div>
  </div>
@endsection

@section('si-page')
  <div class="si-stats">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-gauge-high', 'tone' => 'primary', 'value' => Ui::n($contact->lead_score), 'label' => 'امتیاز لید', 'tip' => $contact->score_reason ?: 'امتیاز با هر تحلیل هوشمند به‌روز می‌شود'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-handshake', 'tone' => 'info', 'value' => Ui::n($openDeals->count()), 'label' => 'فرصت باز'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-sack-dollar', 'tone' => 'success', 'value' => Ui::money($wonValue), 'label' => 'خرید ثبت‌شده (تومان)'])
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-clock', 'tone' => 'warning', 'value' => Ui::ago($contact->last_interaction_at), 'label' => 'آخرین تعامل'])
  </div>

  <div class="si-grid-2">
    <div class="si-stack">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-timeline"></i> تایم‌لاین تعامل</div><span class="si-muted">۶۰ رویداد آخر</span></div>
        <div class="si-rows">
          @forelse($timeline as $message)
            <div class="si-row" style="align-items:flex-start">
              <span class="si-dot is-{{ $message->is_internal_note ? 'warning' : ($message->direction === 'in' ? 'info' : 'success') }}" style="margin-top:6px"></span>
              <div class="si-row-main">
                <div class="si-muted">
                  {{ $message->is_internal_note ? 'یادداشت داخلی' : ($message->direction === 'in' ? 'مشتری' : Ui::label('origin', $message->sent_by)) }}
                  · {{ Ui::label('source', $message->source_type) }} · {{ Ui::date($message->occurred_at, true) }}
                </div>
                <div class="si-text" style="font-size:12.5px;color:var(--text-h);margin-top:3px;line-height:1.9">{{ $message->body ?: ($message->attachments->isNotEmpty() ? '[پیوست]' : '—') }}</div>
              </div>
            </div>
          @empty
            <div class="si-table-empty">تعاملی ثبت نشده.</div>
          @endforelse
        </div>
      </section>

      <section class="content-card si-panel is-flush">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-filter-circle-dollar"></i> فرصت‌های فروش</div></div>
        <div class="si-table-wrap" style="margin-top:10px">
          <table class="table-pro">
            <thead><tr><th>عنوان</th><th>مرحله</th><th>ارزش (تومان)</th><th>منبع</th><th>آخرین تغییر</th></tr></thead>
            <tbody>
              @forelse($contact->deals as $deal)
                <tr><td class="si-td-strong">{{ $deal->title }}</td><td><span class="badge-pro badge-{{ Ui::statusTone($deal->outcome === 'open' ? 'info' : $deal->outcome) }}">{{ Ui::label('pipeline', $deal->stage) }}</span></td><td class="si-num">{{ Ui::money($deal->value_toman) }}</td><td>{{ Ui::label('source', $deal->source_type) }}</td><td class="si-muted">{{ Ui::ago($deal->updated_at) }}</td></tr>
              @empty
                <tr><td colspan="5" class="si-table-empty">فرصتی ثبت نشده.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <div class="si-stack">
      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-id-card"></i> مشخصات</div></div>
        @if($canEdit)
          <form method="POST" action="{{ route('admin.smart-instagram.contacts.update', $contact) }}" class="si-form">
            @csrf @method('PATCH')
            <div class="si-field"><label for="c-name">نام نمایشی</label><input id="c-name" name="display_name" class="input-pro" value="{{ old('display_name', $contact->display_name) }}" maxlength="120"></div>
            <div class="si-field"><label for="c-phone">شماره تماس (با رضایت)</label><input id="c-phone" name="phone" class="input-pro si-ltr" value="{{ old('phone', $contact->phone) }}" maxlength="40"></div>
            <div class="si-field"><label for="c-industry">صنف</label><input id="c-industry" name="industry" class="input-pro" value="{{ old('industry', $contact->industry) }}" maxlength="60" list="si-industries"></div>
            <datalist id="si-industries"><option value="پوشاک"><option value="کیف و کفش"><option value="زیبایی و آرایشی"><option value="طلا و جواهر"><option value="سایر"></datalist>
            <div class="si-field"><label for="c-city">شهر</label><input id="c-city" name="city" class="input-pro" value="{{ old('city', $contact->city) }}" maxlength="80"></div>
            <div class="si-field"><label for="c-status">وضعیت لید</label><select id="c-status" name="lead_status" class="input-pro">@foreach(config('smart_instagram.lead_statuses') as $k => $l)<option value="{{ $k }}" @selected($contact->lead_status === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="si-field"><label for="c-score">امتیاز (۰ تا ۱۰۰)</label><input id="c-score" type="number" min="0" max="100" name="lead_score" class="input-pro" value="{{ $contact->lead_score }}"></div>
            <div class="si-field is-full"><label for="c-assignee">مسئول</label><select id="c-assignee" name="assigned_admin_id" class="input-pro"><option value="">بدون مسئول</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected((int) $contact->assigned_admin_id === $admin->id)>{{ $admin->name }}</option>@endforeach</select></div>
            <div class="si-field is-full" style="flex-direction:row;gap:16px;flex-wrap:wrap">
              <label class="si-check"><input type="checkbox" name="consent_contact" value="1" @checked($contact->consent_contact)> رضایت برای تماس</label>
              <label class="si-check"><input type="checkbox" name="opted_out" value="1" @checked($contact->opted_out)> فهرست توقف (بدون پیام خودکار)</label>
            </div>
            <div class="si-form-actions"><button class="btn-pro btn-pro-primary">ذخیره‌ی پرونده</button></div>
          </form>
        @else
          <dl class="si-kv"><dt>صنف</dt><dd>{{ $contact->industry ?: '—' }}</dd><dt>شهر</dt><dd>{{ $contact->city ?: '—' }}</dd><dt>مسئول</dt><dd>{{ $contact->assignee?->name ?? '—' }}</dd></dl>
        @endif
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-tags"></i> برچسب‌ها</div></div>
        <div class="si-conv-tags" style="margin-bottom:10px">
          @forelse($contact->tags as $tag)
            <span class="badge-pro badge-neutral">{{ $tag->name }}
              @if($canEdit)<form method="POST" action="{{ route('admin.smart-instagram.contacts.tags', $contact) }}" style="display:inline">@csrf<input type="hidden" name="tag" value="{{ $tag->name }}"><input type="hidden" name="remove" value="1"><button style="background:none;border:0;color:inherit;cursor:pointer;padding:0 2px" aria-label="حذف برچسب"><i class="fa-solid fa-xmark" style="font-size:9px"></i></button></form>@endif
            </span>
          @empty<span class="si-muted">بدون برچسب</span>@endforelse
        </div>
        @if($canEdit)
          <form method="POST" action="{{ route('admin.smart-instagram.contacts.tags', $contact) }}" style="display:flex;gap:6px">@csrf
            <input name="tag" class="input-pro" placeholder="برچسب" list="si-all-tags" required maxlength="60">
            <datalist id="si-all-tags">@foreach($allTags as $t)<option value="{{ $t }}">@endforeach</datalist>
            <button class="btn-pro btn-pro-ghost">افزودن</button>
          </form>
        @endif
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-note-sticky"></i> یادداشت‌ها</div></div>
        @if($canEdit)
          <form method="POST" action="{{ route('admin.smart-instagram.contacts.notes', $contact) }}" class="si-form is-1" style="margin-bottom:10px">@csrf
            <textarea name="body" class="input-pro" rows="2" style="min-height:64px" placeholder="یادداشت داخلی، وعده‌ها، نکات مهم…" required maxlength="2000"></textarea>
            <div><button class="btn-pro btn-pro-ghost">ثبت یادداشت</button></div>
          </form>
        @endif
        @forelse($contact->notes as $note)
          <div class="si-row" style="align-items:flex-start"><div class="si-row-main"><div class="si-text" style="font-size:12px;color:var(--text-h);line-height:1.9">{{ $note->body }}</div><div class="si-row-sub">{{ $note->admin?->name ?? 'سیستم' }} · {{ Ui::ago($note->created_at) }}</div></div></div>
        @empty
          <div class="si-muted">یادداشتی ثبت نشده.</div>
        @endforelse
      </section>

      <section class="content-card si-panel">
        <div class="si-panel-head"><div class="si-panel-title"><i class="fa-solid fa-list-check"></i> وظایف</div></div>
        @forelse($contact->tasks as $task)
          <div class="si-row"><span class="si-dot is-{{ $task->status === 'done' ? 'success' : ($task->isOverdue() ? 'danger' : 'info') }}"></span><div class="si-row-main"><div class="si-row-title">{{ $task->title }}</div><div class="si-row-sub">{{ $task->status === 'done' ? 'انجام شد' : ($task->due_at ? Ui::date($task->due_at, true) : 'بدون موعد') }} · {{ $task->assignee?->name ?? '—' }}</div></div></div>
        @empty
          <div class="si-muted">وظیفه‌ای ثبت نشده.</div>
        @endforelse
      </section>

      @if($canErase)
        <section class="content-card si-panel" style="border-color:var(--danger-m)">
          <div class="si-panel-title" style="color:var(--danger)"><i class="fa-solid fa-user-slash" style="color:var(--danger)"></i> حذف کامل داده‌ی مخاطب</div>
          <p class="si-help" style="margin:6px 0 10px">پیام‌ها، فایل‌ها، متن صوت، یادداشت‌ها، برچسب‌ها، فرصت‌ها و وظایف این مخاطب برای همیشه پاک می‌شوند (حریم خصوصی).</p>
          <form method="POST" action="{{ route('admin.smart-instagram.contacts.destroy', $contact) }}" data-confirm="تمام داده‌ی این مخاطب برای همیشه حذف شود؟">@csrf @method('DELETE')
            <button class="btn-pro btn-pro-danger"><i class="fa-solid fa-trash text-[11px]"></i> حذف کامل</button>
          </form>
        </section>
      @endif
    </div>
  </div>
@endsection
