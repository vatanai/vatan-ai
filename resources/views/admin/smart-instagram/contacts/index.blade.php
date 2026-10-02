@extends('admin.smart-instagram.layout')
@use('App\Services\SmartInstagram\Ui')
@php
  $siTitle = 'مشتریان و لیدها';
  $siSubtitle = 'هر شخص یک پرونده‌ی واحد دارد، مستقل از تعداد گفتگو، پست یا مسیر ورودش.';
  $query = fn (array $extra) => route('admin.smart-instagram.contacts.index', array_filter(array_merge(request()->query(), $extra), fn ($v) => $v !== null && $v !== ''));
@endphp

@section('si-actions')
  <div class="si-chips" role="tablist" aria-label="نوع نمایش">
    <a href="{{ $query(['view' => null]) }}" class="chip-filter {{ $view === 'table' ? 'active' : '' }}"><i class="fa-solid fa-table-list"></i> جدول</a>
    <a href="{{ $query(['view' => 'cards']) }}" class="chip-filter {{ $view === 'cards' ? 'active' : '' }}"><i class="fa-solid fa-grip"></i> کارت</a>
  </div>
@endsection

@section('si-page')
  <div class="si-stats is-6">
    @include('admin.smart-instagram.partials.stat', ['icon' => 'fa-address-book', 'tone' => 'primary', 'value' => Ui::n($total), 'label' => 'همه‌ی مخاطبان', 'href' => $query(['status' => null])])
    @foreach(config('smart_instagram.lead_statuses') as $key => $label)
      @include('admin.smart-instagram.partials.stat', [
        'icon' => ['new' => 'fa-seedling', 'qualified' => 'fa-user-check', 'hot' => 'fa-fire', 'customer' => 'fa-crown', 'lost' => 'fa-user-xmark'][$key],
        'tone' => ['new' => 'info', 'qualified' => 'primary', 'hot' => 'danger', 'customer' => 'success', 'lost' => 'warning'][$key],
        'value' => Ui::n($statusCounts[$key] ?? 0), 'label' => $label, 'href' => $query(['status' => $key, 'page' => null]),
      ])
    @endforeach
  </div>

  <form method="GET" class="content-card si-panel" style="margin-bottom:14px">
    @if($view === 'cards')<input type="hidden" name="view" value="cards">@endif
    <div class="si-toolbar" style="margin:0">
      <div class="si-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search" name="q" value="{{ $filters['q'] }}" class="input-pro" placeholder="نام، نام کاربری، شماره یا صنف…"></div>
      <select name="status" class="input-pro" style="width:auto"><option value="">همه‌ی وضعیت‌ها</option>@foreach(config('smart_instagram.lead_statuses') as $k => $l)<option value="{{ $k }}" @selected($filters['status'] === $k)>{{ $l }}</option>@endforeach</select>
      <select name="source" class="input-pro" style="width:auto"><option value="">همه‌ی منابع</option>@foreach(config('smart_instagram.sources') as $k => $l)<option value="{{ $k }}" @selected($filters['source'] === $k)>{{ $l }}</option>@endforeach</select>
      <select name="tag" class="input-pro" style="width:auto"><option value="">همه‌ی برچسب‌ها</option>@foreach($tags as $tag)<option value="{{ $tag }}" @selected($filters['tag'] === $tag)>{{ $tag }}</option>@endforeach</select>
      <select name="assignee" class="input-pro" style="width:auto"><option value="">همه‌ی مسئولان</option><option value="none" @selected($filters['assignee'] === 'none')>بدون مسئول</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected($filters['assignee'] === (string) $admin->id)>{{ $admin->name }}</option>@endforeach</select>
      <select name="sort" class="input-pro" style="width:auto">@foreach($sorts as $k => $l)<option value="{{ $k }}" @selected($filters['sort'] === $k)>مرتب: {{ $l }}</option>@endforeach</select>
      <button class="btn-pro btn-pro-primary"><i class="fa-solid fa-filter text-[11px]"></i> اعمال</button>
      @if(array_filter(\Illuminate\Support\Arr::except($filters, ['sort'])))<a href="{{ route('admin.smart-instagram.contacts.index', $view === 'cards' ? ['view' => 'cards'] : []) }}" class="btn-pro btn-pro-ghost">پاک‌کردن</a>@endif
    </div>
  </form>

  @if($contacts->isEmpty())
    <div class="content-card"><div class="empty-state"><div class="empty-state-icon"><i class="fa-regular fa-address-book"></i></div><div class="empty-state-title">مخاطبی پیدا نشد</div><div class="empty-state-desc">مخاطبان از اولین کامنت یا دایرکت به‌صورت خودکار ساخته می‌شوند.</div></div></div>
  @elseif($view === 'cards')
    <div class="si-cards">
      @foreach($contacts as $contact)
        <a href="{{ route('admin.smart-instagram.contacts.show', $contact) }}" class="si-tile">
          <div class="si-tile-title"><span class="si-avatar is-sm">{{ $contact->initials() }}</span><span class="si-clip">{{ $contact->label() }}</span></div>
          <div class="si-status-strip" style="margin:0">
            <span class="badge-pro badge-{{ ['hot' => 'danger', 'customer' => 'success', 'qualified' => 'primary', 'lost' => 'warning'][$contact->lead_status] ?? 'info' }}">{{ Ui::label('lead', $contact->lead_status) }}</span>
            <span class="badge-pro badge-neutral">{{ Ui::label('source', $contact->first_source) }}</span>
          </div>
          <div class="si-score"><div class="progress-track"><div class="progress-fill" style="width:{{ (int) $contact->lead_score }}%"></div></div><b class="si-num" style="font-size:12px">{{ Ui::n($contact->lead_score) }}</b></div>
          <div class="si-tile-desc">{{ $contact->industry ?: 'صنف نامشخص' }} · آخرین تعامل {{ Ui::ago($contact->last_interaction_at) }}</div>
        </a>
      @endforeach
    </div>
    <div class="content-card" style="margin-top:12px">@include('admin.smart-instagram.partials.pagination', ['paginator' => $contacts])</div>
  @else
    <div class="content-card si-panel is-flush">
      <div class="si-table-wrap">
        <table class="table-pro">
          <thead><tr><th>مخاطب</th><th>وضعیت لید</th><th>امتیاز</th><th>منبع ورود</th><th>صنف</th><th>برچسب‌ها</th><th>مسئول</th><th>فرصت باز</th><th>آخرین تعامل</th></tr></thead>
          <tbody>
            @foreach($contacts as $contact)
              <tr>
                <td><a href="{{ route('admin.smart-instagram.contacts.show', $contact) }}" style="display:flex;align-items:center;gap:9px;color:inherit"><span class="si-avatar is-sm">{{ $contact->initials() }}</span><span><span class="si-td-strong">{{ $contact->display_name ?: '—' }}</span><br><span class="si-muted si-ltr">{{ $contact->username ? '@'.$contact->username : '' }}</span></span></a></td>
                <td><span class="badge-pro badge-{{ ['hot' => 'danger', 'customer' => 'success', 'qualified' => 'primary', 'lost' => 'warning'][$contact->lead_status] ?? 'info' }}">{{ Ui::label('lead', $contact->lead_status) }}</span></td>
                <td><div class="si-score" style="min-width:90px"><div class="progress-track"><div class="progress-fill" style="width:{{ (int) $contact->lead_score }}%"></div></div><span class="si-num">{{ Ui::n($contact->lead_score) }}</span></div></td>
                <td>{{ Ui::label('source', $contact->first_source) }}</td>
                <td>{{ $contact->industry ?: '—' }}</td>
                <td>@foreach($contact->tags->take(3) as $tag)<span class="badge-pro badge-neutral" style="margin:1px">{{ $tag->name }}</span>@endforeach @if($contact->tags->count() > 3)<span class="si-muted">+{{ Ui::n($contact->tags->count() - 3) }}</span>@endif</td>
                <td>{{ $contact->assignee?->name ?? '—' }}</td>
                <td class="si-num">{{ Ui::n($contact->open_deals_count) }}</td>
                <td class="si-muted">{{ Ui::ago($contact->last_interaction_at) }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @include('admin.smart-instagram.partials.pagination', ['paginator' => $contacts])
    </div>
  @endif
@endsection
