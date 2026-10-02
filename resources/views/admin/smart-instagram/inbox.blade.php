@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'صندوق گفتگو';
  $contact = $selected?->contact;
  $listParams = array_filter(['filter' => $filter !== 'open' ? $filter : null, 'q' => $search ?: null, 'source' => $source ?: null]);
  $lastMessageId = (int) ($timeline->last()?->id ?? 0);
@endphp

@section('si-head')<h1 class="si-head-title" style="margin-bottom:12px">صندوق گفتگو</h1>@endsection

@section('si-page')
<div class="si-inbox {{ $selected ? 'has-selected' : '' }}"
     data-poll="{{ route('admin.smart-instagram.inbox.poll', $selected ? ['c' => $selected->id] : []) }}"
     data-latest="{{ $latestStamp }}" data-last-message="{{ $lastMessageId }}">

  {{-- ستون اول: فیلتر و لیست گفتگو --}}
  <aside class="si-inbox-col si-inbox-list" aria-label="فهرست گفتگوها">
    <div class="si-inbox-list-head">
      <form method="GET" action="{{ route('admin.smart-instagram.inbox') }}" class="si-search" role="search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="q" value="{{ $search }}" class="input-pro" placeholder="نام، نام کاربری، متن، برچسب یا شماره…" aria-label="جست‌وجو در گفتگوها">
        @if($filter !== 'open')<input type="hidden" name="filter" value="{{ $filter }}">@endif
        @if($source)<input type="hidden" name="source" value="{{ $source }}">@endif
      </form>
      <div class="si-chips">
        @foreach($filters as $key => $label)
          <a href="{{ route('admin.smart-instagram.inbox', array_filter(['filter' => $key !== 'open' ? $key : null, 'q' => $search ?: null, 'source' => $source ?: null])) }}" class="chip-filter {{ $filter === $key ? 'active' : '' }}">
            {{ $label }}@if(isset($counts[$key]) && $counts[$key] > 0)<span class="chip-count">{{ Ui::n($counts[$key]) }}</span>@endif
          </a>
        @endforeach
      </div>
      <div class="si-chips">
        <a href="{{ route('admin.smart-instagram.inbox', array_filter(['filter' => $filter !== 'open' ? $filter : null, 'q' => $search ?: null])) }}" class="chip-filter {{ $source === '' ? 'active' : '' }}">همه‌ی منابع</a>
        @foreach(config('smart_instagram.sources') as $key => $label)
          <a href="{{ route('admin.smart-instagram.inbox', array_filter(['filter' => $filter !== 'open' ? $filter : null, 'q' => $search ?: null, 'source' => $key])) }}" class="chip-filter {{ $source === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
      </div>
    </div>

    <div class="si-inbox-scroll">
      @forelse($list as $item)
        @php($itemContact = $item->contact)
        <a href="{{ route('admin.smart-instagram.inbox.show', ['conversation' => $item->id] + $listParams) }}" class="si-conv {{ $selected && $selected->id === $item->id ? 'is-active' : '' }}">
          <div class="si-avatar">{{ $itemContact?->initials() ?? '؟' }}</div>
          <div class="si-conv-main">
            <div class="si-conv-top">
              <span class="si-conv-name">{{ $itemContact?->label() ?? 'مخاطب' }}</span>
              <span class="si-conv-time">{{ Ui::ago($item->last_message_at) }}</span>
            </div>
            <div class="si-conv-preview {{ $item->unread_count > 0 ? 'is-unread' : '' }}">
              @if($item->last_message_direction === 'out')<i class="fa-solid fa-reply" style="font-size:9px"></i>@endif
              {{ $item->last_message_preview ?: '—' }}
            </div>
            <div class="si-conv-tags">
              <span class="badge-pro badge-{{ Ui::statusTone($item->status) }}">{{ Ui::label('status', $item->status) }}</span>
              @if($item->needs_human)<span class="badge-pro badge-danger">نیازمند انسان</span>@endif
              @if($item->last_source && $item->last_source !== 'dm')<span class="badge-pro badge-neutral">{{ Ui::label('source', $item->last_source) }}</span>@endif
              @if($item->intent)<span class="badge-pro badge-primary">{{ Ui::label('intent', $item->intent) }}</span>@endif
              @if($item->assignee)<span class="badge-pro badge-neutral"><i class="fa-solid fa-user" style="font-size:8px"></i> {{ $item->assignee->name }}</span>@endif
            </div>
          </div>
          @if($item->unread_count > 0)<span class="si-unread">{{ Ui::n($item->unread_count) }}</span>@endif
        </a>
      @empty
        <div class="empty-state" style="padding:40px 16px">
          <div class="empty-state-icon"><i class="fa-regular fa-comments"></i></div>
          <div class="empty-state-title">گفتگویی پیدا نشد</div>
          <div class="empty-state-desc">{{ $search ? 'عبارت دیگری را جست‌وجو کنید.' : 'از لحظه‌ی اتصال، هر کامنت، دایرکت، پاسخ استوری و ورودی تبلیغ این‌جا ثبت می‌شود.' }}</div>
        </div>
      @endforelse
    </div>
    @if($list->hasPages())
      <div class="si-pagination" style="display:flex;justify-content:space-between;gap:8px">
        <a class="page-btn {{ $list->onFirstPage() ? 'is-disabled' : '' }}" href="{{ $list->previousPageUrl() }}">قبلی</a>
        <span class="si-muted">{{ Ui::n($list->currentPage()) }} از {{ Ui::n($list->lastPage()) }}</span>
        <a class="page-btn {{ $list->hasMorePages() ? '' : 'is-disabled' }}" href="{{ $list->nextPageUrl() }}">بعدی</a>
      </div>
    @endif
  </aside>

  {{-- ستون دوم: گفتگو --}}
  <section class="si-inbox-col si-inbox-chat" aria-label="گفتگو">
    @if($selected)
      <header class="si-chat-head">
        <a href="{{ route('admin.smart-instagram.inbox', $listParams) }}" class="icon-action-btn si-back" aria-label="بازگشت به فهرست"><i class="fa-solid fa-arrow-right"></i></a>
        <div class="si-avatar">{{ $contact?->initials() }}</div>
        <div class="si-chat-head-main">
          <div class="si-conv-name">{{ $contact?->label() }}</div>
          <div class="si-muted si-clip">
            @if($contact?->username)<span class="si-ltr">{{ '@'.$contact->username }}</span> · @endif
            {{ Ui::label('status', $selected->status) }}
            @if($selected->windowOpen()) · <span style="color:var(--success)">پنجره‌ی پیام باز است</span>@elseif($selected->last_inbound_at) · <span style="color:var(--warning)">پنجره‌ی ۲۴ساعته بسته</span>@endif
          </div>
        </div>
        <div class="si-chat-actions">
          @if($canReply)
            @if((int) $selected->assigned_admin_id !== (int) auth('admin')->id())
              <form method="POST" action="{{ route('admin.smart-instagram.inbox.update', $selected) }}">@csrf @method('PATCH')
                <input type="hidden" name="assign" value="1"><input type="hidden" name="assigned_admin_id" value="{{ auth('admin')->id() }}">
                <button class="btn-pro btn-pro-ghost" title="به من واگذار شود"><i class="fa-solid fa-hand text-[11px]"></i><span>برعهده‌ی من</span></button>
              </form>
            @endif
            <form method="POST" action="{{ route('admin.smart-instagram.inbox.update', $selected) }}">@csrf @method('PATCH')
              <input type="hidden" name="status" value="{{ $selected->status === 'closed' ? 'unanswered' : 'closed' }}">
              <button class="btn-pro btn-pro-ghost" title="{{ $selected->status === 'closed' ? 'بازگشایی' : 'بستن گفتگو' }}"><i class="fa-solid {{ $selected->status === 'closed' ? 'fa-rotate-left' : 'fa-check' }} text-[11px]"></i><span>{{ $selected->status === 'closed' ? 'بازگشایی' : 'بستن' }}</span></button>
            </form>
          @endif
          <button type="button" class="icon-action-btn si-side-toggle" data-si-toggle-side aria-label="پرونده‌ی مشتری"><i class="fa-solid fa-id-card"></i></button>
        </div>
      </header>

      <div class="si-chat-body" id="si-chat-body">
        @php($siLastDay = null)
        @forelse($timeline as $message)
          @php($siDay = Ui::date($message->occurred_at))
          @if($siDay !== $siLastDay)<div class="si-day">{{ $siDay }}</div>@php($siLastDay = $siDay)@endif
          @php($siDir = $message->is_internal_note ? 'note' : ($message->direction === 'in' ? 'in' : 'out'))
          <div class="si-msg is-{{ $siDir }}">
            <div class="si-bubble">
              @if($message->body || $message->attachments->isEmpty())<div class="si-text">{{ $message->body ?: '—' }}</div>@endif
              @if($message->attachments->isNotEmpty())
<div class="si-attach">@foreach($message->attachments as $attachment)
  @if($attachment->fetch_status === 'stored')
    @if($attachment->type === 'image')<a href="{{ route('admin.smart-instagram.attachments.show', $attachment) }}" target="_blank" rel="noopener"><img src="{{ route('admin.smart-instagram.attachments.show', $attachment) }}" alt="تصویر ارسالی مشتری" loading="lazy" decoding="async"></a>
    @elseif($attachment->type === 'audio')<audio controls preload="none" src="{{ route('admin.smart-instagram.attachments.show', $attachment) }}"></audio>
    @elseif($attachment->type === 'video')<video controls preload="none" src="{{ route('admin.smart-instagram.attachments.show', $attachment) }}"></video>
    @else<a class="si-link" href="{{ route('admin.smart-instagram.attachments.show', $attachment) }}" target="_blank" rel="noopener"><i class="fa-solid fa-paperclip"></i> دریافت فایل</a>@endif
    @if($attachment->type === 'audio' && $canReply)
      @if($attachment->transcript)<div class="si-help"><b>متن صوت:</b> {{ $attachment->transcript }}</div>@endif
      <details><summary class="si-link" style="cursor:pointer">{{ $attachment->transcript ? 'ویرایش متن صوت' : 'ثبت متن صوت' }}</summary>
        <form method="POST" action="{{ route('admin.smart-instagram.attachments.action', $attachment) }}" style="margin-top:6px">@csrf
          <input type="hidden" name="action" value="transcript">
          <textarea name="transcript" class="input-pro" rows="2" style="min-height:60px">{{ $attachment->transcript }}</textarea>
          <button class="btn-pro btn-pro-ghost" style="margin-top:6px;height:30px">ذخیره‌ی متن</button>
        </form>
      </details>
    @endif
  @elseif(in_array($attachment->fetch_status, ['pending', 'retrying'], true))
    <span class="si-muted"><i class="fa-solid fa-spinner fa-spin"></i> در حال دریافت {{ ['image' => 'تصویر', 'audio' => 'صوت', 'video' => 'ویدیو'][$attachment->type] ?? 'فایل' }}…</span>
  @elseif($attachment->fetch_status === 'failed')
    <form method="POST" action="{{ route('admin.smart-instagram.attachments.action', $attachment) }}">@csrf<input type="hidden" name="action" value="retry">
      <span class="si-muted"><i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)"></i> دریافت ناموفق</span>
      @if($canReply)<button class="si-link" style="background:none;border:0;cursor:pointer">تلاش دوباره</button>@endif
    </form>
  @elseif($attachment->fetch_status === 'deleted')
    <span class="si-muted">فایل حذف شده است</span>
  @else
    <span class="si-muted"><i class="fa-solid fa-paperclip"></i> {{ ['story' => 'منشن در استوری', 'link' => 'لینک/اشتراک'][$attachment->type] ?? 'پیوست' }}</span>
  @endif
@endforeach</div>@endif</div>
            <div class="si-msg-meta">
              @if($siDir === 'note')<i class="fa-solid fa-lock"></i> یادداشت داخلی · {{ $message->admin?->name }}
              @elseif($siDir === 'out'){{ Ui::label('origin', $message->sent_by) }}@if($message->admin) · {{ $message->admin->name }}@endif @if($message->delivery_status === 'manual') · ثبت دستی@endif
              @endif
              @if($message->source_type !== 'dm' && $siDir !== 'note')<span class="si-source">{{ Ui::label('source', $message->source_type) }}</span>@endif
              <span>{{ Ui::time($message->occurred_at) }}</span>
            </div>
          </div>
        @empty
          <div class="si-chat-empty si-muted">پیامی ثبت نشده است.</div>
        @endforelse

        @foreach($pendingOutbound as $out)
          <div class="si-msg is-out is-pending">
            <div class="si-bubble"><div class="si-text">{{ $out->body }}</div></div>
            <div class="si-msg-meta">
              <span class="badge-pro badge-{{ Ui::statusTone($out->status) }}">{{ Ui::label('outbound', $out->status) }}</span>
              <span>{{ Ui::label('kind', $out->kind) }} · {{ Ui::label('origin', $out->origin) }}</span>
            </div>
            @if($out->policy_reason || $out->error)<div class="si-help" style="max-width:320px;text-align:left">{{ $out->policy_reason ?: $out->error }}</div>@endif
            @if(in_array($out->status, ['blocked', 'failed'], true) && $canReply)
              <form method="POST" action="{{ route('admin.smart-instagram.outbound.manual', $out) }}">@csrf
                <button class="si-link" style="background:none;border:0;cursor:pointer" title="اگر این متن را خودتان در اینستاگرام فرستادید، این‌جا ثبتش کنید"><i class="fa-solid fa-check-double"></i> دستی فرستادم، ثبت شود</button>
              </form>
            @endif
          </div>
        @endforeach
      </div>

      <div id="si-new-banner" class="si-flash is-info" style="margin:8px 14px 0" hidden>
        <i class="fa-solid fa-bell"></i><span>پیام تازه رسید.</span>
        <a href="{{ request()->fullUrl() }}" class="si-link">بارگذاری</a>
      </div>

      @if($suggestion)
        <div class="si-suggest" role="region" aria-label="پیشنهاد هوش مصنوعی">
          <div class="si-suggest-head">
            <span class="si-panel-title" style="font-size:12.5px"><i class="fa-solid fa-wand-magic-sparkles"></i> پیشنهاد دستیار</span>
            <span class="si-status-strip" style="margin:0">
              <span class="badge-pro badge-info">اطمینان {{ Ui::pct(($suggestion->confidence ?? 0) * 100) }}</span>
              @if($suggestion->needs_human)<span class="badge-pro badge-danger">نیازمند تصمیم انسان</span>@endif
              @foreach((array) $suggestion->flags as $flag)
                <span class="badge-pro badge-warning">{{ ['sensitive' => 'موضوع حساس', 'forbidden_phrase' => 'عبارت ممنوع', 'unverified_numbers' => 'عدد تأییدنشده', 'no_knowledge' => 'بدون دانش مرتبط', 'low_confidence' => 'اطمینان پایین'][$flag] ?? $flag }}</span>
              @endforeach
            </span>
          </div>
          <div class="si-suggest-body">{{ $suggestion->body }}</div>
          @if($suggestion->reason)<div class="si-suggest-reason"><b>دلیل:</b> {{ $suggestion->reason }}</div>@endif
          @if($canReply)
            <div class="si-suggest-actions">
              <button type="button" class="btn-pro btn-pro-primary" style="height:32px" data-si-use-suggestion="{{ $suggestion->id }}" data-body="{{ $suggestion->body }}"><i class="fa-solid fa-pen text-[11px]"></i> استفاده و ویرایش</button>
              <form method="POST" action="{{ route('admin.smart-instagram.suggestions.review', $suggestion) }}" style="display:flex;gap:6px;flex:1;min-width:200px">@csrf
                <input type="hidden" name="decision" value="rejected">
                <input type="text" name="feedback" class="input-pro" style="height:32px" placeholder="چرا مناسب نیست؟ (برای یادگیری)" maxlength="300">
                <button class="btn-pro btn-pro-ghost" style="height:32px">رد</button>
              </form>
            </div>
          @endif
        </div>
      @endif

      @if($learnable && $canReply)
        <form method="POST" action="{{ route('admin.smart-instagram.suggestions.review', $learnable) }}" class="si-flash is-success" style="margin:0 14px 10px">@csrf
          <input type="hidden" name="decision" value="learn">
          <i class="fa-solid fa-graduation-cap"></i>
          <span>پاسخ {{ $learnable->status === 'edited' ? 'ویرایش‌شده‌ی' : '' }} شما ارسال شد. به دانش دستیار اضافه شود تا دفعه‌ی بعد همین‌طور پاسخ دهد؟</span>
          <button class="btn-pro btn-pro-ghost" style="height:30px;margin-inline-start:auto">افزودن به دانش</button>
        </form>
      @endif

      @if($canReply)
        <form method="POST" action="{{ route('admin.smart-instagram.inbox.reply', $selected) }}" class="si-composer" id="si-composer">
          @csrf
          <input type="hidden" name="kind" value="dm">
          <input type="hidden" name="target_ref" value="">
          <input type="hidden" name="suggestion_id" value="">
          <div class="si-composer-tabs">
            <button type="button" class="chip-filter active" data-si-kind="dm">دایرکت</button>
            @if($lastComment && data_get($lastComment->meta, 'comment_id'))
              <button type="button" class="chip-filter" data-si-kind="private_reply" data-target="{{ data_get($lastComment->meta, 'comment_id') }}">پاسخ خصوصی به کامنت</button>
              <button type="button" class="chip-filter" data-si-kind="public_reply" data-target="{{ data_get($lastComment->meta, 'comment_id') }}">پاسخ عمومی زیر کامنت</button>
            @endif
            @foreach($quickReplies as $quick)
              <button type="button" class="chip-filter" data-si-quick-reply="{{ $quick['body'] }}" title="{{ $quick['body'] }}"><i class="fa-solid fa-bolt" style="font-size:9px"></i> {{ \Illuminate\Support\Str::limit($quick['title'], 22) }}</button>
            @endforeach
          </div>
          <textarea name="body" class="input-pro" rows="2" maxlength="1000" required placeholder="پاسخ خود را بنویسید… (Ctrl+Enter برای ارسال)"></textarea>
          <div class="si-composer-row">
            <span class="si-help">
              @if(!$outboundEnabled || !$selected->channel?->outbound_enabled)
                <i class="fa-solid fa-circle-info"></i> ارسال خودکار خاموش است؛ پیام ثبت می‌شود و می‌توانید آن را دستی بفرستید.
              @else
                <i class="fa-solid fa-shield-halved"></i> پیش از ارسال، پنجره‌ی مجاز متا و قواعد ایمنی بررسی می‌شود.
              @endif
            </span>
            <button type="submit" class="btn-pro btn-pro-primary"><i class="fa-solid fa-paper-plane text-[11px]"></i> ثبت و ارسال</button>
          </div>
        </form>
      @endif
    @else
      <div class="si-chat-empty">
        <div class="empty-state">
          <div class="empty-state-icon"><i class="fa-regular fa-message"></i></div>
          <div class="empty-state-title">یک گفتگو را انتخاب کنید</div>
          <div class="empty-state-desc">تایم‌لاین کامل، پیشنهاد پاسخ هوشمند و پرونده‌ی مشتری این‌جا نمایش داده می‌شود.</div>
        </div>
      </div>
    @endif
  </section>

  {{-- ستون سوم: کارت مشتری --}}
  @if($selected && $contact)
    <aside class="si-inbox-col si-inbox-side" aria-label="پرونده‌ی مشتری">
      <div class="si-side-section" style="display:flex;gap:10px;align-items:center">
        <div class="si-avatar is-lg">{{ $contact->initials() }}</div>
        <div style="min-width:0;flex:1">
          <div class="si-conv-name">{{ $contact->label() }}</div>
          <div class="si-muted">{{ Ui::label('lead', $contact->lead_status) }} · منبع: {{ Ui::label('source', $contact->first_source) }}</div>
          <a href="{{ route('admin.smart-instagram.contacts.show', $contact) }}" class="si-link">پرونده‌ی کامل <i class="fa-solid fa-angle-left"></i></a>
        </div>
        <button type="button" class="icon-action-btn si-side-toggle" data-si-toggle-side aria-label="بستن"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <div class="si-side-section">
        <div class="si-label" style="margin-bottom:6px">امتیاز لید</div>
        <div class="si-score"><div class="progress-track"><div class="progress-fill" style="width:{{ (int) $contact->lead_score }}%"></div></div><b class="si-num">{{ Ui::n($contact->lead_score) }}</b></div>
        @if($contact->score_reason)<div class="si-help" style="margin-top:4px">{{ $contact->score_reason }}</div>@endif
      </div>

      <div class="si-side-section">
        <div class="si-panel-title" style="font-size:12.5px;margin-bottom:8px"><i class="fa-solid fa-brain"></i> تحلیل هوشمند</div>
        <dl class="si-kv">
          <dt>نیت</dt><dd>{{ Ui::label('intent', $selected->intent) }}</dd>
          <dt>مرحله</dt><dd>{{ Ui::label('stage', $selected->stage) }}</dd>
          <dt>فوریت</dt><dd>{{ ['low' => 'کم', 'normal' => 'عادی', 'high' => 'بالا'][$selected->urgency] ?? '—' }}</dd>
        </dl>
        @if($selected->summary)<div class="si-help" style="margin-top:8px"><b>خلاصه:</b> {{ $selected->summary }}</div>@endif
        @if($selected->next_action)<div class="si-help" style="margin-top:4px"><b>اقدام بعدی:</b> {{ $selected->next_action }}</div>@endif
        @if($canReply)
          <div style="display:flex;gap:6px;margin-top:10px;flex-wrap:wrap">
            <form method="POST" action="{{ route('admin.smart-instagram.inbox.analyze', $selected) }}">@csrf<button class="btn-pro btn-pro-ghost" style="height:30px;font-size:11.5px"><i class="fa-solid fa-rotate text-[10px]"></i> تحلیل دوباره</button></form>
            <form method="POST" action="{{ route('admin.smart-instagram.inbox.update', $selected) }}">@csrf @method('PATCH')
              <input type="hidden" name="ai_paused" value="{{ $selected->ai_paused ? 0 : 1 }}">
              <button class="btn-pro {{ $selected->ai_paused ? 'btn-pro-primary' : 'btn-pro-ghost' }}" style="height:30px;font-size:11.5px">{{ $selected->ai_paused ? 'روشن‌کردن دستیار' : 'توقف دستیار در این گفتگو' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.smart-instagram.inbox.update', $selected) }}">@csrf @method('PATCH')
              <input type="hidden" name="needs_human" value="{{ $selected->needs_human ? 0 : 1 }}">
              <button class="btn-pro {{ $selected->needs_human ? 'btn-pro-danger' : 'btn-pro-ghost' }}" style="height:30px;font-size:11.5px">{{ $selected->needs_human ? 'رفع پرچم انسانی' : 'نیازمند انسان' }}</button>
            </form>
          </div>
        @endif
      </div>

      @if($canReply)
        <div class="si-side-section">
          <div class="si-label" style="margin-bottom:6px">مسئول گفتگو</div>
          <form method="POST" action="{{ route('admin.smart-instagram.inbox.update', $selected) }}" style="display:flex;gap:6px">@csrf @method('PATCH')
            <input type="hidden" name="assign" value="1">
            <select name="assigned_admin_id" class="input-pro" style="height:32px;font-size:12px">
              <option value="">بدون مسئول</option>
              @foreach($admins as $admin)<option value="{{ $admin->id }}" @selected((int) $selected->assigned_admin_id === $admin->id)>{{ $admin->name }}</option>@endforeach
            </select>
            <button class="btn-pro btn-pro-ghost" style="height:32px">ثبت</button>
          </form>
        </div>
      @endif

      <div class="si-side-section">
        <div class="si-label" style="margin-bottom:6px">برچسب‌ها</div>
        <div class="si-conv-tags" style="margin:0 0 8px">
          @forelse($contact->tags as $tag)<span class="badge-pro badge-neutral">{{ $tag->name }}</span>@empty<span class="si-muted">بدون برچسب</span>@endforelse
        </div>
        @if($canReply)
          <form method="POST" action="{{ route('admin.smart-instagram.contacts.tags', $contact) }}" style="display:flex;gap:6px">@csrf
            <input name="tag" class="input-pro" style="height:32px;font-size:12px" placeholder="برچسب تازه" maxlength="60" required>
            <button class="btn-pro btn-pro-ghost" style="height:32px">افزودن</button>
          </form>
        @endif
      </div>

      <div class="si-side-section">
        <div class="si-label" style="margin-bottom:6px">فرصت‌های فروش</div>
        @forelse($contact->deals as $deal)
          <div class="si-row" style="padding:6px 0"><div class="si-row-main"><div class="si-row-title">{{ $deal->title }}</div><div class="si-row-sub">{{ Ui::label('pipeline', $deal->stage) }} · {{ Ui::money($deal->value_toman) }} تومان</div></div></div>
        @empty
          <div class="si-muted">فرصتی ثبت نشده.</div>
        @endforelse
        @if($canReply)
          <details style="margin-top:8px"><summary class="si-link" style="cursor:pointer">+ فرصت فروش تازه</summary>
            <form method="POST" action="{{ route('admin.smart-instagram.deals.store') }}" class="si-form is-1" style="margin-top:8px">@csrf
              <input type="hidden" name="contact_id" value="{{ $contact->id }}"><input type="hidden" name="conversation_id" value="{{ $selected->id }}">
              <input name="title" class="input-pro" placeholder="عنوان فرصت" required maxlength="190">
              <input name="value_toman" type="number" min="0" class="input-pro" placeholder="ارزش برآوردی (تومان)">
              <button class="btn-pro btn-pro-primary" style="height:32px">ساخت فرصت</button>
            </form>
          </details>
        @endif
      </div>

      <div class="si-side-section">
        <div class="si-label" style="margin-bottom:6px">وظایف باز</div>
        @forelse($contact->tasks as $task)
          <div class="si-row" style="padding:6px 0">
            <span class="si-dot {{ $task->isOverdue() ? 'is-danger' : 'is-info' }}"></span>
            <div class="si-row-main"><div class="si-row-title">{{ $task->title }}</div><div class="si-row-sub">{{ $task->due_at ? Ui::date($task->due_at, true) : 'بدون موعد' }}</div></div>
            @if($canReply)
              <form method="POST" action="{{ route('admin.smart-instagram.tasks.update', $task) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="done"><button class="icon-action-btn" title="انجام شد"><i class="fa-solid fa-check"></i></button></form>
            @endif
          </div>
        @empty
          <div class="si-muted">وظیفه‌ی بازی نیست.</div>
        @endforelse
        @if($canReply)
          <details style="margin-top:8px"><summary class="si-link" style="cursor:pointer">+ وظیفه‌ی پیگیری</summary>
            <form method="POST" action="{{ route('admin.smart-instagram.tasks.store') }}" class="si-form is-1" style="margin-top:8px">@csrf
              <input type="hidden" name="contact_id" value="{{ $contact->id }}"><input type="hidden" name="conversation_id" value="{{ $selected->id }}">
              <input name="title" class="input-pro" placeholder="مثلاً: ارسال نمونه‌کار پوشاک" required maxlength="190">
              <input name="due_at" type="datetime-local" class="input-pro" value="{{ now('Asia/Tehran')->addDay()->format('Y-m-d\TH:i') }}">
              <button class="btn-pro btn-pro-primary" style="height:32px">ثبت وظیفه</button>
            </form>
          </details>
        @endif
      </div>

      @if($canReply)
        <div class="si-side-section">
          <div class="si-label" style="margin-bottom:6px">یادداشت داخلی</div>
          <form method="POST" action="{{ route('admin.smart-instagram.inbox.note', $selected) }}" class="si-form is-1">@csrf
            <textarea name="body" class="input-pro" rows="2" style="min-height:60px" placeholder="فقط تیم می‌بیند؛ مشتری نمی‌بیند." required maxlength="2000"></textarea>
            <button class="btn-pro btn-pro-ghost" style="height:32px"><i class="fa-solid fa-lock text-[10px]"></i> ثبت یادداشت</button>
          </form>
        </div>
      @endif
    </aside>
  @endif
</div>
@endsection
